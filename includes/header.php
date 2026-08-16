<?php
// Expects $conn, functions.php, currency.php already loaded by the including page.
$base = base_path();
$cartCount = getCartCount();
$currentUser = isLoggedIn() ? getCurrentUser() : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? e($pageTitle) . ' | GadgetZone' : 'GadgetZone - Next-Level Technology' ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;600;700;800&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $base ?>/assets/css/style.css?v=<?= @filemtime(__DIR__ . '/../assets/css/style.css') ?>">
<script>
  (function() {
    const saved = localStorage.getItem('gz_theme') || (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
    document.documentElement.setAttribute('data-theme', saved);
  })();
</script>
</head>
<body>

<header class="site-header">
  <div class="container header-inner">
    <a href="<?= $base ?>/index.php" class="logo">Gadget<span>Zone</span></a>

    <nav class="main-nav">
      <a href="<?= $base ?>/index.php">Home</a>
      <a href="<?= $base ?>/pages/shop.php">Shop</a>
      <a href="<?= $base ?>/pages/shop.php?badge=SALE">Deals</a>
    </nav>

    <form class="nav-search" action="<?= $base ?>/pages/shop.php" method="GET">
      <input type="text" name="search" placeholder="Search gadgets..." value="<?= e($_GET['search'] ?? '') ?>">
      <button type="submit" aria-label="Search">🔍</button>
    </form>

    <div class="header-actions">
      <a href="<?= $base ?>/pages/cart.php" class="cart-link" title="Shopping Cart">
        🛒
        <span class="cart-badge" style="<?= $cartCount === 0 ? 'display:none;' : '' ?>"><?= (int)$cartCount ?></span>
      </a>

      <!-- Live Currency Switcher -->
      <?php 
        $activeCur = getActiveCurrency(); 
        $enabledCurrencies = getEnabledCurrencies();
      ?>
      <div class="currency-selector-wrap">
        <form method="GET" action="<?= $base ?>/pages/set_currency.php" class="currency-form">
          <input type="hidden" name="return" value="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">
          <select name="code" class="currency-dropdown" onchange="this.form.submit()" title="Change Currency (Live Rates)">
            <?php foreach ($enabledCurrencies as $cCode => $cVal): ?>
              <option value="<?= e($cCode) ?>" <?= $cCode === $activeCur['code'] ? 'selected' : '' ?>>
                <?= e($cVal['symbol']) ?> <?= e($cCode) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>

      <!-- Theme Switcher (Light / Dark) -->
      <button type="button" class="theme-toggle-btn" id="themeToggleBtn" aria-label="Toggle theme" title="Toggle Light / Dark Mode">
        <span class="theme-icon-sun">☀️</span>
        <span class="theme-icon-moon">🌙</span>
      </button>

      <?php if ($currentUser): ?>
        <a href="<?= $base ?>/pages/myaccount.php" class="account-link" title="My Account">
          <span class="avatar-mini">
            <?php if (!empty($currentUser['avatar'])): ?>
              <img src="<?= $base ?>/assets/uploads/avatars/<?= e($currentUser['avatar']) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" alt="Avatar">
            <?php else: ?>
              <?= e(strtoupper(substr($currentUser['first_name'],0,1) . substr($currentUser['last_name'],0,1))) ?>
            <?php endif; ?>
          </span>
        </a>
        <?php if (in_array($currentUser['role'], ['admin','super_admin'], true)): ?>
          <a href="<?= $base ?>/admin/index.php" class="btn-outline btn-sm">Admin</a>
        <?php endif; ?>
      <?php else: ?>
        <a href="<?= $base ?>/pages/login.php" class="btn-outline btn-sm">Log In</a>
      <?php endif; ?>
    </div>

    <button class="mobile-toggle" id="mobileToggle" aria-label="Menu">☰</button>
  </div>

  <!-- Amazon-style Category Quick Bar -->
  <div class="sub-header">
    <div class="container sub-header-inner">
      <div class="sub-nav-links">
        <a href="<?= $base ?>/pages/shop.php?badge=SALE" class="deal-tag">🔥 Today's Deals</a>
        <a href="<?= $base ?>/pages/shop.php?cat=smartphones">📱 Smartphones</a>
        <a href="<?= $base ?>/pages/shop.php?cat=laptops">💻 Laptops</a>
        <a href="<?= $base ?>/pages/shop.php?cat=audio">🎧 Audio</a>
        <a href="<?= $base ?>/pages/shop.php?cat=cameras">📷 Cameras</a>
        <a href="<?= $base ?>/pages/shop.php?cat=wearables">⌚ Wearables</a>
        <a href="<?= $base ?>/pages/shop.php?cat=accessories">🔌 Accessories</a>
      </div>
      <div class="sub-header-perk">
        <span>⚡ Free Express Delivery on orders over <?= formatPrice(5000) ?></span>
      </div>
    </div>
  </div>
</header>

<!-- Floating Toast Container -->
<div id="toastContainer" class="toast-container"></div>

<main>
