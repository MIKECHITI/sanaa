<?php
// ============================================================
// register.php
// ============================================================
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) redirect(APP_URL . '/pages/home.php');

$errors = [];
$role   = clean($_GET['role'] ?? 'customer');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $result = Auth::register($_POST);
    if ($result['success']) {
        flash('success', 'Account created successfully! Welcome to Sanaa Ya Kenya.');
        $u = currentUser();
        redirect(APP_URL . ($u['role'] === 'artisan' ? '/artisan/dashboard.php' : '/pages/home.php'));
    } else {
        $errors = $result['errors'];
    }
}

$pageTitle = 'Register — ' . APP_NAME;
include __DIR__ . '/includes/header.php';
?>
<div class="auth-page">
  <div class="auth-card" style="max-width:520px;">
    <div class="auth-logo"><a href="<?= APP_URL ?>/pages/home.php">Sanaa Ya Kenya</a></div>
    <h1 class="auth-title">Create your account</h1>

    <!-- Role tabs -->
    <div class="role-tabs">
      <a href="?role=customer" class="role-tab <?= $role === 'customer' ? 'active' : '' ?>">
        🛍️ Customer
      </a>
      <a href="?role=artisan" class="role-tab <?= $role === 'artisan' ? 'active' : '' ?>">
        🎨 Artisan
      </a>
    </div>

    <?php if (!empty($errors['general'])): ?>
      <div class="alert alert-error"><?= e($errors['general']) ?></div>
    <?php endif; ?>

    <form method="POST" action="?role=<?= e($role) ?>">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="role" value="<?= e($role) ?>">

      <div class="form-group">
        <label class="form-label">Full Name *</label>
        <input type="text" name="name" class="input <?= isset($errors['name']) ? 'input-error' : '' ?>"
               value="<?= e($_POST['name'] ?? '') ?>" required placeholder="Your full name">
        <?php if (isset($errors['name'])): ?><div class="field-error"><?= e($errors['name']) ?></div><?php endif; ?>
      </div>

      <div class="form-group">
        <label class="form-label">Email Address *</label>
        <input type="email" name="email" class="input <?= isset($errors['email']) ? 'input-error' : '' ?>"
               value="<?= e($_POST['email'] ?? '') ?>" required placeholder="you@example.com">
        <?php if (isset($errors['email'])): ?><div class="field-error"><?= e($errors['email']) ?></div><?php endif; ?>
      </div>

      <div class="form-group">
        <label class="form-label">Phone / M-Pesa Number<?= $role === 'artisan' ? ' *' : '' ?></label>
        <input type="tel" name="phone" class="input" value="<?= e($_POST['phone'] ?? '') ?>"
               placeholder="07xx xxx xxx" <?= $role === 'artisan' ? 'required' : '' ?>>
        <?php if (isset($errors['phone'])): ?><div class="field-error"><?= e($errors['phone']) ?></div><?php endif; ?>
      </div>

      <?php if ($role === 'artisan'): ?>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">County *</label>
          <select name="county" class="select" required>
            <option value="">Select county</option>
            <?php foreach (['Narok County','Kisii County','Kwale County','Nairobi','Mombasa','Kisumu','Nakuru','Other'] as $c): ?>
            <option value="<?= e($c) ?>" <?= ($_POST['county'] ?? '') === $c ? 'selected' : '' ?>><?= e($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Craft Speciality</label>
          <select name="speciality" class="select">
            <option value="">Select craft</option>
            <option value="Maasai Beadwork">Maasai Beadwork</option>
            <option value="Soapstone Carvings">Soapstone Carvings</option>
            <option value="Sisal Baskets">Sisal Baskets</option>
            <option value="Other">Other</option>
          </select>
        </div>
      </div>
      <?php endif; ?>

      <div class="form-group">
        <label class="form-label">Password *</label>
        <div class="password-field">
          <input type="password" name="password" class="input <?= isset($errors['password']) ? 'input-error' : '' ?>"
                 required placeholder="Min. 8 characters">
          <button type="button" class="password-toggle" onclick="togglePassword(this)">Show</button>
        </div>
        <?php if (isset($errors['password'])): ?><div class="field-error"><?= e($errors['password']) ?></div><?php endif; ?>
      </div>

      <div class="form-group">
        <label class="form-label">Confirm Password *</label>
        <div class="password-field">
          <input type="password" name="confirm_password" class="input <?= isset($errors['confirm_password']) ? 'input-error' : '' ?>"
                 required placeholder="Repeat password">
          <button type="button" class="password-toggle" onclick="togglePassword(this)">Show</button>
        </div>
        <?php if (isset($errors['confirm_password'])): ?><div class="field-error"><?= e($errors['confirm_password']) ?></div><?php endif; ?>
      </div>

      <button type="submit" class="btn btn-primary btn-full" style="margin-top:0.5rem;">
        <?= $role === 'artisan' ? 'Register as Artisan' : 'Create Account' ?>
      </button>
    </form>

    <p class="auth-switch">Already have an account? <a href="<?= APP_URL ?>/login.php">Sign in</a></p>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

