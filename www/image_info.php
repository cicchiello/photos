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
          padding: 8px 12px;
          text-align: center;
          text-decoration: none;
          display: inline-block;
          margin: 5px 3px;
          cursor: pointer;
          border-radius: 16px;
	  font-size: 100%;
      }

      .pillButton:hover {
          background-color: #f1f1f1;
      }

      .image {
          border: 1px solid #555;
          height: calc(70vh - 90px);  /* Increased reduction to make room for buttons */
          width: auto;
          max-width: 100%;
          object-fit: contain;
          margin: 0;
      }

      #detail {
          width: 80% !important;
          height: 80vh !important;
          max-height: 80vh !important;
          overflow: hidden;
          transform: translate(-50%, -50%);
          position: fixed;
          top: 50%;
          left: 50%;
          display: flex;
          flex-direction: column;
      }

      .detail-content {
          display: flex;
          flex: 1;
          min-height: 0;
      }

      .detail-content fieldset {
          width: 100%;
          margin: 0;
          padding: 18px;  
          display: flex;
          gap: 18px;  
      }

      .detail-content fieldset legend {
          margin-left: 9px;  
          padding: 0 9px;  
          font-size: 108%;  
          font-weight: 500;
      }

      .image-section {
          flex: 0 0 45%;
          display: flex;
          align-items: center;
          justify-content: center;
      }

      .right-section {
          flex: 0 0 50%;
          display: flex;
          flex-direction: column;
      }

      .tags-section {
          margin-bottom: 18px;  
          font-size: 90%;  
      }

      .details-section {
          flex-grow: 1;
          font-size: 90%;  
      }

      .details-section table td {
          padding: 7px 4px;  
      }

      .details-section .detail-label {
          font-weight: 500;
      }

      .details-section .detail-value {
          color: blue;
          word-break: break-all;
      }

      .popupBtn {
          padding: 13px 18px;  
          border-top: 1px solid #eee;
          text-align: center;
          margin-top: 9px;  
          margin-bottom: 4px;  
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
	 class="w3-container w3-display-middle w3-panel w3-card w3-white w3-round-large">
       <div class="detail-content">
           <fieldset style="width: 100%; margin: 0; display: flex; gap: 20px;">
              <legend>Image Detail:</legend>
	      <?php 
                   $id = $_GET['id'];
		   $row = isset($_GET['row']) ? $_GET['row'] : 0;
                   echo renderImgInfo($id,$row); 
               ?>
           </fieldset>
       </div>
	
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
