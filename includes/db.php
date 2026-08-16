<?php
/**
 * Database connection + session bootstrap.
 * Every page includes this FIRST (before any HTML output).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---- Connection settings ----
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'gadgetzone');

mysqli_report(MYSQLI_REPORT_OFF); // we handle errors manually below

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die('Database connection failed: ' . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');
