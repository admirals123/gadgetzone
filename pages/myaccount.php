<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/currency.php';

$base = base_path();
requireLogin();

$pageTitle = 'My Account';
$user = getCurrentUser();
$tab = $_GET['tab'] ?? 'dashboard';
$successMsg = '';
$errorMsg = '';

// ---- Handle Profile Update ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'profile') {
    $firstName = sanitize($_POST['first_name'] ?? '');
    $lastName  = sanitize($_POST['last_name'] ?? '');
    $phone     = sanitize($_POST['phone'] ?? '');
    $address   = sanitize($_POST['address'] ?? '');
    $city      = sanitize($_POST['city'] ?? '');
    $avatar    = $user['avatar'] ?? null;

    if (!empty($_FILES['avatar_file']['name'])) {
        $ext = strtolower(pathinfo($_FILES['avatar_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','webp','gif'], true)) {
            $filename = 'avatar_' . $user['id'] . '_' . time() . '.' . $ext;
            $dest = __DIR__ . '/../assets/uploads/avatars/' . $filename;
            if (move_uploaded_file($_FILES['avatar_file']['tmp_name'], $dest)) {
                $avatar = $filename;
            }
        }
    }

    if ($firstName === '' || $lastName === '') {
        $errorMsg = 'First and last name are required.';
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE users SET first_name=?, last_name=?, phone=?, address=?, city=?, avatar=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "ssssssi", $firstName, $lastName, $phone, $address, $city, $avatar, $user['id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $successMsg = 'Profile updated successfully.';
        $user = getCurrentUser();
    }
    $tab = 'profile';
}

// ---- Handle Password Change ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'password') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!password_verify($current, $user['password'])) {
        $errorMsg = 'Current password is incorrect.';
    } elseif (strlen($new) < 6) {
        $errorMsg = 'New password must be at least 6 characters.';
    } elseif ($new !== $confirm) {
        $errorMsg = 'New passwords do not match.';
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "UPDATE users SET password=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "si", $hash, $user['id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $successMsg = 'Password updated successfully.';
    }
    $tab = 'password';
}

// ---- Stats ----
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total_orders,
                                       SUM(CASE WHEN status='delivered' THEN 1 ELSE 0 END) AS delivered,
                                       COALESCE(SUM(total_amount),0) AS total_spent
                                FROM orders WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user['id']);
mysqli_stmt_execute($stmt);
$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

