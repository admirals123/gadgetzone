<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/currency.php';
requireAdmin();

$pageTitle = 'Categories';
$base = base_path();
$message = '';
$error = '';

// ---- Handle Create / Update Category ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'category') {
    $id    = (int)($_POST['id'] ?? 0);
    $name  = sanitize($_POST['name'] ?? '');
    $slug  = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $_POST['slug'] ?: $name), '-'));
    $icon  = trim($_POST['icon'] ?: '📦');

    if (empty($name)) {
        $error = 'Category name cannot be empty.';
    } else {
        if ($id > 0) {
            // Check if another category has this slug
            $stmt = mysqli_prepare($conn, "SELECT id FROM categories WHERE slug = ? AND id != ?");
            mysqli_stmt_bind_param($stmt, "si", $slug, $id);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $dup = $res ? mysqli_fetch_assoc($res) : null;
            mysqli_stmt_close($stmt);

            if ($dup) {
                $error = 'A category with this URL slug already exists. Please choose another.';
            } else {
                $stmt = mysqli_prepare($conn, "UPDATE categories SET name = ?, slug = ?, icon = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "sssi", $name, $slug, $icon, $id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $message = 'Category "' . $name . '" updated successfully.';
            }
        } else {
            // Check if slug exists
            $stmt = mysqli_prepare($conn, "SELECT id FROM categories WHERE slug = ?");
            mysqli_stmt_bind_param($stmt, "s", $slug);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $dup = $res ? mysqli_fetch_assoc($res) : null;
            mysqli_stmt_close($stmt);

            if ($dup) {
                $slug = $slug . '-' . substr(uniqid(), -3);
            }

            $stmt = mysqli_prepare($conn, "INSERT INTO categories (name, slug, icon) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sss", $name, $slug, $icon);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $message = 'Category "' . $name . '" created successfully.';
        }
    }
}

// ---- Handle Delete Category ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'delete_category') {
    $id = (int)$_POST['id'];
    $reassignTo = (int)($_POST['reassign_to'] ?? 0);
    $deleteProducts = isset($_POST['delete_products']) && $_POST['delete_products'] == '1';

    // Get category info
    $catInfo = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM categories WHERE id = $id"));

    if (!$catInfo) {
        $error = 'Category not found.';
    } else {
        // Count products in this category
        $prodCount = 0;
        $countRes = mysqli_query($conn, "SELECT COUNT(*) AS c FROM products WHERE category_id = $id");
        if ($countRes) {
            $prodCount = (int)mysqli_fetch_assoc($countRes)['c'];
        }

        if ($prodCount > 0) {
            if ($reassignTo > 0 && $reassignTo !== $id) {
                // Reassign products to the selected category
                mysqli_query($conn, "UPDATE products SET category_id = $reassignTo WHERE category_id = $id");
                mysqli_query($conn, "DELETE FROM categories WHERE id = $id");
                $message = 'Category "' . $catInfo['name'] . '" deleted. Its ' . $prodCount . ' product(s) were reassigned.';
            } elseif ($deleteProducts) {
                // Delete products and category
                mysqli_query($conn, "DELETE FROM products WHERE category_id = $id");
                mysqli_query($conn, "DELETE FROM categories WHERE id = $id");
                $message = 'Category "' . $catInfo['name'] . '" and its ' . $prodCount . ' product(s) were deleted.';
            } else {
                // By default if no products were attached or default reassignment
                mysqli_query($conn, "DELETE FROM categories WHERE id = $id");
                $message = 'Category "' . $catInfo['name'] . '" deleted successfully.';
            }
        } else {
            // No products attached — delete immediately
            mysqli_query($conn, "DELETE FROM categories WHERE id = $id");
            $message = 'Category "' . $catInfo['name'] . '" deleted successfully.';
        }
    }
}

// Search
$search = trim($_GET['search'] ?? '');
$where = '';
if ($search !== '') {
    $safeSearch = mysqli_real_escape_string($conn, $search);
    $where = "WHERE name LIKE '%$safeSearch%' OR slug LIKE '%$safeSearch%'";
}

