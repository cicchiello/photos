var baseDbUrl = null;
var rekognizeConfidence = 0;
var allTags = {};
var allUserTags = {};
var selectedImageIds = new Set();
var sCheckedSet = new Set();

var userTagColor = '#B3CCFF';  // Medium blue - between bright and subtle

function getCheckedSet() {
    return sCheckedSet;
}

function setCheckedSet(newSet) {
    sCheckedSet = newSet;
}

function setConfidence(confidence) {
    rekognizeConfidence = confidence;
}

function setDbUrl(dbUrl) {
    baseDbUrl = dbUrl;
}

function calcNonUserIntersection(tset) {
    var inter = null;
    
    Array.from(tset).forEach(objid => {
	inter = inter === null ? allTags[objid] : inter.intersection(allTags[objid]);
    });
    Array.from(tset).forEach(objid => {
	allUserTags[objid].forEach(o => {inter.delete(o);});
    });
    
    return (inter !== null) ? Array.from(inter) : [];
}

function calcUserIntersection(tset) {
    var inter = null;
    
    Array.from(tset).forEach(oid => {
	inter = (inter === null) ? allUserTags[oid] : inter.intersection(allUserTags[oid]);
    });
    
    return (inter !== null) ? Array.from(inter) : [];
}

function renderTagset(fullTagset, userTagset) {
    var str = "";
    if (fullTagset.length === 0 && userTagset.length === 0) {
        str = '<span class="hint-text">...of selected images</span>';
    } else {
        userTagset.forEach(tag => {
	    str += '<span class="pillButton" style="background-color:${userTagColor};color:black">';
	    str += tag;
	    str += '</span><br>';
	});
        fullTagset.forEach(tag => {str += tag+"<br>";});
    }
    
    var keyArea = document.getElementById("key-area");
    keyArea.innerHTML = str;
}

function clearChecks() {
    allTags = {};
    allUserTags = {};
    setCheckedSet(new Set());
    renderTagset(calcNonUserIntersection(getCheckedSet()), calcUserIntersection(getCheckedSet()));
}

function collectTags(dburl, imageId, onCompletion) {
    if (imageId) {
	baseDbUrl = dburl;
        var objurl = dburl+"/"+imageId;
        fetch(objurl)
            .then(res => {
                if (!res.ok) {
                    console.log("ERROR(collectTags): network error");
                } else {
                    return res.json();
                }
            })
            .then(data => {
                allTags[imageId] = new Set();
		allUserTags[imageId] = new Set();
                data.tags.forEach(tagobj => {
                    if (tagobj.Confidence > rekognizeConfidence) {
                        if (tagobj.source === 'rekognition') {
                            allTags[imageId].add(tagobj.Name);
                        }
                        if (tagobj.source === 'user') {
                            allTags[imageId].add(tagobj.Name);
			    allUserTags[imageId].add(tagobj.Name);
                        }
                    }
                });
		onCompletion();
            })
            .catch(error => {
                console.log("ERROR(collectTags): Fetch error: "+error);
		onCompletion();
            });
    }
}

function checkboxAction(checkboxElem, dburl, imageId) {
    if (imageId) {
        if (checkboxElem.checked) {
	    var r = getCheckedSet().add(imageId);
	    setCheckedSet(r);
	    collectTags(dburl, imageId, function onCompletion() {
		renderTagset(calcNonUserIntersection(getCheckedSet()), calcUserIntersection(getCheckedSet()));
		updateAddTagButtonState();
	    });
        } else {
	    getCheckedSet().delete(imageId); // deletes from set in-place
            delete allTags[imageId];
	    delete allUserTags[imageId];
	    renderTagset(calcNonUserIntersection(getCheckedSet()), calcUserIntersection(getCheckedSet()));
	    updateAddTagButtonState();
        }
    }
}

async function persistTagToImage(imageId, newTag, onCompletion) {
    try {
        const tagenc = encodeURIComponent(newTag);
	const url = 'addTag.php?imageid='+imageId+'&tag='+tagenc;
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error('Failed to update tag');
        }
	
	onCompletion();
    } catch (error) {
        console.error(`Error updating ${objid}:`, error);
        return false;
    }
}

