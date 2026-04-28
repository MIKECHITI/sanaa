<?php
// ============================================================
// admin/dashboard.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
requireRole('admin');

// Site-wide stats
$stats = db()->query("
    SELECT
        (SELECT COUNT(*) FROM users WHERE role='customer')              AS total_customers,
        (SELECT COUNT(*) FROM users WHERE role='artisan')               AS total_artisans,
        (SELECT COUNT(*) FROM products WHERE status='approved')         AS live_products,
        (SELECT COUNT(*) FROM products WHERE status='pending')          AS pending_products,
        (SELECT COUNT(*) FROM orders WHERE status IN ('paid','processing','shipped','delivered')) AS total_orders,
        (SELECT COALESCE(SUM(total),0) FROM orders WHERE status IN ('paid','processing','shipped','delivered')) AS gross_revenue,
        (SELECT COUNT(*) FROM orders WHERE status='paid' AND DATE(created_at)=CURDATE()) AS orders_today
")->fetch();

// Recent orders
$recentOrders = db()->query("
    SELECT o.*, u.name AS customer_name
    FROM orders o JOIN users u ON u.id = o.customer_id
    ORDER BY o.created_at DESC LIMIT 8
")->fetchAll();

// Pending products
$pendingProds = db()->query("
    SELECT p.*, c.name AS cat, u.name AS artisan_name
    FROM products p JOIN categories c ON c.id=p.category_id
    JOIN artisans a ON a.id=p.artisan_id JOIN users u ON u.id=a.user_id
    WHERE p.status='pending' ORDER BY p.created_at ASC LIMIT 10
")->fetchAll();

$pageTitle = 'Admin Dashboard — ' . APP_NAME;
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
      <a class="sidebar-item active" href="<?= APP_URL ?>/admin/dashboard.php"><span class="si-icon">📊</span> Dashboard</a>
      <a class="sidebar-item" href="<?= APP_URL ?>/admin/approve-products.php"><span class="si-icon">✅</span> Approve Products
        <?php if ($stats['pending_products']): ?>
        <span class="badge badge-red" style="margin-left:auto;"><?= $stats['pending_products'] ?></span>
        <?php endif; ?>
      </a>
      <a class="sidebar-item" href="<?= APP_URL ?>/admin/manage-users.php"><span class="si-icon">👥</span> Manage Users</a>
      <a class="sidebar-item" href="<?= APP_URL ?>/admin/orders.php"><span class="si-icon">📦</span> Orders</a>
      <a class="sidebar-item" href="<?= APP_URL ?>/admin/contact-messages.php"><span class="si-icon">💬</span> Contact Messages</a>
      <div class="sidebar-label">Site</div>
      <a class="sidebar-item" href="<?= APP_URL ?>/pages/home.php"><span class="si-icon">🏠</span> View Store</a>
      <a class="sidebar-item" href="<?= APP_URL ?>/logout.php"><span class="si-icon">🚪</span> Logout</a>
    </nav>
  </aside>

  <main class="dash-main">
    <div class="dash-topbar">
      <div class="dash-title">Admin Dashboard</div>
      <div style="font-size:0.8rem;color:var(--text-muted);"><?= date('l, F j, Y') ?></div>
    </div>

    <!-- STATS -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-label">Gross Revenue</div>
        <div class="stat-value"><?= formatKES($stats['gross_revenue']) ?></div>
        <div class="stat-change">All confirmed orders</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Total Orders</div>
        <div class="stat-value"><?= $stats['total_orders'] ?></div>
        <div class="stat-change"><?= $stats['orders_today'] ?> today</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Artisans</div>
        <div class="stat-value"><?= $stats['total_artisans'] ?></div>
        <div class="stat-change">Registered sellers</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Live Products</div>
        <div class="stat-value"><?= $stats['live_products'] ?></div>
        <div class="stat-change">
          <?php if ($stats['pending_products']): ?>
          <span style="color:var(--red);"><?= $stats['pending_products'] ?> pending review</span>
          <?php else: ?> None pending <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="dash-grid">
      <!-- RECENT ORDERS -->
      <div class="dash-card">
        <div class="dash-card-title">
          Recent Orders
          <a href="<?= APP_URL ?>/admin/orders.php" style="font-size:0.78rem;color:var(--gold);">View all →</a>
        </div>
        <table class="orders-table" style="width:100%;">
          <tr><th>Order</th><th>Customer</th><th>Total</th><th>Status</th></tr>
          <?php foreach ($recentOrders as $o): ?>
          <tr>
            <td style="font-size:0.78rem;"><?= e($o['order_number']) ?></td>
            <td style="font-size:0.82rem;"><?= e($o['customer_name']) ?></td>
            <td style="font-size:0.82rem;font-weight:600;"><?= formatKES($o['total']) ?></td>
            <td><span class="status-pill sp-<?= e($o['status']) ?>"><?= ucfirst(e($o['status'])) ?></span></td>
          </tr>
          <?php endforeach; ?>
        </table>
      </div>

      <!-- PENDING APPROVALS -->
      <div class="dash-card">
        <div class="dash-card-title">
          Pending Approvals
          <a href="<?= APP_URL ?>/admin/approve-products.php" style="font-size:0.78rem;color:var(--gold);">View all →</a>
        </div>
        <?php if (empty($pendingProds)): ?>
        <p style="color:var(--text-muted);font-size:0.85rem;">No products pending review. ✓</p>
        <?php else: ?>
        <?php foreach ($pendingProds as $p): ?>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:0.6rem 0;border-bottom:1px solid var(--border);gap:1rem;">
          <div>
            <div style="font-size:0.85rem;font-weight:600;"><?= e(substr($p['title'],0,30)) ?></div>
            <div style="font-size:0.72rem;color:var(--text-muted);"><?= e($p['artisan_name']) ?> · <?= e($p['cat']) ?></div>
          </div>
          <a href="<?= APP_URL ?>/admin/approve-products.php" class="btn btn-sm btn-primary" style="white-space:nowrap;">
            Review →
          </a>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
