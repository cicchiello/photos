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
	      height: 256px;
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
    
        function reloadAction(id, row) {
            // Get tag filters and checked images from URL params
            const urlParams = new URLSearchParams(window.location.search);
            var url = "./index.php?row="+row;
 
            const tagFilters = urlParams.get('tags');
            if (tagFilters) 
                url += "&tags=" + tagFilters;

            const checkedImages = urlParams.get('checked');
            if (checkedImages) 
                url += "&checked=" + checkedImages;

            window.location.replace(url, "", "", true);
        }
    
        async function downloadAction(id, row) {
            //console.log("TRACE(image_info.php:downloadAction): id: "+id);
            const urlParams = new URLSearchParams(window.location.search);
            var url = './image_download.php?id='+id+'&row='+row;

            // Add tag filters and checked images if present
            const tagFilters = urlParams.get('tags');
            if (tagFilters) 
                url += "&tags=" + tagFilters;

            const checkedImages = urlParams.get('checked');
            if (checkedImages) 
                url += "&checked=" + checkedImages;

            var f = document.getElementById("detail");
            if ("callback" in f) {
                f.callback(url);
            } else {
                console.log("ERROR(image_info.php:downloadAction): f doesn't have a callback member");
            }
        }

        async function deleteTag(tagName, imageId) {
            if (!confirm(`Are you sure you want to delete the tag "${tagName}"?`)) {
                return;
            }

            try {
		const tagnameenc = encodeURIComponent(tagName);
                const response = await fetch('deleteTag.php?imageid='+imageId+'&tagname='+tagnameenc);
                if (!response.ok) {
                    throw new Error('Failed to delete tag');
                }
                
                // Refresh the page to show updated tags
                location.reload();
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to delete tag: ' + error.message);
            }
        }

  </script>
  
  </head>
  
  <body class="bg" onload="init()">

    <?php
        if (isset($_COOKIE['login_user'])) {
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
               $row = $_GET['row'];
               echo renderImgInfo($id,$row); 
           ?>
       </fieldset>
	
	<br>
	<div class="popupBtn">
	    <?php
                echo '<img id="return" onclick="reloadAction('."'".$id."','".$row."'".')" src="img/return.png" ';
                echo '     width="48" height = "48" title="Return" align="left">';

                echo '<img onclick="downloadAction('."'".$id."',".$row.')" src="img/download.png" ';
                echo '    class="Btn" title="Download" width="48" height="48" align="right">';
	    ?>
	</div>
    </div>

</body>
</html>
