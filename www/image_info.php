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
    function reload() {
       /*alert("TRACE(image_info.php:reload):");*/
       window.location.replace('./index.php', "", "", true);
    }
    
  </script>
  
  </head>
  
  <body class="bg">

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
	  <?php echo renderImgInfo($_GET['id']); ?>
       </fieldset>
	
	<br>
	<div class="popupBtn">
	   <img id="return" onclick="reload()" src="img/return.png"
	        align="left" width="48" height="48" title="Return">
	</div>
    </div>

</body>
</html>
