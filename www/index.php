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
  </style>

  <script>
    var rekognizeConfidence = 0;
    var allTags = {};

    function calcIntersection() {
	var intersection = new Set();
	var first = true;
	for (const objid in allTags) {
	    if (first) {
		intersection = allTags[objid];
	    } else {
		intersection = intersection.intersection(allTags[objid]);
	    }
	    first = false;
	}
	return intersection;
    }

    function renderTagset(tagset) {
	var str = "";
	tagset.forEach(tag => {str += tag+"\n";});

	var keyArea = document.getElementById("key-area");
	keyArea.innerHTML = str;
    }

    function clearChecks() {
	allTags = {};
		
	renderTagset(calcIntersection());
    }
    
    function checkboxAction(checkboxElem, dburl, objid) {
	if (objid) {
            if (checkboxElem.checked) {
		var objurl = dburl+"/"+objid;
		fetch(objurl)
		    .then(res => {
			if (!res.ok) {
			    console.log("ERROR(checkboxAction): network error");
			} else {
			    return res.json();
			}
		    })
		    .then(data => {
			allTags[objid] = new Set();
			data.tags.forEach(tagobj => {
			    if (tagobj.source === 'rekognition') {
				if (tagobj.Confidence > rekognizeConfidence) {
				    allTags[objid].add(tagobj.Name);
				}
			    }
			});
			
			renderTagset(calcIntersection());
		    })
		    .catch(error => {
			console.log("ERROR(checkboxAction): Fetch error: "+error);
		    });
	    } else {
		delete allTags[objid];
		
		renderTagset(calcIntersection());
	    }
	}
    }
    
    function init(row, confidence) {
	rekognizeConfidence = confidence;
	
        var f = document.getElementById("imgArrayFrame");
        f.callback = function onChannel(url) {
            /* alert("TRACE(index.php:init:f.callback): url: "+url); */
            window.location.replace(url, "", "", true);
        };
	f.checkboxAction = function checkAction(checkboxElem, dbUrl, objid) {
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
            echo renderMainMenu($_COOKIE['login_user']);
        } else {
            echo 'onload="forceLogin()">';
        }
        #echo var_dump(isset($_COOKIE['login_user']));
    ?>

    <textarea readonly placeholder="Common tags of selected images..."
	      id="key-area" rows="10" cols="4"
	      style="width:27%; padding:20px; margin:10px"
	      class="w3-round-large w3-display-bottomleft"></textarea>
      
    <div style="height:90%; width:70%; padding:10px; margin:10px"
	 class="w3-white w3-round-large w3-panel w3-display-bottomright">

      <iframe id="imgArrayFrame" src="" frameBorder="0"
	      height="100%" width="100%" style="float:right; z-index:999">
	<p>Your browser does not support iframes.</p>
      </iframe>

    </div>

  </body>
</html>
