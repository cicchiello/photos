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
      
      .album-container {
	  height: 100px; /* any fixed value for the parent */
      }
      
      .center {
          display: block;
          margin-left: auto;
          margin-right: auto;
          width: 100%;
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
	    const rowsPerPage = 4;
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
              $viewUrl = $DbBase.'/_design/photos/_view/photos?descending=false';
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
