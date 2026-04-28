<?php
// ============================================================
// artisan/my-products.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('artisan');

$artisan = Auth::getArtisanProfile($_SESSION['user_id']);
$aid     = $artisan['id'];

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    verifyCsrf();
    $pid = cleanInt($_POST['product_id']);
    // Only delete if pending (approved products just deactivate)
    $check = db()->prepare('SELECT status FROM products WHERE id = ? AND artisan_id = ?');
    $check->execute([$pid, $aid]);
    $prod = $check->fetch();
    if ($prod) {
        if ($prod['status'] === 'pending') {
            db()->prepare('DELETE FROM products WHERE id = ? AND artisan_id = ?')->execute([$pid, $aid]);
            flash('success', 'Product deleted.');
        } else {
            flash('error', 'Only pending products can be deleted.');
        }
    }
    redirect(APP_URL . '/artisan/my-products.php');
}

$products = db()->prepare("
    SELECT p.*, c.name AS category_name,
           (SELECT filename FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS image,
           COALESCE((SELECT SUM(oi.quantity) FROM order_items oi WHERE oi.product_id = p.id),0) AS units_sold
    FROM products p
    JOIN categories c ON c.id = p.category_id
    WHERE p.artisan_id = ?
    ORDER BY p.created_at DESC
");
$products->execute([$aid]);
$products = $products->fetchAll();

$pageTitle = 'My Products — ' . APP_NAME;
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
      <a class="sidebar-item active" href="<?= APP_URL ?>/artisan/my-products.php"><span class="si-icon">🛍️</span> My Products</a>
      <a class="sidebar-item" href="<?= APP_URL ?>/artisan/upload-product.php"><span class="si-icon">➕</span> Upload Product</a>
      <a class="sidebar-item" href="<?= APP_URL ?>/artisan/earnings.php"><span class="si-icon">💰</span> Earnings</a>
      <div class="sidebar-label">Settings</div>
      <a class="sidebar-item" href="<?= APP_URL ?>/artisan/profile.php"><span class="si-icon">👤</span> My Profile</a>
      <a class="sidebar-item" href="<?= APP_URL ?>/pages/home.php"><span class="si-icon">🏠</span> Back to Store</a>
    </nav>
  </aside>

  <main class="dash-main">
    <div class="dash-topbar">
      <div class="dash-title">My Products</div>
      <a href="<?= APP_URL ?>/artisan/upload-product.php" class="btn btn-primary">+ Upload New</a>
    </div>

    <?php if (empty($products)): ?>
    <div style="text-align:center;padding:4rem 2rem;background:var(--white);border:1px solid var(--border);border-radius:var(--radius-lg);">
      <div style="font-size:3rem;margin-bottom:1rem;">🎨</div>
      <h3 style="font-family:'Playfair Display',serif;">No products yet</h3>
      <p style="color:var(--text-muted);margin:0.5rem 0 1.5rem;">Upload your first product to start selling.</p>
      <a href="<?= APP_URL ?>/artisan/upload-product.php" class="btn btn-primary">Upload First Product</a>
    </div>
    <?php else: ?>
    <div class="dash-card" style="padding:0;overflow:hidden;">
      <table class="orders-table" style="width:100%;">
        <thead>
          <tr style="background:var(--cream-dark);">
            <th style="padding:0.875rem 1.25rem;">Product</th>
            <th>Category</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Status</th>
            <th>Sold</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($products as $p): ?>
          <tr>
            <td style="padding:0.875rem 1.25rem;">
              <div style="display:flex;align-items:center;gap:0.75rem;">
                <div style="width:44px;height:44px;border-radius:var(--radius);background:var(--cream-dark);overflow:hidden;flex-shrink:0;">
                  <?php if ($p['image']): ?>
                  <img src="<?= APP_URL ?>/assets/images/uploads/<?= e($p['image']) ?>"
                       alt="" style="width:100%;height:100%;object-fit:cover;">
                  <?php else: ?>
                  <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:1.2rem;">🎨</div>
                  <?php endif; ?>
                </div>
                <div>
                  <div style="font-weight:600;font-size:0.875rem;"><?= e(substr($p['title'],0,30)) ?></div>
                  <div style="font-size:0.72rem;color:var(--text-muted);"><?= e($p['region']) ?></div>
                </div>
              </div>
            </td>
            <td style="font-size:0.82rem;"><?= e($p['category_name']) ?></td>
            <td style="font-size:0.85rem;font-weight:600;"><?= formatKES($p['price']) ?></td>
            <td style="font-size:0.85rem;"><?= $p['stock'] ?></td>
            <td>
              <span class="status-pill sp-<?= $p['status'] === 'approved' ? 'paid' : ($p['status'] === 'rejected' ? 'cancelled' : 'pending') ?>">
                <?= ucfirst(e($p['status'])) ?>
              </span>
            </td>
            <td style="font-size:0.85rem;font-weight:600;color:var(--green);"><?= $p['units_sold'] ?></td>
            <td>
              <div style="display:flex;gap:0.4rem;">
                <?php if ($p['status'] === 'approved'): ?>
                <a href="<?= APP_URL ?>/pages/product-detail.php?slug=<?= urlencode($p['slug']) ?>"
                   target="_blank" class="btn btn-sm btn-outline" style="font-size:0.72rem;padding:0.3rem 0.6rem;">
                  View
                </a>
                <?php endif; ?>
                <?php if ($p['status'] === 'pending'): ?>
                <form method="POST" onsubmit="return confirm('Delete this product?')">
                  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                  <input type="hidden" name="delete_product" value="1">
                  <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-danger" style="font-size:0.72rem;padding:0.3rem 0.6rem;">
                    Delete
                  </button>
                </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
