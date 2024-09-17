<!DOCTYPE html>
<html>
  
  <head>
    
  <link href="./w3.css" media="all" rel="stylesheet">
  <link href="./style.css" media="all" rel="stylesheet">
  <link href="./menu.css" media="all" rel="stylesheet">

  <style>
  </style>

  <script>
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
       // Uncomment to see php errors
       //ini_set('display_errors', 1);
       //ini_set('display_startup_errors', 1);
       //error_reporting(E_ALL);

       include('photos_utils.php');

       if (isset($_COOKIE['login_user'])) {
         echo '> ';
         echo renderMainMenu($_COOKIE['login_user']);
       } else {
         echo 'onload="forceLogin()">';
         echo '> ';
       }
       //echo var_dump(isset($_COOKIE['login_user']));
    ?>

  </body>
</html>
