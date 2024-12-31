<?php

function deltaTimeStr($deltaTime)
{
   $deltaM = floor($deltaTime/60);
   $deltaH = floor($deltaM/60);
   $tstr = '';
   if ($deltaH == 0)
      $tstr .= $deltaM.' min';
   else {
      $tstr .= $deltaH.'hr'.($deltaH>1?'s':'');
      $remainder = $deltaM - 60*$deltaH;
      if ($remainder > 0) $tstr .= ', '.$remainder.' min';
   }

   return $tstr;
}


function realFileSize($path)
{
    $size = trim(`stat -L -c%s '$path'`);
    return $size;
}


/**
* Converts bytes into human readable file size.
*
* @param string $bytes
* @return string human readable file size (2,87 Мб)
* @author Mogilev Arseny
*/
function readableSize($bytes)
{
    $bytes = floatval($bytes);
    $result = $bytes;
    $arBytes = array(
        0 => array(
	    "UNIT" => "TB",
	    "VALUE" => pow(1024, 4)
	),
	1 => array(
	    "UNIT" => "GB",
	    "VALUE" => pow(1024, 3)
	),
	2 => array(
	    "UNIT" => "MB",
	    "VALUE" => pow(1024, 2)
	),
	3 => array(
	    "UNIT" => "KB",
	    "VALUE" => 1024
	),
	4 => array(
	    "UNIT" => "B",
	    "VALUE" => 1
	),
    );

    foreach($arBytes as $arItem) {
        if($bytes >= $arItem["VALUE"]) {
	    $result = strval(round($bytes / $arItem["VALUE"], 2))." ".$arItem["UNIT"];
	    break;
	}
    }
    return $result;
}


function renderLookAndFeel()
{
   $result = '';
   $result .= '<link rel="shortcut icon" type="image/x-icon" href="./img/photos-favicon.ico" />';
   $result .= '<link href="./w3.css" media="all" rel="stylesheet">';
   $result .= '<link href="./style.css" media="all" rel="stylesheet">';
   $result .= '<link href="./menu.css" media="all" rel="stylesheet">';
   return $result;
}


