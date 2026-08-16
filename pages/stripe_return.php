<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/currency.php';

$base = base_path();
$sessionId = $_GET['session_id'] ?? '';
$orderNumber = $_GET['order'] ?? '';

if (!$sessionId || !$orderNumber) {
    header('Location: ' . $base . '/pages/checkout.php');
    exit;
}

$secretKey = getSetting('stripe_secret_key');

// ---- Verify the session with Stripe directly ----
$ch = curl_init('https://api.stripe.com/v1/checkout/sessions/' . urlencode($sessionId));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $secretKey]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$session = json_decode($response, true);
$paid = isset($session['payment_status']) && $session['payment_status'] === 'paid';

$stmt = mysqli_prepare($conn, "SELECT id FROM orders WHERE order_number = ? AND stripe_session_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "ss", $orderNumber, $sessionId);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$order) {
    header('Location: ' . $base . '/pages/checkout.php');
    exit;
}

if ($paid) {
    $stmt = mysqli_prepare($conn, "UPDATE orders SET payment_status = 'paid', status = 'processing' WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $order['id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $_SESSION['cart'] = [];
    header('Location: ' . $base . '/pages/order_success.php?order=' . urlencode($orderNumber));
    exit;
}

// Payment not completed
mysqli_query($conn, "UPDATE orders SET status = 'cancelled' WHERE id = " . (int)$order['id']);
$_SESSION['checkout_error'] = 'Payment not completed. Please try again or choose another payment method.';
header('Location: ' . $base . '/pages/checkout.php');
exit;
