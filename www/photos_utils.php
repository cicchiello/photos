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


function getMenuCnts()
{
//   $ini = parse_ini_file("./config.ini");
//   $DbBase = $ini['couchbase'];
//   $Db = "photos";
//   $DbViewBase = $DbBase.'/'.$Db.'/_design/photos/_view';

   $numRecordings = 0;
   $numCapturing = 0;
   $numChannels = 0;
   $numScheduled = 0;

   return array(
      "numChannels" => $numChannels,
      "numRecordings" => $numRecordings,
      "numScheduled" => $numScheduled,
      "numCapturing" => $numCapturing
      );
}


function renderImgArrayTable($firstrow, $DbBase, $items, $onImgAction, $onCheckAction)
{
    $q = "'";

    $result = '';
    $rendering = false;
    $row = 0;
    $col = 0;
    $ColsPerRow = 5;
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
   $result .= '	 <div>';
   $result .= '       <img class="image" id="image" src="'.$imgUrl.'"';
   $result .= '            align="center" title="Image"/>';
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
        $result .= '    <button class="pillButton" style="background-color:#6898FF;color:black;cursor:pointer" ';
        $result .= 'title="Added by '.$username.'" ';
        $result .= 'onclick="deleteTag('.$q.$tag['Name'].$q.','.$q.$id.$q.');">';
        $result .= $tag['Name'].'</button>';
        $cnt += 1;
      }
   }
   $result .= '	 </div>';
   $result .= '<p>';
   
   $result .= '<table>';
   $result .= '	 <tr>';
   $result .= '	   <td>File:</td>';
   $result .= '	   <td colspan="2" style="color:blue" class="w3-right">'.$path.'</td>';
   $result .= '	 </tr>';
   $result .= '	 <tr>';
   $result .= '	   <td>File size:</td>';
   $result .= '	   <td colspan="2" style="color:blue" class="w3-right">'.readableSize($size).'</td>';
   $result .= '	 </tr>';
   $result .= '  <tr>';
   $result .= '    <td>Db Id:</td>';
   $result .= '	   <td colspan="2" style="color:blue; font-size:80%" class="w3-right">'.$id.'</td>';
   $result .= '  </tr>';
   $result .= '</table>';
   
   return $result;
}


function renderSchdInfo($id)
{
   $ini = parse_ini_file("./config.ini");
   $DbBase = $ini['couchbase'];
   $Db = "photos";
   $detailUrl = $DbBase.'/'.$Db.'/'.$id;

   $detail = json_decode(file_get_contents($detailUrl), true);
   $channel = $detail['channel'];
   $description = $detail['description'];
   $result = '';
   
   $recordStart = $detail['record-start'];
   $recordEnd = $detail['record-end'];
   $startTimeStr = date("h:i a",$recordStart);
   $scheduledDuration = deltaTimeStr($recordEnd-$recordStart);

   $url = "http://ipv4-api.hdhomerun.com/discover";
   $devices = json_decode(file_get_contents($url), true);
   foreach ($devices as $device) {
      $deviceUrl = $device['DiscoverURL'];
      $device_detail = json_decode(file_get_contents($deviceUrl), true);
      $deviceId = $device_detail['DeviceID'];
      $lineupJsonUrl = $device_detail['LineupURL'];
      $lineup = json_decode(file_get_contents($lineupJsonUrl), true);
   }

   $channelName = 'unknown';
   foreach ($lineup as $c) {
      $num = $c['GuideNumber'];
      $name = $c['GuideName'];
      if ($num === $channel) $channelName = $name;
   }

   $result .= '<table>';
   $result .= '  <tr>';
   $result .= '    <td>Channel:</td>';
   $result .= '    <td>';
   $result .= '       <b style="color:blue" class="w3-right">'.$channel.'</b>';
   $result .= '    </td>';
   $result .= '    <td>';
   $result .= '       <b style="color:blue" class="w3-right">'.$channelName.'</b>';
   $result .= '	   </td>';
   $result .= '	 </tr>';
   
   $result .= '	 <tr>';
   $result .= '	   <td>Description:</td>';
   $result .= '	   <td colspan="2">';
   $result .= '	      <b style="color:blue" class="w3-right">'.$description.'</b>';
   $result .= '	   </td>';
   $result .= '	 </tr>';

   $result .= '	 <tr>';
   $result .= '	   <td>'.'Start Time'.':</td>';
   $result .= '	   <td colspan="2">';
   $result .= '	      <b style="color:blue" class="w3-right">'.$startTimeStr.'</b>';
   $result .= '	   </td>';
   $result .= '	 </tr>';

   $result .= '	 <tr>';
   $result .= '	   <td>'.'Duration'.':</td>';
   $result .= '	   <td colspan="2">';
   $result .= '	      <b style="color:blue" class="w3-right">'.$scheduledDuration.'</b>';
   $result .= '	   </td>';
   $result .= '	 </tr>';

   $result .= '</table>';

   $result .= '<p> </p>';
   $result .= '<p> </p>';
   
   return $result;
}


