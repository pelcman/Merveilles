<?php
// Router for PHP built-in server (php -S)
// Apache/Nginx uses .htaccess instead

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Serve existing static files directly
$filePath = __DIR__ . $uri;
if ($uri !== '/' && file_exists($filePath) && is_file($filePath)) {
    return false;
}

// Route everything else through the front controller
require __DIR__ . '/index.php';
