<?php
header('Content-Type: application/json');
include('photos_utils.php');

// Get parameters
$imageId = $_GET['imageid'] ?? null;
$tagName = $_GET['tagname'] ?? null;
$username = $_SESSION['login_user'] ?? null;

// Validate parameters
if (!$imageId || !$tagName || !$username || !isset($_GET['csrf']) || !verifyCsrfToken($_GET['csrf'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

// Delete tag from image
$result = deleteTagFromImage($imageId, $tagName, $username);

if ($result === true) {
    echo json_encode(['status' => 'success']);
} elseif (is_string($result)) {
    http_response_code(403);
    echo json_encode(['error' => 'Tag was added by ' . $result, 'creator' => $result]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Failed to delete tag']);
}
?>
