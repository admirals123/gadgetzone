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

  <!-- Amazon-style zoom layout -->
  <div class="product-detail" style="position:relative;">
    <div style="position:relative;">
      <!-- Main image with hover lens -->
      <div class="pz-main" id="pzMain">
        <img src="<?= e($product['image_url']) ?>" alt="<?= e($product['name']) ?>" id="pzImg" draggable="false">
        <div class="pz-lens" id="pzLens"></div>
      </div>
      <!-- Zoom result panel (appears to the right on hover) -->
      <div class="pz-result" id="pzResult">
        <div id="pzResultInner" style="width:100%;height:100%;background-repeat:no-repeat;"></div>
      </div>
    </div>
    <p class="pz-hint">🔍 Hover to zoom &nbsp;·&nbsp; Click to enlarge</p>
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

<!-- Fullscreen Lightbox -->
<div id="gz-lightbox" onclick="closeLightboxOnBg(event)" style="display:none;position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,.93);backdrop-filter:blur(8px);flex-direction:column;align-items:center;justify-content:center;">
  <div style="position:absolute;top:16px;right:16px;display:flex;gap:8px;z-index:2;">
    <button onclick="lbZoom(.3)"   style="width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);color:#fff;font-size:20px;cursor:pointer;">+</button>
    <button onclick="lbZoom(-.3)"  style="width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);color:#fff;font-size:20px;cursor:pointer;">−</button>
    <button onclick="lbReset()"    style="width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);color:#fff;font-size:12px;cursor:pointer;font-weight:700;">1:1</button>
    <button onclick="closeLightbox()" style="width:40px;height:40px;border-radius:50%;background:rgba(239,68,68,.25);border:1px solid rgba(239,68,68,.4);color:#f87171;font-size:22px;cursor:pointer;">×</button>
  </div>
  <div id="lbBadge" style="position:absolute;top:16px;left:16px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);color:#fff;padding:4px 14px;border-radius:999px;font-size:13px;font-weight:700;">100%</div>
  <div id="lbCont" style="overflow:hidden;width:100%;height:100%;display:flex;align-items:center;justify-content:center;cursor:grab;">
    <img id="lbImg" src="" alt="" draggable="false" style="max-width:90vw;max-height:90vh;object-fit:contain;border-radius:8px;box-shadow:0 30px 80px rgba(0,0,0,.8);transform-origin:center;transition:transform .12s;user-select:none;">
  </div>
  <div id="lbCaption" style="color:rgba(255,255,255,.5);font-size:13px;margin-top:10px;"></div>
</div>

<style>
/* Amazon hover zoom */
.pz-main {
  position:relative; width:100%; border-radius:var(--radius);
  overflow:hidden; cursor:crosshair;
  border:1px solid var(--border); background:var(--surface2);
}
.pz-main img { width:100%; display:block; user-select:none; }
.pz-lens {
  position:absolute; width:130px; height:130px; display:none; pointer-events:none;
  border:2px solid var(--accent); background:rgba(245,158,11,.08);
  box-shadow:0 0 0 9999px rgba(0,0,0,.22); border-radius:2px; z-index:10;
}
.pz-result {
  display:none; position:absolute; left:calc(100% + 16px); top:0;
  width:440px; height:440px; border:1px solid var(--border);
  border-radius:var(--radius); overflow:hidden; background:var(--surface);
  box-shadow:0 20px 60px rgba(0,0,0,.5); z-index:200; pointer-events:none;
}
.pz-hint { font-size:12px; color:var(--text2); margin-top:8px; text-align:center; opacity:.7; }
@media (max-width:1200px) {
  .pz-main { cursor:zoom-in; }
  .pz-lens, .pz-result { display:none !important; }
}
</style>

