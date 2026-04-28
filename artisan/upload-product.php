<?php
// ============================================================
// artisan/upload-product.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('artisan');

$artisan = Auth::getArtisanProfile($_SESSION['user_id']);
$aid     = $artisan['id'];

$categories = db()->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$errors     = [];
$success    = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $title   = clean($_POST['title']          ?? '');
    $catId   = cleanInt($_POST['category_id'] ?? 0);
    $desc    = clean($_POST['description']    ?? '');
    $story   = clean($_POST['cultural_story'] ?? '');
    $material= clean($_POST['material']       ?? '');
    $region  = clean($_POST['region']         ?? $artisan['county']);
    $price   = cleanFloat($_POST['price']     ?? 0);
    $stock   = cleanInt($_POST['stock']       ?? 1);

    // Validate
    if (strlen($title) < 3)  $errors['title']       = 'Title must be at least 3 characters.';
    if (!$catId)              $errors['category_id'] = 'Please select a category.';
    if (strlen($desc) < 20)  $errors['description'] = 'Description must be at least 20 characters.';
    if ($price <= 0)          $errors['price']       = 'Please enter a valid price.';
    if ($stock < 1)           $errors['stock']       = 'Stock must be at least 1.';
    if (empty($_FILES['images']['name'][0])) $errors['images'] = 'At least one product image is required.';

    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $slug = slugify($title);
            // Ensure unique slug
            $existing = $pdo->prepare('SELECT COUNT(*) FROM products WHERE slug LIKE ?');
            $existing->execute([$slug . '%']);
            if ($existing->fetchColumn() > 0) $slug .= '-' . time();

            $pdo->prepare("
                INSERT INTO products (artisan_id,category_id,title,slug,description,cultural_story,
                                      material,region,price,stock,status)
                VALUES (?,?,?,?,?,?,?,?,?,?,'pending')
            ")->execute([$aid,$catId,$title,$slug,$desc,$story,$material,$region,$price,$stock]);
            $productId = (int)$pdo->lastInsertId();

            // Handle image uploads
            $uploadDir = UPLOAD_DIR;
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $files = $_FILES['images'];
            $first = true;
            foreach ($files['tmp_name'] as $i => $tmp) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $tmp);
                finfo_close($finfo);
                if (!in_array($mime, ALLOWED_IMG_TYPES)) continue;
                if ($files['size'][$i] > MAX_UPLOAD_MB * 1024 * 1024) continue;

                $ext      = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
                $filename = 'prod_' . $productId . '_' . uniqid() . '.' . strtolower($ext);
                if (move_uploaded_file($tmp, $uploadDir . $filename)) {
                    $pdo->prepare("
                        INSERT INTO product_images (product_id, filename, is_primary, sort_order)
                        VALUES (?,?,?,?)
                    ")->execute([$productId, $filename, $first ? 1 : 0, $i]);
                    $first = false;
                }
            }

            $pdo->commit();
            $success = true;
            flash('success', '"' . $title . '" submitted for review. It will go live within 24 hours.');
            redirect(APP_URL . '/artisan/my-products.php');

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors['general'] = 'Upload failed. Please try again.';
        }
    }
}

