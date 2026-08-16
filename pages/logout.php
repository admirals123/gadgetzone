<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$base = base_path();

$_SESSION = [];
session_destroy();

header('Location: ' . $base . '/index.php');
exit;
