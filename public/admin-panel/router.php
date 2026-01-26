<?php
// Simple router for PHP built-in server
// This ensures static files are served correctly

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $path;

// If it's a directory, try index.html
if (is_dir($file)) {
    $file = rtrim($file, '/') . '/index.html';
}

// If file exists and is not a PHP file, serve it
if (file_exists($file) && !is_dir($file) && pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
    return false; // Let PHP built-in server handle it
}

// If it's a PHP file, execute it
if (file_exists($file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
    require $file;
    return true;
}

// 404
http_response_code(404);
echo '404 Not Found';
return true;
