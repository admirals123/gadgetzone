<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/currency.php';

$base = base_path();
$pageTitle = 'Home';

// Categories with product counts
$categories = [];
$catResult = mysqli_query($conn, "SELECT c.*, COUNT(p.id) AS product_count
                                   FROM categories c
                                   LEFT JOIN products p ON p.category_id = c.id
                                   GROUP BY c.id ORDER BY c.id");
while ($row = mysqli_fetch_assoc($catResult)) $categories[] = $row;

// Featured products (6)
$featured = [];
$fResult = mysqli_query($conn, "SELECT p.*, c.name AS category_name FROM products p
                                 LEFT JOIN categories c ON c.id = p.category_id
                                 WHERE p.featured = 1 ORDER BY p.created_at DESC LIMIT 6");
while ($row = mysqli_fetch_assoc($fResult)) $featured[] = $row;

// New arrivals (4 newest)
$newArrivals = [];
$nResult = mysqli_query($conn, "SELECT p.*, c.name AS category_name FROM products p
                                 LEFT JOIN categories c ON c.id = p.category_id
                                 ORDER BY p.created_at DESC LIMIT 4");
while ($row = mysqli_fetch_assoc($nResult)) $newArrivals[] = $row;

// Deal of the day: pick the product with the biggest discount
$deal = null;
$dResult = mysqli_query($conn, "SELECT p.*, c.name AS category_name FROM products p
                                 LEFT JOIN categories c ON c.id = p.category_id
                                 WHERE p.old_price IS NOT NULL
                                 ORDER BY (p.old_price - p.price) DESC LIMIT 1");
if ($dResult) $deal = mysqli_fetch_assoc($dResult);

function renderProductCard($p) {
    global $base;
    $discount = $p['old_price'] ? round((($p['old_price'] - $p['price']) / $p['old_price']) * 100) : 0;
    ?>
    <?php $isLowStock = ((int)$p['stock'] < 10 && (int)$p['stock'] > 0); ?>
    <div class="product-card">
        <?php if ($p['badge']): ?><span class="badge <?= e($p['badge']) ?>"><?= e($p['badge']) ?></span><?php endif; ?>
        <a href="<?= $base ?>/pages/product.php?slug=<?= urlencode($p['slug']) ?>" class="product-img">
            <img src="<?= e($p['image_url']) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
        </a>
        <div class="product-body">
            <div>
                <div class="product-cat"><?= e($p['category_name'] ?? '') ?></div>
                <a href="<?= $base ?>/pages/product.php?slug=<?= urlencode($p['slug']) ?>">
                    <div class="product-name"><?= e($p['name']) ?></div>
                </a>
                <div class="product-rating">★★★★★ <span>(4.8)</span></div>
                <?php if ($isLowStock): ?>
                    <div class="card-urgency">⚡ Only <?= (int)$p['stock'] ?> left in stock!</div>
                <?php else: ?>
                    <div class="card-delivery">🚚 FREE Express Delivery</div>
                <?php endif; ?>
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
    <?php
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- HERO -->
<section class="hero">
  <div class="container hero-inner">
    <div class="hero-content">
      <h1 class="hero-headline">Your World.<br>Next-Level Technology.</h1>
      <p class="hero-desc">Discover premium gadgets — smartphones, laptops, audio and more — curated for people who expect the best.</p>
      <div class="hero-ctas">
        <a href="<?= $base ?>/pages/shop.php" class="btn-primary btn-lg">Shop Now</a>
        <a href="<?= $base ?>/pages/shop.php?badge=SALE" class="btn-outline btn-lg">Explore Deals</a>
      </div>
      <div class="hero-stats">
        <div class="hero-stat"><strong>500+</strong><span>Products</span></div>
        <div class="hero-stat"><strong>50K+</strong><span>Happy Customers</span></div>
        <div class="hero-stat"><strong>4.9★</strong><span>Average Rating</span></div>
      </div>
    </div>
    <div class="hero-media">
      <img src="https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=900&q=80" alt="Featured gadget">
      <div class="hero-badge">🔥 Hot Deal Today — Up to 40% Off</div>
    </div>
  </div>
</section>

<!-- FEATURE STRIP -->
<section class="feature-strip">
  <div class="container feature-strip-inner">
    <div class="feature-item"><span class="icon">🚚</span><div><strong>Fast Delivery</strong><span>Nationwide shipping</span></div></div>
    <div class="feature-item"><span class="icon">🔒</span><div><strong>Secure Payment</strong><span>100% protected checkout</span></div></div>
    <div class="feature-item"><span class="icon">↩️</span><div><strong>No Returns</strong><span>All sales are final</span></div></div>
    <div class="feature-item"><span class="icon">🎧</span><div><strong>24/7 Support</strong><span>We're always here</span></div></div>
    <div class="feature-item"><span class="icon">🏷️</span><div><strong>Best Prices</strong><span>Guaranteed value</span></div></div>
  </div>
</section>

<!-- CATEGORY GRID -->
<section class="section">
  <div class="container">
    <h2 class="section-title">Shop by Category</h2>
    <p class="section-sub">Find exactly what you're looking for</p>
    <div class="category-grid">
      <?php foreach ($categories as $cat): ?>
      <a href="<?= $base ?>/pages/shop.php?cat=<?= urlencode($cat['slug']) ?>" class="category-card">
        <div class="emoji"><?= e($cat['icon']) ?></div>
        <strong><?= e($cat['name']) ?></strong>
        <span><?= (int)$cat['product_count'] ?> items</span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- FEATURED PRODUCTS -->
<section class="section">
  <div class="container">
    <div class="section-head">
      <div>
        <h2 class="section-title">Featured Products</h2>
        <p class="section-sub">Hand-picked gadgets our customers love</p>
      </div>
      <a href="<?= $base ?>/pages/shop.php" class="btn-outline">View All</a>
    </div>
    <div class="product-grid">
      <?php foreach ($featured as $p) renderProductCard($p); ?>
    </div>
  </div>
</section>

<!-- DEAL OF THE DAY -->
<?php if ($deal): ?>
<section class="section">
  <div class="container">
    <div class="deal-banner">
      <div class="deal-info">
        <span class="badge <?= e($deal['badge']) ?>" style="position:static;display:inline-block;"><?= e($deal['badge']) ?></span>
        <h2 style="margin-top:12px;"><?= e($deal['name']) ?></h2>
        <p style="color:var(--text2);margin-top:8px;">Deal of the Day — grab it before it's gone.</p>
        <div class="deal-timer" id="dealTimer">
          <div class="unit"><strong class="hh">00</strong><span>HRS</span></div>
          <div class="unit"><strong class="mm">00</strong><span>MIN</span></div>
          <div class="unit"><strong class="ss">00</strong><span>SEC</span></div>
        </div>
        <div class="deal-price"><?= formatPrice($deal['price']) ?> <span class="old" style="font-size:16px;"><?= formatPrice($deal['old_price']) ?></span></div>
        <div class="deal-actions">
          <button class="btn-primary add-to-cart-btn" data-id="<?= (int)$deal['id'] ?>">Add to Cart</button>
          <a href="<?= $base ?>/pages/shop.php" class="btn-outline">View Shop</a>
        </div>
      </div>
      <div class="deal-img">
        <img src="<?= e($deal['image_url']) ?>" alt="<?= e($deal['name']) ?>">
      </div>
      <div class="deal-meta">
        <div class="product-rating" style="font-size:16px;">★★★★★ <span style="color:var(--text2)">4.9 (2.1k reviews)</span></div>
        <p style="color:var(--text2); margin-top:12px;">🚚 Free delivery on orders over <?= formatPrice(5000) ?></p>
        <p style="color:var(--text2); margin-top:8px;"><?= (int)$deal['stock'] ?> left in stock — order soon</p>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- NEW ARRIVALS -->
<section class="section">
  <div class="container">
    <div class="section-head">
      <div>
        <h2 class="section-title">New Arrivals</h2>
        <p class="section-sub">Just landed — the latest tech</p>
      </div>
      <a href="<?= $base ?>/pages/shop.php?sort=newest" class="btn-outline">View All</a>
    </div>
    <div class="product-grid cols-4">
      <?php foreach ($newArrivals as $p) renderProductCard($p); ?>
    </div>
  </div>
</section>

<!-- TESTIMONIALS -->
<section class="section">
  <div class="container">
    <h2 class="section-title">What Our Customers Say</h2>
    <p class="section-sub">Real reviews from real gadget lovers</p>
    <div class="testimonial-grid">
      <?php
      $testimonials = [
        ['name' => 'Rahim Ahmed', 'loc' => 'Dhaka, BD', 'text' => 'Fast delivery and the product was exactly as described. GadgetZone is now my go-to store for tech.'],
        ['name' => 'Nusrat Jahan', 'loc' => 'Chittagong, BD', 'text' => 'Amazing prices and the customer support team helped me pick the right laptop. Highly recommend!'],
        ['name' => 'Kamal Hossain', 'loc' => 'Sylhet, BD', 'text' => 'Bought my headphones here — top quality and genuine products. Will definitely shop again.'],
      ];
      foreach ($testimonials as $t):
          $initials = strtoupper(substr($t['name'], 0, 1) . substr(explode(' ', $t['name'])[1] ?? '', 0, 1));
      ?>
      <div class="testimonial-card">
        <div class="testimonial-stars">★★★★★</div>
        <p class="testimonial-text">"<?= e($t['text']) ?>"</p>
        <div class="testimonial-user">
          <div class="avatar"><?= e($initials) ?></div>
          <div><strong><?= e($t['name']) ?></strong><span><?= e($t['loc']) ?></span></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- NEWSLETTER -->
<section class="newsletter">
  <div class="container">
    <h2>Get Exclusive Deals First 🎉</h2>
    <p>Subscribe to our newsletter and never miss a deal.</p>
    <form class="newsletter-form" onsubmit="event.preventDefault(); this.querySelector('button').textContent='Subscribed ✓';">
      <input type="email" placeholder="Enter your email" required>
      <button type="submit">Subscribe</button>
    </form>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
