<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/currency.php';

$base = base_path();
$pageTitle = 'Order Confirmed';
$orderNumber = $_GET['order'] ?? '';

$order = null;
if ($orderNumber) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM orders WHERE order_number = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $orderNumber);
    mysqli_stmt_execute($stmt);
    $order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container section" style="text-align:center; max-width:560px;">
  <?php if ($order): ?>
    <div style="font-size:56px; margin-bottom:16px;">✅</div>
    <h1 style="margin-bottom:10px;">Order Confirmed!</h1>
    <p style="color:var(--text2); margin-bottom:28px;">Thank you for your order. A confirmation has been recorded.</p>
    <div style="background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); padding:24px; text-align:left; margin-bottom:28px;">
      <div class="summary-row"><span>Order Number</span><span style="color:var(--accent); font-weight:700;"><?= e($order['order_number']) ?></span></div>
      <div class="summary-row"><span>Total</span><span><?= formatPrice($order['total_amount']) ?></span></div>
      <div class="summary-row"><span>Payment Method</span><span style="text-transform:capitalize;"><?= e($order['payment_method']) ?></span></div>
      <div class="summary-row"><span>Status</span><span class="status-badge status-<?= e($order['status']) ?>"><?= e($order['status']) ?></span></div>
    </div>
    <div style="display:flex; gap:12px; justify-content:center;">
      <a href="<?= $base ?>/pages/shop.php" class="btn-outline">Continue Shopping</a>
      <a href="<?= $base ?>/pages/myaccount.php" class="btn-primary">View My Orders</a>
    </div>
  <?php else: ?>
    <div style="font-size:56px; margin-bottom:16px;">🔍</div>
    <h1>Order Not Found</h1>
    <p style="color:var(--text2); margin:12px 0 24px;">We couldn't find that order.</p>
    <a href="<?= $base ?>/index.php" class="btn-primary">Back to Home</a>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
