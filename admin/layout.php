<?php
// Expects $conn, functions.php already loaded, and requireAdmin() already called.
$base = base_path();
$currentPage = basename($_SERVER['PHP_SELF']);
$adminUser = getCurrentUser();
$adminInitials = $adminUser ? strtoupper(substr($adminUser['first_name'],0,1) . substr($adminUser['last_name'],0,1)) : 'A';

// Notification bell: count of orders still awaiting action
$pendingCount = 0;
if (isset($conn)) {
    $pendingResult = mysqli_query($conn, "SELECT COUNT(*) AS c FROM orders WHERE status = 'pending'");
    if ($pendingResult) $pendingCount = (int) mysqli_fetch_assoc($pendingResult)['c'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? e($pageTitle) . ' | GadgetZone Admin' : 'GadgetZone Admin' ?></title>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;600;700;800&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= $base ?>/admin/admin.css">
<script>
  (function() {
    const saved = localStorage.getItem('gz_theme') || (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
    document.documentElement.setAttribute('data-theme', saved);
  })();
</script>
</head>
<body class="admin-body">

<div class="admin-shell">
  <aside class="admin-sidebar">
    <a href="<?= $base ?>/index.php" class="logo admin-logo">Gadget<span>Zone</span></a>

    <?php if ($adminUser): ?>
    <div class="admin-user-strip">
      <div class="admin-user-avatar">
        <?php if (!empty($adminUser['avatar'])): ?>
          <img src="<?= $base ?>/assets/uploads/avatars/<?= e($adminUser['avatar']) ?>" alt="">
        <?php else: ?><?= e($adminInitials) ?><?php endif; ?>
      </div>
      <div class="admin-user-info">
        <strong><?= e($adminUser['first_name'] . ' ' . $adminUser['last_name']) ?></strong>
        <span><?= e(str_replace('_', ' ', $adminUser['role'])) ?></span>
      </div>
    </div>
    <?php endif; ?>

    <nav class="admin-nav">
      <a href="<?= $base ?>/admin/index.php" class="<?= $currentPage==='index.php'?'active':'' ?>">📊 Dashboard</a>
      <a href="<?= $base ?>/admin/products.php" class="<?= $currentPage==='products.php'?'active':'' ?>">📦 Products</a>
      <a href="<?= $base ?>/admin/categories.php" class="<?= $currentPage==='categories.php'?'active':'' ?>">🏷️ Categories</a>
      <a href="<?= $base ?>/admin/orders.php" class="<?= $currentPage==='orders.php'?'active':'' ?>" style="display:flex; justify-content:space-between; align-items:center;">
        <span>🧾 Orders</span>
        <?php if ($pendingCount > 0): ?>
          <span class="sidebar-badge"><?= $pendingCount ?></span>
        <?php endif; ?>
      </a>
      <a href="<?= $base ?>/admin/users.php" class="<?= $currentPage==='users.php'?'active':'' ?>">👥 Users</a>
      <a href="<?= $base ?>/admin/settings.php" class="<?= $currentPage==='settings.php'?'active':'' ?>">⚙️ Settings</a>
      <a href="<?= $base ?>/pages/logout.php" class="logout">🚪 Logout</a>
    </nav>
  </aside>

  <div class="admin-main">
    <header class="admin-topbar">
      <h1><?= isset($pageTitle) ? e($pageTitle) : 'Admin' ?></h1>
      <div class="admin-topbar-actions">
        <!-- Theme Switcher (Light / Dark) -->
        <button type="button" class="theme-toggle-btn" id="adminThemeToggleBtn" aria-label="Toggle theme" title="Toggle Light / Dark Mode">
          <span class="theme-icon-sun">☀️</span>
          <span class="theme-icon-moon">🌙</span>
        </button>

        <a href="<?= $base ?>/admin/orders.php?status=pending" class="notif-bell" title="<?= $pendingCount ?> pending order(s)">
          🔔
          <?php if ($pendingCount > 0): ?><span class="notif-badge"><?= $pendingCount ?></span><?php endif; ?>
        </a>
        <a href="<?= $base ?>/index.php" class="btn-outline btn-sm" target="_blank">View Store ↗</a>
      </div>
    </header>
    <div class="admin-content">
