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
  </style>

  <script>
    function init(row) {
        console.log("TRACE(index.php:init)");	
        var f = document.getElementById("imgArrayFrame");
        f.callback = function onChannel(url) {
            /* alert("TRACE(index.php:init:f.callback): url: "+url); */
            window.location.replace(url, "", "", true);
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

        $row = array_key_exists('rowfoo', $_GET) ? $_GET['row'] : 0;

        if (isset($_COOKIE['login_user'])) {
            echo 'onload="init('."'".$row."'".')">';
            echo renderMainMenu($_COOKIE['login_user']);
        } else {
            echo 'onload="forceLogin()">';
        }
        #echo var_dump(isset($_COOKIE['login_user']));
    ?>

    <div style="height:90%; width:75%; padding:10px; margin-right:2px"
	 class="w3-white w3-round-large w3-panel w3-display-right">

      <iframe id="imgArrayFrame" src="" frameBorder="0"
	      height="100%" width="100%" style="float:right; z-index:999">
	<p>Your browser does not support iframes.</p>
      </iframe>

    </div>

  </body>
</html>
