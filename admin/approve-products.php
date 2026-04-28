<?php
// ============================================================
// admin/approve-products.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
requireRole('admin');

// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $pid    = cleanInt($_POST['product_id'] ?? 0);
    $action = clean($_POST['action'] ?? '');

    if ($pid && in_array($action, ['approved','rejected'])) {
        db()->prepare("UPDATE products SET status = ?, updated_at = NOW() WHERE id = ?")
            ->execute([$action, $pid]);
        flash('success', 'Product ' . $action . ' successfully.');
    }
    redirect(APP_URL . '/admin/approve-products.php');
}

$status = clean($_GET['status'] ?? 'pending');
$products = db()->prepare("
    SELECT p.*, c.name AS cat, u.name AS artisan_name, a.county,
           (SELECT filename FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS image
    FROM products p
    JOIN categories c ON c.id = p.category_id
    JOIN artisans a   ON a.id = p.artisan_id
    JOIN users u      ON u.id = a.user_id
    WHERE p.status = ?
    ORDER BY p.created_at ASC
");
$products->execute([$status]);
$products = $products->fetchAll();

$pageTitle = 'Approve Products — ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
  <aside class="sidebar">
    <div class="sidebar-profile">
      <div class="sidebar-avatar">AD</div>
      <div class="sidebar-name">Administrator</div>
      <div class="sidebar-role">Platform Admin</div>
    </div>
    <nav class="sidebar-nav">
      <div class="sidebar-label">Admin</div>
      <a class="sidebar-item" href="<?= APP_URL ?>/admin/dashboard.php"><span class="si-icon">📊</span> Dashboard</a>
      <a class="sidebar-item active" href="<?= APP_URL ?>/admin/approve-products.php"><span class="si-icon">✅</span> Approve Products</a>
      <a class="sidebar-item" href="<?= APP_URL ?>/admin/manage-users.php"><span class="si-icon">👥</span> Manage Users</a>
      <a class="sidebar-item" href="<?= APP_URL ?>/admin/orders.php"><span class="si-icon">📦</span> Orders</a>
      <a class="sidebar-item" href="<?= APP_URL ?>/admin/contact-messages.php"><span class="si-icon">💬</span> Contact Messages</a>
      <div class="sidebar-label">Site</div>
      <a class="sidebar-item" href="<?= APP_URL ?>/pages/home.php"><span class="si-icon">🏠</span> View Store</a>
    </nav>
  </aside>

  <main class="dash-main">
    <div class="dash-topbar">
      <div class="dash-title">Product Approvals</div>
    </div>

    <!-- Status Tabs -->
    <div style="display:flex;gap:0.5rem;margin-bottom:1.5rem;">
      <?php foreach (['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected'] as $s=>$label): ?>
      <a href="?status=<?= $s ?>"
         class="btn btn-sm <?= $status===$s ? 'btn-primary' : 'btn-outline' ?>">
        <?= $label ?>
      </a>
      <?php endforeach; ?>
    </div>

    <?php if (empty($products)): ?>
    <div style="text-align:center;padding:4rem;background:var(--white);border:1px solid var(--border);border-radius:var(--radius-lg);">
      <div style="font-size:3rem;margin-bottom:1rem;">✅</div>
      <h3 style="font-family:'Playfair Display',serif;">No <?= $status ?> products</h3>
    </div>
    <?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:1.25rem;">
      <?php foreach ($products as $p): ?>
      <div class="card" style="padding:0;overflow:hidden;">
        <!-- Product Image -->
        <div style="height:180px;background:var(--cream-dark);display:flex;align-items:center;justify-content:center;position:relative;">
          <?php if ($p['image']): ?>
          <img src="<?= APP_URL ?>/assets/images/uploads/<?= e($p['image']) ?>"
               alt="" style="width:100%;height:100%;object-fit:cover;">
          <?php else: ?>
          <span style="font-size:3.5rem;">🎨</span>
          <?php endif; ?>
          <span class="badge badge-<?= $p['status']==='approved'?'green':($p['status']==='rejected'?'red':'gold') ?>"
                style="position:absolute;top:10px;right:10px;">
            <?= ucfirst($p['status']) ?>
          </span>
        </div>

        <div style="padding:1rem;">
          <div style="font-size:0.72rem;color:var(--gold);font-weight:600;letter-spacing:0.05em;margin-bottom:0.25rem;">
            <?= e($p['artisan_name']) ?> · <?= e($p['county']) ?>
          </div>
          <div style="font-family:'Playfair Display',serif;font-size:1rem;font-weight:600;margin-bottom:0.35rem;">
            <?= e($p['title']) ?>
          </div>
          <div style="font-size:0.78rem;color:var(--text-muted);margin-bottom:0.5rem;">
            <?= e($p['cat']) ?> · <?= formatKES($p['price']) ?> · Stock: <?= $p['stock'] ?>
          </div>
          <?php if ($p['description']): ?>
          <p style="font-size:0.8rem;color:var(--text-secondary);line-height:1.55;margin-bottom:0.75rem;">
            <?= e(substr($p['description'],0,120)) ?>…
          </p>
          <?php endif; ?>
          <?php if ($p['cultural_story']): ?>
          <div style="background:rgba(201,168,76,0.07);border-left:2px solid var(--gold);padding:0.5rem 0.75rem;border-radius:0 4px 4px 0;font-size:0.75rem;font-style:italic;color:var(--text-secondary);margin-bottom:0.75rem;">
            "<?= e(substr($p['cultural_story'],0,100)) ?>…"
          </div>
          <?php endif; ?>

          <?php if ($p['status'] === 'pending'): ?>
          <form method="POST" style="display:flex;gap:0.5rem;">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
            <button type="submit" name="action" value="approved" class="btn btn-green btn-sm btn-full">✓ Approve</button>
            <button type="submit" name="action" value="rejected" class="btn btn-danger btn-sm btn-full">✗ Reject</button>
          </form>
          <?php else: ?>
          <div style="font-size:0.78rem;color:var(--text-muted);text-align:center;">
            <?= ucfirst($p['status']) ?> on <?= date('M j, Y', strtotime($p['updated_at'])) ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
