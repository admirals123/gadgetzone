<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/currency.php';
requireAdmin();

$pageTitle = 'Products';
$base = base_path();
$message = '';

$categories = mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM categories ORDER BY name"), MYSQLI_ASSOC);

// ---- Handle Create / Update ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'product') {
    $id          = (int)($_POST['id'] ?? 0);
    $categoryId  = (int)$_POST['category_id'];
    $name        = sanitize($_POST['name']);
    $slug        = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-'));
    $description = trim($_POST['description'] ?? '');
    $price       = (float)$_POST['price'];
    $oldPrice    = (isset($_POST['old_price']) && $_POST['old_price'] !== '') ? (float)$_POST['old_price'] : null;
    $badge       = (isset($_POST['badge']) && $_POST['badge'] !== '') ? $_POST['badge'] : null;
    $stock       = (int)$_POST['stock'];
    $featured    = isset($_POST['featured']) ? 1 : 0;
    $imageUrl    = trim($_POST['image_url'] ?? '');

    // Handle file upload (overrides image_url if a file was uploaded)
    if (!empty($_FILES['image_file']['name'])) {
        $ext = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','webp','gif'], true)) {
            $filename = 'prod_' . uniqid() . '.' . $ext;
            $dest = __DIR__ . '/uploads/' . $filename;
            if (!is_dir(__DIR__ . '/uploads')) {
                mkdir(__DIR__ . '/uploads', 0777, true);
            }
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $dest)) {
                $imageUrl = $base . '/admin/uploads/' . $filename;
            }
        }
    }

    if ($id > 0) {
        $stmt = mysqli_prepare($conn, "UPDATE products SET category_id=?, name=?, slug=?, description=?, price=?, old_price=?, image_url=?, badge=?, stock=?, featured=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "isssddsssii", $categoryId, $name, $slug, $description, $price, $oldPrice, $imageUrl, $badge, $stock, $featured, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $message = 'Product "' . $name . '" updated successfully.';
    } else {
        $slug = $slug . '-' . substr(uniqid(), -4); // ensure uniqueness
        $stmt = mysqli_prepare($conn, "INSERT INTO products (category_id, name, slug, description, price, old_price, image_url, badge, stock, featured) VALUES (?,?,?,?,?,?,?,?,?,?)");
        mysqli_stmt_bind_param($stmt, "isssddsssi", $categoryId, $name, $slug, $description, $price, $oldPrice, $imageUrl, $badge, $stock, $featured);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $message = 'Product "' . $name . '" added to catalog.';
    }
}

// ---- Handle Quick Stock Update ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'quick_stock') {
    $id = (int)$_POST['id'];
    $stock = max(0, (int)$_POST['stock']);
    $stmt = mysqli_prepare($conn, "UPDATE products SET stock = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $stock, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $message = 'Stock updated to ' . $stock . ' units.';
}

// ---- Handle Delete ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'delete_product') {
    $id = (int)$_POST['id'];
    $stmt = mysqli_prepare($conn, "DELETE FROM products WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $message = 'Product deleted.';
}

// ---- Handle Featured Toggle ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'toggle_featured') {
    $id = (int)$_POST['id'];
    $stmt = mysqli_prepare($conn, "UPDATE products SET featured = NOT featured WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header('Location: ' . $base . '/admin/products.php?' . http_build_query($_GET));
    exit;
}

// ---- Filters: search, category, quick filter (low_stock, featured) ----
$search = trim($_GET['search'] ?? '');
$catFilter = (int)($_GET['category'] ?? 0);
$quickFilter = $_GET['filter'] ?? '';

$where = [];
$types = '';
$params = [];