$categories = mysqli_fetch_all(mysqli_query($conn, "SELECT c.*,
    (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS product_count
    FROM categories c $where ORDER BY c.name ASC"), MYSQLI_ASSOC);

require_once __DIR__ . '/layout.php';
?>

<?php if ($message): ?><div class="alert alert-success">✅ <?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error">⚠️ <?= e($error) ?></div><?php endif; ?>

<div class="admin-panel">
  <div class="admin-panel-head">
    <div>
      <h3 style="margin-bottom:2px;">Category Management</h3>
      <span style="color:var(--text2); font-size:13px;">Create, edit, organize emoji icons, or remove product categories</span>
    </div>
    <button type="button" class="btn-primary btn-sm" data-open-modal="categoryModal" data-new-category>
      + Add New Category
    </button>
  </div>

  <!-- Search Filter -->
  <form method="GET" action="<?= $base ?>/admin/categories.php" class="admin-filter-bar">
    <input type="text" name="search" placeholder="🔍 Search category name, slug..." value="<?= e($search) ?>">
    <button type="submit" class="btn-outline btn-sm">Search</button>
    <?php if ($search !== ''): ?>
      <a href="<?= $base ?>/admin/categories.php" class="btn-outline btn-sm">Reset</a>
    <?php endif; ?>
  </form>

  <?php if (empty($categories)): ?>
    <div class="admin-empty">
      <div style="font-size:36px; margin-bottom:12px;">🏷️</div>
      <p>No categories found matching your search.</p>
      <a href="<?= $base ?>/admin/categories.php" class="btn-outline btn-sm" style="margin-top:12px;">View All Categories</a>
    </div>
  <?php else: ?>
  <div style="overflow-x: auto;">
    <table class="admin-table">
      <thead>
        <tr>
          <th style="width:70px;">Icon</th>
          <th>Category Name</th>
          <th>URL Slug</th>
          <th>Products Count</th>
          <th>Store Link</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($categories as $c): ?>
        <tr>
          <td>
            <div style="font-size:26px; width:44px; height:44px; background:var(--surface2); border:1px solid var(--border); border-radius:8px; display:flex; align-items:center; justify-content:center;">
              <?= e($c['icon'] ?: '📦') ?>
            </div>
          </td>
          <td>
            <strong style="font-size:15px;"><?= e($c['name']) ?></strong>
          </td>
          <td>
            <code style="background:var(--surface2); padding:3px 8px; border-radius:4px; font-size:12px; color:var(--accent);">
              <?= e($c['slug']) ?>
            </code>
          </td>
          <td>
            <?php if ((int)$c['product_count'] > 0): ?>
              <a href="<?= $base ?>/admin/products.php?category=<?= (int)$c['id'] ?>" class="role-badge" style="background:rgba(245,158,11,.15); color:var(--accent);" title="View products in this category">
                📦 <?= (int)$c['product_count'] ?> product(s) →
              </a>
            <?php else: ?>
              <span class="role-badge" style="opacity:0.6;">0 products</span>
            <?php endif; ?>
          </td>
          <td>
            <a href="<?= $base ?>/pages/shop.php?cat=<?= urlencode($c['slug']) ?>" target="_blank" class="icon-btn" title="View category in store">
              🔗 View in Shop ↗
            </a>
          </td>
          <td class="admin-actions">
            <button type="button" class="icon-btn" data-open-modal="categoryModal" data-edit-category='<?= json_encode($c, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
              ✏️ Edit
            </button>
            <button type="button" class="icon-btn danger" data-open-modal="deleteModal_<?= $c['id'] ?>" title="Delete category">
              🗑️ Delete
            </button>
          </td>
        </tr>

        <!-- Delete Category Confirmation Modal -->
        <div class="modal-overlay" id="deleteModal_<?= $c['id'] ?>">
          <div class="modal-box" style="width:460px;">
            <button type="button" class="modal-close" data-close-modal>×</button>
            <h3 style="color:#f87171; margin-bottom:8px;">🗑️ Delete Category</h3>
            <p style="font-size:14px; margin-bottom:16px;">
              Are you sure you want to delete category <strong>"<?= e($c['name']) ?>"</strong>?
            </p>

            <form method="POST" action="<?= $base ?>/admin/categories.php">
              <input type="hidden" name="form" value="delete_category">
              <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">

              <?php if ((int)$c['product_count'] > 0): ?>
                <div style="background:rgba(239,68,68,.1); border:1px solid rgba(239,68,68,.3); border-radius:8px; padding:12px; margin-bottom:16px; font-size:13px; color:#fca5a5;">
                  ⚠️ This category currently has <strong><?= (int)$c['product_count'] ?> product(s)</strong> attached.
                </div>

                <div class="form-group" style="margin-bottom:16px;">
                  <label style="font-size:13px; font-weight:700;">What would you like to do with its products?</label>
                  
                  <div style="margin-bottom:10px;">
                    <label class="radio" style="display:flex; align-items:center; gap:8px; font-size:13px; cursor:pointer;">
                      <input type="radio" name="reassign_option" value="reassign" checked onchange="document.getElementById('reassignSelect_<?= $c['id'] ?>').disabled=false; document.getElementById('delProdFlag_<?= $c['id'] ?>').value='0';">
                      Move products to another category:
                    </label>
                    <select name="reassign_to" id="reassignSelect_<?= $c['id'] ?>" style="margin-top:6px; width:100%;">
                      <?php foreach ($categories as $otherCat): if ((int)$otherCat['id'] === (int)$c['id']) continue; ?>
                        <option value="<?= (int)$otherCat['id'] ?>"><?= e($otherCat['icon'] . ' ' . $otherCat['name']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div>
                    <label class="radio" style="display:flex; align-items:center; gap:8px; font-size:13px; color:#f87171; cursor:pointer;">
                      <input type="radio" name="reassign_option" value="delete" onchange="document.getElementById('reassignSelect_<?= $c['id'] ?>').disabled=true; document.getElementById('delProdFlag_<?= $c['id'] ?>').value='1';">
                      Delete all <?= (int)$c['product_count'] ?> attached product(s) permanently
                    </label>
                    <input type="hidden" name="delete_products" id="delProdFlag_<?= $c['id'] ?>" value="0">
                  </div>
                </div>
              <?php endif; ?>

              <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" class="btn-outline btn-sm" data-close-modal>Cancel</button>
                <button type="submit" class="btn-danger btn-sm">Confirm & Delete</button>
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

<!-- Category Add / Edit Modal -->
<div class="modal-overlay" id="categoryModal">
  <div class="modal-box" style="width:480px;">
    <button type="button" class="modal-close" data-close-modal>×</button>
    <h3 id="categoryModalTitle">Add New Category</h3>

    <form method="POST" action="<?= $base ?>/admin/categories.php" id="categoryForm">
      <input type="hidden" name="form" value="category">
      <input type="hidden" name="id" id="catIdInput" value="">

      <div class="form-group">
        <label>Category Name <span style="color:var(--danger)">*</span></label>
        <input type="text" name="name" id="catNameInput" placeholder="e.g. Gaming Consoles, Smart Home, Drones" required>
      </div>

      <div class="form-group">
        <label>URL Slug <span style="color:var(--text2); font-weight:400;">(auto-generated for URL routing)</span></label>
        <input type="text" name="slug" id="catSlugInput" placeholder="e.g. gaming-consoles">
      </div>

      <div class="form-group">
        <label>Emoji Icon <span style="color:var(--text2); font-weight:400;">(Pick below or type custom)</span></label>
        <div style="display:flex; gap:10px; align-items:center; margin-bottom:10px;">
          <input type="text" name="icon" id="catIconInput" value="📦" style="width:60px; text-align:center; font-size:22px;">
          <span style="font-size:12px; color:var(--text2);">Selected Icon Preview</span>
        </div>

        <!-- Quick Emoji Preset Selector -->
        <div style="font-size:12px; color:var(--text2); margin-bottom:6px;">Quick Presets:</div>
        <div class="emoji-preset-grid" style="display:flex; flex-wrap:wrap; gap:8px;">
          <?php 
          $presets = ['📱','💻','🎧','📷','⌚','🔌','🎮','🖥️','🕹️','🔊','🤖','⚡','🖨️','📡','🕶️','🛸','🔋','⌨️','🖱️','📺','🏠','🛡️'];
          foreach ($presets as $emoji): ?>
            <button type="button" class="icon-btn" onclick="document.getElementById('catIconInput').value='<?= $emoji ?>';" style="font-size:18px; width:38px; height:38px; padding:0; display:inline-flex; align-items:center; justify-content:center;">
              <?= $emoji ?>
            </button>
          <?php endforeach; ?>
        </div>
      </div>

      <div style="display:flex; gap:10px; margin-top:24px;">
        <button type="button" class="btn-outline btn-full" data-close-modal>Cancel</button>
        <button type="submit" class="btn-primary btn-full">Save Category</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
