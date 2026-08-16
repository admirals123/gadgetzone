<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/currency.php';

$base = base_path();

// ---- Server-side remove fallback: handled BEFORE any HTML output ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_id'])) {
    removeFromCart((int)$_POST['remove_id']);
    header('Location: ' . $base . '/pages/cart.php');
    exit;
}

$pageTitle = 'Shopping Cart';
$items = getCartItemsWithDetails();
$subtotal = getCartTotal();
$freeShipThreshold = 5000;
$shipping = $subtotal >= $freeShipThreshold ? 0 : 150;
$total = $subtotal + $shipping;

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
  <div class="breadcrumb"><a href="<?= $base ?>/index.php">Home</a> &rsaquo; Shopping Cart</div>
  <h1 style="margin-bottom:24px;">Shopping Cart <span style="color:var(--text2); font-weight:400; font-size:16px;">(<?= count($items) ?> items)</span></h1>

  <?php if (empty($items)): ?>
    <div class="empty-state">
      <div class="icon">🛍️</div>
      <h3>Your cart is empty</h3>
      <p style="margin:10px 0 20px;">Looks like you haven't added anything yet.</p>
      <a href="<?= $base ?>/pages/shop.php" class="btn-primary">Start Shopping</a>
    </div>
  <?php else: ?>
  <div class="cart-layout">
    <div>
      <table class="cart-table">
        <thead>
          <tr><th>Product</th><th>Price</th><th>Quantity</th><th>Subtotal</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($items as $item): ?>
          <tr>
            <td>
              <div class="cart-row-product">
                <img src="<?= e($item['image_url']) ?>" alt="<?= e($item['name']) ?>">
                <div>
                  <div style="font-weight:700; font-size:14px;"><?= e($item['name']) ?></div>
                  <div style="color:var(--text2); font-size:12px;"><?= e($item['category_name'] ?? '') ?></div>
                </div>
              </div>
            </td>
            <td style="color:var(--accent); font-weight:700;"><?= formatPrice($item['price']) ?></td>
            <td>
              <div class="qty-controls" data-id="<?= (int)$item['id'] ?>">
                <button type="button" class="qty-minus">−</button>
                <input type="number" class="qty-input" value="<?= (int)$item['qty'] ?>" min="1" max="99">
                <button type="button" class="qty-plus">+</button>
              </div>
            </td>
            <td class="line-subtotal"><?= formatPrice($item['line_subtotal']) ?></td>
            <td>
              <!-- Server-side fallback form; intercepted by JS for AJAX removal -->
              <form method="POST" class="remove-form" data-id="<?= (int)$item['id'] ?>">
                <input type="hidden" name="remove_id" value="<?= (int)$item['id'] ?>">
                <button type="submit" aria-label="Remove">✕</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <a href="<?= $base ?>/pages/shop.php" class="btn-outline" style="margin-top:20px; display:inline-block;">← Continue Shopping</a>
    </div>

    <div class="order-summary">
      <h3 style="margin-bottom:16px;">Order Summary</h3>
      
      <!-- Free Shipping Meter -->
      <?php 
        $progressPct = min(100, round(($subtotal / $freeShipThreshold) * 100));
        $neededForFree = max(0, $freeShipThreshold - $subtotal);
      ?>
      <div class="free-shipping-meter" id="freeShippingMeter" data-threshold="<?= $freeShipThreshold ?>">
        <div class="meter-text" id="meterText">
          <?php if ($neededForFree > 0): ?>
            🚚 Add <strong style="color:var(--accent);"><?= formatPrice($neededForFree) ?></strong> more for <strong>FREE Delivery</strong>!
          <?php else: ?>
            🎉 <strong style="color:#4ade80;">You've unlocked FREE Express Delivery!</strong>
          <?php endif; ?>
        </div>
        <div class="meter-bar-track">
          <div class="meter-bar-fill" id="meterFill" style="width: <?= $progressPct ?>%;"></div>
        </div>
      </div>

      <div class="summary-row"><span>Subtotal</span><span class="js-cart-subtotal"><?= formatPrice($subtotal) ?></span></div>
      <div class="summary-row"><span>Estimated Shipping</span><span class="js-shipping-val"><?= $shipping === 0 ? '<strong style="color:#4ade80;">FREE</strong>' : formatPrice($shipping) ?></span></div>
      <div class="summary-row total"><span>Total Amount</span><span class="js-cart-total"><?= formatPrice($total) ?></span></div>

      <div class="delivery-estimate-card">
        <span>📦</span>
        <div>
          <strong>Estimated Delivery</strong>
          <small>Dispatched within 24 hours (2-3 business days)</small>
        </div>
      </div>

      <div class="coupon-row">
        <input type="text" placeholder="Promo / Coupon code">
        <button class="btn-outline btn-sm">Apply</button>
      </div>

      <a href="<?= $base ?>/pages/checkout.php" class="btn-primary btn-full btn-lg">Proceed to Checkout →</a>

      <div class="payment-icons">
        <span class="pay-pill">💳 Visa</span>
        <span class="pay-pill">💳 Mastercard</span>
        <span class="pay-pill">📱 bKash</span>
        <span class="pay-pill">📱 Nagad</span>
        <span class="pay-pill">🚚 COD</span>
      </div>
      <div class="security-badge">🔒 256-Bit SSL Encrypted &amp; Protected Checkout</div>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