function renderImgArrayTable($firstrow, $DbBase, $items, $onImgAction, $onCheckAction)
{
    $q = "'";

    $result = '';
    $rendering = false;
    $row = 0;
    $col = 0;
    $ColsPerRow = 6;
    $NumRows = 5;
    $cnt = 0;
    foreach ($items as $item) {
        if ($col == 0) {
            if ($row == $firstrow) {
                $rendering = true;
                $result .= '<table style="width:100%">';
            }
	    if ($row == $firstrow+$NumRows ) {
                $rendering = false;
	    }

            if ($rendering) {
                if ($row%2 == 0)
                    $style = "background-color:#b8d7b0";
                else
                    $style = "background-color:#e2f4dd";
		$class = 'row'.$row;
                $result .= '<tr class="'.$class.'" style="'.$style.'">';	    
            }
        }

        if ($rendering) {
            $id = $item['id'];
	    $imgId = 'image'.$cnt;
	    $entryId = 'entry'.$cnt;
	    $labelId = 'label'.$cnt;
	    $checkId = 'check'.$cnt;
	    $checkStr = $onCheckAction.'(this,'.$q.$DbBase.$q.','.$q.$imgId.$q.')';
            $imgUrl = $DbBase.'/'.$id.'/thumbnail';
      
            $result .= '  <td style="text-align:left">';
	    $result .= '     <div id="'.$entryId.'">';
	    $result .= '        <label id="'.$labelId.'" class="check-container">';
	    $result .= '           <input id="'.$checkId.'" type="checkbox" ';
	    $result .= '                  onclick="'.$checkStr.'">';
	    $result .= '           <span class="checkmark"></span>';
	    $result .= '        </label>';
            $result .= '        <img id="'.$imgId.'" src="'.$imgUrl.'" alt="image"';
            $result .= '             data-objid="'.$id.'"';
            $result .= '             data-firstrow="'.$firstrow.'"';
            $result .= '             class="album-img album-container center Btn"';
            $result .= '             onclick="'.$onImgAction.'('.$q.$imgId.$q.')"';
            $result .= '             style="vertical-align:horizontal-align;margin:2px 2px 2px 2px"';
            $result .= '             title="'.basename($item['key']).'"/>';
	    $result .= '     </div>';
            $result .= '  </td>';

            $cnt += 1;
	}
	
        $col += 1;
        if ($col == $ColsPerRow) {
	    if ($rendering) {
                $result .= '</tr>';
            }

            $row += 1;
	    $col = 0;
        }
    }
    if (($row == 0) && ($col == 0)) {
        $result .= "<p>No Images.</p>";
    } else {
    
        while (($row >= $firstrow) && ($row < $firstrow+$NumRows) && ($col < $ColsPerRow)) {
            if ($col == 0) {
                if ($row%2 == 0)
                    $style = "background-color:#b8d7b0";
                else
                    $style = "background-color:#e2f4dd";
                $class = 'row'.$row;
                $result .= '<tr class="'.$class.'" style="'.$style.'">';	    
            }
	
	    $imgId = 'image'.$cnt;
	    $entryId = 'entry'.$cnt;
	    $labelId = 'label'.$cnt;
	    $checkId = 'check'.$cnt;
      
            $result .= '  <td style="text-align:left; >';
	    $result .= '     <div id="'.$entryId.'">';
	    $result .= '        <label id="'.$labelId.'" class="check-container" style="visibility:hidden">';
	    $result .= '           <input id="'.$checkId.'" type="checkbox"';
	    $result .= '                  onclick="'.$onCheckAction.'(this,'.$q.$DbBase.$q.',null)">';
	    $result .= '           <span class="checkmark"></span>';
	    $result .= '        </label>';
            $result .= '        <img id="'.$imgId.'" src="img/transparent.png" alt="image"';
            $result .= '             data-objid="null"';
            $result .= '             data-firstrow="'.$firstrow.'"';
            $result .= '             class="album-img album-container center Btn"';
            $result .= '             onclick="'.$onImgAction.'('.$q.$imgId.$q.')"';
            $result .= '             style="vertical-align:horizontal-align;margin:2px 2px 2px 2px; visibility:hidden"';
            $result .= '             title=""/>';
	    $result .= '     </div>';
            $result .= '  </td>';
	    
            $cnt += 1;
	    
            $col += 1;
            if ($col == $ColsPerRow) {
                $result .= '</tr>';

                $row += 1;
	        $col = 0;
            }
	}
	
        $result .= '</table>';
    }
    return $result;
}


function renderImgInfo($id,$row)
{
   $q = "'";
   $userTagColor = '#B3CCFF';  // Medium blue - between bright and subtle

   $ini = parse_ini_file("./config.ini");
   $confidenceLimit = $ini['rekognizeConfidence']; 
   $DbBase = $ini['couchbase'];
   $Db = $ini['dbname'];
   $detailUrl = $DbBase.'/'.$Db.'/'.$id;
   $imgUrl = $detailUrl.'/web_image';

   $detail = json_decode(file_get_contents($detailUrl), true);
   $id = $detail['_id'];
   $path = $detail['paths'][0];
   $size = $detail['size'];
   
   $result = '';
   
   // Image section
   $result .= '<div class="image-section">';
   $result .= '    <img class="image" id="image" src="'.$imgUrl.'" title="Image"/>';
   $result .= '</div>';
   
   // Right section containing tags and details
   $result .= '<div class="right-section">';
   
   // Tags section
   $result .= '<div class="tags-section">';
   $cnt = 0;

   // Sort tags by confidence
   usort($detail['tags'], function($a, $b) {
       $confA = strcasecmp($a['source'], 'user') === 0 ? 100.0 : $a['Confidence'];
       $confB = strcasecmp($b['source'], 'user') === 0 ? 100.0 : $b['Confidence'];
       return $confB <=> $confA;  // Sort in descending order
   });

   foreach($detail['tags'] as $tag) {
      if (strcasecmp($tag['source'], 'rekognition') === 0) {
        $confidence = $tag['Confidence'];
        if ($confidence > $confidenceLimit) {
          $confidenceStr = sprintf("%2.0f%%", $confidence);
          $strength = ($confidence-$confidenceLimit)/(100.0-$confidenceLimit);
          $cstr = '#0a'.sprintf("%02x", 255*$strength).'40';
          if ($strength < 0.5) {
            $color = 'white';
          } else {
            $color = 'black';
          }
          $result .= '    <button class="pillButton" style="background-color:'.$cstr;
          $result .= ';color:'.$color.';cursor:default" title="Confidence '.$confidenceStr.'">';
          $result .= $tag['Name'].'</button>';
          $cnt += 1;
        }
      } else if (strcasecmp($tag['source'], 'user') === 0) {
        // User tags are always shown with full confidence
        $username = isset($tag['username']) ? $tag['username'] : 'unknown';
        $result .= '    <button class="pillButton" style="background-color:'.$userTagColor.';color:black;cursor:pointer" ';
        $result .= 'title="Added by '.$username.'" ';
        $result .= 'onclick="deleteTag('.$q.$tag['Name'].$q.','.$q.$id.$q.');">';
        $result .= $tag['Name'].'</button>';
        $cnt += 1;
      }
   }
   $result .= '</div>';
   
   // Details section
   $result .= '<div class="details-section">';
   $result .= '<table class="w3-table">';
   $result .= '	 <tr>';
   $result .= '	   <td class="detail-label">File:</td>';
   $result .= '	   <td class="detail-value w3-right">'.$path.'</td>';
   $result .= '	 </tr>';
   $result .= '	 <tr>';
   $result .= '	   <td class="detail-label">File size:</td>';
   $result .= '	   <td class="detail-value w3-right">'.readableSize($size).'</td>';
   $result .= '	 </tr>';
   $result .= '  <tr>';
   $result .= '    <td class="detail-label">Db Id:</td>';
   $result .= '	   <td class="detail-value w3-right" style="font-size:90%">'.$id.'</td>';
   $result .= '  </tr>';
   $result .= '</table>';
   $result .= '</div>';
   
   // Close right section
   $result .= '</div>';
   
   return $result;
}


