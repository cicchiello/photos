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
       
  <link href="./loader.css" media="all" rel="stylesheet" />
  
  <style>
  </style>

  <script>
    "use strict";

    function sleep(ms) {
       return new Promise(resolve => setTimeout(resolve, ms));
    }

    async function init(id, row, imagepath, basename, tagFilters, checkedImages) {
        //console.log('TRACE(image_download.php): id: '+id);
        //console.log('TRACE(image_download.php): imagepath: '+imagepath);
        //console.log('TRACE(image_download.php): basename: '+basename);
        //await sleep(10000);
        var b = document.getElementById("download");
	b.href = imagepath;
        b.download = basename;
        b.click();
        var url = "./image_info.php?id="+id+"&row="+row;
        if (tagFilters)
            url += "&tags=" + tagFilters;
        if (checkedImages)
            url += "&checked=" + checkedImages;
        open(url, "_self");
    }

  </script>

  </head>
  
  <body class="bg" 

    <?php
        $id = $_GET['id'];
        $row = $_GET['row'];
        $tagFilters = isset($_GET['tags']) ? $_GET['tags'] : '';
        $checkedImages = isset($_GET['checked']) ? $_GET['checked'] : '';

        $ini = parse_ini_file("./config.ini");
        $DbBase = $ini['couchbase'];
        $Db = $ini['dbname'];
        $imgAttachName = $ini['imgAttachName'];
        $scratchpad = $ini['scratchPath'];

        $docUrl = $DbBase.'/'.$Db.'/'.$id;
        $doc = json_decode(file_get_contents($docUrl), true);
        $imageUrl = $docUrl.'/'.$imgAttachName;

        $dstpath = $scratchpad.'/'.$id;
        downloadFile($imageUrl, $dstpath);

        $basename = basename($doc['paths'][0]);
        echo ' onload="init('."'".$id."',".$row.",'".$dstpath."','".$basename."','".$tagFilters."','".$checkedImages."'".')" >';
        $enabled = array(
            'live' => false,
            'library' => true,
            'recording' => false,
            'scheduled' => false
        );
       ?>
    
    <div class="w3-container w3-display-middle">
      <div class="w3-panel w3-card w3-white w3-padding-16 w3-round-large w3-show loader">
        <a id="download" type="hidden" href="./bar.jpg" download="bar.jpg"/>
      </div>
    </div>

    <?php
       $doc = json_decode(file_get_contents($docUrl), true);
       $downloadName = basename($doc['paths'][0])
    ?>
	  
  </body>
  
</html>
