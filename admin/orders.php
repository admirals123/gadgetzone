<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/currency.php';
requireAdmin();

$pageTitle = 'Orders';
$base = base_path();
$message = '';

// Handle Status & Payment Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'update_order') {
    $id = (int)$_POST['id'];
    $status = $_POST['status'] ?? 'pending';
    $payStatus = $_POST['payment_status'] ?? 'unpaid';

    if (in_array($status, ['pending','processing','shipped','delivered','cancelled'], true) &&
        in_array($payStatus, ['unpaid','paid','refunded'], true)) {
        $stmt = mysqli_prepare($conn, "UPDATE orders SET status = ?, payment_status = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ssi", $status, $payStatus, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $message = 'Order updated successfully.';
    }
}

// Search and Filters
$search       = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$payFilter    = $_GET['payment_status'] ?? '';

$where = [];
$types = '';
$params = [];

if ($search !== '') {
    $where[] = "(o.order_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR o.shipping_address LIKE ?)";
    $types .= 'sssss';
    $term = "%$search%";
    $params = [$term, $term, $term, $term, $term];
}

if ($statusFilter !== '' && in_array($statusFilter, ['pending','processing','shipped','delivered','cancelled'], true)) {
    $where[] = "o.status = ?";
    $types .= 's';
    $params[] = $statusFilter;
}