function renderProfileArea($userName)
{
   $result = '  <div id="profileArea" class="row box col-sm-4 w3-panel w3-card w3-white w3-round-large w3-display-topright">';
   $result .= '    Hi, '.$userName.'!&nbsp;<b>';
   $result .= '    <a href="profile.php" id="ProfileBtn" class="Btn" title="My Profile">';
   $result .= '      <img class="profileIcon" src="img/profile_626.png">';
   $result .= '      Profile';
   $result .= '    </a>&nbsp;';
   $result .= '    <a href="logout_action.php" id="LogoutBtn" class="Btn" title="Logout">';
   $result .= '      <img class="profileIcon" src="img/logout_512.png">';
   $result .= '      Logout';
   $result .= '    </a>';
   $result .= ' </div>';
   
   return $result;
}

function renderMainMenu($userName)
{
   $d = getMenuCnts();
   $enabled = array(
      'live' => true,
      'library' => true,
      'recording' => true,
      'scheduled' => true
   );
   $refs = array(
      'live' => './live.php',
      'library' => './recordings.php',
      'recording' => './recordings.php',
      'scheduled' => './schedules.php'
   );

   $result = '';
   $result .= renderProfileArea($userName);
   $result .= ' <div id="menuArea">';
   $result .= '   <input onclick="menuAction()" type="image" src="img/showmenu.png"';
   $result .= '          width="48" height="48" title="Menu" class="Btn">';
   $result .= '   <div id="menuItems" class="w3-hide">';
   $result .= renderMenuItems($enabled,$refs);
   $result .= '   </div>';
   $result .= ' </div>';
   
   return $result;
}


function renderMenuItems($enabled,$refs)
{
   $d = getMenuCnts();

   $imgs = array(
      'live' => 'img/livetv2.png',
      'library' => 'img/video.png',
      'recording' => 'img/recording.png',
      'scheduled' => 'img/schedule.png'
   );
   $imgs_gray = array(
      'live' => 'img/livetv2-gray.png',
      'library' => 'img/video-gray.png',
      'recording' => 'img/recording-gray.png',
      'scheduled' => 'img/schedule-gray.png'
   );
   $lbl_singular = array(
      'live' => 'Channel',
      'library' => 'Item',
      'recording' => 'Recording',
      'scheduled' => 'Scheduled'
   );
   $lbl = array(
      'live' => 'Channels',
      'library' => 'Items',
      'recording' => 'Recording',
      'scheduled' => 'Scheduled'
   );
   $lbl_val = array(
      'live' => $d['numChannels'],
      'library' => $d['numRecordings'],
      'recording' => $d['numCapturing'],
      'scheduled' => $d['numScheduled']
   );
   
   $result = '';

   $cnt = 1;
   foreach(array_keys($enabled) as $key) {
      if ($refs[$key]) {
         $result .= '<a class="_URL" href="'.$refs[$key].'">';
      }
      $result .= '     <div class="menuLbl Btn" title="'.$key.'">';
      if ($enabled[$key]) {
         $result .= '<img id="menu'.$cnt.'" src="'.$imgs[$key].'" width="64" height="64" class="Btn">';
	 if ($lbl_val[$key] == 1) {
	    $result .= '<p><b>'.$lbl_val[$key].' '.$lbl_singular[$key].'</b></p>';
	 } else {
	    $result .= '<p><b>'.$lbl_val[$key].' '.$lbl[$key].'</b></p>';
	 }
      } else {
         $result .= '<img id="menu'.$cnt.'" src="'.$imgs_gray[$key].'" width="64" height="64">';
         $result .= '<span style="color:#7a9538"><p><b>'.$lbl_val[$key].' '.$lbl[$key].'</b></p></span>';
      }
      $cnt += 1;
      $result .= '     </div>';
      if ($refs[$key]) {
         $result .= '</a>';
      }
   }
   
   $result .= '   </div>';

   return $result;
}


function renderMenu($enabled, $userName)
{
   $refs = array(
      'live' => './live.php',
      'library' => './recordings.php',
      'recording' => './recordings.php',
      'scheduled' => './schedules.php'
   );
   
   $result = '';
   $result .= renderProfileArea($userName);
   $result .= ' <div id="menuArea">';
   $result .= '   <a style="border:5px" class="_URL" href="./index.php">';
   $result .= '     <img src="img/home.png" width="48" height="48" title="Home" style="padding:5px;" class="Btn">';
   $result .= '   </a>';
   $result .= '   <div id="menuItems" class="w3-show">';
   $result .= renderMenuItems($enabled,$refs);
   $result .= '   </div>';
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