function persistTagToImages(imageIds, idx, newTag, onCompletion) {
    if (idx >= imageIds.length) {
	onCompletion();
	
	return;
    } else {
	persistTagToImage(imageIds[idx], newTag, function innerOnCompletion() {
	    persistTagToImages(imageIds, idx+1, newTag, onCompletion);
	});
    }
}

function collectImageListTags(imageIds, idx, onCompletion) {
    if (idx >= imageIds.length) {
	onCompletion();
	
	return;
    } else {
	collectTags(baseDbUrl, imageIds[idx], function innerOnCompletion() {
	    collectImageListTags(imageIds, idx+1, onCompletion);
	});
    }
};

async function handleAddTag() {
    const newTagInput = document.getElementById('newTag');
    const newTag = newTagInput.value.trim().toLowerCase();
    
    // Clear the input field immediately
    newTagInput.value = '';
    
    if (newTag === '') {
        alert('Please enter a tag');
        return;
    }
    
    var checkedList = Array.from(getCheckedSet());
    persistTagToImages(checkedList, 0, newTag, function onCompletion() {
        allTags = {};
	allUserTags = {};
	collectImageListTags(checkedList, 0, function innerOnCompletion() {
	    renderTagset(calcNonUserIntersection(getCheckedSet()), calcUserIntersection(getCheckedSet()));
	});
    });
}


function updateAddTagButtonState() {
    const addButton = document.getElementById('addNewTagButton');
    if (addButton) {
        addButton.disabled = getCheckedSet().size === 0;
    }
}


function setAllCheckboxes(checkboxes, idx, dbUrl, onCompletion) {
    if (idx >= checkboxes.length) {
	onCompletion();
    } else {
	const checkbox = checkboxes[idx];
	const img = checkbox.parentElement.nextElementSibling;
	const imageId = img ? img.getAttribute('data-objid') : null;
        checkbox.checked = true;
        setCheckedSet(getCheckedSet().add(imageId));
        collectTags(dbUrl, imageId, function onDbCompletion() {
            setAllCheckboxes(checkboxes, idx+1, dbUrl, onCompletion);
	});
    }
}


function clearAllCheckboxes(checkboxes, onCompletion) {
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
        const img = checkbox.parentElement.nextElementSibling;
        const imageId = img ? img.getAttribute('data-objid') : null;
	getCheckedSet().delete(imageId);
        delete allTags[imageId];
	delete allUserTags[imageId];
    });
    onCompletion();
}


function selectAllAction(checkboxes, checked, dbUrl) {
    const renderOnCompletion = function r() {
        renderTagset(calcNonUserIntersection(getCheckedSet()), calcUserIntersection(getCheckedSet()));
        updateAddTagButtonState();
    };
    
    if (checked) {
	setAllCheckboxes(checkboxes, 0, dbUrl, renderOnCompletion);
    } else {
	clearAllCheckboxes(checkboxes, renderOnCompletion);
    }
}


function setNewTagListener(document) {
    const newTagInput = document.getElementById('newTag');
    newTagInput.addEventListener('keypress', function(event) {
        if (event.key === 'Enter') {
	    event.preventDefault(); // Prevent form submission
	    const addTagButton = document.getElementById('addNewTagButton');
	    if (!addTagButton.disabled) {
                handleAddTag();
	    }
        }
    });
}


function collectImagesThenRender(imgArrayFrame, row, tagFilters, checkedImages) {
    collectImageListTags(Array.from(getCheckedSet()), 0, function innerOnCompletion() {
	renderTagset(calcNonUserIntersection(getCheckedSet()), calcUserIntersection(getCheckedSet()));
    });
    
    // Load the imgArrayTbl *after* the init function finishes so callback
    // is set before users might click on images
    var src = "./imgArrayTbl.php?row="+row;
    if (tagFilters) 
        src += '&tags=' + tagFilters;
    if (checkedImages) 
        src += '&checked=' + checkedImages;
    
    imgArrayFrame.src = src;
}
