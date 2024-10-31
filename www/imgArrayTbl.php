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
  
    <link href="./thumbs.css" media="all" rel="stylesheet" />
    
    <style>
      .bg {
	  height: 100%;
      }
      
      table, th, td {
      	  border: 1px solid black;
          border-collapse: collapse;
      }
     
      th, td {
          padding: 5px;
	  width: 120px; /* any fixed value for the parent */
      }
     
      .Btn:hover {
         background-color: #465702; /* Add a dark-grey background on hover */
         outline: none; /* Remove outline */
         cursor: pointer;
      }
      
      .album-container {
	  height: 120px; /* any fixed value for the parent */
      }

      img {
	  width: auto;
	  height: 100%;
	  aspect-ratio: 1; /* will make width equal to height (500px container) */
	  object-fit: cover; /* use the one you need */
      }

    </style>
    
    <script>
        function infoAction(id,row) {
            var f = parent.document.getElementById("imgArrayFrame");
	    if (!row) row = 0;
            if (f) {
	        /*alert("TRACE(imgArrayTbl.php:infoAction): before callback; id: "+id);*/
                f.callback('./image_info.php?id='+id+'&row='+row);
            } else
                document.getElementById("result").innerHTML = "no imgArrayFrame to process "+id;
	}
	
    </script>

  </head>

  <body class="bg">
     <p id="result"></p>
        <table style="width:100%; overflow:scroll">
           <?php
	      include('photos_utils.php');
	      
              $row = array_key_exists('rowfoo', $_GET) ? $_GET['row'] : 0;
	      echo renderImgArrayTable($row, 'infoAction');
           ?>
        </table>
  </body>
  
</html>
