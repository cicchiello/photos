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
    <link rel="stylesheet" href="pagination.css">
    
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
	  width: 100px; /* any fixed value for the parent */
      }
     
      .Btn:hover {
         background-color: #465702; /* Add a dark-grey background on hover */
         outline: none; /* Remove outline */
         cursor: pointer;
      }
      
      .album-img {
	  width: auto;
	  height: 90%;
	  aspect-ratio: 1; /* will make width equal to height (500px container) */
	  object-fit: contain; /* use the one you need */
	  /*object-position: 35% 100%;*/
      }

      /* The checkbox container */
      .check-container {
        display: block;
        position: relative;
        padding-left: 35px;
        margin-bottom: 0px;
        cursor: pointer;
        font-size: 22px;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
      }

      /* Hide the browser's default checkbox */
      .check-container input {
        position: absolute;
        opacity: 0;
        cursor: pointer;
        height: 0;
        width: 0;
      }

      /* Create a custom checkbox */
      .checkmark {
        position: absolute;
        top: 0;
        left: 0;
        height: 25px;
        width: 25px;
        background-color: #eee;
	border: 1px solid black;
      }

      /* On mouse-over, add a grey background color */
      .check-container:hover input ~ .checkmark {
        background-color: #ccc;
      }

      /* When the checkbox is checked, add a blue background */
      .check-container input:checked ~ .checkmark {
        background-color: #2196F3;
      }

      /* Create the checkmark/indicator (hidden when not checked) */
      .checkmark:after {
        content: "";
        position: absolute;
        display: none;
      }

      /* Show the checkmark when checked */
      .check-container input:checked ~ .checkmark:after {
        display: block;
      }
      
      /* Style the checkmark/indicator */
      .check-container .checkmark:after {
        left: 9px;
        top: 5px;
        width: 5px;
        height: 10px;
        border: solid white;
        border-width: 0 3px 3px 0;
        -webkit-transform: rotate(45deg);
        -ms-transform: rotate(45deg);
        transform: rotate(45deg);
      }

      .album-container {
	  height: 100px; /* any fixed value for the parent */
      }

      .center {
          display: inline;
	  float: right;
          width: 80%;
      }
      
      img {
	  width: auto;
	  height: 100%;
	  aspect-ratio: 1; /* will make width equal to height (500px container) */
	  object-fit: contain; /* use the one you need */
      }

    </style>
    
    <script>
        function init(row) {
	    const rowsPerPage = 5;
	    paginateTable(true, Math.trunc(row/rowsPerPage));
        }
      
        function infoAction(elemid) {
            var f = parent.document.getElementById("imgArrayFrame");
	    var img = document.getElementById(elemid);
	    var objid = img.getAttribute('data-objid');
	    if (objid !== "null") {
                var row = img.getAttribute('data-firstrow');
                f.callback('./image_info.php?id='+objid+'&row='+row);
	    }
	}
	
    </script>

  </head>

  <body class="bg"
	<?php
	    include('photos_utils.php');
	      
            $row = array_key_exists('row', $_GET) ? $_GET['row'] : 0;

            echo 'onload="init('."'".$row."'".')">';
	 ?>
     <div id="myTableParent" class="table-responsive">
       <span id = "dbUrl" hidden>
           <?php
              $ini = parse_ini_file("./config.ini");
              $Db = $ini['dbname'];
              $DbBase = $ini['couchbase'].'/'.$Db;
	      echo $DbBase;
           ?>
       </span>
       <table id="myTable" style="width:100%; overflow:scroll">
           <?php
              $viewUrl = $DbBase.'/_design/photos/_view/photo_ids?descending=false';
              $view0 = json_decode(file_get_contents($viewUrl),true);
              $numitems = $view0['total_rows'];

              $items = $view0['rows'];
	      echo renderImgArrayTable($row, $DbBase, $items, 'infoAction');
           ?>
       </table>
     </div>
     <div id="pagination">
       <div id="entriesDisplayDiv">
         Showing <span id="from"> </span> to <span id="to"></span> out of
	 <span id="totalTableEntries"><?php echo $numitems; ?></span> entries 
       </div>
       <div id="pageNumbersContainer">
         <div id="pageNumbers"></div>
         <div id="goToPage">Go to Page <input id="pageNumberInput" type="number">
	   <button id="goToPageButton">Confirm</button>
	 </div>
       </div>
     </div>
	
     <script src="pagination.js"></script>
  </body>
  
</html>
