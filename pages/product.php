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
      <img
        src="<?= e($product['image_url']) ?>"
        alt="<?= e($product['name']) ?>"
        id="productMainImg"
        onclick="openLightbox(this.src, this.alt)"
        title="Click to zoom"
        style="cursor: zoom-in;"
      >
      <div class="zoom-hint">🔍 Click image to zoom</div>
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

<!-- ── Lightbox Overlay ─────────────────────────────────────── -->
<div id="gz-lightbox" onclick="closeLightboxOnBg(event)" style="
  display:none; position:fixed; inset:0; z-index:99999;
  background:rgba(0,0,0,0.92); backdrop-filter:blur(6px);
  align-items:center; justify-content:center; flex-direction:column;
">
  <!-- Controls -->
  <div style="position:absolute; top:16px; right:16px; display:flex; gap:10px; z-index:2;">
    <button onclick="zoomLightbox(0.25)" title="Zoom In"  style="width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);color:#fff;font-size:20px;cursor:pointer;line-height:1;">+</button>
    <button onclick="zoomLightbox(-0.25)" title="Zoom Out" style="width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);color:#fff;font-size:20px;cursor:pointer;line-height:1;">−</button>
    <button onclick="resetLightbox()" title="Reset"    style="width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);color:#fff;font-size:13px;cursor:pointer;font-weight:700;">1:1</button>
    <button onclick="closeLightbox()" title="Close"    style="width:40px;height:40px;border-radius:50%;background:rgba(239,68,68,.25);border:1px solid rgba(239,68,68,.4);color:#f87171;font-size:20px;cursor:pointer;line-height:1;">×</button>
  </div>

  <!-- Zoom level badge -->
  <div id="lbZoomBadge" style="position:absolute;top:16px;left:16px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);color:#fff;padding:4px 12px;border-radius:999px;font-size:13px;font-weight:700;z-index:2;">100%</div>

  <!-- Image container -->
  <div id="lbContainer" style="overflow:hidden;width:100%;height:100%;display:flex;align-items:center;justify-content:center;cursor:grab;">
    <img id="lbImg" src="" alt="" style="max-width:90vw;max-height:90vh;object-fit:contain;border-radius:8px;box-shadow:0 30px 80px rgba(0,0,0,.8);transform-origin:center center;transition:transform .15s ease;user-select:none;-webkit-user-drag:none;">
  </div>

  <!-- Caption -->
  <div id="lbCaption" style="color:rgba(255,255,255,.6);font-size:13px;margin-top:12px;text-align:center;padding:0 20px;"></div>
</div>

<style>
#productMainImg { transition: transform .2s, box-shadow .2s; }
#productMainImg:hover { transform: scale(1.02); box-shadow: 0 20px 60px rgba(0,0,0,.3); }
.zoom-hint { text-align:center; font-size:12px; color:var(--text2); margin-top:8px; opacity:.7; }
</style>

<script>
let _lbScale = 1, _lbDragging = false, _lbDragX = 0, _lbDragY = 0, _lbTransX = 0, _lbTransY = 0;

function openLightbox(src, alt) {
  const lb = document.getElementById('gz-lightbox');
  document.getElementById('lbImg').src = src;
  document.getElementById('lbCaption').textContent = alt || '';
  lb.style.display = 'flex';
  document.body.style.overflow = 'hidden';
  resetLightbox();
}

function closeLightbox() {
  document.getElementById('gz-lightbox').style.display = 'none';
  document.body.style.overflow = '';
}

function closeLightboxOnBg(e) {
  if (e.target === document.getElementById('gz-lightbox') ||
      e.target === document.getElementById('lbContainer')) closeLightbox();
}

function zoomLightbox(delta) {
  _lbScale = Math.min(5, Math.max(0.5, _lbScale + delta));
  applyLbTransform();
}

function resetLightbox() {
  _lbScale = 1; _lbTransX = 0; _lbTransY = 0;
  applyLbTransform();
}

function applyLbTransform() {
  const img = document.getElementById('lbImg');
  img.style.transform = `translate(${_lbTransX}px, ${_lbTransY}px) scale(${_lbScale})`;
  document.getElementById('lbZoomBadge').textContent = Math.round(_lbScale * 100) + '%';
}

// Keyboard
document.addEventListener('keydown', e => {
  const lb = document.getElementById('gz-lightbox');
  if (lb.style.display === 'none') return;
  if (e.key === 'Escape') closeLightbox();
  if (e.key === '+' || e.key === '=') zoomLightbox(0.25);
  if (e.key === '-') zoomLightbox(-0.25);
  if (e.key === '0') resetLightbox();
});

// Mouse wheel zoom
document.getElementById('lbContainer').addEventListener('wheel', e => {
  e.preventDefault();
  zoomLightbox(e.deltaY < 0 ? 0.15 : -0.15);
}, { passive: false });

// Drag to pan
const lbCont = document.getElementById('lbContainer');
lbCont.addEventListener('mousedown', e => {
  if (_lbScale <= 1) return;
  _lbDragging = true; _lbDragX = e.clientX - _lbTransX; _lbDragY = e.clientY - _lbTransY;
  lbCont.style.cursor = 'grabbing';
});
document.addEventListener('mousemove', e => {
  if (!_lbDragging) return;
  _lbTransX = e.clientX - _lbDragX; _lbTransY = e.clientY - _lbDragY;
  applyLbTransform();
});
document.addEventListener('mouseup', () => { _lbDragging = false; lbCont.style.cursor = 'grab'; });

// Touch pinch zoom
let _lbTouches = [], _lbInitDist = 0, _lbInitScale = 1;
lbCont.addEventListener('touchstart', e => {
  _lbTouches = [...e.touches];
  if (_lbTouches.length === 2) {
    const dx = _lbTouches[0].clientX - _lbTouches[1].clientX;
    const dy = _lbTouches[0].clientY - _lbTouches[1].clientY;
    _lbInitDist = Math.hypot(dx, dy);
    _lbInitScale = _lbScale;
  }
}, { passive: true });
lbCont.addEventListener('touchmove', e => {
  if (e.touches.length === 2) {
    const dx = e.touches[0].clientX - e.touches[1].clientX;
    const dy = e.touches[0].clientY - e.touches[1].clientY;
    const dist = Math.hypot(dx, dy);
    _lbScale = Math.min(5, Math.max(0.5, _lbInitScale * (dist / _lbInitDist)));
    applyLbTransform();
  }
}, { passive: true });
</script>
