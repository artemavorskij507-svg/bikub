<?php
/**
 * Simple deployment endpoint - POST multipart file
 */

// Allow any origin for deployment  
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'POST only']));
}

if (!isset($_FILES['template'])) {
    http_response_code(400);
    die(json_encode(['error' => 'No file uploaded']));
}

$file = $_FILES['template'];
$target = dirname(dirname(__FILE__)) . '/resources/views/public/delivery-market.blade.php';
$dir = dirname($target);

if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

if (move_uploaded_file($file['tmp_name'], $target)) {
    chmod($target, 0644);
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Template deployed',
        'file' => $target,
        'size' => filesize($target)
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Upload failed']);
}
?>
