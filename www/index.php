<!DOCTYPE html>
<?php
    // intentionally place this before the html tag

    // Uncomment to see php errors
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
  ?>

<html>
  
  <head>
    
  <link href="./w3.css" media="all" rel="stylesheet">
  <link href="./style.css" media="all" rel="stylesheet">
  <link href="./menu.css" media="all" rel="stylesheet">

  <style>
    textarea:placeholder-shown {
        font-style: italic;
    }
    
    textarea {
        resize: none;
    }
    
    div.relative {
      position: relative;
      width: 100%;
      height: 10%;
    }

    .addTagArea {
        position: absolute;
        bottom: 10px;
        left: 20px;
        width: 200px;
        height: 100px;
        border: 3px solid #73AD21;
    }

    input:placeholder-shown {
        font-style: italic;
    }
    
    .tagAddArea {
	position: relative;
	bottom: 10px;
	padding: 10px;
	margin: 10px;
        display: flex;
        justify-content: center;
    }

    .hint-text {
        color: #999;
        font-style: italic;
    }

    .key-area {
	background-color: #F5F5F5;
	width: 100%;
	height: 90%;
	overflow-y: auto;
    }

  </style>

  <script>
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
		str += '${tag}';
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
		setCheckedSet(getCheckedSet());
                delete allTags[imageId];
		delete allUserTags[imageId];
		renderTagset(calcNonUserIntersection(getCheckedSet()), calcUserIntersection(getCheckedSet()));
		updateAddTagButtonState();
            }
        }
        updateAddTagButtonState();
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

    document.addEventListener('DOMContentLoaded', function() {
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
    });

    function init(dbUrl, row, confidence, tagFilters, checkedImages) {
        rekognizeConfidence = confidence;

	baseDbUrl = dbUrl;
	
        var f = document.getElementById("imgArrayFrame");
        f.callback = function callback(url) {
            window.location.replace(url, "", "", true);
        };
        f.checkboxAction = function checkAction(checkboxElem, dbUrl, objid) {
	    baseDbUrl = dbUrl;
            checkboxAction(checkboxElem, dbUrl, objid);
        };
        f.clearChecksAction = function clearChecksAction() {
            clearChecks();
        };
        f.getCheckedImages = function getCheckedImages() {
            return Array.from(getCheckedSet());
        };

	const checkedList = decodeURIComponent(checkedImages).split(' ');
	var tags = new Set();
	checkedList.forEach(id => {if (id) tags.add(id);});
	setCheckedSet(tags);

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
	
        f.src = src;
    }

    function menuAction() {
        var x = document.getElementById("menuItems");
        if (x.className.indexOf("w3-show") == -1) {
            x.className += " w3-show";
        } else {
            x.className = x.className.replace(" w3-show", "");
        }
    }

    function sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    async function forceLogin() {
        await sleep(3000);
        open('./login.php',"_self");
    }
    
  </script>
  
  </head>
  
  <body class="bg"

    <?php       
        include('photos_utils.php');

        $ini = parse_ini_file("./config.ini");
        $confidence = $ini['rekognizeConfidence'];

        if (isset($_COOKIE['login_user'])) {
            $Db = $ini['dbname'];
            $DbBase = $ini['couchbase'].'/'.$Db;
	    
            $row = array_key_exists('row', $_GET) ? $_GET['row'] : 0;
            $tagFilters = array_key_exists('tags', $_GET) ? $_GET['tags'] : '';
	    $checkedImages = array_key_exists('checked', $_GET) ? $_GET['checked'] : '';
            echo 'onload="init('."'".$DbBase."','".$row."',".$confidence.",'".$tagFilters."','".$checkedImages."'".')">';
            echo renderProfileArea($_COOKIE['login_user']);
        } else {
            echo 'onload="forceLogin()">';
        }
        #echo var_dump(isset($_COOKIE['login_user']));
    ?>

    <div style="height:90%; width:90%;">

        <div class="relative">
        </div>
	
        <div style="position: relative;">
            <div style="position:fixed; width:20%; height:75%; margin-left:10px; background-color:white"
                 class="w3-round-large">
                <span class="w3-medium" style="font-weight:bold; margin-left:45px;">Common tags: </span>
                <div id="key-area" class="key-area" style="font-family:monospace; padding:10px; overflow-y=scroll;">
                    <span class="hint-text">...of selected images</span>
                </div>
	    </div>

            <div style="position:fixed; width:20%; bottom:0; height:70px; margin:10px; z-index:999;"
                class="w3-white w3-round-large">
	      
                <span class="w3-medium" style="font-weight:bold; margin-left:70px;">New tag:</span>
                <div class="tagAddArea">
		    <div>
                        <input id="newTag" type="text" size="12" class="w3-small" placeholder="enter tag here">
                        <button id="addNewTagButton" class="w3-small" style="font-weight:bold" onclick="handleAddTag()" disabled>Add</button>
                    </div>
                </div>
		
            </div>
       
        </div>
	
        <div style="height:89.5%; width:77%; padding:10px; margin:10px"
            class="w3-white w3-round-large w3-panel w3-display-bottomright">

            <iframe id="imgArrayFrame" src="" frameBorder="0"
                height="100%" width="100%" style="float:right; z-index:999">
                <p>Your browser does not support iframes.</p>
            </iframe>

      </div>
      
    </div>
    
  </body>
</html>
