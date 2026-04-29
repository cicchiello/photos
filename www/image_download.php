<?php
include('photos_utils.php');

if (!isset($_SESSION['login_user'])) {
    header('Location: ./login.php');
    exit;
}

$id  = $_GET['id'];
$ini = parse_ini_file("./config.ini");
$DbBase        = $ini['couchbase'];
$Db            = $ini['dbname'];
$imgAttachName = $ini['imgAttachName'];

$docUrl   = $DbBase.'/'.$Db.'/'.$id;
$doc      = json_decode(file_get_contents($docUrl), true);
$basename = basename($doc['paths'][0]);
$imageUrl = $docUrl.'/'.$imgAttachName;

$ext = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
$mimeTypes = [
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'gif'  => 'image/gif',
    'bmp'  => 'image/bmp',
    'webp' => 'image/webp',
];
$contentType = $mimeTypes[$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $contentType);
header('Content-Disposition: attachment; filename="' . $basename . '"');
header('Cache-Control: no-cache');

$ch = curl_init($imageUrl);
curl_setopt($ch, CURLOPT_TIMEOUT, 600);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_exec($ch);
curl_close($ch);
exit;
