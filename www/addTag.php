<?php
header('Content-Type: application/json');
include('photos_utils.php');

// Get parameters
$imageId = $_GET['imageid'] ?? null;
$tag = $_GET['tag'] ?? null;
$username = $_COOKIE['login_user'] ?? null;

// Validate parameters
if (!$imageId || !$tag || !$username) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

// Add tag to image
$success = addTagToImage($imageId, $tag, $username);

if ($success) {
    echo json_encode(['status' => 'success']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update image']);
}
?>
