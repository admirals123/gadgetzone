<?php
/**
 * Image Upload Handler
 * Tries Cloudinary (if configured), falls back to local disk (XAMPP).
 * Returns JSON: { success, url, error }
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['image']['name'])) {
    echo json_encode(['success' => false, 'error' => 'No file received.']);
    exit;
}

$file    = $_FILES['image'];
$ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

if (!in_array($ext, $allowed, true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type. Use JPG, PNG, WEBP or GIF.']);
    exit;
}

if ($file['size'] > 10 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => 'File too large. Maximum 10MB.']);
    exit;
}

// ── Option 1: Cloudinary (if env vars are configured) ──────────────────────
$cloudName = gz_env('CLOUDINARY_CLOUD_NAME');
$apiKey    = gz_env('CLOUDINARY_API_KEY');
$apiSecret = gz_env('CLOUDINARY_API_SECRET');

if ($cloudName && $apiKey && $apiSecret) {
    $timestamp = time();
    $folder    = 'gadgetzone/products';
    $paramsToSign = "folder={$folder}&timestamp={$timestamp}";
    $signature = sha1($paramsToSign . $apiSecret);

    $ch = curl_init("https://api.cloudinary.com/v1_1/{$cloudName}/image/upload");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => [
            'file'      => new CURLFile($file['tmp_name'], $file['type'], $file['name']),
            'api_key'   => $apiKey,
            'timestamp' => $timestamp,
            'folder'    => $folder,
            'signature' => $signature,
        ],
    ]);
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        echo json_encode(['success' => false, 'error' => 'Upload failed: ' . $curlError]);
        exit;
    }

    $data = json_decode($response, true);
    if (!empty($data['secure_url'])) {
        echo json_encode(['success' => true, 'url' => $data['secure_url']]);
        exit;
    }

    $cloudError = $data['error']['message'] ?? 'Unknown Cloudinary error';
    echo json_encode(['success' => false, 'error' => 'Cloudinary: ' . $cloudError]);
    exit;
}

// ── Option 2: Local disk (XAMPP / any writable server) ─────────────────────
$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if (!is_writable($uploadDir)) {
    echo json_encode(['success' => false, 'error' => 'Upload folder is not writable. On Vercel, set Cloudinary env vars.']);
    exit;
}

$filename = 'prod_' . uniqid() . '.' . $ext;
$dest     = $uploadDir . $filename;

if (move_uploaded_file($file['tmp_name'], $dest)) {
    $base = base_path();
    $url  = $base . '/admin/uploads/' . $filename;
    echo json_encode(['success' => true, 'url' => $url]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to save file. On Vercel, set Cloudinary env vars.']);
}
