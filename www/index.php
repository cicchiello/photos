<!DOCTYPE html>
<?php
    // intentionally place this before the html tag

    // Uncomment to see php errors
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    include('photos_utils.php');
  ?>

<html>
  
  <head>
    <?php echo renderLookAndFeel(); ?>

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

    .profile-area {
        position: fixed;
        top: 10px;
        left: 10px;
        z-index: 1000;
    }
  </style>

  <script src="tags.js"></script>
  
  <script>
    
    function init(dbUrl, row, confidence, tagFilters, checkedImages) {

	setConfidence(confidence);
	setDbUrl(dbUrl);
	setNewTagListener(document);
	
        var f = document.getElementById("imgArrayFrame");
        f.callback = function callback(url) {
            window.location.replace(url, "", "", true);
        };
        f.checkboxAction = function checkAction(checkboxElem, dbUrl, objid) {
            checkboxAction(checkboxElem, dbUrl, objid);
        };
        f.selectAllAction = function selectAllActionCallback(allCheckboxElems, checked, dbUrl) {
	    selectAllAction(allCheckboxElems, checked, dbUrl);
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

	collectImagesThenRender(f, row, tagFilters, checkedImages);
    }
    
    function sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    async function forceLogin() {
        await sleep(2000);
        open('./login.php',"_self");
    }
    
  </script>
  
  </head>
  
  <body class="bg"

    <?php       
        $ini = parse_ini_file("./config.ini");
        $confidence = $ini['rekognizeConfidence'];

        if (isset($_COOKIE['login_user'])) {
            $Db = $ini['dbname'];
            $DbBase = $ini['couchbase'].'/'.$Db;
	    
            $row = array_key_exists('row', $_GET) ? $_GET['row'] : 0;
            $tagFilters = array_key_exists('tags', $_GET) ? $_GET['tags'] : '';
	    $checkedImages = array_key_exists('checked', $_GET) ? $_GET['checked'] : '';
            echo 'onload="init('."'".$DbBase."','".$row."',".$confidence.",'".$tagFilters."','".$checkedImages."'".')">';
            echo '<div class="profile-area">';
            echo renderProfileArea($_COOKIE['login_user']);
            echo '</div>';
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

            <div style="position:fixed; width:20%; bottom:10px; height:70px; margin-left:10px; z-index:999;"
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
	
        <div style="position:fixed; height:95%; width:77%; top:5px; padding-right:10px; margin-right:10px;"
            class="w3-white w3-round-large w3-panel w3-display-bottomright">

            <iframe id="imgArrayFrame" src="" frameBorder="0"
                height="100%" width="100%" style="float:right; z-index:999">
                <p>Your browser does not support iframes.</p>
            </iframe>
        </div>
      
    </div>
    
  </body>
</html>