$pageTitle = 'Upload Product — ' . APP_NAME;
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
      <a class="sidebar-item active" href="<?= APP_URL ?>/artisan/upload-product.php"><span class="si-icon">➕</span> Upload Product</a>
      <a class="sidebar-item" href="<?= APP_URL ?>/artisan/earnings.php"><span class="si-icon">💰</span> Earnings</a>
      <div class="sidebar-label">Settings</div>
      <a class="sidebar-item" href="<?= APP_URL ?>/artisan/profile.php"><span class="si-icon">👤</span> My Profile</a>
      <a class="sidebar-item" href="<?= APP_URL ?>/pages/home.php"><span class="si-icon">🏠</span> Back to Store</a>
    </nav>
  </aside>

  <main class="dash-main">
    <div class="dash-topbar">
      <div class="dash-title">Upload New Product</div>
      <a href="<?= APP_URL ?>/artisan/my-products.php" class="btn btn-outline btn-sm">← My Products</a>
    </div>

    <div style="max-width:680px;">
      <div class="dash-card">

        <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-error"><?= e($errors['general']) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

          <!-- Images -->
          <div class="form-group">
            <label class="form-label">Product Images * (up to 5, max <?= MAX_UPLOAD_MB ?>MB each)</label>
            <div class="upload-zone" id="uploadZone" onclick="document.getElementById('imgInput').click()">
              <span class="upload-icon">📸</span>
              <div style="font-weight:600;margin-bottom:0.25rem;">Click to upload images</div>
              <div style="font-size:0.78rem;color:var(--text-muted);">JPG, PNG, WEBP · At least 1 required</div>
            </div>
            <input type="file" id="imgInput" name="images[]" accept="image/*" multiple
                   style="display:none;" onchange="previewImages(this)">
            <div id="imgPreview" style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-top:0.75rem;"></div>
            <?php if (isset($errors['images'])): ?>
            <div class="field-error"><?= e($errors['images']) ?></div>
            <?php endif; ?>
          </div>

          <!-- Title & Category -->
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Product Title *</label>
              <input type="text" name="title" class="input <?= isset($errors['title'])?'input-error':'' ?>"
                     value="<?= e($_POST['title'] ?? '') ?>" placeholder="e.g. Handcrafted Soapstone Lion" required>
              <?php if (isset($errors['title'])): ?><div class="field-error"><?= e($errors['title']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
              <label class="form-label">Category *</label>
              <select name="category_id" class="select <?= isset($errors['category_id'])?'input-error':'' ?>" required>
                <option value="">Select category</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>"
                  <?= ($_POST['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                  <?= e($cat['name']) ?>
                </option>
                <?php endforeach; ?>
              </select>
              <?php if (isset($errors['category_id'])): ?><div class="field-error"><?= e($errors['category_id']) ?></div><?php endif; ?>
            </div>
          </div>

          <!-- Description -->
          <div class="form-group">
            <label class="form-label">Product Description *</label>
            <textarea name="description" class="textarea <?= isset($errors['description'])?'input-error':'' ?>"
                      placeholder="Describe your product — size, weight, finish, and what makes it special..."
                      required><?= e($_POST['description'] ?? '') ?></textarea>
            <?php if (isset($errors['description'])): ?><div class="field-error"><?= e($errors['description']) ?></div><?php endif; ?>
          </div>

          <!-- Cultural Story -->
          <div class="form-group">
            <label class="form-label">Cultural Story</label>
            <div style="font-size:0.75rem;color:var(--text-muted);margin-bottom:0.4rem;">
              Share the history, symbolism, or tribal significance behind this piece.
            </div>
            <textarea name="cultural_story" class="textarea" style="min-height:80px;"
                      placeholder="e.g. Red symbolises warrior bravery in Maasai tradition..."
                      ><?= e($_POST['cultural_story'] ?? '') ?></textarea>
          </div>

          <!-- Price, Stock -->
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Price (KES) *</label>
              <input type="number" name="price" class="input <?= isset($errors['price'])?'input-error':'' ?>"
                     value="<?= e($_POST['price'] ?? '') ?>" placeholder="e.g. 2500" min="1" step="1" required>
              <?php if (isset($errors['price'])): ?><div class="field-error"><?= e($errors['price']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
              <label class="form-label">Stock Quantity *</label>
              <input type="number" name="stock" class="input <?= isset($errors['stock'])?'input-error':'' ?>"
                     value="<?= e($_POST['stock'] ?? 1) ?>" min="1" required>
              <?php if (isset($errors['stock'])): ?><div class="field-error"><?= e($errors['stock']) ?></div><?php endif; ?>
            </div>
          </div>

          <!-- Material, Region -->
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Material</label>
              <input type="text" name="material" class="input"
                     value="<?= e($_POST['material'] ?? '') ?>" placeholder="e.g. Kisii soapstone">
            </div>
            <div class="form-group">
              <label class="form-label">Region</label>
              <select name="region" class="select">
                <?php foreach (['Narok County','Kisii County','Kwale County','Other'] as $r): ?>
                <option value="<?= e($r) ?>"
                  <?= ($_POST['region'] ?? $artisan['county']) === $r ? 'selected' : '' ?>>
                  <?= e($r) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="alert alert-info" style="margin-top:0.5rem;">
            ℹ️ Your product will be reviewed by our admin team within 24 hours before going live.
          </div>

          <div style="display:flex;gap:1rem;margin-top:1.25rem;">
            <button type="submit" class="btn btn-primary btn-full">Submit for Review</button>
            <a href="<?= APP_URL ?>/artisan/my-products.php"
               class="btn btn-full" style="background:none;border:1px solid var(--border);color:var(--text-muted);">
              Cancel
            </a>
          </div>
        </form>
      </div>
    </div>
  </main>
</div>

<script>
function previewImages(input) {
    const preview = document.getElementById('imgPreview');
    preview.innerHTML = '';
    const zone = document.getElementById('uploadZone');
    const files = Array.from(input.files).slice(0, 5);
    files.forEach((file, i) => {
        const reader = new FileReader();
        reader.onload = e => {
            const div = document.createElement('div');
            div.style.cssText = 'position:relative;width:80px;height:80px;border-radius:8px;overflow:hidden;border:2px solid var(--border-strong);';
            div.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;">
                ${i===0 ? '<div style="position:absolute;bottom:0;left:0;right:0;background:var(--gold);color:var(--earth);font-size:0.6rem;font-weight:700;text-align:center;padding:2px;">PRIMARY</div>' : ''}`;
            preview.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
    zone.style.borderColor = 'var(--gold)';
    zone.innerHTML = `<span style="font-size:1.5rem;">✅</span><div style="font-weight:600;margin-top:0.25rem;">${files.length} image${files.length>1?'s':''} selected</div>`;
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
