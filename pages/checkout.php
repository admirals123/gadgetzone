<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/currency.php';

$base = base_path();
$pageTitle = 'Checkout';

$items = getCartItemsWithDetails();
if (empty($items)) {
    header('Location: ' . $base . '/pages/cart.php');
    exit;
}

$subtotal = getCartTotal();
$freeShipThreshold = 5000;
$shipping = $subtotal >= $freeShipThreshold ? 0 : 150;
$total = $subtotal + $shipping;

$currentUser = isLoggedIn() ? getCurrentUser() : null;
$errors = [];

// Pre-fill values
$vals = [
    'first_name' => $currentUser['first_name'] ?? '',
    'last_name'  => $currentUser['last_name'] ?? '',
    'email'      => $currentUser['email'] ?? '',
    'phone'      => $currentUser['phone'] ?? '',
    'address'    => $currentUser['address'] ?? '',
    'city'       => $currentUser['city'] ?? '',
    'notes'      => '',
    'payment_method' => 'cod',
];

$stripeReady = getSetting('stripe_publishable_key') && strpos(getSetting('stripe_publishable_key'), 'REPLACE') === false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($vals as $k => $default) {
        if ($k !== 'payment_method') $vals[$k] = sanitize($_POST[$k] ?? '');
    }
    $vals['payment_method'] = $_POST['payment_method'] ?? 'cod';

    if ($vals['first_name'] === '') $errors[] = 'First name is required.';
    if ($vals['last_name'] === '') $errors[] = 'Last name is required.';
    if ($vals['email'] === '' || !filter_var($vals['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
    if ($vals['phone'] === '' || !preg_match('/^[0-9+\-\s()]{7,20}$/', $vals['phone'])) $errors[] = 'A valid phone number is required.';
    if ($vals['address'] === '') $errors[] = 'Street address is required.';
    if ($vals['city'] === '') $errors[] = 'City is required.';

    if (empty($errors)) {
        if ($vals['payment_method'] === 'card') {
            // Hand off to Stripe checkout session creation
            $_SESSION['pending_checkout'] = $vals;
            header('Location: ' . $base . '/pages/stripe_checkout.php');
            exit;
        }

        // COD / bKash / Nagad — create order immediately
        $orderNumber = generateOrderNumber();
        $shippingAddress = $vals['address'] . ', ' . $vals['city'] . ($vals['notes'] ? ' | Notes: ' . $vals['notes'] : '');
        $userId = $currentUser['id'] ?? null;

        $stmt = mysqli_prepare($conn, "INSERT INTO orders (user_id, order_number, total_amount, payment_method, shipping_address, status)
                                        VALUES (?, ?, ?, ?, ?, 'pending')");
        mysqli_stmt_bind_param($stmt, "isdss", $userId, $orderNumber, $total, $vals['payment_method'], $shippingAddress);
        mysqli_stmt_execute($stmt);
        $orderId = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        foreach ($items as $item) {
            $stmt = mysqli_prepare($conn, "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "iiid", $orderId, $item['id'], $item['qty'], $item['price']);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        $_SESSION['cart'] = [];
        header('Location: ' . $base . '/pages/order_success.php?order=' . urlencode($orderNumber));
        exit;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
  <div class="breadcrumb"><a href="<?= $base ?>/index.php">Home</a> &rsaquo; <a href="<?= $base ?>/pages/cart.php">Cart</a> &rsaquo; Checkout</div>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-error">
      <strong>Please fix the following:</strong>
      <ul style="margin:8px 0 0 18px;">
        <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="POST" class="checkout-layout">
    <div>
      <div class="checkout-section">
        <h3><span class="step-badge">1</span> Contact Information</h3>
        <div class="form-row">
          <div class="form-group"><label>First Name *</label><input type="text" name="first_name" value="<?= e($vals['first_name']) ?>" required></div>
          <div class="form-group"><label>Last Name *</label><input type="text" name="last_name" value="<?= e($vals['last_name']) ?>" required></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Email Address *</label><input type="email" name="email" value="<?= e($vals['email']) ?>" required></div>
          <div class="form-group"><label>Phone Number *</label><input type="text" name="phone" value="<?= e($vals['phone']) ?>" required></div>
        </div>
      </div>

      <div class="checkout-section">
        <h3><span class="step-badge">2</span> Shipping Address</h3>
        <div class="form-group"><label>Street Address *</label><input type="text" name="address" value="<?= e($vals['address']) ?>" required></div>
        <div class="form-row">
          <div class="form-group"><label>City *</label><input type="text" name="city" value="<?= e($vals['city']) ?>" required></div>
          <div class="form-group"><label>Country</label><input type="text" value="Bangladesh" readonly></div>
        </div>
        <div class="form-group"><label>Order Notes</label><textarea name="notes" rows="3"><?= e($vals['notes']) ?></textarea></div>
      </div>

      <div class="checkout-section">
        <h3><span class="step-badge">3</span> Payment Method</h3>
        <div class="payment-options">
          <label class="payment-option"><input type="radio" name="payment_method" value="cod" <?= $vals['payment_method']==='cod'?'checked':'' ?>> 💵 Cash on Delivery</label>
          <label class="payment-option"><input type="radio" name="payment_method" value="bkash" <?= $vals['payment_method']==='bkash'?'checked':'' ?>> bKash</label>
          <label class="payment-option"><input type="radio" name="payment_method" value="nagad" <?= $vals['payment_method']==='nagad'?'checked':'' ?>> Nagad</label>
          <?php if ($stripeReady): ?>
          <label class="payment-option"><input type="radio" name="payment_method" value="card" <?= $vals['payment_method']==='card'?'checked':'' ?>> 💳 Credit/Debit Card</label>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="order-summary">
      <h3 style="margin-bottom:16px;"><span class="step-badge">✓</span> Order Review</h3>
      <?php foreach ($items as $item): ?>
      <div class="review-item">
        <img src="<?= e($item['image_url']) ?>" alt="<?= e($item['name']) ?>">
        <div class="grow">
          <div style="font-weight:700;"><?= e($item['name']) ?></div>
          <div style="color:var(--text2);">Qty: <?= (int)$item['qty'] ?></div>
        </div>
        <div><?= formatPrice($item['line_subtotal']) ?></div>
      </div>
      <?php endforeach; ?>

      <div class="summary-row"><span>Subtotal</span><span><?= formatPrice($subtotal) ?></span></div>
      <div class="summary-row"><span>Shipping</span><span><?= $shipping === 0 ? 'Free' : formatPrice($shipping) ?></span></div>
      <div class="summary-row total"><span>Total</span><span><?= formatPrice($total) ?></span></div>

      <button type="submit" class="btn-primary btn-full btn-lg">Place Order – <?= formatPrice($total) ?></button>
      <div class="security-badge">🔒 Your information is secure &amp; encrypted</div>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
