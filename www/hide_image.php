<?php
header('Content-Type: application/json');
include('photos_utils.php');

if (!isset($_SESSION['login_user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

$imageId = $_GET['imageid'] ?? null;

if (!$imageId || !isset($_GET['csrf']) || !verifyCsrfToken($_GET['csrf'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

$ini = parse_ini_file("./config.ini");
$DbBase = $ini['couchbase'];
$Db = $ini['dbname'];

$objUrl = $DbBase.'/'.$Db.'/'.$imageId;
$doc = json_decode(file_get_contents($objUrl), true);

if (!$doc) {
    http_response_code(404);
    echo json_encode(['error' => 'Document not found']);
    exit;
}

$doc['hidden'] = true;

$success = updateDoc($objUrl.'?rev='.$doc['_rev'], $doc);

if ($success) {
    echo json_encode(['status' => 'success']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to hide image']);
}
?>