function renderProfileArea($userName)
{
   $ini = parse_ini_file("./config.ini");
   $dbname = $ini['dbname'];
   $version = $ini['version'];
   
   $result = '  <div id="profileArea" class="row box w3-panel w3-card w3-white w3-round-large" style="position:fixed; top:5px; left:10px; z-index:1000; padding:10px; width:20%;">';
   
   // App name and version row
   $result .= '    <div style="display:flex; align-items:center; gap:5px; margin-bottom:8px;">';
   $result .= '      <span style="font-size:115%; font-weight:500;">Photos</span>';
   $result .= '      <span style="font-size:90%; color:#666;">v'.$version.'</span>';
   if ($dbname != "photos-test") {
       $result .= '      <span style="font-size:90%; color:#666; margin-left:auto;">['.$dbname.']</span>';
   }
   $result .= '    </div>';
   
   // User profile row
   $result .= '    <div style="display:flex; align-items:center; gap:10px; justify-content:space-between;">';
   $result .= '      <span style="min-width:60px; max-width:100px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="Hi, '.$userName.'!">Hi, '.$userName.'!</span>';
   $result .= '      <div style="display:flex; gap:10px;">';
   $result .= '        <a href="profile.php" id="ProfileBtn" class="Btn" title="My Profile" style="display:flex; align-items:center; gap:5px;">';
   $result .= '          <img class="profileIcon" src="img/profile_626.png">';
   $result .= '          Profile';
   $result .= '        </a>';
   $result .= '        <a href="logout_action.php" id="LogoutBtn" class="Btn" title="Logout" style="display:flex; align-items:center; gap:5px;">';
   $result .= '          <img class="profileIcon" src="img/logout_512.png">';
   $result .= '          Logout';
   $result .= '        </a>';
   $result .= '      </div>';
   $result .= '    </div>';
   
   $result .= ' </div>';
   
   return $result;
}


function downloadFile($url,$dstpath) {
  set_time_limit(0);

  // File we'll be creating
  $file = fopen($dstpath, "w+");
  
  //Here is the file we are downloading; if any spaces, replace with %20
  $ch = curl_init(str_replace(" ","%20",$url));
  
  // make sure to set timeout high enough to avoid interruption
  curl_setopt($ch, CURLOPT_TIMEOUT, 600);
  
  // write curl response to file
  curl_setopt($ch, CURLOPT_FILE, $file);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

  // do it!
  curl_exec ($ch);

  // clean up
  curl_close($ch);
  fclose($file);
}