if ($search !== '') {
    $where[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $types .= 'ss';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($catFilter > 0) {
    $where[] = "p.category_id = ?";
    $types .= 'i';
    $params[] = $catFilter;
}
if ($quickFilter === 'low_stock') {
    $where[] = "p.stock < 5";
} elseif ($quickFilter === 'featured') {
    $where[] = "p.featured = 1";
} elseif ($quickFilter === 'out_of_stock') {
    $where[] = "p.stock = 0";
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "SELECT p.*, c.name AS category_name FROM products p
        LEFT JOIN categories c ON c.id = p.category_id $whereSql ORDER BY p.created_at DESC";

if ($params) {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $products = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
} else {
    $products = mysqli_fetch_all(mysqli_query($conn, $sql), MYSQLI_ASSOC);
}

// Counts for quick tabs
$counts = mysqli_fetch_assoc(mysqli_query($conn, "SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN stock < 5 THEN 1 ELSE 0 END) AS low_stock,
    SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) AS out_of_stock,
    SUM(CASE WHEN featured = 1 THEN 1 ELSE 0 END) AS featured
FROM products"));

require_once __DIR__ . '/layout.php';
?>

<?php if ($message): ?>
  <div class="alert alert-success">✅ <?= e($message) ?></div>
<?php endif; ?>

<div class="admin-panel">
  <div class="admin-panel-head">
    <div>
      <h3 style="margin-bottom:2px;">Product Catalog</h3>
      <span style="color:var(--text2); font-size:13px;">Manage inventory, pricing, and showcase status</span>
    </div>
    <button class="btn-primary btn-sm" data-open-modal="productModal" data-new-product>
      + Add New Product
    </button>
  </div>

  <!-- Quick Filter Tabs -->
  <div class="admin-filter-tabs">
    <a href="<?= $base ?>/admin/products.php" class="tab-item <?= empty($quickFilter) ? 'active' : '' ?>">
      All Products <span class="tab-badge"><?= (int)$counts['total'] ?></span>
    </a>
    <a href="<?= $base ?>/admin/products.php?filter=low_stock" class="tab-item <?= $quickFilter==='low_stock' ? 'active' : '' ?>">
      ⚠️ Low Stock (<5) <span class="tab-badge" style="background:rgba(239,68,68,.2); color:#f87171;"><?= (int)$counts['low_stock'] ?></span>
    </a>
    <a href="<?= $base ?>/admin/products.php?filter=featured" class="tab-item <?= $quickFilter==='featured' ? 'active' : '' ?>">
      ⭐ Featured <span class="tab-badge"><?= (int)$counts['featured'] ?></span>
    </a>
    <?php if ((int)$counts['out_of_stock'] > 0): ?>
    <a href="<?= $base ?>/admin/products.php?filter=out_of_stock" class="tab-item <?= $quickFilter==='out_of_stock' ? 'active' : '' ?>">
      ❌ Out of Stock <span class="tab-badge" style="background:rgba(239,68,68,.3); color:#f87171;"><?= (int)$counts['out_of_stock'] ?></span>
    </a>
    <?php endif; ?>
  </div>

  <!-- Search & Category Filter Form -->
  <form method="GET" class="admin-filter-bar">
    <?php if ($quickFilter): ?><input type="hidden" name="filter" value="<?= e($quickFilter) ?>"><?php endif; ?>
    <input type="text" name="search" placeholder="🔍 Search product name, description..." value="<?= e($search) ?>">
    <select name="category" onchange="this.form.submit()">
      <option value="0">All Categories</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= $catFilter === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn-outline btn-sm">Search</button>
    <?php if ($search !== '' || $catFilter > 0 || $quickFilter !== ''): ?>
      <a href="<?= $base ?>/admin/products.php" class="btn-outline btn-sm">Reset</a>
    <?php endif; ?>
  </form>

  <?php if (empty($products)): ?>
    <div class="admin-empty">
      <div style="font-size:36px; margin-bottom:12px;">📦</div>
      <p>No products match your current search or category filters.</p>
      <a href="<?= $base ?>/admin/products.php" class="btn-outline btn-sm" style="margin-top:12px;">View All Products</a>
    </div>
  <?php else: ?>
  <div style="overflow-x: auto;">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Image</th>
          <th>Product Name</th>
          <th>Category</th>
          <th>Price</th>
          <th>Old Price</th>
          <th>Stock</th>
          <th>Badge</th>
          <th>Featured</th>
          <th>Store View</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($products as $p): ?>
        <tr>
          <td><img src="<?= e($p['image_url']) ?>" class="thumb" alt=""></td>
          <td>
            <strong><?= e($p['name']) ?></strong>
            <div style="font-size:11px; color:var(--text2);"><?= e($p['slug']) ?></div>
          </td>
          <td><span style="color:var(--text2);"><?= e($p['category_name']) ?></span></td>
          <td><strong style="color:var(--accent);"><?= formatPrice($p['price']) ?></strong></td>
          <td style="color:var(--text2); font-size:13px;">
            <?= $p['old_price'] ? formatPrice($p['old_price']) : '—' ?>
            <?php if ($p['old_price'] && $p['old_price'] > $p['price']): ?>
              <div style="font-size:10px; color:#4ade80; font-weight:700;">
                -<?= round((($p['old_price'] - $p['price']) / $p['old_price']) * 100) ?>% OFF
              </div>
            <?php endif; ?>
          </td>
          <td>
            <button type="button" class="quick-stock-btn <?= $p['stock'] < 5 ? 'low' : '' ?>"
                    data-open-modal="stockModal_<?= $p['id'] ?>"
                    title="Click to quickly update stock">
              <?= (int)$p['stock'] ?> units <?= $p['stock'] == 0 ? '❌' : ($p['stock'] < 5 ? '⚠️' : '✓') ?>
            </button>
          </td>
          <td>
            <?php if ($p['badge']): ?>
              <span class="badge <?= e($p['badge']) ?>" style="position:static; display:inline-block; font-size:10px; padding:2px 6px;">
                <?= e($p['badge']) ?>
              </span>
            <?php else: ?>
              <span style="color:var(--text2); font-size:12px;">—</span>
            <?php endif; ?>
          </td>
          <td>
            <form method="POST" style="display:inline;">
              <input type="hidden" name="form" value="toggle_featured">
              <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
              <button type="submit" class="star-toggle <?= $p['featured'] ? 'on' : '' ?>" title="<?= $p['featured'] ? 'Remove from featured' : 'Mark as featured' ?>">
                <?= $p['featured'] ? '★' : '☆' ?>
              </button>
            </form>
          </td>
          <td>
            <a href="<?= $base ?>/pages/product.php?slug=<?= urlencode($p['slug']) ?>" target="_blank" class="icon-btn" title="View product in store">
              🔗 View ↗
            </a>
          </td>
          <td class="admin-actions">
            <button class="icon-btn" data-open-modal="productModal" data-edit-product='<?= json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
              ✏️ Edit
            </button>
            <form method="POST" onsubmit="return confirm('Delete \'<?= addslashes($p['name']) ?>\' from catalog?');" style="display:inline;">
              <input type="hidden" name="form" value="delete_product">
              <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
              <button type="submit" class="icon-btn danger">🗑️</button>
            </form>
          </td>
        </tr>

        <!-- Quick Stock Adjustment Modal -->
        <div class="modal-overlay" id="stockModal_<?= $p['id'] ?>">
          <div class="modal-box" style="width:380px;">
            <button class="modal-close" data-close-modal>×</button>
            <h3 style="font-size:16px;">Quick Stock Update</h3>
            <p style="font-size:13px; color:var(--text2); margin-bottom:16px;"><?= e($p['name']) ?></p>

            <form method="POST" action="<?= $base ?>/admin/products.php">
              <input type="hidden" name="form" value="quick_stock">
              <input type="hidden" name="id" value="<?= $p['id'] ?>">

              <div class="form-group">
                <label>Current Stock Units:</label>
                <div style="display:flex; gap:8px; align-items:center;">
                  <button type="button" class="icon-btn" onclick="const el=document.getElementById('qs_<?= $p['id'] ?>'); el.value = Math.max(0, parseInt(el.value||0)-1);">-</button>
                  <input type="number" id="qs_<?= $p['id'] ?>" name="stock" value="<?= (int)$p['stock'] ?>" min="0" max="9999" required style="text-align:center; font-weight:700; font-size:16px;">
                  <button type="button" class="icon-btn" onclick="const el=document.getElementById('qs_<?= $p['id'] ?>'); el.value = parseInt(el.value||0)+1;">+</button>
                </div>
              </div>

              <div style="display:flex; gap:8px; justify-content:flex-end;">
                <button type="button" class="btn-outline btn-sm" data-close-modal>Cancel</button>
                <button type="submit" class="btn-primary btn-sm">Update Stock</button>
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

<!-- Product Add / Edit Modal -->
<div class="modal-overlay" id="productModal">
  <div class="modal-box" style="width:600px;">
    <button class="modal-close" data-close-modal>×</button>
    <h3 id="productModalTitle">Add Product</h3>
    <form method="POST" enctype="multipart/form-data" id="productForm">
      <input type="hidden" name="form" value="product">
      <input type="hidden" name="id" value="">

      <div class="form-group">
        <label>Product Name <span style="color:var(--danger)">*</span></label>
        <input type="text" name="name" id="productNameInput" placeholder="e.g. iPhone 15 Pro Max" required>
      </div>

      <div class="form-group">
        <label>Slug <span style="color:var(--text2); font-weight:400;">(auto-generated for SEO URLs)</span></label>
        <input type="text" name="slug_preview" id="productSlugPreview" placeholder="auto-generated-from-name" readonly style="background:var(--surface); opacity:0.8;">
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>
            Category <span style="color:var(--danger)">*</span>
            <a href="<?= $base ?>/admin/categories.php" target="_blank" style="float:right; font-size:11px; color:var(--accent); font-weight:600;">+ New Category ↗</a>
          </label>
          <select name="category_id" required>
            <?php foreach ($categories as $c): ?>
              <option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Badge Highlight</label>
          <select name="badge">
            <option value="">None</option>
            <option value="NEW">🟢 NEW</option>
            <option value="HOT">🔴 HOT</option>
            <option value="SALE">🟡 SALE</option>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Selling Price (in base ₹) <span style="color:var(--danger)">*</span></label>
          <input type="number" step="0.01" name="price" id="prodPriceInput" placeholder="29999" required>
        </div>
        <div class="form-group">
          <label>Original Price (Strike-through)</label>
          <input type="number" step="0.01" name="old_price" id="prodOldPriceInput" placeholder="34999">
          <div id="discountPreviewTag" style="font-size:11px; color:#4ade80; font-weight:700; margin-top:4px; display:none;"></div>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Inventory Stock <span style="color:var(--danger)">*</span></label>
          <input type="number" name="stock" value="10" min="0" required>
        </div>
        <div class="form-group" style="display:flex; align-items:center; gap:8px; margin-top:28px;">
          <input type="checkbox" name="featured" id="featuredCheck" style="width:18px; height:18px; accent-color:var(--accent);">
          <label for="featuredCheck" style="margin:0; cursor:pointer;">⭐ Showcase on Home Page</label>
        </div>
      </div>

      <div class="form-group">
        <label>Description & Specifications</label>
        <textarea name="description" rows="3" placeholder="Enter key features and specs..."></textarea>
      </div>

      <div class="form-group">
        <label>Image URL (or select file below)</label>
        <input type="text" name="image_url" id="prodImageUrlInput" placeholder="https://images.unsplash.com/...">
      </div>

      <div class="form-group">
        <label>Upload Image File</label>
        <input type="file" name="image_file" id="productImageInput" accept="image/*">
        <div style="margin-top:10px;">
          <img id="productImagePreview" style="display:none; width:80px; height:80px; object-fit:cover; border-radius:8px; border:1px solid var(--border);" alt="Preview">
        </div>
      </div>

      <div style="display:flex; gap:10px; margin-top:20px;">
        <button type="button" class="btn-outline btn-full" data-close-modal>Cancel</button>
        <button type="submit" class="btn-primary btn-full">Save Product</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
