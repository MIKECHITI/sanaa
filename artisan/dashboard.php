<?php
// ============================================================
// artisan/dashboard.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('artisan');

$artisan = Auth::getArtisanProfile($_SESSION['user_id']);
if (!$artisan) { flash('error','Artisan profile not found.'); redirect(APP_URL.'/logout.php'); }
$aid = $artisan['id'];

// Stats
$stats = db()->prepare("
    SELECT
        COUNT(DISTINCT o.id)            AS total_orders,
        COALESCE(SUM(oi.subtotal),0)    AS gross_revenue,
        COALESCE(SUM(ae.net_amount),0)  AS net_revenue,
        COUNT(DISTINCT p.id)            AS total_products
    FROM artisans a
    LEFT JOIN products p    ON p.artisan_id = a.id AND p.status = 'approved'
    LEFT JOIN order_items oi ON oi.artisan_id = a.id
    LEFT JOIN orders o      ON o.id = oi.order_id AND o.status IN ('paid','processing','shipped','delivered')
    LEFT JOIN artisan_earnings ae ON ae.artisan_id = a.id AND ae.status IN ('available','withdrawn')
    WHERE a.id = ?
");
$stats->execute([$aid]);
$stats = $stats->fetch();

// Recent orders
$orders = db()->prepare("
    SELECT o.order_number, o.status, o.created_at, oi.quantity, oi.subtotal,
           p.title AS product_title, u.name AS customer_name
    FROM order_items oi
    JOIN orders o   ON o.id = oi.order_id
    JOIN products p ON p.id = oi.product_id
    JOIN users u    ON u.id = o.customer_id
    WHERE oi.artisan_id = ?
    ORDER BY o.created_at DESC LIMIT 10
");
$orders->execute([$aid]);
$orders = $orders->fetchAll();

// Top products
$topProducts = db()->prepare("
    SELECT p.title, p.price, COALESCE(SUM(oi.quantity),0) AS units_sold,
           (SELECT filename FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS image
    FROM products p
    LEFT JOIN order_items oi ON oi.product_id = p.id
    WHERE p.artisan_id = ?
    GROUP BY p.id ORDER BY units_sold DESC LIMIT 5
");
$topProducts->execute([$aid]);
$topProducts = $topProducts->fetchAll();

// Available balance
$balance = db()->prepare("SELECT COALESCE(SUM(net_amount),0) AS bal FROM artisan_earnings WHERE artisan_id = ? AND status = 'available'");
$balance->execute([$aid]);
$balance = (float)$balance->fetch()['bal'];

$pageTitle = 'Artisan Dashboard — ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-profile">
      <div class="sidebar-avatar"><?= strtoupper(substr($artisan['name'],0,2)) ?></div>
      <div class="sidebar-name"><?= e($artisan['name']) ?></div>
      <div class="sidebar-role"><?= e($artisan['county'] ?? 'Artisan') ?></div>
    </div>
    <nav class="sidebar-nav">
      <div class="sidebar-label">Main</div>
      <a class="sidebar-item <?= basename($_SERVER['PHP_SELF'])==='dashboard.php'?'active':'' ?>"
         href="<?= APP_URL ?>/artisan/dashboard.php">
        <span class="si-icon">📊</span> Overview
      </a>
      <a class="sidebar-item" href="<?= APP_URL ?>/artisan/my-products.php">
        <span class="si-icon">🛍️</span> My Products
      </a>
      <a class="sidebar-item" href="<?= APP_URL ?>/artisan/upload-product.php">
        <span class="si-icon">➕</span> Upload Product
      </a>
      <a class="sidebar-item" href="<?= APP_URL ?>/artisan/earnings.php">
        <span class="si-icon">💰</span> Earnings
      </a>
      <div class="sidebar-label">Settings</div>
      <a class="sidebar-item" href="<?= APP_URL ?>/artisan/profile.php">
        <span class="si-icon">👤</span> My Profile
      </a>
      <a class="sidebar-item" href="<?= APP_URL ?>/pages/home.php">
        <span class="si-icon">🏠</span> Back to Store
      </a>
    </nav>
  </aside>

  <!-- MAIN -->
  <main class="dash-main">
    <div class="dash-topbar">
      <div>
        <div class="label">Good day,</div>
        <div class="dash-title"><?= e(explode(' ',$artisan['name'])[0]) ?>'s Dashboard</div>
      </div>
      <a href="<?= APP_URL ?>/artisan/upload-product.php" class="btn btn-primary">+ Upload Product</a>
    </div>

    <!-- STATS -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-label">Total Revenue</div>
        <div class="stat-value"><?= formatKES($stats['net_revenue']) ?></div>
        <div class="stat-change">Net after platform fee</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Available Balance</div>
        <div class="stat-value"><?= formatKES($balance) ?></div>
        <div class="stat-change"><a href="<?= APP_URL ?>/artisan/earnings.php" style="color:var(--green);">Withdraw via M-Pesa →</a></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Total Orders</div>
        <div class="stat-value"><?= $stats['total_orders'] ?></div>
        <div class="stat-change">All time</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Products Listed</div>
        <div class="stat-value"><?= $stats['total_products'] ?></div>
        <div class="stat-change">Approved &amp; live</div>
      </div>
    </div>

    <div class="dash-grid">
      <!-- RECENT ORDERS -->
      <div class="dash-card">
        <div class="dash-card-title">
          Recent Orders
          <?php if (count($orders) > 0): ?>
          <span class="badge badge-gold"><?= count($orders) ?></span>
          <?php endif; ?>
        </div>
        <?php if (empty($orders)): ?>
          <p style="color:var(--text-muted);font-size:0.85rem;">No orders yet.</p>
        <?php else: ?>
        <table class="orders-table">
          <tr><th>Order</th><th>Product</th><th>Amount</th><th>Status</th></tr>
          <?php foreach ($orders as $o): ?>
          <tr>
            <td style="font-size:0.8rem;"><?= e($o['order_number']) ?></td>
            <td style="font-size:0.82rem;"><?= e(substr($o['product_title'],0,22)) ?>…</td>
            <td style="font-size:0.82rem;"><?= formatKES($o['subtotal']) ?></td>
            <td><span class="status-pill sp-<?= e($o['status']) ?>"><?= ucfirst(e($o['status'])) ?></span></td>
          </tr>
          <?php endforeach; ?>
        </table>
        <?php endif; ?>
      </div>

      <!-- TOP PRODUCTS -->
      <div class="dash-card">
        <div class="dash-card-title">Top Selling Products</div>
        <?php if (empty($topProducts)): ?>
          <p style="color:var(--text-muted);font-size:0.85rem;">No products yet.</p>
        <?php else: ?>
        <?php foreach ($topProducts as $tp): ?>
        <div class="mini-product">
          <div class="mini-product-img" style="background:var(--cream-dark);">
            <?php if ($tp['image']): ?>
            <img src="<?= APP_URL ?>/assets/images/uploads/<?= e($tp['image']) ?>"
                 alt="" style="width:100%;height:100%;object-fit:cover;border-radius:4px;">
            <?php else: ?>🎨<?php endif; ?>
          </div>
          <div>
            <div class="mini-product-name"><?= e(substr($tp['title'],0,28)) ?></div>
            <div class="mini-product-price"><?= formatKES($tp['price']) ?></div>
          </div>
          <div class="mini-product-sales"><?= $tp['units_sold'] ?> sold</div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- M-PESA SUMMARY -->
    <div class="dash-card">
      <div class="dash-card-title">M-Pesa Payment Summary</div>
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;">
        <div style="background:rgba(45,90,39,0.06);border:1px solid rgba(45,90,39,0.15);border-radius:var(--radius-lg);padding:1rem;text-align:center;">
          <div style="font-size:1.5rem;font-weight:700;color:var(--green);font-family:'Playfair Display',serif;">
            <?= formatKES($stats['net_revenue']) ?>
          </div>
          <div style="font-size:0.72rem;color:var(--text-muted);margin-top:0.25rem;">Total Earned</div>
        </div>
        <div style="background:rgba(201,168,76,0.08);border:1px solid var(--border);border-radius:var(--radius-lg);padding:1rem;text-align:center;">
          <div style="font-size:1.5rem;font-weight:700;color:var(--gold-dark);font-family:'Playfair Display',serif;">
            <?= formatKES($balance) ?>
          </div>
          <div style="font-size:0.72rem;color:var(--text-muted);margin-top:0.25rem;">Available to Withdraw</div>
        </div>
        <div style="background:rgba(44,24,16,0.04);border:1px solid var(--border);border-radius:var(--radius-lg);padding:1rem;text-align:center;">
          <div style="font-size:1.5rem;font-weight:700;color:var(--earth-mid);font-family:'Playfair Display',serif;">
            <?= PLATFORM_FEE_PCT ?>%
          </div>
          <div style="font-size:0.72rem;color:var(--text-muted);margin-top:0.25rem;">Platform Fee</div>
        </div>
      </div>
    </div>
  </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
