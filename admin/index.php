<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/currency.php';
requireAdmin();

$pageTitle = 'Dashboard';
$base = base_path();

// Handle quick order status update from dashboard modal if submitted
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'quick_update_order') {
    $id = (int)$_POST['id'];
    $status = $_POST['status'] ?? 'pending';
    $payStatus = $_POST['payment_status'] ?? 'unpaid';

    if (in_array($status, ['pending','processing','shipped','delivered','cancelled'], true) &&
        in_array($payStatus, ['unpaid','paid','refunded'], true)) {
        $stmt = mysqli_prepare($conn, "UPDATE orders SET status = ?, payment_status = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ssi", $status, $payStatus, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $message = 'Order status updated successfully.';
    }
}

// Analytics queries
$stats = mysqli_fetch_assoc(mysqli_query($conn, "SELECT
    (SELECT COUNT(*) FROM orders) AS total_orders,
    (SELECT COUNT(*) FROM orders WHERE status = 'pending') AS pending_orders,
    (SELECT COUNT(*) FROM orders WHERE status = 'processing') AS processing_orders,
    (SELECT COUNT(*) FROM orders WHERE status = 'shipped') AS shipped_orders,
    (SELECT COUNT(*) FROM orders WHERE status = 'delivered') AS delivered_orders,
    (SELECT COUNT(*) FROM users WHERE role = 'member') AS total_customers,
    (SELECT COUNT(*) FROM products) AS total_products,
    (SELECT COUNT(*) FROM products WHERE stock < 5) AS low_stock_count,
    (SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE payment_status='paid' OR status='delivered') AS total_revenue,
    (SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE DATE(created_at) = CURDATE()) AS today_revenue,
    (SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()) AS today_orders
"));

$recentOrders = mysqli_fetch_all(mysqli_query($conn, "SELECT o.*, u.first_name, u.last_name, u.email, u.phone,
    (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
    FROM orders o
    LEFT JOIN users u ON u.id = o.user_id ORDER BY o.created_at DESC LIMIT 8"), MYSQLI_ASSOC);

// Pre-fetch items for modal
$itemsByOrder = [];
if (!empty($recentOrders)) {
    $orderIds = array_map(fn($o) => (int)$o['id'], $recentOrders);
    $idsList = implode(',', $orderIds);
    $itemRows = mysqli_fetch_all(mysqli_query($conn, "SELECT oi.*, p.name AS product_name, p.image_url
        FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id
        WHERE oi.order_id IN ($idsList)"), MYSQLI_ASSOC);
    foreach ($itemRows as $row) {
        $itemsByOrder[$row['order_id']][] = $row;
    }
}

$lowStockThreshold = 5;
$lowStockProducts = mysqli_fetch_all(mysqli_query($conn, "SELECT p.*, c.name AS category_name FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE p.stock < $lowStockThreshold ORDER BY p.stock ASC LIMIT 6"), MYSQLI_ASSOC);

require_once __DIR__ . '/layout.php';
?>

<?php if ($message): ?>
  <div class="alert alert-success">✅ <?= e($message) ?></div>
<?php endif; ?>

<!-- Quick Actions Hub -->
<div class="admin-quick-actions">
  <div class="quick-actions-left">
    <span class="quick-actions-title">⚡ Quick Actions:</span>
    <a href="<?= $base ?>/admin/products.php?action=new" class="btn-primary btn-sm">+ Add New Product</a>
    <a href="<?= $base ?>/admin/orders.php?status=pending" class="btn-outline btn-sm">
      ⏳ Pending Orders <?= (int)$stats['pending_orders'] > 0 ? '(' . (int)$stats['pending_orders'] . ')' : '' ?>
    </a>
    <a href="<?= $base ?>/admin/products.php?filter=low_stock" class="btn-outline btn-sm">
      ⚠️ Low Stock <?= (int)$stats['low_stock_count'] > 0 ? '(' . (int)$stats['low_stock_count'] . ')' : '' ?>
    </a>
    <a href="<?= $base ?>/admin/settings.php" class="btn-outline btn-sm">⚙️ Currency & Settings</a>
  </div>
  <div class="quick-actions-right">
    <span class="live-clock" id="adminClock">🕒 Live Status</span>
  </div>
</div>

<!-- Key Performance Indicators -->
<div class="admin-stat-grid">
  <div class="admin-stat-card revenue">
    <div class="admin-stat-header">
      <div class="admin-stat-icon">💰</div>
      <span class="stat-badge-today">Today: <?= formatPrice($stats['today_revenue']) ?></span>
    </div>
    <strong><?= formatPrice($stats['total_revenue']) ?></strong>
    <span>Total Revenue</span>
  </div>

  <div class="admin-stat-card">
    <div class="admin-stat-header">
      <div class="admin-stat-icon">🧾</div>
      <span class="stat-badge-today">Today: <?= (int)$stats['today_orders'] ?></span>
    </div>
    <strong><?= (int)$stats['total_orders'] ?></strong>
    <span>Total Orders</span>
  </div>

  <div class="admin-stat-card">
    <div class="admin-stat-header">
      <div class="admin-stat-icon">📦</div>
      <span class="stat-badge-today" style="<?= (int)$stats['low_stock_count'] > 0 ? 'background:rgba(239,68,68,.15);color:#f87171;' : '' ?>">
        <?= (int)$stats['low_stock_count'] ?> Low Stock
      </span>
    </div>
    <strong><?= (int)$stats['total_products'] ?></strong>
    <span>Total Products</span>
  </div>

  <div class="admin-stat-card">
    <div class="admin-stat-header">
      <div class="admin-stat-icon">👥</div>
      <span class="stat-badge-today">Active</span>
    </div>
    <strong><?= (int)$stats['total_customers'] ?></strong>
    <span>Total Customers</span>
  </div>
</div>

<!-- Order Status Quick-Filter Bar -->
<div class="admin-status-pipeline">
  <a href="<?= $base ?>/admin/orders.php?status=pending" class="pipeline-card status-pending">
    <div class="pipeline-count"><?= (int)$stats['pending_orders'] ?></div>
    <div class="pipeline-label">⏳ Pending</div>
  </a>
  <a href="<?= $base ?>/admin/orders.php?status=processing" class="pipeline-card status-processing">
    <div class="pipeline-count"><?= (int)$stats['processing_orders'] ?></div>
    <div class="pipeline-label">⚙️ Processing</div>
  </a>
  <a href="<?= $base ?>/admin/orders.php?status=shipped" class="pipeline-card status-shipped">
    <div class="pipeline-count"><?= (int)$stats['shipped_orders'] ?></div>
    <div class="pipeline-label">🚚 Shipped</div>
  </a>
  <a href="<?= $base ?>/admin/orders.php?status=delivered" class="pipeline-card status-delivered">
    <div class="pipeline-count"><?= (int)$stats['delivered_orders'] ?></div>
    <div class="pipeline-label">✅ Delivered</div>
  </a>
</div>

<!-- Low Stock Alerts Panel (if any) -->
<?php if (!empty($lowStockProducts)): ?>
<div class="admin-panel low-stock-panel">
  <div class="admin-panel-head">
    <h3>⚠️ Inventory Attention Needed <span style="color:var(--text2); font-weight:400; font-size:13px;">(Fewer than <?= $lowStockThreshold ?> units in stock)</span></h3>
    <a href="<?= $base ?>/admin/products.php" class="btn-outline btn-sm">Manage Products →</a>
  </div>
  <div style="overflow-x: auto;">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Image</th>
          <th>Product Name</th>
          <th>Category</th>
          <th>Current Stock</th>
          <th>Price</th>
          <th>Quick Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($lowStockProducts as $p): ?>
        <tr>
          <td><img src="<?= e($p['image_url']) ?>" class="thumb" alt=""></td>
          <td><strong><?= e($p['name']) ?></strong></td>
          <td><span style="color:var(--text2);"><?= e($p['category_name']) ?></span></td>
          <td>
            <span class="low-stock <?= $p['stock'] == 0 ? 'out' : '' ?>">
              <?= (int)$p['stock'] ?> units <?= $p['stock'] == 0 ? '❌ (Out of Stock)' : '⚠️ (Low)' ?>
            </span>
          </td>
          <td><strong><?= formatPrice($p['price']) ?></strong></td>
          <td>
            <a href="<?= $base ?>/admin/products.php?search=<?= urlencode($p['name']) ?>" class="icon-btn">
              ✏️ Restock / Edit
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- Recent Orders Table -->
<div class="admin-panel">
  <div class="admin-panel-head">
    <h3>Recent Orders</h3>
    <div style="display:flex; gap:10px;">
      <a href="<?= $base ?>/admin/orders.php" class="btn-outline btn-sm">View All Orders (<?= (int)$stats['total_orders'] ?>) →</a>
    </div>
  </div>

  <div style="overflow-x: auto;">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Order #</th>
          <th>Customer</th>
          <th>Items</th>
          <th>Total</th>
          <th>Payment</th>
          <th>Status</th>
          <th>Date</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($recentOrders)): ?>
          <tr><td colspan="8" class="admin-empty">No orders found yet.</td></tr>
        <?php else: ?>
          <?php foreach ($recentOrders as $o):
            $customerName = trim(($o['first_name'] ?? 'Guest') . ' ' . ($o['last_name'] ?? ''));
          ?>
          <tr>
            <td><strong style="color:var(--accent);"><?= e($o['order_number']) ?></strong></td>
            <td>
              <strong><?= e($customerName) ?></strong>
              <?php if (!empty($o['email'])): ?><div style="font-size:11px; color:var(--text2);"><?= e($o['email']) ?></div><?php endif; ?>
            </td>
            <td><span class="role-badge"><?= (int)$o['item_count'] ?> item(s)</span></td>
            <td><strong><?= formatPrice($o['total_amount']) ?></strong></td>
            <td>
              <span class="pay-status pay-<?= e($o['payment_status']) ?>">
                <?= ucfirst($o['payment_status']) ?> (<?= strtoupper($o['payment_method']) ?>)
              </span>
            </td>
            <td><span class="status-badge status-<?= e($o['status']) ?>"><?= ucfirst(e($o['status'])) ?></span></td>
            <td style="color:var(--text2); font-size:13px;"><?= date('M j, Y - H:i', strtotime($o['created_at'])) ?></td>
            <td>
              <button type="button" class="icon-btn" data-open-modal="orderModal_<?= $o['id'] ?>">
                👁️ View / Update
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Order Detail & Quick Status Modals -->
<?php foreach ($recentOrders as $o):
  $items = $itemsByOrder[$o['id']] ?? [];
  $customerName = trim(($o['first_name'] ?? 'Guest') . ' ' . ($o['last_name'] ?? ''));
?>
<div class="modal-overlay" id="orderModal_<?= $o['id'] ?>">
  <div class="modal-box">
    <button type="button" class="modal-close" data-close-modal>×</button>
    <h3>Order <?= e($o['order_number']) ?></h3>
    <div style="font-size:13px; color:var(--text2); margin-bottom:16px;">
      Placed on <?= date('M j, Y at g:i A', strtotime($o['created_at'])) ?>
    </div>

    <!-- Customer Information -->
    <div style="background:var(--surface2); border:1px solid var(--border); border-radius:8px; padding:14px; margin-bottom:16px; font-size:13px;">
      <strong style="display:block; font-size:14px; margin-bottom:4px;"><?= e($customerName) ?></strong>
      <?php if (!empty($o['email'])): ?><div>✉️ <?= e($o['email']) ?></div><?php endif; ?>
      <?php if (!empty($o['phone'])): ?><div>📞 <?= e($o['phone']) ?></div><?php endif; ?>
      <div style="margin-top:6px; color:var(--text2);">
        <strong>📍 Shipping Address:</strong><br>
        <?= nl2br(e($o['shipping_address'])) ?>
      </div>
    </div>

    <!-- Order Items List -->
    <h4 style="font-size:14px; margin-bottom:10px;">Purchased Items:</h4>
    <div style="max-height:180px; overflow-y:auto; margin-bottom:16px; border:1px solid var(--border); border-radius:8px; padding:8px;">
      <?php foreach ($items as $item): ?>
      <div class="order-modal-item">
        <img src="<?= e($item['image_url']) ?>" alt="">
        <div class="grow">
          <strong><?= e($item['product_name'] ?? 'Product') ?></strong>
          <div style="color:var(--text2); font-size:12px;">Qty: <?= (int)$item['quantity'] ?> × <?= formatPrice($item['price']) ?></div>
        </div>
        <strong><?= formatPrice($item['price'] * $item['quantity']) ?></strong>
      </div>
      <?php endforeach; ?>
    </div>

    <div style="display:flex; justify-content:space-between; align-items:center; font-size:15px; margin-bottom:18px; padding:10px 0; border-top:1px solid var(--border);">
      <span>Order Total:</span>
      <strong style="font-size:18px; color:var(--accent);"><?= formatPrice($o['total_amount']) ?></strong>
    </div>

    <!-- Quick Status Update Form -->
    <form method="POST" action="<?= $base ?>/admin/index.php" style="background:var(--surface2); border:1px solid var(--border); border-radius:8px; padding:14px;">
      <input type="hidden" name="form" value="quick_update_order">
      <input type="hidden" name="id" value="<?= $o['id'] ?>">

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
        <div class="form-group" style="margin-bottom:0;">
          <label style="font-size:12px;">Fulfillment Status</label>
          <select name="status" style="width:100%;">
            <?php foreach (['pending','processing','shipped','delivered','cancelled'] as $s): ?>
              <option value="<?= $s ?>" <?= $o['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
          <label style="font-size:12px;">Payment Status</label>
          <select name="payment_status" style="width:100%;">
            <?php foreach (['unpaid','paid','refunded'] as $ps): ?>
              <option value="<?= $ps ?>" <?= $o['payment_status']===$ps?'selected':'' ?>><?= ucfirst($ps) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div style="display:flex; justify-content:flex-end; gap:8px;">
        <button type="button" class="btn-outline btn-sm" data-close-modal>Close</button>
        <button type="submit" class="btn-primary btn-sm">Save Changes</button>
      </div>
    </form>
  </div>
</div>
<?php endforeach; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
