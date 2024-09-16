<?php
    // intentionally place this before the html tag

    setcookie('login_user', "unknown", time()-3600, '/');
  ?>


<html>

  <head>
    <?php
       // Uncomment to see php errors
       ini_set('display_errors', 1);
       ini_set('display_startup_errors', 1);
       error_reporting(E_ALL);

       include('photos_utils.php');

       echo renderLookAndFeel();
       ?>

    <link href="./login.css" media="all" rel="stylesheet">
    
  </head>

  <style>
  </style>

<script>
    "use strict";

    function sleep(ms) {
       return new Promise(resolve => setTimeout(resolve, ms));
    }

    async function success() {
       await sleep(2000);
       open('./login.php',"_self");
    }
    
  </script>

  <body class="bg" onload="success()">

  </body>
</html>