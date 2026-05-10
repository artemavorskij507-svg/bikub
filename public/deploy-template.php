<?php
/**
 * Deployment script for delivery-market.blade.php
 * Access: POST http://136.119.84.22/deploy-template.php?key=YOUR_SECRET_KEY
 */

// Security key (change this!)
define('DEPLOY_KEY', 'bikube-delivery-2024-secret-key');

// Verify authentication
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method not allowed');
}

$key = $_GET['key'] ?? null;
if ($key !== DEPLOY_KEY) {
    http_response_code(403);
    die('Access denied');
}

// Template content - embedded base64
$template_b64 = <<<'TEMPLATE_END'
QGV4dGVuZHMoJ2xheW91dHMuYXBwJykKCkBzZWN0aW9uKCd0aXRsZScsICfQnNGD0YDQruKCrNCf0YDQviDQtNC80L/RjNC10LQg4oCi'
TEMPLATE_END;

// Create deployment directory if needed
$target_dir = dirname(__DIR__) . '/resources/views/public';
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0755, true);
}

$target_file = $target_dir . '/delivery-market.blade.php';

// Verify we have the full template
if (file_exists(__DIR__ . '/../../resources/views/public/delivery-market.blade.php')) {
    $content = file_get_contents(__DIR__ . '/../../resources/views/public/delivery-market.blade.php');
    if (file_put_contents($target_file, $content)) {
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Template deployed successfully',
            'file' => $target_file,
            'size' => filesize($target_file),
            'mtime' => filemtime($target_file)
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to write file']);
    }
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Source template not found']);
}
?>
