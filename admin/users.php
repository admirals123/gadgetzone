<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/currency.php';
requireAdmin();

$pageTitle = 'Users';
$base = base_path();
$message = '';
$error = '';

// Only super_admin can change roles
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'update_role') {
    if (($_SESSION['role'] ?? '') === 'super_admin') {
        $id = (int)$_POST['id'];
        $role = $_POST['role'];
        if (in_array($role, ['member','admin','super_admin'], true) && $id !== (int)$_SESSION['user_id']) {
            $canUpdate = true;
            if ($role !== 'super_admin') {
                $superRes = mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE role = 'super_admin'");
                $superCount = (int)mysqli_fetch_assoc($superRes)['c'];
                $targetRes = mysqli_query($conn, "SELECT role FROM users WHERE id = $id");
                $targetUser = mysqli_fetch_assoc($targetRes);
                if (($targetUser['role'] ?? '') === 'super_admin' && $superCount <= 1) {
                    $error = 'Cannot demote the last Super Admin.';
                    $canUpdate = false;
                }
            }
            if ($canUpdate) {
                $stmt = mysqli_prepare($conn, "UPDATE users SET role = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "si", $role, $id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $message = 'User role updated successfully.';
            }
        }
    } else {
        $error = 'Only super admins have permission to change user roles.';
    }
}

// Search and role filters
$search = trim($_GET['search'] ?? '');
$roleFilter = $_GET['role'] ?? '';

$where = [];
$types = '';
$params = [];

if ($search !== '') {
    $where[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR u.city LIKE ?)";
    $types .= 'sssss';
    $term = "%$search%";
    $params = [$term, $term, $term, $term, $term];
}

if ($roleFilter !== '' && in_array($roleFilter, ['member','admin','super_admin'], true)) {
    $where[] = "u.role = ?";
    $types .= 's';
    $params[] = $roleFilter;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "SELECT u.*, (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS order_count
        FROM users u $whereSql ORDER BY u.created_at DESC";

if ($params) {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $users = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
} else {
    $users = mysqli_fetch_all(mysqli_query($conn, $sql), MYSQLI_ASSOC);
}

// Breakdown counts
$counts = mysqli_fetch_assoc(mysqli_query($conn, "SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN role = 'member' THEN 1 ELSE 0 END) AS members,
    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) AS admins,
    SUM(CASE WHEN role = 'super_admin' THEN 1 ELSE 0 END) AS super_admins
FROM users"));

require_once __DIR__ . '/layout.php';
?>

<?php if ($message): ?><div class="alert alert-success">✅ <?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error">⚠️ <?= e($error) ?></div><?php endif; ?>

<div class="admin-panel">
  <div class="admin-panel-head">
    <div>
      <h3 style="margin-bottom:2px;">User Management</h3>
      <span style="color:var(--text2); font-size:13px;">Manage customer accounts, administrative staff, and permissions</span>
    </div>
    <span class="role-badge" style="font-size:13px; padding:6px 14px;">
      Total: <?= (int)$counts['total'] ?> Users
    </span>
  </div>

  <!-- Role Filter Tabs -->
  <div class="admin-filter-tabs">
    <a href="<?= $base ?>/admin/users.php" class="tab-item <?= empty($roleFilter) ? 'active' : '' ?>">
      All Users <span class="tab-badge"><?= (int)$counts['total'] ?></span>
    </a>
    <a href="<?= $base ?>/admin/users.php?role=member" class="tab-item <?= $roleFilter==='member' ? 'active' : '' ?>">
      👥 Customers <span class="tab-badge"><?= (int)$counts['members'] ?></span>
    </a>
    <a href="<?= $base ?>/admin/users.php?role=admin" class="tab-item <?= $roleFilter==='admin' ? 'active' : '' ?>">
      🛡️ Admins <span class="tab-badge"><?= (int)$counts['admins'] ?></span>
    </a>
    <a href="<?= $base ?>/admin/users.php?role=super_admin" class="tab-item <?= $roleFilter==='super_admin' ? 'active' : '' ?>">
      ⚡ Super Admins <span class="tab-badge"><?= (int)$counts['super_admins'] ?></span>
    </a>
  </div>

  <!-- Search Bar -->
  <form method="GET" class="admin-filter-bar">
    <?php if ($roleFilter): ?><input type="hidden" name="role" value="<?= e($roleFilter) ?>"><?php endif; ?>
    <input type="text" name="search" placeholder="🔍 Search by Name, Email, Phone, or City..." value="<?= e($search) ?>">
    <button type="submit" class="btn-outline btn-sm">Search</button>
    <?php if ($search !== '' || $roleFilter !== ''): ?>
      <a href="<?= $base ?>/admin/users.php" class="btn-outline btn-sm">Reset</a>
    <?php endif; ?>
  </form>

  <?php if (empty($users)): ?>
    <div class="admin-empty">
      <div style="font-size:36px; margin-bottom:12px;">👥</div>
      <p>No users found matching your search criteria.</p>
      <a href="<?= $base ?>/admin/users.php" class="btn-outline btn-sm" style="margin-top:12px;">View All Users</a>
    </div>
  <?php else: ?>
  <div style="overflow-x: auto;">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Avatar</th>
          <th>Full Name</th>
          <th>Email Address</th>
          <th>Phone</th>
          <th>Location</th>
          <th>Orders Placed</th>
          <th>Role</th>
          <th>Member Since</th>
          <th>Update Role</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u):
          $initials = strtoupper(substr($u['first_name'],0,1) . substr($u['last_name'],0,1));
        ?>
        <tr>
          <td>
            <?php if (!empty($u['avatar'])): ?>
              <img src="<?= $base ?>/assets/uploads/avatars/<?= e($u['avatar']) ?>" class="thumb" style="width:38px; height:38px; border-radius:50%; object-fit:cover;" alt="">
            <?php else: ?>
              <span class="avatar-mini" style="position:static; width:38px; height:38px; font-size:12px;"><?= e($initials) ?></span>
            <?php endif; ?>
          </td>
          <td>
            <strong><?= e($u['first_name'] . ' ' . $u['last_name']) ?></strong>
            <?php if ((int)$u['id'] === (int)$_SESSION['user_id']): ?>
              <span style="font-size:10px; color:var(--accent); font-weight:700;">(You)</span>
            <?php endif; ?>
          </td>
          <td>
            <a href="mailto:<?= e($u['email']) ?>" style="color:var(--text2);"><?= e($u['email']) ?></a>
          </td>
          <td style="color:var(--text2);"><?= e($u['phone'] ?: '—') ?></td>
          <td style="color:var(--text2);"><?= e($u['city'] ?: '—') ?></td>
          <td>
            <?php if ((int)$u['order_count'] > 0): ?>
              <a href="<?= $base ?>/admin/orders.php?search=<?= urlencode($u['email']) ?>" class="role-badge" style="background:rgba(59,130,246,.15); color:#60a5fa;" title="Click to view user orders">
                🛍️ <?= (int)$u['order_count'] ?> order(s) →
              </a>
            <?php else: ?>
              <span style="color:var(--text2); font-size:12px;">0 orders</span>
            <?php endif; ?>
          </td>
          <td>
            <span class="role-badge <?= e($u['role']) ?>">
              <?= ucfirst(e(str_replace('_',' ', $u['role']))) ?>
            </span>
          </td>
          <td style="color:var(--text2); font-size:12px; white-space:nowrap;">
            <?= date('M j, Y', strtotime($u['created_at'])) ?>
          </td>
          <td>
            <?php if (($_SESSION['role'] ?? '') === 'super_admin' && (int)$u['id'] !== (int)$_SESSION['user_id']): ?>
            <form method="POST" action="<?= $base ?>/admin/users.php" style="display:flex; gap:6px; align-items:center;">
              <input type="hidden" name="form" value="update_role">
              <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
              <select name="role" style="background:var(--surface2); color:var(--text); border:1px solid var(--border); border-radius:6px; padding:4px 8px; font-size:12px;">
                <option value="member" <?= $u['role']==='member'?'selected':'' ?>>Member</option>
                <option value="admin" <?= $u['role']==='admin'?'selected':'' ?>>Admin</option>
                <option value="super_admin" <?= $u['role']==='super_admin'?'selected':'' ?>>Super Admin</option>
              </select>
              <button type="submit" class="icon-btn" title="Save Role">💾</button>
            </form>
            <?php else: ?>
              <span style="color:var(--text2); font-size:12px;">—</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
