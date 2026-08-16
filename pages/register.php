<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/currency.php';

$base = base_path();
$pageTitle = 'Create Account';
$errors = [];

if (isLoggedIn()) {
    header('Location: ' . $base . '/pages/myaccount.php');
    exit;
}

$vals = ['first_name' => '', 'last_name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vals['first_name'] = sanitize($_POST['first_name'] ?? '');
    $vals['last_name']  = sanitize($_POST['last_name'] ?? '');
    $vals['email']      = trim($_POST['email'] ?? '');
    $password           = $_POST['password'] ?? '';
    $confirmPassword    = $_POST['confirm_password'] ?? '';

    if ($vals['first_name'] === '' || $vals['last_name'] === '') $errors[] = 'First and last name are required.';
    if (!filter_var($vals['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirmPassword) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $vals['email']);
        mysqli_stmt_execute($stmt);
        if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
            $errors[] = 'This email is already registered.';
        }
        mysqli_stmt_close($stmt);
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "INSERT INTO users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, 'customer')");
        mysqli_stmt_bind_param($stmt, "ssss", $vals['first_name'], $vals['last_name'], $vals['email'], $hash);
        mysqli_stmt_execute($stmt);
        $newId = mysqli_insert_id($conn);
        $insertError = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);

        if ($newId > 0) {
            $_SESSION['user_id'] = $newId;
            $_SESSION['role'] = 'customer';
            header('Location: ' . $base . '/pages/myaccount.php');
            exit;
        } else {
            $errors[] = 'Registration failed. ' . ($insertError ? htmlspecialchars($insertError) : 'Please try again.');
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrap">
  <div class="auth-card">
    <h1>Create Account 🚀</h1>
    <p class="sub">Join GadgetZone and start shopping</p>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-error">
        <ul style="margin-left:18px;"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-row">
        <div class="form-group"><label>First Name</label><input type="text" name="first_name" value="<?= e($vals['first_name']) ?>" required></div>
        <div class="form-group"><label>Last Name</label><input type="text" name="last_name" value="<?= e($vals['last_name']) ?>" required></div>
      </div>
      <div class="form-group"><label>Email Address</label><input type="email" name="email" value="<?= e($vals['email']) ?>" required></div>
      <div class="form-group">
        <label>Password</label>
        <div class="password-wrapper">
          <input type="password" name="password" id="reg-password" minlength="6" required>
          <button type="button" class="password-toggle" onclick="togglePassword('reg-password', this)" title="Show/Hide Password">
            <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>
      <div class="form-group">
        <label>Confirm Password</label>
        <div class="password-wrapper">
          <input type="password" name="confirm_password" id="reg-confirm-password" minlength="6" required>
          <button type="button" class="password-toggle" onclick="togglePassword('reg-confirm-password', this)" title="Show/Hide Password">
            <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>
      <button type="submit" class="btn-primary btn-full btn-lg">Create Account</button>
    </form>

    <div class="switch">Already have an account? <a href="<?= $base ?>/pages/login.php">Log in →</a></div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
