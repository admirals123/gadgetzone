<?php
/**
 * Database connection + session bootstrap.
 * Every page includes this FIRST (before any HTML output).
 */

// Hide PHP errors from end users (production safety)
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper to reliably read environment variables across Vercel / PHP runtimes
if (!function_exists('gz_env')) {
    function gz_env($key, $default = '') {
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') return $_ENV[$key];
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') return $_SERVER[$key];
        $val = getenv($key);
        return ($val !== false && $val !== '') ? $val : $default;
    }
}

// 1. Check for Public Database URL first (e.g. Railway Public URL, PlanetScale, Supabase)
$publicUrl = gz_env('MYSQL_PUBLIC_URL') ?: (gz_env('DATABASE_PUBLIC_URL') ?: '');
$rawUrl    = gz_env('MYSQL_URL') ?: (gz_env('DATABASE_URL') ?: '');

$dbHost = '';
$dbPort = 3306;
$dbUser = 'root';
$dbPass = '';
$dbName = 'gadgetzone';

if (!empty($publicUrl)) {
    $parsed = parse_url($publicUrl);
    $dbHost = $parsed['host'] ?? '';
    $dbPort = isset($parsed['port']) ? (int)$parsed['port'] : 3306;
    $dbUser = $parsed['user'] ?? 'root';
    $dbPass = isset($parsed['pass']) ? urldecode($parsed['pass']) : '';
    $dbName = isset($parsed['path']) ? ltrim($parsed['path'], '/') : 'railway';
} elseif (!empty($rawUrl) && strpos($rawUrl, '.internal') === false) {
    $parsed = parse_url($rawUrl);
    $dbHost = $parsed['host'] ?? '';
    $dbPort = isset($parsed['port']) ? (int)$parsed['port'] : 3306;
    $dbUser = $parsed['user'] ?? 'root';
    $dbPass = isset($parsed['pass']) ? urldecode($parsed['pass']) : '';
    $dbName = isset($parsed['path']) ? ltrim($parsed['path'], '/') : 'railway';
}

// 2. If no valid external URL, read individual environment variables
if (empty($dbHost) || strpos($dbHost, '.internal') !== false) {
    $dbHost = gz_env('DB_HOST') ?: (gz_env('MYSQLHOST') ?: (gz_env('MYSQL_HOST') ?: 'localhost'));
    $dbUser = gz_env('DB_USER') ?: (gz_env('MYSQLUSER') ?: (gz_env('MYSQL_USER') ?: 'root'));
    $dbPass = gz_env('DB_PASS') !== '' ? gz_env('DB_PASS') : (gz_env('MYSQLPASSWORD') !== '' ? gz_env('MYSQLPASSWORD') : (gz_env('MYSQL_PASSWORD') !== '' ? gz_env('MYSQL_PASSWORD') : (gz_env('MYSQL_ROOT_PASSWORD') !== '' ? gz_env('MYSQL_ROOT_PASSWORD') : '')));
    $dbName = gz_env('DB_NAME') ?: (gz_env('MYSQLDATABASE') ?: (gz_env('MYSQL_DATABASE') ?: 'gadgetzone'));
    $dbPort = (int)(gz_env('DB_PORT') ?: (gz_env('MYSQLPORT') ?: (gz_env('MYSQL_PORT') ?: 3306)));
}

$dbSsl = gz_env('DB_SSL') === 'true' || gz_env('MYSQL_SSL') === 'true';

define('DB_HOST', $dbHost);
define('DB_USER', $dbUser);
define('DB_PASS', $dbPass);
define('DB_NAME', $dbName);
define('DB_PORT', $dbPort);

mysqli_report(MYSQLI_REPORT_OFF);

$conn = mysqli_init();
mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 10);

if ($dbSsl) {
    mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
    $connected = @mysqli_real_connect($conn, DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT, NULL, MYSQLI_CLIENT_SSL);
} else {
    $connected = @mysqli_real_connect($conn, DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    if (!$connected) {
        $connected = @mysqli_real_connect($conn, DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT, NULL, MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT);
    }
}

if (!$connected) {
    $isInternal = (strpos(DB_HOST, '.internal') !== false);
    die('<div style="font-family:system-ui, -apple-system, sans-serif; padding:40px; text-align:center; max-width:620px; margin:40px auto; background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,0.08);">'
      . '<h2 style="color:#ef4444; margin-bottom:12px;">⚠️ Database Connection Error</h2>'
      . '<p style="color:#475569; font-size:14px; line-height:1.6;">' . htmlspecialchars(mysqli_connect_error() ?: 'Unable to connect to database host: ' . DB_HOST) . '</p>'
      . ($isInternal ? '<div style="background:#fef2f2; border:1px solid #fca5a5; border-radius:8px; padding:12px; font-size:13px; color:#991b1b; margin:16px 0; text-align:left;">'
      . '<strong>Action Needed:</strong> Your <code>MYSQLHOST</code> is set to an internal address (<code>' . htmlspecialchars(DB_HOST) . '</code>). In Railway, go to <strong>MySQL &rarr; Settings &rarr; Networking &rarr; Generate Domain / TCP Proxy</strong> and update <code>MYSQLHOST</code> in Vercel to your public proxy domain (e.g. <code>xxxx.proxy.rlwy.net</code>).'
      . '</div>' : '')
      . '<div style="background:#f8fafc; border:1px solid #cbd5e1; border-radius:8px; padding:14px; font-size:12px; text-align:left; margin-top:16px; color:#334155; line-height:1.7;">'
      . '<strong>Active Values Tested:</strong><br>'
      . '• Host: <code>' . htmlspecialchars(DB_HOST) . '</code><br>'
      . '• Port: <code>' . DB_PORT . '</code><br>'
      . '• User: <code>' . htmlspecialchars(DB_USER) . '</code><br>'
      . '• Database: <code>' . htmlspecialchars(DB_NAME) . '</code><br>'
      . '</div>'
      . '</div>');
}

mysqli_set_charset($conn, 'utf8mb4');
