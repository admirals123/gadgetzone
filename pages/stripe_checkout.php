<?php
/**
 * Creates a Stripe Checkout session and redirects the customer to Stripe's
 * hosted payment page. Requires the Stripe PHP SDK (composer require stripe/stripe-php)
 * OR a direct cURL call to the Stripe API (used below to avoid a hard dependency).
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/currency.php';

$base = base_path();

$items = getCartItemsWithDetails();
$vals = $_SESSION['pending_checkout'] ?? null;

if (empty($items) || !$vals) {
    header('Location: ' . $base . '/pages/checkout.php');
    exit;
}

$secretKey = getSetting('stripe_secret_key');
if (!$secretKey || strpos($secretKey, 'REPLACE') !== false) {
    $_SESSION['checkout_error'] = 'Stripe is not configured. Please choose another payment method.';
    header('Location: ' . $base . '/pages/checkout.php');
    exit;
}

$subtotal = getCartTotal();
$freeShipThreshold = 5000;
$shipping = $subtotal >= $freeShipThreshold ? 0 : 150;
$total = $subtotal + $shipping;

// Pre-create a pending order so we can attach the Stripe session id to it
$orderNumber = generateOrderNumber();
$shippingAddress = $vals['address'] . ', ' . $vals['city'] . ($vals['notes'] ? ' | Notes: ' . $vals['notes'] : '');
$userId = isLoggedIn() ? $_SESSION['user_id'] : null;

$stmt = mysqli_prepare($conn, "INSERT INTO orders (user_id, order_number, total_amount, payment_method, shipping_address, status, payment_status)
                                VALUES (?, ?, ?, 'card', ?, 'pending', 'unpaid')");
mysqli_stmt_bind_param($stmt, "isds", $userId, $orderNumber, $total, $shippingAddress);
mysqli_stmt_execute($stmt);
$orderId = mysqli_insert_id($conn);
mysqli_stmt_close($stmt);

foreach ($items as $item) {
    $stmt = mysqli_prepare($conn, "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iiid", $orderId, $item['id'], $item['qty'], $item['price']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// ---- Build Stripe Checkout Session via direct API call (no SDK dependency) ----
$currencyCode = getStripeCurrencyCode();
$successUrl = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $base . '/pages/stripe_return.php?session_id={CHECKOUT_SESSION_ID}&order=' . urlencode($orderNumber);
$cancelUrl  = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $base . '/pages/checkout.php';

$postFields = [
    'mode' => 'payment',
    'success_url' => $successUrl,
    'cancel_url' => $cancelUrl,
    'customer_email' => $vals['email'],
];

$i = 0;
foreach ($items as $item) {
    $postFields["line_items[$i][price_data][currency]"] = $currencyCode;
    $postFields["line_items[$i][price_data][product_data][name]"] = $item['name'];
    $postFields["line_items[$i][price_data][unit_amount]"] = getStripeAmount($item['price']);
    $postFields["line_items[$i][quantity]"] = $item['qty'];
    $i++;
}
if ($shipping > 0) {
    $postFields["line_items[$i][price_data][currency]"] = $currencyCode;
    $postFields["line_items[$i][price_data][product_data][name]"] = 'Shipping';
    $postFields["line_items[$i][price_data][unit_amount]"] = getStripeAmount($shipping);
    $postFields["line_items[$i][quantity]"] = 1;
}

$ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $secretKey]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

$session = json_decode($response, true);

if (!empty($session['id']) && !empty($session['url'])) {
    $stmt = mysqli_prepare($conn, "UPDATE orders SET stripe_session_id = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $session['id'], $orderId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    unset($_SESSION['pending_checkout']);
    header('Location: ' . $session['url']);
    exit;
}

// Session creation failed — clean up the pending order and bounce back
mysqli_query($conn, "DELETE FROM orders WHERE id = " . (int)$orderId);
$_SESSION['checkout_error'] = 'Could not start Stripe payment' . ($curlError ? " ($curlError)" : '') . '. Please try again or choose another payment method.';
header('Location: ' . $base . '/pages/checkout.php');
exit;
