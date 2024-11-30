<?php
header('Content-Type: application/json');
include('photos_utils.php');

// Get parameters
$imageId = $_GET['imageid'] ?? null;
$tagName = $_GET['tagname'] ?? null;
$username = $_COOKIE['login_user'] ?? null;

// Validate parameters
if (!$imageId || !$tagName || !$username) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

// Delete tag from image
$success = deleteTagFromImage($imageId, $tagName, $username);

if ($success) {
    echo json_encode(['status' => 'success']);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Failed to delete tag']);
}
?>
