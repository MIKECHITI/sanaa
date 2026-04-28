<?php
// ============================================================
// login.php
// ============================================================
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    $u = currentUser();
    redirect(APP_URL . ($u['role'] === 'artisan' ? '/artisan/dashboard.php'
                      : ($u['role'] === 'admin'  ? '/admin/dashboard.php'
                                                  : '/pages/home.php')));
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $result = Auth::login(
        clean($_POST['email']    ?? ''),
        $_POST['password'] ?? ''
    );
    if ($result['success']) {
        $dest = $_SESSION['redirect_after_login'] ?? null;
        unset($_SESSION['redirect_after_login']);
        redirect($dest ?? APP_URL . ($result['role'] === 'artisan' ? '/artisan/dashboard.php'
                                   : ($result['role'] === 'admin'  ? '/admin/dashboard.php'
                                                                    : '/pages/home.php')));
    } else {
        $errors['general'] = $result['error'];
    }
}

$pageTitle = 'Login — ' . APP_NAME;
include __DIR__ . '/includes/header.php';
?>
<div class="auth-page">
  <div class="auth-card">
    <div class="auth-logo"><a href="<?= APP_URL ?>/pages/home.php">Sanaa Ya Kenya</a></div>
    <h1 class="auth-title">Welcome back</h1>
    <p class="auth-sub">Sign in to your account</p>

    <?php if (!empty($errors['general'])): ?>
      <div class="alert alert-error"><?= e($errors['general']) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <div class="form-group">
        <label class="form-label">Email address</label>
        <input type="email" name="email" class="input <?= isset($errors['email']) ? 'input-error' : '' ?>"
               value="<?= e($_POST['email'] ?? '') ?>" required autofocus placeholder="you@example.com">
      </div>
      <div class="form-group">
        <label class="form-label" style="display:flex;justify-content:space-between;">
          Password <a href="<?= APP_URL ?>/forgot-password.php" style="font-weight:400;color:var(--gold);">Forgot password?</a>
        </label>
        <div class="password-field">
        <input type="password" name="password" class="input" required placeholder="••••••••">
        <button type="button" class="password-toggle" onclick="togglePassword(this)">Show</button>
      </div>
    </div>
      <button type="submit" class="btn btn-primary btn-full" style="margin-top:0.5rem;">Sign In</button>
    </form>

    <p class="auth-switch">Don't have an account?
      <a href="<?= APP_URL ?>/register.php">Create one</a>
    </p>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
