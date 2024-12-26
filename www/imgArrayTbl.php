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
          padding: 1px;
	  width: 100px;
          height: auto;
      }

      img.thumb {
          max-width: 100%;
          height: auto;
          object-fit: contain;
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
        height: 20px;
        width: 20px;
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
        left: 7px;
        top: 2px;
        width: 5px;
        height: 10px;
        border: solid white;
        border-width: 0 3px 3px 0;
        -webkit-transform: rotate(45deg);
        -ms-transform: rotate(45deg);
        transform: rotate(45deg);
      }

      .album-container {
	  height: calc(20vh - 40px); /* any fixed value for the parent */
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

      input:placeholder-shown {
          font-style: italic;
      }
    
      .tagList { 
          text-align: right; 
      }

      .tag-display {
          background-color: #f8f8f8;
          border: 1px solid #ddd;
          border-radius: 4px;
          padding: 4px 8px;
          color: #666;
          cursor: default;
      }
      
      .label-container {
        display: inline-block;
        width: 90px;
        text-align: right;
        margin-right: 5px;
      }
      
    </style>
    
    <script>
        function sleep(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }

        function getImageId(elem) {
            const imageId = elem ? elem.getAttribute('data-objid') : null;
            return imageId === "null" ? null : imageId;
        }

        function init(row, tagFilters, checkedImages) {
            var checkedIds = new Set();
            if (checkedImages) {
		const checkedList = decodeURIComponent(checkedImages).split(' ');
		checkedList.forEach(id => {if (id) checkedIds.add(id);});
	    }

	    const rowsPerPage = 5;
            // Restore tag filters from URL parameters if they exist
            const urlParams = new URLSearchParams(window.location.search);
	    setSearchTagList([]);
            if (tagFilters) {
                const tagList = decodeURIComponent(tagFilters);
                document.getElementById('tagList').value = tagList;
		
                // Split and clean the tag list
		var cleanedSearchTagSet = new Set();
		tagList.split(' ').forEach((tag,i) => {if (tag) cleanedSearchTagSet.add(tag);});
		setSearchTagList(Array.from(cleanedSearchTagSet));
	    }
	    
            const setCheckMarksFunc = async function setCheckMarks() {
		await sleep(100);
		const checkboxes = document.querySelectorAll('input[type="checkbox"]');
		var allChecked = true;
		const selectAllCheckboxElem = document.getElementById("selectAllCheckbox");
		checkboxes.forEach(checkbox => {
		    if (checkbox != selectAllCheckboxElem) {
			const img = checkbox.parentElement.nextElementSibling;
			checkbox.checked = img && checkedIds.has(getImageId(img));
			allChecked &= checkbox.checked;
		    }
		});
		selectAllCheckboxElem.checked = allChecked;
 	    };

	    if (getSearchTagList().length > 0) {
		const paginateFunc = async function adjustPagination() {
		    await sleep(100);
		    changeSearchPage(null, getSelectedList(), Math.trunc(row/rowsPerPage)+1);
		    setCheckMarksFunc();
		};
		
                onFindImagesButton(paginateFunc);
	    } else {
		changeSearchPage(null, getSelectedList(), Math.trunc(row/rowsPerPage)+1);
		setCheckMarksFunc();
	    }
        }
      
        function imgInfoAction(elemid) {
            var f = parent.document.getElementById("imgArrayFrame");
	    var img = document.getElementById(elemid);
	    var objid = getImageId(img);
	    if (objid !== "null") {
                var row = img.getAttribute('data-firstrow');

                // Include current tag filters and checked images in the URL
                var tagFilters = document.getElementById('tagList').value;
                var tagParam = tagFilters ? '&tags=' + encodeURIComponent(tagFilters) : '';
                var checkedImages = f.getCheckedImages();
		var checkedParam = "";
		if (checkedImages.length > 0)
		    checkedParam = '&checked=' + encodeURIComponent(checkedImages.join(' '));
                f.callback('./image_info.php?id='+objid+'&row='+row+tagParam+checkedParam);
	    }
	}

        function checkAction(checkboxElem, dburl, elemid) {
	    var objid = getImageId(document.getElementById(elemid));
	    if (objid) {
                var f = parent.document.getElementById("imgArrayFrame");
                f.checkboxAction(checkboxElem, dburl, objid);
	    }
	}

        function selectAllAction(selectAllCheckboxElem, dburl) {
	    const checkboxes = Array.from(document.querySelectorAll('input[type="checkbox"]'))
		  .filter(checkbox => checkbox !== selectAllCheckboxElem);
	    
            var f = parent.document.getElementById("imgArrayFrame");
	    f.selectAllAction(checkboxes, selectAllCheckboxElem.checked, dburl);
        }
      
    </script>

  </head>

  <body class="bg"
	<?php
	    include('photos_utils.php');
	      
            $row = array_key_exists('row', $_GET) ? $_GET['row'] : 0;
            $tagFilters = array_key_exists('tags', $_GET) ? $_GET['tags'] : '';
            $checkedImages = array_key_exists('checked', $_GET) ? $_GET['checked'] : '';

            echo 'onload="init('."'".$row."','".$tagFilters."','".$checkedImages."'".')">';
	 ?>
     <div id="myTableParent" class="table-responsive" style="width: calc(100% - 15px);" >
       <span id = "dbUrl" hidden>
           <?php
              $ini = parse_ini_file("./config.ini");
              $Db = $ini['dbname'];
              $DbBase = $ini['couchbase'].'/'.$Db;
	      echo $DbBase;
           ?>
       </span>
       <span>
         <span class="label-container">
           <label for="tagInput" class="w3-small" style="font-weight:bold">Search tag:</label>
         </span>
         <input id="tagInput" class="w3-small" type="text" size="12" placeholder="enter tag here">
	 <button id="findImagesButton" class="w3-small" style="font-weight:bold">&gt;&gt;&gt;&gt;</button>
         <input id="tagList" class="tagList w3-small tag-display" type="text" placeholder="tag filters collect here..." style="width:55%; border:none; background-color:#f8f8f8;" readonly>
         <label class="check-container" style="float:right; margin-right: 20px;">
           <input type="checkbox" id="selectAllCheckbox" onclick="selectAllAction(this,'<?php echo $DbBase ?>')">
           <span class="checkmark"></span>
           <span style="font-size: 14px; margin-left: 5px;">Select All</span>
         </label>
         <button id="clearFindButton" class="w3-small" style="font-weight:bold">Clear</button>
       </span>
       <p></p>
       <span>
         <span class="label-container">
           <label for="excludeTagInput" class="w3-small" style="font-weight:bold">Exclude tag:</label>
         </span>
         <input id="excludeTagInput" class="w3-small" type="text" size="12" placeholder="enter tag here">
	 <button id="excludeImagesButton" class="w3-small" style="font-weight:bold">&gt;&gt;&gt;&gt;</button>
       </span>
       <p></p>
       <table id="myTable" style="width:100%; overflow:scroll">
           <?php
              $viewUrl = $DbBase.'/_design/photos/_view/photo_ids?descending=false';
              $view0 = json_decode(file_get_contents($viewUrl),true);
              $numitems = $view0['total_rows'];

              $items = $view0['rows'];
	      echo renderImgArrayTable($row, $DbBase, $items, 'imgInfoAction', 'checkAction');
           ?>
       </table>
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
     </div>
	
     <script src="pagination.js"></script>
  </body>
  
</html>
