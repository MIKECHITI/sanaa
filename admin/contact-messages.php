<?php
// ============================================================
// admin/contact-messages.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
requireRole('admin');

// Handle mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_read'])) {
        $messageId = cleanInt($_POST['message_id'] ?? 0);
        if ($messageId) {
            db()->prepare("UPDATE contact_messages SET status = 'read' WHERE id = ?")->execute([$messageId]);
            flash('success', 'Message marked as read.');
            redirect(APP_URL . '/admin/contact-messages.php');
        }
    } elseif (isset($_POST['mark_all_read'])) {
        db()->prepare("UPDATE contact_messages SET status = 'read' WHERE status = 'new'")->execute();
        flash('success', 'All messages marked as read.');
        redirect(APP_URL . '/admin/contact-messages.php');
    }
}

// Get contact messages
$messages = db()->query("
    SELECT * FROM contact_messages
    ORDER BY 
        CASE WHEN status = 'new' THEN 0 ELSE 1 END,
        created_at DESC
")->fetchAll();

// Count unread messages
$unreadCount = array_reduce($messages, function($count, $msg) {
    return $count + ($msg['status'] === 'new' ? 1 : 0);
}, 0);

$pageTitle = 'Contact Messages — ' . APP_NAME;
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
      <a class="sidebar-item" href="<?= APP_URL ?>/admin/approve-products.php"><span class="si-icon">✅</span> Approve Products
        <?php
        $pending = db()->query("SELECT COUNT(*) as count FROM products WHERE status='pending'")->fetch()['count'];
        if ($pending): ?>
        <span class="badge badge-red" style="margin-left:auto;"><?= $pending ?></span>
        <?php endif; ?>
      </a>
      <a class="sidebar-item" href="<?= APP_URL ?>/admin/manage-users.php"><span class="si-icon">👥</span> Manage Users</a>
      <a class="sidebar-item" href="<?= APP_URL ?>/admin/orders.php"><span class="si-icon">📦</span> Orders</a>
      <a class="sidebar-item active" href="<?= APP_URL ?>/admin/contact-messages.php"><span class="si-icon">💬</span> Contact Messages</a>
      <div class="sidebar-label">Site</div>
      <a class="sidebar-item" href="<?= APP_URL ?>/pages/home.php"><span class="si-icon">🏠</span> View Store</a>
      <a class="sidebar-item" href="<?= APP_URL ?>/logout.php"><span class="si-icon">🚪</span> Logout</a>
    </nav>
  </aside>

  <main class="dash-main">
    <div class="dash-topbar">
      <div class="dash-title">Contact Messages</div>
      <div style="display:flex;align-items:center;gap:1rem;">
        <div style="font-size:0.8rem;color:var(--text-muted);">
          <?= count($messages) ?> total messages
          <?php if ($unreadCount > 0): ?>
            <span style="color:var(--gold);font-weight:600;">(<?= $unreadCount ?> unread)</span>
          <?php endif; ?>
        </div>
        <?php if ($unreadCount > 0): ?>
        <form method="POST" style="margin:0;">
          <button type="submit" name="mark_all_read" class="btn btn-sm btn-primary">Mark All as Read</button>
        </form>
        <?php endif; ?>
      </div>
    </div>

    <div class="dash-content">

    <div class="dash-content">
      <?php
      $flash = getFlash();
      if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?>" style="margin-bottom:1.5rem;"><?= e($flash['msg']) ?></div>
      <?php endif; ?>

      <?php if (empty($messages)): ?>
        <div style="text-align:center;padding:4rem 2rem;background:var(--white);border:1px solid var(--border);border-radius:var(--radius-lg);">
          <div style="font-size:3rem;margin-bottom:1rem;">💬</div>
          <h3 style="font-family:'Playfair Display',serif;">No messages yet</h3>
          <p style="color:var(--text-muted);margin-top:0.5rem;">Contact messages from users will appear here.</p>
        </div>
      <?php else: ?>
        <div class="messages-list">
          <?php foreach ($messages as $msg): ?>
          <div class="message-card <?= $msg['status'] === 'new' ? 'message-unread' : 'message-read' ?>">
            <div class="message-header">
              <div class="message-from">
                <strong><?= e($msg['name']) ?></strong>
                <span style="color:var(--text-muted);font-size:0.85rem;">&lt;<?= e($msg['email']) ?>&gt;</span>
                <span class="message-status status-<?= $msg['status'] ?>">
                  <?= ucfirst($msg['status']) ?>
                </span>
              </div>
              <div class="message-date">
                <?= date('M j, Y g:i A', strtotime($msg['created_at'])) ?>
              </div>
            </div>
            <div class="message-subject">
              <strong>Subject:</strong> <?= e($msg['subject']) ?>
            </div>
            <div class="message-body">
              <?= nl2br(e($msg['message'])) ?>
            </div>
            <div class="message-actions">
              <?php if ($msg['status'] === 'new'): ?>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                <button type="submit" name="mark_read" class="btn btn-sm btn-primary">Mark as Read</button>
              </form>
              <?php endif; ?>
              <a href="mailto:michaelchimwo@gmail.com?subject=Re: <?= urlencode($msg['subject']) ?>" class="btn btn-sm btn-outline">Reply via Email</a>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </main>
</div>

<style>
.messages-list { display: flex; flex-direction: column; gap: 1.5rem; }
.message-card { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.5rem; transition: all 0.2s; }
.message-unread { border-left: 4px solid var(--gold); background: linear-gradient(90deg, rgba(201,168,76,0.05) 0%, rgba(255,255,255,1) 20px); }
.message-read { opacity: 0.8; }
.message-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; }
.message-from { font-size: 1.1rem; display: flex; flex-direction: column; gap: 0.25rem; }
.message-status { font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.5rem; border-radius: var(--radius); text-transform: uppercase; letter-spacing: 0.5px; }
.status-new { background: var(--gold); color: var(--earth); }
.status-read { background: var(--success, #2D5A27); color: white; }
.status-replied { background: var(--primary, #8B1A1A); color: white; }
.message-date { font-size: 0.85rem; color: var(--text-muted); }
.message-subject { margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border-light); }
.message-body { color: var(--text-primary); line-height: 1.6; margin-bottom: 1rem; }
.message-actions { display: flex; gap: 0.75rem; }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>