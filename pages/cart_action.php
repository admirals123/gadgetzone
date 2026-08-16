<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/currency.php';

@header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$productId = (int)($_POST['product_id'] ?? 0);
$qty = (int)($_POST['qty'] ?? 1);

if ($productId <= 0 || !in_array($action, ['add', 'buy_now', 'update', 'remove'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$base = base_path();

// Verify product exists
$stmt = mysqli_prepare($conn, "SELECT id, name, price FROM products WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $productId);
mysqli_stmt_execute($stmt);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

switch ($action) {
    case 'add':
        addToCart($productId, $qty);
        break;
    case 'buy_now':
        addToCart($productId, $qty);
        break;
    case 'update':
        updateCartQty($productId, $qty);
        break;
    case 'remove':
        removeFromCart($productId);
        break;
}

$cart = getCart();
$lineQty = $cart[$productId] ?? 0;
$lineSubtotal = $lineQty * $product['price'];

$response = [
    'success' => true,
    'product_name' => $product['name'],
    'cart_count' => getCartCount(),
    'cart_total' => (float)getCartTotal(),
    'formatted_total' => formatPrice(getCartTotal()),
    'formatted_subtotal' => formatPrice(getCartTotal()),
    'line_subtotal_formatted' => formatPrice($lineSubtotal),
];

if ($action === 'buy_now') {
    $response['redirect'] = $base . '/pages/checkout.php';
}

echo json_encode($response);
