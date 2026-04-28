<?php
// ============================================================
// admin/manage-users.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
requireRole('admin');

// Toggle user status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $uid    = cleanInt($_POST['user_id'] ?? 0);
    $action = clean($_POST['action'] ?? '');
    if ($uid && in_array($action, ['active','suspended'])) {
        db()->prepare("UPDATE users SET status = ? WHERE id = ? AND role != 'admin'")
            ->execute([$action, $uid]);
        flash('success', 'User status updated.');
    }
    redirect(APP_URL . '/admin/manage-users.php');
}

$role   = clean($_GET['role'] ?? 'artisan');
$search = clean($_GET['search'] ?? '');

$where  = ['u.role = ?'];
$params = [$role];
if ($search) {
    $where[]  = '(u.name LIKE ? OR u.email LIKE ?)';
    $like     = "%$search%";
    $params   = array_merge($params, [$like, $like]);
}

$users = db()->prepare("
    SELECT u.*,
           COALESCE((SELECT COUNT(*) FROM products WHERE artisan_id = a.id),0) AS product_count,
           COALESCE((SELECT COUNT(*) FROM orders WHERE customer_id = u.id),0)  AS order_count
    FROM users u
    LEFT JOIN artisans a ON a.user_id = u.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY u.created_at DESC
");
$users->execute($params);
$users = $users->fetchAll();

$pageTitle = 'Manage Users — ' . APP_NAME;
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
      <a class="sidebar-item active" href="<?= APP_URL ?>/admin/manage-users.php"><span class="si-icon">👥</span> Manage Users</a>
      <a class="sidebar-item" href="<?= APP_URL ?>/admin/orders.php"><span class="si-icon">📦</span> Orders</a>
      <a class="sidebar-item" href="<?= APP_URL ?>/admin/contact-messages.php"><span class="si-icon">💬</span> Contact Messages</a>
      <div class="sidebar-label">Site</div>
      <a class="sidebar-item" href="<?= APP_URL ?>/pages/home.php"><span class="si-icon">🏠</span> View Store</a>
    </nav>
  </aside>

  <main class="dash-main">
    <div class="dash-topbar">
      <div class="dash-title">Manage Users</div>
    </div>

    <!-- Filters -->
    <div style="display:flex;gap:1rem;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;">
      <div style="display:flex;gap:0.5rem;">
        <?php foreach (['artisan'=>'Artisans','customer'=>'Customers'] as $r=>$lbl): ?>
        <a href="?role=<?= $r ?>&search=<?= urlencode($search) ?>"
           class="btn btn-sm <?= $role===$r?'btn-primary':'btn-outline' ?>"><?= $lbl ?></a>
        <?php endforeach; ?>
      </div>
      <form method="GET" style="display:flex;gap:0.5rem;flex:1;max-width:340px;">
        <input type="hidden" name="role" value="<?= e($role) ?>">
        <input type="text" name="search" class="input" placeholder="Search name or email…"
               value="<?= e($search) ?>" style="font-size:0.85rem;">
        <button type="submit" class="btn btn-dark btn-sm">Search</button>
      </form>
    </div>

    <div class="dash-card" style="padding:0;overflow:hidden;">
      <table class="orders-table" style="width:100%;">
        <thead>
          <tr style="background:var(--cream-dark);">
            <th style="padding:0.875rem 1.25rem;">Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th><?= $role === 'artisan' ? 'Products' : 'Orders' ?></th>
            <th>Joined</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($users)): ?>
          <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-muted);">No users found.</td></tr>
          <?php endif; ?>
          <?php foreach ($users as $u): ?>
          <tr>
            <td style="padding:0.875rem 1.25rem;">
              <div style="display:flex;align-items:center;gap:0.75rem;">
                <div style="width:36px;height:36px;border-radius:50%;background:var(--earth-mid);display:flex;align-items:center;justify-content:center;color:var(--gold);font-weight:700;font-size:0.85rem;flex-shrink:0;">
                  <?= strtoupper(substr($u['name'],0,2)) ?>
                </div>
                <span style="font-weight:600;font-size:0.875rem;"><?= e($u['name']) ?></span>
              </div>
            </td>
            <td style="font-size:0.82rem;"><?= e($u['email']) ?></td>
            <td style="font-size:0.82rem;"><?= e($u['phone'] ?? '—') ?></td>
            <td style="font-size:0.85rem;font-weight:600;">
              <?= $role === 'artisan' ? $u['product_count'] : $u['order_count'] ?>
            </td>
            <td style="font-size:0.78rem;"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
            <td>
              <span class="status-pill <?= $u['status']==='active'?'sp-paid':'sp-cancelled' ?>">
                <?= ucfirst(e($u['status'])) ?>
              </span>
            </td>
            <td>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                <?php if ($u['status'] === 'active'): ?>
                <button type="submit" name="action" value="suspended"
                        class="btn btn-sm btn-danger" style="font-size:0.72rem;padding:0.3rem 0.7rem;">
                  Suspend
                </button>
                <?php else: ?>
                <button type="submit" name="action" value="active"
                        class="btn btn-sm btn-green" style="font-size:0.72rem;padding:0.3rem 0.7rem;">
                  Activate
                </button>
                <?php endif; ?>
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
