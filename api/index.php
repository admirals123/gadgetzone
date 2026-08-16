<?php
/**
 * Vercel Serverless PHP Gateway & Router
 * Routes all requests to the appropriate PHP files.
 */

// Fix working directory to project root
$projectRoot = dirname(__DIR__);
chdir($projectRoot);

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
$cleanUri = ltrim($uri, '/');

// 1. Serve static files directly if they exist
$staticExtensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot', 'map', 'txt'];
$ext = strtolower(pathinfo($cleanUri, PATHINFO_EXTENSION));

if ($ext !== '' && in_array($ext, $staticExtensions)) {
    $staticFile = $projectRoot . '/' . $cleanUri;
    if (file_exists($staticFile) && is_file($staticFile)) {
        $mimeTypes = [
            'css'   => 'text/css',
            'js'    => 'application/javascript',
            'png'   => 'image/png',
            'jpg'   => 'image/jpeg',
            'jpeg'  => 'image/jpeg',
            'gif'   => 'image/gif',
            'webp'  => 'image/webp',
            'svg'   => 'image/svg+xml',
            'ico'   => 'image/x-icon',
            'woff'  => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf'   => 'font/ttf',
            'eot'   => 'application/vnd.ms-fontobject',
        ];
        if (isset($mimeTypes[$ext])) {
            header('Content-Type: ' . $mimeTypes[$ext]);
        }
        header('Cache-Control: public, max-age=31536000');
        readfile($staticFile);
        exit;
    }
}

// 2. Route to PHP files
if (empty($cleanUri) || $cleanUri === 'index.php') {
    require $projectRoot . '/index.php';
    exit;
}

// Direct PHP file match
$targetFile = $projectRoot . '/' . $cleanUri;
if (is_file($targetFile) && pathinfo($targetFile, PATHINFO_EXTENSION) === 'php') {
    require $targetFile;
    exit;
}

// Without .php extension
if (is_file($targetFile . '.php')) {
    require $targetFile . '.php';
    exit;
}

// 3. Default: serve home page
require $projectRoot . '/index.php';
