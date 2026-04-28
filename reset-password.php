<?php
// ============================================================
// reset-password.php
// ============================================================
require_once __DIR__ . '/includes/auth.php';

$token = clean($_GET['token'] ?? '');
if (!$token) {
    redirect(APP_URL . '/forgot-password.php');
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $result = Auth::resetPassword($token, $newPassword, $confirmPassword);
    if ($result['success']) {
        $success = true;
    } else {
        $errors = $result['errors'] ?? ['general' => 'Unable to reset password.'];
    }
}

$pageTitle = 'Reset Password — ' . APP_NAME;
include __DIR__ . '/includes/header.php';
?>
<div class="auth-page">
  <div class="auth-card">
    <div class="auth-logo"><a href="<?= APP_URL ?>/pages/home.php">Sanaa Ya Kenya</a></div>
    <h1 class="auth-title">Reset your password</h1>
    <p class="auth-sub">Choose a new password for your account.</p>

    <?php if ($success): ?>
      <div class="alert alert-success">
        Your password has been reset. <a href="<?= APP_URL ?>/login.php">Sign in now</a>.
      </div>
    <?php else: ?>
      <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-error"><?= e($errors['general']) ?></div>
      <?php endif; ?>

      <form method="POST" action="?token=<?= e($token) ?>">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <div class="form-group">
          <label class="form-label">New password</label>
          <input type="password" name="password" class="input <?= isset($errors['password']) ? 'input-error' : '' ?>" required placeholder="••••••••">
          <?php if (isset($errors['password'])): ?><div class="field-error"><?= e($errors['password']) ?></div><?php endif; ?>
        </div>
        <div class="form-group">
          <label class="form-label">Confirm password</label>
          <input type="password" name="confirm_password" class="input <?= isset($errors['confirm_password']) ? 'input-error' : '' ?>" required placeholder="••••••••">
          <?php if (isset($errors['confirm_password'])): ?><div class="field-error"><?= e($errors['confirm_password']) ?></div><?php endif; ?>
        </div>
        <button type="submit" class="btn btn-primary btn-full" style="margin-top:0.5rem;">Reset password</button>
      </form>
    <?php endif; ?>

    <p class="auth-switch"><a href="<?= APP_URL ?>/login.php">Back to login</a></p>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php';
