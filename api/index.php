<?php
/**
 * Vercel Serverless PHP Gateway & Router
 */

// Fix working directory to project root
chdir(dirname(__DIR__));

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));

// 1. Static file passthrough for assets and uploads
if ($uri !== '/' && preg_match('#^/(assets|admin/uploads)/#', $uri)) {
    return false;
}

// 2. Direct script routing
$cleanUri = ltrim($uri, '/');
if (empty($cleanUri) || $cleanUri === 'index.php') {
    require __DIR__ . '/../index.php';
    exit;
}

$targetFile = dirname(__DIR__) . '/' . $cleanUri;

if (file_exists($targetFile) && is_file($targetFile) && pathinfo($targetFile, PATHINFO_EXTENSION) === 'php') {
    require $targetFile;
    exit;
}

// 3. Fallback: Check in pages/ or admin/
if (file_exists(dirname(__DIR__) . '/pages/' . $cleanUri . '.php')) {
    require dirname(__DIR__) . '/pages/' . $cleanUri . '.php';
    exit;
}

if (file_exists(dirname(__DIR__) . '/admin/' . $cleanUri . '.php')) {
    require dirname(__DIR__) . '/admin/' . $cleanUri . '.php';
    exit;
}

// 4. Default fallback to home index.php
require __DIR__ . '/../index.php';
