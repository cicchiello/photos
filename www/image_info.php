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
    
    <?php
       include ('photos_utils.php');
       
       echo renderLookAndFeel();
       ?>

   
  <style>
      .pillButton {
          border: none;
          padding: 5px 5px;
          text-align: center;
          text-decoration: none;
          display: inline-block;
          margin: 4px 2px;
          cursor: pointer;
          border-radius: 16px;
	  font-size: 70%;
      }

      .pillButton:hover {
          background-color: #f1f1f1;
      }

      .image {
          border: 1px solid #555;
	  height: 128px;
	  width: auto;
	  float: left;
	  margin: 15px;
      }
      
  </style>

  <script>
        function sleep(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }

        function init() {
            var f = document.getElementById("detail");
            f.callback = async function onChannel(url) {
                //console.log("TRACE(image_info.php:init:callback): url: "+url);		
                window.location.replace(url, "", "", true);
            };
        }
    
        function reloadAction(id) {
           /*alert("TRACE(image_info.php:reload):");*/
           window.location.replace('./index.php', "", "", true);
        }
    
        async function downloadAction(id) {
            //console.log("TRACE(image_info.php:downloadAction): id: "+id);
            var f = document.getElementById("detail");
            if ("callback" in f) {
                f.callback('./image_download.php?id='+id);
	    } else {
                console.log("ERROR(image_info.php:downloadAction): f doesn't have a callback member");
	    }
	}

  </script>
  
  </head>
  
  <body class="bg" onload="init()">

    <?php
        if (isset($_COOKIE['login_user'])) {
            echo renderMainMenu($_COOKIE['login_user']);
        } else {
            echo 'onload="forceLogin()">';
        }
       
       ?>

    <div id="detail"
	 class="w3-container w3-display-middle w3-panel w3-card w3-white w3-padding-16 w3-round-large">
       <fieldset>
          <legend>Image Detail:</legend>
	  <?php 
               $id = $_GET['id'];
               echo renderImgInfo($id); 
           ?>
       </fieldset>
	
	<br>
	<div class="popupBtn">
	    <?php
                echo '<img id="return" onclick="reloadAction('."'".$id."'".')" src="img/return.png" ';
                echo '     width="48" height = "48" title="Return" align="left">';

                echo '<img onclick="downloadAction('."'".$id."'".')" src="img/download.png" ';
                echo '    class="Btn" title="Download" width="48" height="48" align="right">';
	    ?>
	</div>
    </div>

</body>
</html>