function writeEmail($id,$email) {
  if (!isset($ini)) {
    $ini = parse_ini_file("./config.ini");
  }
  
  $WriteDbBase = $ini['couchbase'].'/'.$ini['dbname'];
  $row = json_decode(file_get_contents($WriteDbBase.'/'.$id), true);
  $row['email'] = $email;
  unset($row['_id']);
  
  putDb($WriteDbBase.'/'.$id, $row);
}

function writePassword($id,$pswd) {
  if (!isset($ini)) {
    $ini = parse_ini_file("./config.ini");
  }
  
  $WriteDbBase = $ini['couchbase'].'/'.$ini['dbname'];
  $row = json_decode(file_get_contents($WriteDbBase.'/'.$id), true);
  $row['password'] = hash('sha256', $pswd);
  unset($row['_id']);
  
  putDb($WriteDbBase.'/'.$id, $row);
}

function writeName($id,$fname,$lname) {
  if (!isset($ini)) {
    $ini = parse_ini_file("./config.ini");
  }
  
  $WriteDbBase = $ini['couchbase'].'/'.$ini['dbname'];
  $row = json_decode(file_get_contents($WriteDbBase.'/'.$id), true);
  $row['fname'] = $fname;
  $row['lname'] = $lname;
  unset($row['_id']);
  
  putDb($WriteDbBase.'/'.$id, $row);
}

function writeUsername($id,$uname) {
  if (!isset($ini)) {
    $ini = parse_ini_file("./config.ini");
  }
  
  $WriteDbBase = $ini['couchbase'].'/'.$ini['dbname'];
  $row = json_decode(file_get_contents($WriteDbBase.'/'.$id), true);
  $row['username'] = $uname;
  unset($row['_id']);
  
  putDb($WriteDbBase.'/'.$id, $row);
}

function updateDoc($objUrl, $doc) {
    // Update the document in CouchDB
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'PUT',
            'content' => json_encode($doc)
        )
    );
    $context = stream_context_create($options);
    $result = file_get_contents($objUrl, false, $context);
        
    return ($result !== FALSE);
}

function addTagToImage($imageId, $newTag, $username) {
   // First get the db details
   $ini = parse_ini_file("./config.ini");
   $DbBase = $ini['couchbase'];
   $Db = $ini['dbname'];

    // First get the current document
    $objUrl = $DbBase.'/'.$Db.'/'.$imageId;
    $response = file_get_contents($objUrl);
    
    if ($response === FALSE) {
        return false;
    }
    
    $doc = json_decode($response, true);
    if (!$doc) {
        return false;
    }

    // Check if tag already exists with 100.0 confidence
    $skipTag = false;
    foreach ($doc['tags'] as $tag) {
        if ($tag['Name'] === strtolower($newTag) && 
            isset($tag['Confidence']) && 
            $tag['Confidence'] == 100.0) {
            $skipTag = true;
            break;
        }
    }

    if (!$skipTag) {
        // Create new tag object
        $newTagObj = array(
            'Name' => strtolower($newTag),
            'Confidence' => 100.0,
            'timestamp' => time(),
            'source' => 'user',
            'username' => $username,
            'Instances' => array(),
            'Parents' => array(),
            'Aliases' => array(),
            'Categories' => array()
        );

        // Add new tag to document
        $doc['tags'][] = $newTagObj;

	return updateDoc($objUrl, $doc);
    }
    
    return true;
}


function deleteTagFromImage($imageId, $tagName, $username) {
   // First get the db details
   $ini = parse_ini_file("./config.ini");
   $DbBase = $ini['couchbase'];
   $Db = $ini['dbname'];

    // Then get the current document
    $objUrl = $DbBase.'/'.$Db.'/'.$imageId;
    $response = file_get_contents($objUrl);
    
    if ($response === FALSE) {
        return false;
    }
    
    $doc = json_decode($response, true);
    if (!$doc) {
        return false;
    }

    // Find and remove the tag if it belongs to this user
    $found = false;
    $doc['tags'] = array_filter($doc['tags'], function($tag) use ($tagName, $username, &$found) {
        if (strcasecmp($tag['Name'], $tagName) === 0 && 
            strcasecmp($tag['source'], 'user') === 0 && 
            isset($tag['username']) && 
            $tag['username'] === $username) {
            $found = true;
            return false;
        }
        return true;
    });

    if (!$found) {
        return false;
    }

    // Update the document in CouchDB 
    return updateDoc($objUrl, $doc);
}

?>
