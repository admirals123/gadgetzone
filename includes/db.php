<?php
/**
 * Database connection + session bootstrap.
 * Every page includes this FIRST (before any HTML output).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---- Connection settings (supports local XAMPP & Cloud Environment Variables) ----
$dbHost = getenv('DB_HOST') ?: (getenv('MYSQLHOST') ?: 'localhost');
$dbUser = getenv('DB_USER') ?: (getenv('MYSQLUSER') ?: 'root');
$dbPass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : (getenv('MYSQLPASSWORD') !== false ? getenv('MYSQLPASSWORD') : '');
$dbName = getenv('DB_NAME') ?: (getenv('MYSQLDATABASE') ?: 'gadgetzone');
$dbPort = getenv('DB_PORT') ? (int)getenv('DB_PORT') : (getenv('MYSQLPORT') ? (int)getenv('MYSQLPORT') : 3306);
$dbSsl  = getenv('DB_SSL') === 'true' || getenv('MYSQL_SSL') === 'true';

define('DB_HOST', $dbHost);
define('DB_USER', $dbUser);
define('DB_PASS', $dbPass);
define('DB_NAME', $dbName);
define('DB_PORT', $dbPort);

mysqli_report(MYSQLI_REPORT_OFF);

$conn = mysqli_init();

// Set 10-second connect timeout
mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 10);

if ($dbSsl) {
    mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
    $connected = @mysqli_real_connect($conn, DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT, NULL, MYSQLI_CLIENT_SSL);
} else {
    // Try standard connect first, if fails try with SSL flag for cloud providers
    $connected = @mysqli_real_connect($conn, DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    if (!$connected) {
        $connected = @mysqli_real_connect($conn, DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT, NULL, MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT);
    }
}

if (!$connected) {
    die('<div style="font-family:system-ui, -apple-system, sans-serif; padding:40px; text-align:center; max-width:600px; margin:40px auto; background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,0.08);">'
      . '<h2 style="color:#ef4444; margin-bottom:12px;">⚠️ Database Connection Error</h2>'
      . '<p style="color:#475569; font-size:14px; line-height:1.6;">' . htmlspecialchars(mysqli_connect_error() ?: 'Unable to connect to database host: ' . DB_HOST) . '</p>'
      . '<div style="background:#f8fafc; border:1px solid #cbd5e1; border-radius:8px; padding:14px; font-size:12px; text-align:left; margin-top:20px; color:#334155; line-height:1.7;">'
      . '<strong>Active Config:</strong><br>'
      . '• Host: <code>' . htmlspecialchars(DB_HOST) . '</code><br>'
      . '• Port: <code>' . DB_PORT . '</code><br>'
      . '• User: <code>' . htmlspecialchars(DB_USER) . '</code><br>'
      . '• Database: <code>' . htmlspecialchars(DB_NAME) . '</code><br><br>'
      . '<strong>To Fix on Vercel:</strong><br>'
      . '1. Go to <strong>Vercel &rarr; Project Settings &rarr; Environment Variables</strong>.<br>'
      . '2. Add <code>DB_HOST</code>, <code>DB_USER</code>, <code>DB_PASS</code>, <code>DB_NAME</code>, <code>DB_PORT</code>.<br>'
      . '3. Click <strong>Deployments &rarr; Redeploy</strong> for new variables to take effect.'
      . '</div>'
      . '</div>');
}

mysqli_set_charset($conn, 'utf8mb4');