if ($payFilter !== '' && in_array($payFilter, ['unpaid','paid','refunded'], true)) {
    $where[] = "o.payment_status = ?";
    $types .= 's';
    $params[] = $payFilter;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "SELECT o.*, u.first_name, u.last_name, u.email, u.phone,
        (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
        FROM orders o
        LEFT JOIN users u ON u.id = o.user_id $whereSql ORDER BY o.created_at DESC";

if ($params) {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $orders = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
} else {
    $orders = mysqli_fetch_all(mysqli_query($conn, $sql), MYSQLI_ASSOC);
}

// Pre-fetch order items for modal
$itemsByOrder = [];
if (!empty($orders)) {
    $orderIds = array_map(fn($o) => (int)$o['id'], $orders);
    $idsList = implode(',', $orderIds);
    $itemRows = mysqli_fetch_all(mysqli_query($conn, "SELECT oi.*, p.name AS product_name, p.image_url
        FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id
        WHERE oi.order_id IN ($idsList)"), MYSQLI_ASSOC);
    foreach ($itemRows as $row) {
        $itemsByOrder[$row['order_id']][] = $row;
    }
}

// Status breakdown counts
$counts = mysqli_fetch_assoc(mysqli_query($conn, "SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) AS processing,
    SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) AS shipped,
    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) AS delivered,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled
FROM orders"));

require_once __DIR__ . '/layout.php';
?>

<?php if ($message): ?>
  <div class="alert alert-success">✅ <?= e($message) ?></div>
<?php endif; ?>

<div class="admin-panel">
  <div class="admin-panel-head">
    <div>
      <h3 style="margin-bottom:2px;">Orders & Fulfillment</h3>
      <span style="color:var(--text2); font-size:13px;">Manage customer shipments, track payments, and update order statuses</span>
    </div>
    <div>
      <span class="role-badge" style="font-size:13px; padding:6px 14px;">
        Total: <?= (int)$counts['total'] ?> Orders
      </span>
    </div>
  </div>

  <!-- Status Filter Tabs -->
  <div class="admin-filter-tabs">
    <a href="<?= $base ?>/admin/orders.php" class="tab-item <?= empty($statusFilter) ? 'active' : '' ?>">
      All Orders <span class="tab-badge"><?= (int)$counts['total'] ?></span>
    </a>
    <a href="<?= $base ?>/admin/orders.php?status=pending" class="tab-item <?= $statusFilter==='pending' ? 'active' : '' ?>">
      ⏳ Pending <span class="tab-badge" style="background:rgba(245,158,11,.2); color:var(--accent);"><?= (int)$counts['pending'] ?></span>
    </a>
    <a href="<?= $base ?>/admin/orders.php?status=processing" class="tab-item <?= $statusFilter==='processing' ? 'active' : '' ?>">
      ⚙️ Processing <span class="tab-badge"><?= (int)$counts['processing'] ?></span>
    </a>
    <a href="<?= $base ?>/admin/orders.php?status=shipped" class="tab-item <?= $statusFilter==='shipped' ? 'active' : '' ?>">
      🚚 Shipped <span class="tab-badge"><?= (int)$counts['shipped'] ?></span>
    </a>
    <a href="<?= $base ?>/admin/orders.php?status=delivered" class="tab-item <?= $statusFilter==='delivered' ? 'active' : '' ?>">
      ✅ Delivered <span class="tab-badge" style="background:rgba(34,197,94,.2); color:#4ade80;"><?= (int)$counts['delivered'] ?></span>
    </a>
    <?php if ((int)$counts['cancelled'] > 0): ?>
    <a href="<?= $base ?>/admin/orders.php?status=cancelled" class="tab-item <?= $statusFilter==='cancelled' ? 'active' : '' ?>">
      ❌ Cancelled <span class="tab-badge"><?= (int)$counts['cancelled'] ?></span>
    </a>
    <?php endif; ?>
  </div>

  <!-- Search & Payment Filter Bar -->
  <form method="GET" class="admin-filter-bar">
    <?php if ($statusFilter): ?><input type="hidden" name="status" value="<?= e($statusFilter) ?>"><?php endif; ?>
    <input type="text" name="search" placeholder="🔍 Search Order #, Customer Name, Email, Address..." value="<?= e($search) ?>">
    
    <select name="payment_status" onchange="this.form.submit()">
      <option value="">All Payment Statuses</option>
      <?php foreach (['unpaid','paid','refunded'] as $ps): ?>
        <option value="<?= $ps ?>" <?= $payFilter===$ps?'selected':'' ?>><?= ucfirst($ps) ?></option>
      <?php endforeach; ?>
    </select>

    <button type="submit" class="btn-outline btn-sm">Search</button>
    <?php if ($search !== '' || $statusFilter !== '' || $payFilter !== ''): ?>
      <a href="<?= $base ?>/admin/orders.php" class="btn-outline btn-sm">Reset</a>
    <?php endif; ?>
  </form>
  
  <?php if (empty($orders)): ?>
    <div class="admin-empty">
      <div style="font-size:36px; margin-bottom:12px;">🧾</div>
      <p>No orders found matching your selected search or filter criteria.</p>
      <a href="<?= $base ?>/admin/orders.php" class="btn-outline btn-sm" style="margin-top:12px;">View All Orders</a>
    </div>
  <?php else: ?>
  <div style="overflow-x: auto;">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Order #</th>
          <th>Customer</th>
          <th>Items</th>
          <th>Total</th>
          <th>Payment</th>
          <th>Payment Status</th>
          <th>Fulfillment Status</th>
          <th>Date</th>
          <th>Quick Status Update</th>
          <th>Details</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $o):
          $customerName = trim(($o['first_name'] ?? 'Guest') . ' ' . ($o['last_name'] ?? ''));
        ?>
        <tr>
          <td><strong style="color:var(--accent);"><?= e($o['order_number']) ?></strong></td>
          <td>
            <strong><?= e($customerName) ?></strong>
            <?php if (!empty($o['email'])): ?>
              <div style="font-size:11px; color:var(--text2);"><?= e($o['email']) ?></div>
            <?php endif; ?>
          </td>
          <td><span class="role-badge"><?= (int)$o['item_count'] ?> item(s)</span></td>
          <td><strong><?= formatPrice($o['total_amount']) ?></strong></td>
          <td style="text-transform:uppercase; font-size:12px; font-weight:700;"><?= e($o['payment_method']) ?></td>
          <td>
            <span class="pay-status pay-<?= e($o['payment_status'] ?? 'unpaid') ?>">
              <?= ucfirst(e($o['payment_status'] ?? 'unpaid')) ?>
            </span>
          </td>
          <td>
            <span class="status-badge status-<?= e($o['status']) ?>">
              <?= ucfirst(e($o['status'])) ?>
            </span>
          </td>
          <td style="color:var(--text2); font-size:12px; white-space:nowrap;">
            <?= date('M j, Y', strtotime($o['created_at'])) ?>
          </td>
          <td>
            <form method="POST" action="<?= $base ?>/admin/orders.php" style="display:flex; gap:6px; align-items:center;">
              <input type="hidden" name="form" value="update_order">
              <input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
              <input type="hidden" name="payment_status" value="<?= e($o['payment_status'] ?? 'unpaid') ?>">
              <select name="status" style="background:var(--surface2); color:var(--text); border:1px solid var(--border); border-radius:6px; padding:4px 8px; font-size:12px;">
                <?php foreach (['pending','processing','shipped','delivered','cancelled'] as $s): ?>
                  <option value="<?= $s ?>" <?= $o['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
              </select>
              <button type="submit" class="icon-btn" title="Quick Save">💾</button>
            </form>
          </td>
          <td class="admin-actions">
            <button class="icon-btn" data-open-modal="orderModal_<?= $o['id'] ?>">
              👁️ View Full
            </button>
          </td>
        </tr>

        <!-- Order Detail & Print Invoice Modal -->
        <div class="modal-overlay" id="orderModal_<?= $o['id'] ?>">
          <div class="modal-box" style="width:580px;">
            <button class="modal-close" data-close-modal>×</button>
            
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:14px;">
              <div>
                <h3 style="margin-bottom:4px;">Order <?= e($o['order_number']) ?></h3>
                <span style="font-size:12px; color:var(--text2);">Placed on <?= date('M j, Y at g:i A', strtotime($o['created_at'])) ?></span>
              </div>
              <button type="button" class="btn-outline btn-sm" onclick="window.print()" title="Print Order Invoice">
                🖨️ Print Invoice
              </button>
            </div>

            <!-- Customer & Shipping Box -->
            <div style="background:var(--surface2); border:1px solid var(--border); border-radius:8px; padding:14px; margin-bottom:16px; font-size:13px;">
              <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                <strong style="font-size:14px;"><?= e($customerName) ?></strong>
                <span class="status-badge status-<?= e($o['status']) ?>"><?= ucfirst(e($o['status'])) ?></span>
              </div>
              
              <div style="display:flex; gap:12px; margin-bottom:8px; flex-wrap:wrap;">
                <?php if (!empty($o['email'])): ?>
                  <a href="mailto:<?= e($o['email']) ?>" class="icon-btn" style="font-size:12px;">✉️ <?= e($o['email']) ?></a>
                <?php endif; ?>
                <?php if (!empty($o['phone'])): ?>
                  <a href="tel:<?= e($o['phone']) ?>" class="icon-btn" style="font-size:12px;">📞 <?= e($o['phone']) ?></a>
                <?php endif; ?>
              </div>

              <div style="color:var(--text2); font-size:12px; border-top:1px solid var(--border); padding-top:8px; margin-top:8px;">
                <strong>📍 Shipping Address:</strong><br>
                <?= nl2br(e($o['shipping_address'])) ?>
              </div>
            </div>

            <!-- Items Purchased -->
            <h4 style="font-size:13px; text-transform:uppercase; color:var(--text2); margin-bottom:8px;">Order Items (<?= count($itemsByOrder[$o['id']] ?? []) ?>):</h4>
            <div style="max-height:180px; overflow-y:auto; margin-bottom:16px; border:1px solid var(--border); border-radius:8px; padding:8px;">
              <?php foreach (($itemsByOrder[$o['id']] ?? []) as $item): ?>
              <div class="order-modal-item">
                <img src="<?= e($item['image_url']) ?>" alt="">
                <div class="grow">
                  <strong><?= e($item['product_name'] ?? 'Product') ?></strong>
                  <div style="color:var(--text2); font-size:12px;">Qty: <?= (int)$item['quantity'] ?> × <?= formatPrice($item['price']) ?></div>
                </div>
                <strong style="color:var(--accent);"><?= formatPrice($item['price'] * $item['quantity']) ?></strong>
              </div>
              <?php endforeach; ?>
            </div>

            <!-- Order Total -->
            <div style="display:flex; justify-content:space-between; align-items:center; font-size:15px; margin-bottom:18px; padding:10px 0; border-top:1px solid var(--border);">
              <span>Grand Total:</span>
              <strong style="font-size:20px; color:var(--accent); font-family:var(--font-head);"><?= formatPrice($o['total_amount']) ?></strong>
            </div>

            <!-- Status Editor Form -->
            <form method="POST" action="<?= $base ?>/admin/orders.php" style="background:var(--surface2); border:1px solid var(--border); border-radius:8px; padding:14px;">
              <input type="hidden" name="form" value="update_order">
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
                <button type="submit" class="btn-primary btn-sm">Save Order Status</button>
              </div>
            </form>
          </div>
        </div>

        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
