<?php
// ============================================================
// forgot-password.php
// ============================================================
require_once __DIR__ . '/includes/auth.php';

$errors = [];
$success = false;
$resetLink = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email = clean($_POST['email'] ?? '');
    $result = Auth::requestPasswordReset($email);
    if ($result['success']) {
        $success = true;
        $resetLink = $result['reset_link'] ?? null;
    } else {
        $errors['email'] = $result['error'] ?? 'Unable to process request.';
    }
}

$pageTitle = 'Forgot Password — ' . APP_NAME;
include __DIR__ . '/includes/header.php';
?>
<div class="auth-page">
  <div class="auth-card">
    <div class="auth-logo"><a href="<?= APP_URL ?>/pages/home.php">Sanaa Ya Kenya</a></div>
    <h1 class="auth-title">Forgot your password?</h1>
    <p class="auth-sub">Enter your email and we’ll send a reset link.</p>

    <?php if ($success): ?>
      <?php if ($resetLink): ?>
      <div class="alert alert-info" style="word-break:break-word;">
        Reset link: <a href="<?= e($resetLink) ?>">Open reset page</a>
      </div>
      <?php endif; ?>
    <?php endif; ?>

    <form method="POST" action="">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <div class="form-group">
        <label class="form-label">Email address</label>
        <input type="email" name="email" class="input <?= isset($errors['email']) ? 'input-error' : '' ?>"
               value="<?= e($_POST['email'] ?? '') ?>" required placeholder="you@example.com">
        <?php if (isset($errors['email'])): ?><div class="field-error"><?= e($errors['email']) ?></div><?php endif; ?>
      </div>
      <button type="submit" class="btn btn-primary btn-full" style="margin-top:0.5rem;">Send reset link</button>
    </form>

    <p class="auth-switch">Remembered your password? <a href="<?= APP_URL ?>/login.php">Sign in</a></p>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php';