// ---- Orders (recent 5 for dashboard, all for My Orders tab) ----
$limit = ($tab === 'orders') ? 100 : 5;
$stmt = mysqli_prepare($conn, "SELECT o.*, (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
                                FROM orders o WHERE o.user_id = ? ORDER BY o.created_at DESC LIMIT ?");
mysqli_stmt_bind_param($stmt, "ii", $user['id'], $limit);
mysqli_stmt_execute($stmt);
$orders = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$initials = strtoupper(substr($user['first_name'],0,1) . substr($user['last_name'],0,1));

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
  <div class="breadcrumb"><a href="<?= $base ?>/index.php">Home</a> &rsaquo; My Account</div>

  <div class="account-layout">
    <aside class="account-sidebar">
      <div class="account-user-card">
        <div class="avatar-lg">
          <?php if (!empty($user['avatar'])): ?>
            <img src="<?= $base ?>/assets/uploads/avatars/<?= e($user['avatar']) ?>" alt="Avatar">
          <?php else: ?><?= e($initials) ?><?php endif; ?>
        </div>
        <strong style="display:block;"><?= e($user['first_name'] . ' ' . $user['last_name']) ?></strong>
        <span style="color:var(--text2); font-size:13px;"><?= e($user['email']) ?></span>
      </div>
      <nav class="account-nav">
        <a href="?tab=dashboard" class="<?= $tab==='dashboard'?'active':'' ?>">📊 Dashboard</a>
        <a href="?tab=orders" class="<?= $tab==='orders'?'active':'' ?>">🛍️ My Orders</a>
        <a href="?tab=profile" class="<?= $tab==='profile'?'active':'' ?>">👤 Profile</a>
        <a href="?tab=password" class="<?= $tab==='password'?'active':'' ?>">🔒 Change Password</a>
        <a href="<?= $base ?>/pages/logout.php" class="logout">🚪 Logout</a>
      </nav>
    </aside>

    <div>
      <?php if ($successMsg): ?><div class="alert alert-success"><?= e($successMsg) ?></div><?php endif; ?>
      <?php if ($errorMsg): ?><div class="alert alert-error"><?= e($errorMsg) ?></div><?php endif; ?>

      <?php if ($tab === 'dashboard'): ?>
        <h2 style="margin-bottom:20px;">Welcome back, <?= e($user['first_name']) ?>! 👋</h2>
        <div class="stat-cards">
          <div class="stat-card"><strong><?= (int)$stats['total_orders'] ?></strong><span>Total Orders</span></div>
          <div class="stat-card"><strong><?= (int)$stats['delivered'] ?></strong><span>Delivered</span></div>
          <div class="stat-card"><strong><?= formatPrice($stats['total_spent']) ?></strong><span>Total Spent</span></div>
        </div>
        <h3 style="margin-bottom:14px;">Recent Orders</h3>
        <?php if (empty($orders)): ?>
          <p style="color:var(--text2);">No orders yet. <a href="<?= $base ?>/pages/shop.php" style="color:var(--accent);">Start shopping →</a></p>
        <?php else: ?>
        <table class="data-table">
          <thead><tr><th>Order #</th><th>Date</th><th>Total</th><th>Status</th><th>Payment</th></tr></thead>
          <tbody>
            <?php foreach ($orders as $o): ?>
            <tr>
              <td><?= e($o['order_number']) ?></td>
              <td><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
              <td><?= formatPrice($o['total_amount']) ?></td>
              <td><span class="status-badge status-<?= e($o['status']) ?>"><?= e($o['status']) ?></span></td>
              <td style="text-transform:capitalize;"><?= e($o['payment_method']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>

      <?php elseif ($tab === 'orders'): ?>
        <h2 style="margin-bottom:20px;">My Orders</h2>
        <?php if (empty($orders)): ?>
          <p style="color:var(--text2);">No orders yet. <a href="<?= $base ?>/pages/shop.php" style="color:var(--accent);">Start shopping →</a></p>
        <?php else: ?>
        <table class="data-table">
          <thead><tr><th>Order #</th><th>Date &amp; Time</th><th>Items</th><th>Total</th><th>Status</th><th>Payment</th></tr></thead>
          <tbody>
            <?php foreach ($orders as $o): ?>
            <tr>
              <td><?= e($o['order_number']) ?></td>
              <td><?= date('M j, Y g:i A', strtotime($o['created_at'])) ?></td>
              <td><?= (int)$o['item_count'] ?></td>
              <td><?= formatPrice($o['total_amount']) ?></td>
              <td><span class="status-badge status-<?= e($o['status']) ?>"><?= e($o['status']) ?></span></td>
              <td style="text-transform:capitalize;"><?= e($o['payment_method']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>

      <?php elseif ($tab === 'profile'): ?>
        <h2 style="margin-bottom:20px;">Profile</h2>
        <form method="POST" enctype="multipart/form-data" style="max-width:600px;">
          <input type="hidden" name="form" value="profile">
          <div class="form-row">
            <div class="form-group"><label>First Name</label><input type="text" name="first_name" value="<?= e($user['first_name']) ?>" required></div>
            <div class="form-group"><label>Last Name</label><input type="text" name="last_name" value="<?= e($user['last_name']) ?>" required></div>
          </div>
          <div class="form-group"><label>Email</label><input type="email" value="<?= e($user['email']) ?>" disabled><span style="color:var(--text2); font-size:12px;">Email cannot be changed</span></div>
          <div class="form-group"><label>Profile Picture</label>
            <input type="file" name="avatar_file" accept="image/*">
            <?php if (!empty($user['avatar'])): ?>
              <div style="margin-top:8px; display:flex; align-items:center; gap:10px;">
                <img src="<?= $base ?>/assets/uploads/avatars/<?= e($user['avatar']) ?>" style="width:48px; height:48px; border-radius:50%; object-fit:cover;" alt="Current Avatar">
                <span style="color:var(--text2); font-size:12px;">Current profile picture</span>
              </div>
            <?php endif; ?>
          </div>
          <div class="form-group"><label>Phone Number</label><input type="text" name="phone" value="<?= e($user['phone']) ?>"></div>
          <div class="form-group"><label>Address</label><textarea name="address" rows="3"><?= e($user['address']) ?></textarea></div>
          <div class="form-group"><label>City</label><input type="text" name="city" value="<?= e($user['city']) ?>"></div>
          <button type="submit" class="btn-primary">Save Changes</button>
        </form>

      <?php elseif ($tab === 'password'): ?>
        <h2 style="margin-bottom:20px;">Change Password</h2>
        <form method="POST" style="max-width:460px;">
          <input type="hidden" name="form" value="password">
          <div class="form-group"><label>Current Password *</label><input type="password" name="current_password" required></div>
          <div class="form-group"><label>New Password *</label><input type="password" name="new_password" minlength="6" required></div>
          <div class="form-group"><label>Confirm New Password *</label><input type="password" name="confirm_password" minlength="6" required></div>
          <button type="submit" class="btn-primary">Update Password</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
