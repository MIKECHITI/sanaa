<?php
// ============================================================
// artisan/profile.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('artisan');

$artisan = Auth::getArtisanProfile($_SESSION['user_id']);
$aid     = $artisan['id'];
$uid     = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name     = clean($_POST['name']         ?? '');
    $phone    = clean($_POST['phone']        ?? '');
    $bio      = clean($_POST['bio']          ?? '');
    $county   = clean($_POST['county']       ?? '');
    $spec     = clean($_POST['speciality']   ?? '');
    $mpesa    = clean($_POST['mpesa_number'] ?? $phone);
    $yrs      = cleanInt($_POST['years_exp'] ?? 0);

    $errors = [];
    if (strlen($name) < 2) $errors['name'] = 'Name is required.';

    if (!$errors) {
        $pdo = db();
        $pdo->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?")->execute([$name, $phone, $uid]);
        $pdo->prepare("UPDATE artisans SET bio = ?, county = ?, speciality = ?, mpesa_number = ?, years_exp = ? WHERE id = ?")
            ->execute([$bio, $county, $spec, $mpesa, $yrs, $aid]);
        flash('success', 'Profile updated successfully.');
        redirect(APP_URL . '/artisan/profile.php');
    }
}

$pageTitle = 'My Profile — ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
  <aside class="sidebar">
    <div class="sidebar-profile">
      <div class="sidebar-avatar"><?= strtoupper(substr($artisan['name'],0,2)) ?></div>
      <div class="sidebar-name"><?= e($artisan['name']) ?></div>
      <div class="sidebar-role"><?= e($artisan['county'] ?? 'Artisan') ?></div>
    </div>
    <nav class="sidebar-nav">
      <div class="sidebar-label">Main</div>
      <a class="sidebar-item" href="<?= APP_URL ?>/artisan/dashboard.php"><span class="si-icon">📊</span> Overview</a>
      <a class="sidebar-item" href="<?= APP_URL ?>/artisan/my-products.php"><span class="si-icon">🛍️</span> My Products</a>
      <a class="sidebar-item" href="<?= APP_URL ?>/artisan/upload-product.php"><span class="si-icon">➕</span> Upload Product</a>
      <a class="sidebar-item" href="<?= APP_URL ?>/artisan/earnings.php"><span class="si-icon">💰</span> Earnings</a>
      <div class="sidebar-label">Settings</div>
      <a class="sidebar-item active" href="<?= APP_URL ?>/artisan/profile.php"><span class="si-icon">👤</span> My Profile</a>
      <a class="sidebar-item" href="<?= APP_URL ?>/pages/home.php"><span class="si-icon">🏠</span> Back to Store</a>
    </nav>
  </aside>

  <main class="dash-main">
    <div class="dash-topbar"><div class="dash-title">My Profile</div></div>
    <div style="max-width:600px;">
      <div class="dash-card">
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Full Name *</label>
              <input type="text" name="name" class="input" value="<?= e($artisan['name']) ?>" required>
            </div>
            <div class="form-group">
              <label class="form-label">County</label>
              <select name="county" class="select">
                <?php foreach (['Narok County','Kisii County','Kwale County','Nairobi','Mombasa','Kisumu','Nakuru','Other'] as $c): ?>
                <option value="<?= e($c) ?>" <?= $artisan['county'] === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Phone Number</label>
              <input type="tel" name="phone" class="input" value="<?= e($artisan['phone']) ?>" placeholder="07xx xxx xxx">
            </div>
            <div class="form-group">
              <label class="form-label">M-Pesa Number (for payouts)</label>
              <input type="tel" name="mpesa_number" class="input"
                     value="<?= e($artisan['mpesa_number'] ?? $artisan['phone']) ?>" placeholder="07xx xxx xxx">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Craft Speciality</label>
            <select name="speciality" class="select">
              <?php foreach (['Maasai Beadwork','Soapstone Carvings','Sisal Baskets','Mixed Crafts','Other'] as $s): ?>
              <option value="<?= e($s) ?>" <?= ($artisan['speciality'] ?? '') === $s ? 'selected' : '' ?>><?= e($s) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Years of Experience</label>
            <input type="number" name="years_exp" class="input" min="0" max="70"
                   value="<?= (int)($artisan['years_exp'] ?? 0) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Artisan Bio / Story</label>
            <textarea name="bio" class="textarea" style="min-height:120px;"
                      placeholder="Tell buyers about yourself, your craft, and your community..."
                      ><?= e($artisan['bio'] ?? '') ?></textarea>
          </div>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
      </div>
    </div>
  </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
