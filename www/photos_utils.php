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
   $ini = parse_ini_file("./config.ini");
   $DbBase = $ini['couchbase'];
   $Db = "photos";
   $DbViewBase = $DbBase.'/'.$Db.'/_design/photos/_view';

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


function renderImgArrayTable($items, $action)
{
   $ini = parse_ini_file("./config.ini");
   $urlBase = $ini['couchbase'].'/photos';
   
   $result = '';
   $row = 0;
   $col = 0;
   $numColumns = 4;
   foreach ($items as $item) {
      if ($col == 0) {
          if ($row == 0) {
             $result .= '<table style="width:100%">';
          }

          if ($row%2 == 0) 
             $result .= '<tr style="background-color:#b8d7b0">';
          else
             $result .= '<tr style="background-color:#e2f4dd">';
      }

      $id = $item['value']['_id'];
      $imgUrl = $urlBase.'/'.$id.'/thumbnail';
      
      $q = "'";

      $result .= '  <td style="text-align:left">';
      $result .= '        <img id="image" src="'.$imgUrl.'" alt="image"';
      $result .= '             class="album-img album-container Btn"';
      $result .= '             onclick="'.$action['onclick'].'('.$q.$id.$q.')"';
      $result .= '             style="vertical-align:middle;margin:0px 50px"';
      $result .= '             title="'.basename($item['value']['paths'][0]).'"/>';
      $result .= '  </td>';      
      
      $col += 1;
      if ($col == $numColumns) {
          $result .= '</tr>';

          $row += 1;
	  $col = 0;
      }
   }
   if (($row == 0) && ($col == 0)) {
      $result .= "<p>No Images.</p>";
   } else {
      $result .= '</table>';
   }
   return $result;
}

function renderImgInfo($id)
{
   $ini = parse_ini_file("./config.ini");
   $DbBase = $ini['couchbase'];
   $Db = "photos";
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
   $confidenceLimit = 70.0;
   foreach($detail['tags'] as $tag) {
      if ($tag['source'] == 'rekognition') {
        $confidence = $tag['Confidence'];
        if ($confidence > $confidenceLimit) {
	  $confidenceStr = sprintf("%2.1f", $confidence);
	  $strength = ($confidence-$confidenceLimit)/(100.0-$confidenceLimit);
	  $cstr = '#0a'.sprintf("%02x", 255*$strength).'40';
	  if ($strength < 0.5) {
            $color = 'white';
	  } else {
            $color = 'black';
	  }
          $result .= '    <button class="pillButton" style="background-color:'.$cstr;
          $result .= ';color:'.$color.'" title="'.$confidenceStr.'">';
	  $result .= $tag['Name'].'<small></small> <b>-</b></button>';
          $cnt += 1;
        }
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


function putDb($couchUrl,$row) {
  //$couchUrl = $WriteDb.'/'.$id;
  $ch = curl_init($couchUrl);
  $dataStr = json_encode($row);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
  curl_setopt($ch, CURLOPT_POSTFIELDS, $dataStr);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
       'Content-Type: application/json;charset=UTF-8',
       'Content-Length: '.strlen($dataStr))
  );
  $resultStr = curl_exec($ch);
  $result = json_decode($resultStr, true);
  return $result;
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

?>
