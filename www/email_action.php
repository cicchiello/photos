<!DOCTYPE html>
<html>
  <head>
    <?php
       // Uncomment to see php errors
       ini_set('display_errors', 1);
       ini_set('display_startup_errors', 1);
       error_reporting(E_ALL);
    
       include('photos_utils.php');

       echo renderLookAndFeel();

       $id = $_POST['id'];
       $email = $_POST['email'];
      ?>

    <link href="./login.css" media="all" rel="stylesheet">
    
    <script src="./photos_utils.js"></script>
  
  </head>
  
  <script>

    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function(event) {
      var modal = document.getElementById('id01');
      if (event.target == modal) {
        modal.style.display = "none";
        open('./profile.php',"_self");
      }
    }

    function onCancel() {
      open('./profile.php',"_self");
    }

    async function onCommit(id) {
      post("./commit_email.php", {
          "id": id,
          "email":document.getElementById('email').value
      });
    }

    // Add return key handler
    document.addEventListener('DOMContentLoaded', function() {
      document.getElementById('email').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          onCommit('<?php echo $id; ?>');
        }
      });
    });
    
  </script>
    
  <body class="bg" 

    <?php       
       if (isset($_COOKIE['login_user'])) {
         echo 'onload="document.getElementById('."'id01'".').style.display='."'block'".'">';
       } else {
         echo 'onload="onCancel()">';
       }
     ?>

    <!-- The Modal -->
    <div id="id01" class="modal">
      <span onclick="onCancel()" class="close" title="Close Modal">&times;</span>

      <!-- Modal Content -->
        <div class="modal-content animate w3-container w3-round-large w3-padding">
	  <?php
	    echo '<label><b>Email</b></label>';
	    echo '<input id="email" type="text" value="'.$email.'" name="email">';
	    echo '<br>';
	    echo '<img onclick="onCancel()" src="img/cancel.png" width="48" height="48"';
	    echo '     title="Cancel" class="popupBtn" align="left">';
	    echo '<img onclick="onCommit('."'".$id."'".')" src="img/ok.png" width="48" height="48"';
	    echo '     title="Done" class="popupBtn" align="right">';
	   ?>
	</div>

    </div>
  </body>
</html>
