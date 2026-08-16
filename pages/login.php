<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/currency.php';

$base = base_path();
$pageTitle = 'Log In';
$error = '';

if (isLoggedIn()) {
    header('Location: ' . $base . '/pages/myaccount.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter both email and password.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $defaultRedirect = in_array($user['role'], ['admin', 'super_admin'], true) ? ($base . '/admin/index.php') : ($base . '/pages/myaccount.php');
            $redirect = $_SESSION['redirect_after_login'] ?? $defaultRedirect;
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrap">
  <div class="auth-card">
    <h1>Welcome Back 👋</h1>
    <p class="sub">Log in to your GadgetZone account</p>

    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <form method="POST">
      <div class="form-group"><label>Email Address</label><input type="email" name="email" required></div>
      <div class="form-group">
        <label>Password</label>
        <div class="password-wrapper">
          <input type="password" name="password" id="login-password" required>
          <button type="button" class="password-toggle" onclick="togglePassword('login-password', this)" title="Show/Hide Password">
            <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>
      <button type="submit" class="btn-primary btn-full btn-lg">🔒 Log In</button>
    </form>

    <div class="switch">Don't have an account? <a href="<?= $base ?>/pages/register.php">Create one →</a></div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
