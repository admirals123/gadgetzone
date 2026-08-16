<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/currency.php';

$base = base_path();
$slug = $_GET['slug'] ?? '';

$stmt = mysqli_prepare($conn, "SELECT p.*, c.name AS category_name, c.slug AS category_slug
                                FROM products p LEFT JOIN categories c ON c.id = p.category_id
                                WHERE p.slug = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "s", $slug);
mysqli_stmt_execute($stmt);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$product) {
    http_response_code(404);
    $pageTitle = 'Product Not Found';
    require_once __DIR__ . '/../includes/header.php';
    echo '<div class="container section"><div class="empty-state"><div class="icon">📦</div><h3>Product not found</h3>
          <p style="margin:10px 0 20px;">This product may have been removed.</p>
          <a href="' . $base . '/pages/shop.php" class="btn-primary">Back to Shop</a></div></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$pageTitle = $product['name'];

// Related products (same category)
$related = [];
$stmt = mysqli_prepare($conn, "SELECT p.*, c.name AS category_name FROM products p
                                LEFT JOIN categories c ON c.id = p.category_id
                                WHERE p.category_id = ? AND p.id != ? LIMIT 4");
mysqli_stmt_bind_param($stmt, "ii", $product['category_id'], $product['id']);
mysqli_stmt_execute($stmt);
$related = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
  <div class="breadcrumb">
    <a href="<?= $base ?>/index.php">Home</a> &rsaquo;
    <a href="<?= $base ?>/pages/shop.php?cat=<?= urlencode($product['category_slug'] ?? '') ?>"><?= e($product['category_name']) ?></a> &rsaquo;
    <?= e($product['name']) ?>
  </div>

  <div class="product-detail">
    <div class="product-detail-img">
      <img src="<?= e($product['image_url']) ?>" alt="<?= e($product['name']) ?>">
    </div>
    <div class="product-detail-info">
      <?php if ($product['badge']): ?><span class="badge <?= e($product['badge']) ?>" style="position:static; display:inline-block;"><?= e($product['badge']) ?></span><?php endif; ?>
      <h1 class="product-detail-title"><?= e($product['name']) ?></h1>
      <div class="product-rating">★★★★★ <span style="color:var(--text2)">4.8 (312 verified reviews)</span></div>
      
      <div class="product-detail-price">
        <span class="current"><?= formatPrice($product['price']) ?></span>
        <?php if ($product['old_price']): ?>
          <span class="old"><?= formatPrice($product['old_price']) ?></span>
          <?php 
            $pDiscount = round((($product['old_price'] - $product['price']) / $product['old_price']) * 100);
            if ($pDiscount > 0): 
          ?>
            <span class="discount-pill">-<?= $pDiscount ?>% OFF</span>
          <?php endif; ?>
        <?php endif; ?>
      </div>

      <p class="product-detail-desc"><?= nl2br(e($product['description'])) ?></p>

      <div class="stock-status-wrap" style="margin-bottom:20px;">
        <?php if ($product['stock'] > 0 && $product['stock'] < 10): ?>
          <span class="stock-urgency">⚡ Only <?= (int)$product['stock'] ?> left in stock — order soon!</span>
        <?php elseif ($product['stock'] >= 10): ?>
          <span style="color:#4ade80; font-weight:700; font-size:14px;">✅ In Stock (Ready to ship)</span>
        <?php else: ?>
          <span style="color:#f87171; font-weight:700; font-size:14px;">❌ Currently Out of Stock</span>
        <?php endif; ?>
      </div>

      <div class="qty-add-row" style="flex-wrap:wrap; gap:12px;">
        <div class="qty-controls" style="border:1px solid var(--border); border-radius:8px; padding:4px;">
          <button type="button" onclick="document.getElementById('qtyInput').stepDown()">−</button>
          <input type="number" id="qtyInput" value="1" min="1" max="<?= max(1, (int)$product['stock']) ?>">
          <button type="button" onclick="document.getElementById('qtyInput').stepUp()">+</button>
        </div>
        <button class="btn-primary btn-lg add-to-cart-btn" data-id="<?= (int)$product['id'] ?>" <?= $product['stock'] <= 0 ? 'disabled' : '' ?> style="flex:1; min-width:160px;">
          🛒 Add to Cart
        </button>
        <button class="btn-buy-now btn-lg" id="buyNowBtn" data-id="<?= (int)$product['id'] ?>" <?= $product['stock'] <= 0 ? 'disabled' : '' ?> style="flex:1; min-width:160px;">
          ⚡ Buy Now
        </button>
      </div>

      <!-- Amazon-style Trust Guarantee Strip -->
      <div class="trust-guarantees">
        <div class="trust-item">
          <span class="trust-icon">🛡️</span>
          <div>
            <strong>1-Year Warranty</strong>
            <small>Brand replacement</small>
          </div>
        </div>
        <div class="trust-item">
          <span class="trust-icon">🔄</span>
          <div>
            <strong>7-Day Returns</strong>
            <small>Easy return policy</small>
          </div>
        </div>
        <div class="trust-item">
          <span class="trust-icon">🚚</span>
          <div>
            <strong>Fast Delivery</strong>
            <small>Dispatched in 24h</small>
          </div>
        </div>
        <div class="trust-item">
          <span class="trust-icon">🔒</span>
          <div>
            <strong>100% Secure</strong>
            <small>Encrypted checkout</small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php if (!empty($related)): ?>
  <section class="section">
    <h2 class="section-title">You Might Also Like</h2>
    <div class="product-grid cols-4">
      <?php foreach ($related as $p):
        $discount = $p['old_price'] ? round((($p['old_price'] - $p['price']) / $p['old_price']) * 100) : 0;
      ?>
      <div class="product-card">
        <?php if ($p['badge']): ?><span class="badge <?= e($p['badge']) ?>"><?= e($p['badge']) ?></span><?php endif; ?>
        <a href="<?= $base ?>/pages/product.php?slug=<?= urlencode($p['slug']) ?>" class="product-img">
          <img src="<?= e($p['image_url']) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
        </a>
        <div class="product-body">
          <div>
            <div class="product-cat"><?= e($p['category_name'] ?? '') ?></div>
            <a href="<?= $base ?>/pages/product.php?slug=<?= urlencode($p['slug']) ?>"><div class="product-name"><?= e($p['name']) ?></div></a>
            <div class="product-rating">★★★★★ <span>(4.8)</span></div>
          </div>
          <div>
            <div class="product-price">
              <span class="current"><?= formatPrice($p['price']) ?></span>
              <?php if ($p['old_price']): ?>
                <span class="old"><?= formatPrice($p['old_price']) ?></span>
                <?php if ($discount > 0): ?><span class="discount-pill">-<?= $discount ?>%</span><?php endif; ?>
              <?php endif; ?>
            </div>
            <button class="btn-primary add-to-cart-btn" data-id="<?= (int)$p['id'] ?>">Add to Cart</button>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
