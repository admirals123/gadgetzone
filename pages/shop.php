<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/currency.php';

$base = base_path();
$pageTitle = 'Shop';

// ---- Read filters from GET ----
$catSlug   = $_GET['cat'] ?? '';
$search    = trim($_GET['search'] ?? '');
$sort      = $_GET['sort'] ?? 'newest';
$badge     = $_GET['badge'] ?? '';
$minPrice  = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$maxPrice  = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 300000;
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 9;

// ---- Categories (with counts) for sidebar ----
$categories = [];
$catRes = mysqli_query($conn, "SELECT c.*, COUNT(p.id) AS product_count
                                FROM categories c LEFT JOIN products p ON p.category_id = c.id
                                GROUP BY c.id ORDER BY c.id");
while ($row = mysqli_fetch_assoc($catRes)) $categories[] = $row;

// ---- Build dynamic WHERE clause ----
$where = ["p.price BETWEEN ? AND ?"];
$types = "dd";
$params = [$minPrice, $maxPrice];
$activeCategoryName = null;

if ($catSlug !== '') {
    $where[] = "c.slug = ?";
    $types .= "s";
    $params[] = $catSlug;
    foreach ($categories as $c) if ($c['slug'] === $catSlug) $activeCategoryName = $c['name'];
}
if ($search !== '') {
    $where[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $types .= "ss";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($badge !== '' && in_array($badge, ['NEW','HOT','SALE'], true)) {
    $where[] = "p.badge = ?";
    $types .= "s";
    $params[] = $badge;
}

$whereSql = implode(' AND ', $where);

// ---- Sort ----
$orderBy = match ($sort) {
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'popular'    => 'p.featured DESC, p.id DESC',
    'rating'     => 'p.featured DESC, p.id DESC', // no ratings table in schema — fallback
    default      => 'p.created_at DESC',
};

// ---- Count total ----
$countSql = "SELECT COUNT(*) AS total FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE $whereSql";
$stmt = mysqli_prepare($conn, $countSql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$totalRecords = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];
mysqli_stmt_close($stmt);

$totalPages = max(1, (int)ceil($totalRecords / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

// ---- Fetch products ----
$sql = "SELECT p.*, c.name AS category_name FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE $whereSql ORDER BY $orderBy LIMIT ? OFFSET ?";
$typesFull = $types . "ii";
$paramsFull = array_merge($params, [$perPage, $offset]);
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $typesFull, ...$paramsFull);
mysqli_stmt_execute($stmt);
$products = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// ---- Helper to build query string, preserving filters ----
function buildQuery($overrides = []) {
    $params = array_merge($_GET, $overrides);
    foreach ($params as $k => $v) if ($v === '' || $v === null) unset($params[$k]);
    return http_build_query($params);
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
  <div class="breadcrumb"><a href="<?= $base ?>/index.php">Home</a> &rsaquo; Shop<?= $activeCategoryName ? ' &rsaquo; ' . e($activeCategoryName) : '' ?></div>

  <div class="shop-layout">
    <!-- SIDEBAR FILTERS -->
    <aside class="filters-sidebar">
      <form method="GET" id="filterForm">
        <?php if ($search !== ''): ?><input type="hidden" name="search" value="<?= e($search) ?>"><?php endif; ?>
        <?php if ($sort !== 'newest'): ?><input type="hidden" name="sort" value="<?= e($sort) ?>"><?php endif; ?>
        <?php if ($badge !== ''): ?><input type="hidden" name="badge" value="<?= e($badge) ?>"><?php endif; ?>

        <h3>Category</h3>
        <div class="filter-group">
          <label class="radio"><input type="radio" name="cat" value="" onchange="this.form.submit()" <?= $catSlug === '' ? 'checked' : '' ?>> All</label>
          <?php foreach ($categories as $c): ?>
          <label class="radio"><input type="radio" name="cat" value="<?= e($c['slug']) ?>" onchange="this.form.submit()" <?= $catSlug === $c['slug'] ? 'checked' : '' ?>> <?= e($c['name']) ?> (<?= (int)$c['product_count'] ?>)</label>
          <?php endforeach; ?>
        </div>

        <h3>Price Range</h3>
        <div class="filter-group">
          <input type="range" name="max_price" min="0" max="300000" step="1000" value="<?= (int)$maxPrice ?>"
                 oninput="document.getElementById('priceLabel').textContent = this.value">
          <div style="color:var(--text2); font-size:13px; margin-top:8px;">Up to <?= e(getActiveCurrency()['symbol']) ?> <span id="priceLabel"><?= number_format((int)$maxPrice) ?></span></div>
        </div>

        <button type="submit" class="btn-primary btn-full" style="margin-bottom:10px;">Apply Filters</button>
        <a href="<?= $base ?>/pages/shop.php" class="btn-outline btn-full" style="text-align:center;">Clear All</a>
      </form>
    </aside>

    <!-- MAIN CONTENT -->
    <div>
      <div class="shop-header">
        <div style="color:var(--text2); font-size:14px;">
          Showing <?= $totalRecords > 0 ? ($offset + 1) : 0 ?>-<?= min($offset + $perPage, $totalRecords) ?> of <?= $totalRecords ?> results
          <?= $activeCategoryName ? 'in ' . e($activeCategoryName) : '' ?>
        </div>
        <form method="GET" id="sortForm">
          <?php foreach (['cat'=>$catSlug,'search'=>$search,'badge'=>$badge,'max_price'=>$maxPrice] as $k=>$v): if ($v !== '' && $v !== 0.0): ?>
            <input type="hidden" name="<?= $k ?>" value="<?= e((string)$v) ?>">
          <?php endif; endforeach; ?>
          <select name="sort" onchange="this.form.submit()" style="background:var(--surface2); color:var(--text); border:1px solid var(--border); border-radius:8px; padding:10px 14px;">
            <option value="newest" <?= $sort==='newest'?'selected':'' ?>>Newest</option>
            <option value="popular" <?= $sort==='popular'?'selected':'' ?>>Most Popular</option>
            <option value="rating" <?= $sort==='rating'?'selected':'' ?>>Top Rated</option>
            <option value="price_asc" <?= $sort==='price_asc'?'selected':'' ?>>Price Low-High</option>
            <option value="price_desc" <?= $sort==='price_desc'?'selected':'' ?>>Price High-Low</option>
          </select>
        </form>
      </div>

      <?php if (empty($products)): ?>
        <div class="empty-state">
          <div class="icon">🔍</div>
          <h3>No products found</h3>
          <p style="margin:10px 0 20px;">Try adjusting your filters or search terms.</p>
          <a href="<?= $base ?>/pages/shop.php" class="btn-primary">Clear Filters</a>
        </div>
      <?php else: ?>
        <div class="product-grid">
          <?php foreach ($products as $p):
            $discount = $p['old_price'] ? round((($p['old_price'] - $p['price']) / $p['old_price']) * 100) : 0;
            $isLowStock = ((int)$p['stock'] < 10 && (int)$p['stock'] > 0);
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
          <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="pagination">
          <a href="?<?= buildQuery(['page' => max(1, $page - 1)]) ?>">&laquo;</a>
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?<?= buildQuery(['page' => $i]) ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
          <?php endfor; ?>
          <a href="?<?= buildQuery(['page' => min($totalPages, $page + 1)]) ?>">&raquo;</a>
        </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
