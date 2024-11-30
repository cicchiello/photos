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

  </style>

  <script>
    var baseDbUrl = null;
    var rekognizeConfidence = 0;
    var allTags = {};
    var allUserTags = {};
    var selectedImageIds = new Set();

    function calcNonUserIntersection(imageIds) {
        var intersection = new Set();
        var first = true;
        
        for (const objid of imageIds) {
            if (first) {
		intersection = allTags[objid];
                first = false;
            } else {
		intersection = intersection.intersection(allTags[objid]);
            }
        }
	imageIds.forEach(imageId => {allUserTags[imageId].forEach(Set.prototype.delete, intersection);});

        return !!intersection ? Array.from(intersection) : [];
    }

    function calcUserIntersection(imageIds) {
        var intersection = new Set();
        var first = true;
	
        for (const objid of imageIds) {
            if (first) {
		intersection = allUserTags[objid];
                first = false;
            } else {
		intersection = intersection.intersection(allUserTags[objid]);
            }
        }

        return !!intersection ? Array.from(intersection) : [];
    }

    function renderTagset(fullTagset, userTagset) {
        var str = "";
        userTagset.forEach(tag => {
	    str += tag+"*\n";
	});
        fullTagset.forEach(tag => {
	    str += tag+"\n";
	});

        var keyArea = document.getElementById("key-area");
        keyArea.innerHTML = str;
    }

    function clearChecks() {
        allTags = {};
	allUserTags = {};
        selectedImageIds = new Set();
        renderTagset(calcNonUserIntersection(selectedImageIds), calcUserIntersection(selectedImageIds));
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
                selectedImageIds.add(imageId);
		collectTags(dburl, imageId, function onCompletion() {
		    renderTagset(calcNonUserIntersection(selectedImageIds), calcUserIntersection(selectedImageIds));
		    updateAddTagButtonState();
		});
            } else {
                selectedImageIds.delete(imageId);
                delete allTags[imageId];
		delete allUserTags[imageId];
		renderTagset(calcNonUserIntersection(selectedImageIds), calcUserIntersection(selectedImageIds));
		updateAddTagButtonState();
            }
        }
        updateAddTagButtonState();
    }
    
    async function persistTagToImage(imageId, newTag) {
        try {
            const tagenc = encodeURIComponent(newTag);
            const response = await fetch('addTag.php?imageid='+imageId+'&tag='+tagenc);
            if (!response.ok) {
                throw new Error('Failed to update tag');
            }
            
            // Refresh the tags for this image 
	    collectTags(baseDbUrl, imageId, function onCompletion() {
		renderTagset(calcNonUserIntersection(selectedImageIds), calcUserIntersection(selectedImageIds));
	    });
	    
        } catch (error) {
            console.error(`Error updating ${objid}:`, error);
            return false;
        }
    }

    function persistTagToImages(imageIds, idx, newTag, onCompletion) {
	if (idx > imageIds.length) {
	    onCompletion();
	    return;
	} else {
	    persistTagToImage(imageIds[idx], newTag, function innerOnCompletion() {
		persistTagToImages(imageIds, idx+1, newTag, onCompletion);
	    });
	}
    }

    function collectImageListTags(imageIds, idx, onCompletion) {
	if (idx > imageIds.length) {
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
        
        if (newTag === '') {
            alert('Please enter a tag');
            return;
        }

        let success = true;
	persistTagToImages(Array.from(selectedImageIds), 0, newTag, function onCompletion() {
            newTagInput.value = '';
            allTags = {};
	    allUserTags = {};
	    collectImageListTags(selectedImageIds, 0, function innerOnCompletion() {
		renderTagset(calcNonUserIntersection(selectedImageIds), calcUserIntersection(selectedImageIds));
	    });
	});
    }
    
    function updateAddTagButtonState() {
        const addButton = document.getElementById('addNewTagButton');
        if (addButton) {
            addButton.disabled = selectedImageIds.size === 0;
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

    function init(row, confidence) {
        rekognizeConfidence = confidence;
        
        var f = document.getElementById("imgArrayFrame");
        f.callback = function onChannel(url) {
            window.location.replace(url, "", "", true);
        };
        f.checkboxAction = function checkAction(checkboxElem, dbUrl, objid) {
	    baseDbUrl = dbUrl;
            checkboxAction(checkboxElem, dbUrl, objid);
        };
        f.clearChecksAction = function clearChecksAction() {
            clearChecks();
        };

        // Load the imgArrayTbl *after* the init function finishes so callback
        // is set before users might click on images
        f.src = "./imgArrayTbl.php?row="+row;
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

        $row = array_key_exists('row', $_GET) ? $_GET['row'] : 0;

        if (isset($_COOKIE['login_user'])) {
            echo 'onload="init('."'".$row."',".$confidence.')">';
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
	  
            <textarea readonly placeholder="Common tags of selected images..."
                id="key-area" rows="18" cols="4"
                style="position:fixed; width:27%; height:80%; padding-left:10px; margin-left:10px; padding-top:10px"
                class="w3-round-large"></textarea>

            <div style="position:fixed; width:27%; bottom:0; height:45px; margin:10px; z-index:999;"
                class="w3-white w3-round-large">
	      
                <div class="tagAddArea">
                    <label for="newTag" class="w3-small">New tag:&nbsp;</label>
                    <input id="newTag" type="text" size="12" class="w3-small" placeholder="enter tag here">
                    <button id="addNewTagButton" class="w3-small" style="font-weight:bold" onclick="handleAddTag()" disabled>Add</button>
                </div>
		
            </div>
       
        </div>
	
        <div style="height:90%; width:70%; padding:10px; margin:10px"
            class="w3-white w3-round-large w3-panel w3-display-bottomright">

            <iframe id="imgArrayFrame" src="" frameBorder="0"
                height="100%" width="100%" style="float:right; z-index:999">
                <p>Your browser does not support iframes.</p>
            </iframe>

      </div>
      
    </div>
    
  </body>
</html>
