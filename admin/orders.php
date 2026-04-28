<?php
// ============================================================
// admin/orders.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
requireRole('admin');

// Update order status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $oid    = cleanInt($_POST['order_id'] ?? 0);
    $status = clean($_POST['status'] ?? '');
    if ($oid && in_array($status, ['paid','processing','shipped','delivered','cancelled'])) {
        db()->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?")
            ->execute([$status, $oid]);
        flash('success', 'Order status updated.');
    }
    redirect(APP_URL . '/admin/orders.php');
}

$statusFilter = clean($_GET['status'] ?? '');
$where  = $statusFilter ? 'WHERE o.status = ?' : '';
$params = $statusFilter ? [$statusFilter] : [];

$orders = db()->prepare("
    SELECT o.*, u.name AS customer_name, u.phone AS customer_phone,
           COUNT(oi.id) AS item_count
    FROM orders o
    JOIN users u ON u.id = o.customer_id
    LEFT JOIN order_items oi ON oi.order_id = o.id
    $where
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT 100
");
$orders->execute($params);
$orders = $orders->fetchAll();

$pageTitle = 'Orders — ' . APP_NAME;
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
      <a class="sidebar-item" href="<?= APP_URL ?>/admin/approve-products.php"><span class="si-icon">✅</span> Approve Products</a>
      <a class="sidebar-item" href="<?= APP_URL ?>/admin/manage-users.php"><span class="si-icon">👥</span> Manage Users</a>
      <a class="sidebar-item active" href="<?= APP_URL ?>/admin/orders.php"><span class="si-icon">📦</span> Orders</a>
      <a class="sidebar-item" href="<?= APP_URL ?>/admin/contact-messages.php"><span class="si-icon">💬</span> Contact Messages</a>
      <div class="sidebar-label">Site</div>
      <a class="sidebar-item" href="<?= APP_URL ?>/pages/home.php"><span class="si-icon">🏠</span> View Store</a>
    </nav>
  </aside>

  <main class="dash-main">
    <div class="dash-topbar"><div class="dash-title">All Orders</div></div>

    <!-- Status Filter Tabs -->
    <div style="display:flex;gap:0.5rem;margin-bottom:1.5rem;flex-wrap:wrap;">
      <?php foreach (['' => 'All', 'pending' => 'Pending', 'paid' => 'Paid', 'processing' => 'Processing', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'] as $s => $lbl): ?>
      <a href="?status=<?= $s ?>" class="btn btn-sm <?= $statusFilter===$s?'btn-primary':'btn-outline' ?>"><?= $lbl ?></a>
      <?php endforeach; ?>
    </div>

    <div class="dash-card" style="padding:0;overflow:hidden;">
      <table class="orders-table" style="width:100%;">
        <thead>
          <tr style="background:var(--cream-dark);">
            <th style="padding:0.875rem 1.25rem;">Order #</th>
            <th>Customer</th>
            <th>Phone</th>
            <th>Items</th>
            <th>Total</th>
            <th>Date</th>
            <th>Status</th>
            <th>Update</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($orders)): ?>
          <tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--text-muted);">No orders found.</td></tr>
          <?php endif; ?>
          <?php foreach ($orders as $o): ?>
          <tr>
            <td style="padding:0.875rem 1.25rem;font-size:0.78rem;font-weight:600;"><?= e($o['order_number']) ?></td>
            <td style="font-size:0.82rem;"><?= e($o['customer_name']) ?></td>
            <td style="font-size:0.78rem;"><?= e($o['customer_phone']) ?></td>
            <td style="font-size:0.85rem;"><?= $o['item_count'] ?></td>
            <td style="font-size:0.85rem;font-weight:600;"><?= formatKES($o['total']) ?></td>
            <td style="font-size:0.75rem;"><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
            <td><span class="status-pill sp-<?= e($o['status']) ?>"><?= ucfirst(e($o['status'])) ?></span></td>
            <td>
              <form method="POST" style="display:flex;gap:0.3rem;">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                <select name="status" class="sort-select" style="font-size:0.75rem;padding:0.3rem 0.5rem;">
                  <?php foreach (['paid','processing','shipped','delivered','cancelled'] as $s): ?>
                  <option value="<?= $s ?>" <?= $o['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                  <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-sm btn-dark" style="font-size:0.72rem;padding:0.3rem 0.5rem;">
                  Save
                </button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
