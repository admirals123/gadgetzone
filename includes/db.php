<?php
/**
 * Database connection + session bootstrap.
 * Every page includes this FIRST (before any HTML output).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---- Connection settings (supports local XAMPP & Cloud Environment Variables) ----
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_NAME', getenv('DB_NAME') ?: 'gadgetzone');
define('DB_PORT', getenv('DB_PORT') ? (int)getenv('DB_PORT') : 3306);

mysqli_report(MYSQLI_REPORT_OFF); // we handle errors manually below

$conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if (!$conn) {
    die('Database connection failed: ' . mysqli_connect_error() . '<br><small>If deploying to Vercel/Cloud, please set DB_HOST, DB_USER, DB_PASS, DB_NAME environment variables.</small>');
}

mysqli_set_charset($conn, 'utf8mb4');