<script>
(function(){
  const ZOOM = 3;
  const main  = document.getElementById('pzMain');
  const lens  = document.getElementById('pzLens');
  const res   = document.getElementById('pzResult');
  const inner = document.getElementById('pzResultInner');
  const img   = document.getElementById('pzImg');
  if (!main || !img) return;

  function init() {
    inner.style.backgroundImage = `url('${img.src}')`;
    main.addEventListener('mouseenter', ()=>{ lens.style.display='block'; res.style.display='block'; });
    main.addEventListener('mouseleave', ()=>{ lens.style.display='none';  res.style.display='none'; });
    main.addEventListener('mousemove', move);
    main.addEventListener('click', ()=> openLightbox(img.src, img.alt));
  }

  function move(e) {
    const r  = main.getBoundingClientRect();
    const lw = lens.offsetWidth, lh = lens.offsetHeight;
    const rw = res.offsetWidth,  rh = res.offsetHeight;
    let x = e.clientX - r.left - lw/2;
    let y = e.clientY - r.top  - lh/2;
    x = Math.max(0, Math.min(x, r.width  - lw));
    y = Math.max(0, Math.min(y, r.height - lh));
    lens.style.left = x+'px'; lens.style.top = y+'px';
    const sx = (img.naturalWidth  || img.offsetWidth)  / img.offsetWidth;
    const sy = (img.naturalHeight || img.offsetHeight) / img.offsetHeight;
    const bw = img.offsetWidth * sx * ZOOM;
    const bh = img.offsetHeight * sy * ZOOM;
    const bx = -(x * sx * ZOOM - (rw - lw*ZOOM)/2);
    const by = -(y * sy * ZOOM - (rh - lh*ZOOM)/2);
    inner.style.backgroundSize     = `${bw}px ${bh}px`;
    inner.style.backgroundPosition = `${bx}px ${by}px`;
  }

  if (img.complete) init(); else img.addEventListener('load', init);

  /* --- Fullscreen Lightbox --- */
  let _s=1,_drag=false,_ox=0,_oy=0,_tx=0,_ty=0;
  window.openLightbox = (src,alt)=>{
    document.getElementById('lbImg').src = src;
    document.getElementById('lbCaption').textContent = alt||'';
    document.getElementById('gz-lightbox').style.display = 'flex';
    document.body.style.overflow='hidden'; lbReset();
  };
  window.closeLightbox = ()=>{
    document.getElementById('gz-lightbox').style.display='none';
    document.body.style.overflow='';
  };
  window.closeLightboxOnBg = e=>{
    if(e.target===document.getElementById('gz-lightbox')||e.target===document.getElementById('lbCont')) closeLightbox();
  };
  window.lbZoom  = d=>{ _s=Math.min(6,Math.max(.5,_s+d)); lbApply(); };
  window.lbReset = ()=>{ _s=1;_tx=0;_ty=0; lbApply(); };
  function lbApply(){
    document.getElementById('lbImg').style.transform=`translate(${_tx}px,${_ty}px) scale(${_s})`;
    document.getElementById('lbBadge').textContent=Math.round(_s*100)+'%';
  }
  document.addEventListener('keydown',e=>{
    if(document.getElementById('gz-lightbox').style.display==='none') return;
    if(e.key==='Escape') closeLightbox();
    if(e.key==='+'||e.key==='=') lbZoom(.3);
    if(e.key==='-') lbZoom(-.3);
    if(e.key==='0') lbReset();
  });
  document.getElementById('lbCont').addEventListener('wheel',e=>{
    e.preventDefault(); lbZoom(e.deltaY<0?.2:-.2);
  },{passive:false});
  const lbc=document.getElementById('lbCont');
  lbc.addEventListener('mousedown',e=>{ if(_s<=1)return; _drag=true;_ox=e.clientX-_tx;_oy=e.clientY-_ty; lbc.style.cursor='grabbing'; });
  document.addEventListener('mousemove',e=>{ if(!_drag)return; _tx=e.clientX-_ox;_ty=e.clientY-_oy; lbApply(); });
  document.addEventListener('mouseup',()=>{ _drag=false; lbc.style.cursor='grab'; });
})();
</script>