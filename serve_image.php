<?php
require_once 'functions.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    header('HTTP/1.0 400 Bad Request');
    echo 'Token is required.';
    exit;
}

$filePath = validateToken($token);

if ($filePath && file_exists($filePath)) {
    // Check if file is within allowed directory (extra safety)
    if (strpos(realpath($filePath), realpath(IMG_DIR)) !== 0) {
        header('HTTP/1.0 403 Forbidden');
        echo 'Access denied.';
        exit;
    }

    $filename = basename($filePath);
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    $mimeTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp'
    ];
    
    $contentType = $mimeTypes[$ext] ?? 'application/octet-stream';
    
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $contentType);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filePath));
    
    readfile($filePath);
    exit;
} else {
    header('HTTP/1.0 404 Not Found');
    echo 'Invalid or expired token.';
    exit;
}
?>
