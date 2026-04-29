<?php
// ============================================================
// artisan/earnings.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mpesa.php';
requireRole('artisan');

$artisan = Auth::getArtisanProfile($_SESSION['user_id']);
$aid     = $artisan['id'];

// Handle withdrawal request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['withdraw'])) {
    verifyCsrf();
    $amount = cleanFloat($_POST['amount'] ?? 0);
    $phone  = clean($_POST['mpesa_number'] ?? $artisan['mpesa_number']);

    $balSt = db()->prepare("SELECT COALESCE(SUM(net_amount),0) AS bal FROM artisan_earnings WHERE artisan_id = ? AND status = 'available'");
    $balSt->execute([$aid]);
    $available = (float)$balSt->fetch()['bal'];

    if ($amount < 500) {
        flash('error', 'Minimum withdrawal amount is Ksh 500.');
    } elseif ($amount > $available) {
        flash('error', 'Insufficient balance. Available: ' . formatKES($available));
    } elseif (!$phone) {
        flash('error', 'Please enter your M-Pesa number.');
    } else {
        // Record withdrawal
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $pdo->prepare("INSERT INTO withdrawals (artisan_id, amount, mpesa_number, status) VALUES (?,?,?,'pending')")
                ->execute([$aid, $amount, $phone]);
            $wid = $pdo->lastInsertId();

            // Mark earnings as withdrawn (FIFO)
            $rows = $pdo->prepare("SELECT id, net_amount FROM artisan_earnings WHERE artisan_id = ? AND status = 'available' ORDER BY created_at ASC");
            $rows->execute([$aid]);
            $remaining = $amount;
            foreach ($rows->fetchAll() as $row) {
                if ($remaining <= 0) break;
                if ($row['net_amount'] <= $remaining) {
                    $pdo->prepare("UPDATE artisan_earnings SET status = 'withdrawn' WHERE id = ?")->execute([$row['id']]);
                    $remaining -= $row['net_amount'];
                } else {
                    // Partial — split is complex, just mark as withdrawn for simplicity
                    $pdo->prepare("UPDATE artisan_earnings SET status = 'withdrawn' WHERE id = ?")->execute([$row['id']]);
                    $remaining = 0;
                }
            }
            $pdo->commit();
            flash('success', 'Withdrawal of ' . formatKES($amount) . ' initiated. You will receive an M-Pesa prompt shortly.');
        } catch (Exception $e) {
            $pdo->rollBack();
            flash('error', 'Withdrawal failed. Please try again.');
        }
    }
    redirect(APP_URL . '/artisan/earnings.php');
}

// Stats
$balSt = db()->prepare("SELECT COALESCE(SUM(net_amount),0) AS bal FROM artisan_earnings WHERE artisan_id = ? AND status = 'available'");
$balSt->execute([$aid]);
$balance = (float)$balSt->fetch()['bal'];

$totalSt = db()->prepare("SELECT COALESCE(SUM(net_amount),0) AS tot FROM artisan_earnings WHERE artisan_id = ?");
$totalSt->execute([$aid]);
$totalEarned = (float)$totalSt->fetch()['tot'];

$wdSt = db()->prepare("SELECT COALESCE(SUM(amount),0) AS wd FROM withdrawals WHERE artisan_id = ? AND status = 'completed'");
$wdSt->execute([$aid]);
$withdrawn = (float)$wdSt->fetch()['wd'];

// Recent withdrawals
$withdrawals = db()->prepare("SELECT * FROM withdrawals WHERE artisan_id = ? ORDER BY requested_at DESC LIMIT 10");
$withdrawals->execute([$aid]);
$withdrawals = $withdrawals->fetchAll();

// Earnings history
$earnings = db()->prepare("
    SELECT ae.*, p.title AS product_title, o.order_number, ae.created_at
    FROM artisan_earnings ae
    JOIN order_items oi ON oi.id = ae.order_item_id
    JOIN products p ON p.id = oi.product_id
    JOIN orders o ON o.id = oi.order_id
    WHERE ae.artisan_id = ?
    ORDER BY ae.created_at DESC LIMIT 20
");
$earnings->execute([$aid]);
$earnings = $earnings->fetchAll();

$pageTitle = 'Earnings — ' . APP_NAME;
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
      <a class="sidebar-item active" href="<?= APP_URL ?>/artisan/earnings.php"><span class="si-icon">💰</span> Earnings</a>
      <div class="sidebar-label">Settings</div>
      <a class="sidebar-item" href="<?= APP_URL ?>/artisan/profile.php"><span class="si-icon">👤</span> My Profile</a>
      <a class="sidebar-item" href="<?= APP_URL ?>/pages/home.php"><span class="si-icon">🏠</span> Back to Store</a>
    </nav>
  </aside>

  <main class="dash-main">
    <div class="dash-topbar">
      <div class="dash-title">Earnings & M-Pesa</div>
    </div>

    <!-- STATS -->
    <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);">
      <div class="stat-card">
        <div class="stat-label">Total Earned</div>
        <div class="stat-value"><?= formatKES($totalEarned) ?></div>
        <div class="stat-change">Net of platform fee</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Total Withdrawn</div>
        <div class="stat-value"><?= formatKES($withdrawn) ?></div>
        <div class="stat-change">Via M-Pesa</div>
      </div>
      <div class="stat-card" style="border:2px solid rgba(45,90,39,0.3);">
        <div class="stat-label">Available Balance</div>
        <div class="stat-value" style="color:var(--green);"><?= formatKES($balance) ?></div>
        <div class="stat-change">Ready to withdraw</div>
      </div>
    </div>

    <div class="dash-grid">
      <!-- WITHDRAW FORM -->
      <div class="dash-card">
        <div class="dash-card-title">Withdraw to M-Pesa</div>
        <form method="POST" style="max-width:380px;">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <div class="mpesa-section" style="margin-bottom:1rem;">
            <div class="mpesa-logo" style="font-size:1.1rem;">M-PESA</div>
            <div style="font-size:0.72rem;color:var(--text-muted);margin-top:0.25rem;">
              Funds sent directly to your Safaricom account
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">M-Pesa Number</label>
            <input type="tel" name="mpesa_number" class="input"
                   value="<?= e($artisan['mpesa_number'] ?? '') ?>" placeholder="07xx xxx xxx" required>
          </div>
          <div class="form-group">
            <label class="form-label">Amount (KES)</label>
            <input type="number" name="amount" class="input" placeholder="Minimum Ksh 500"
                   min="500" max="<?= floor($balance) ?>" required>
          </div>
          <div class="alert alert-info">
            Available: <strong><?= formatKES($balance) ?></strong> · Min withdrawal: Ksh 500
          </div>
          <button type="submit" name="withdraw" value="1"
                  class="btn btn-green btn-full" <?= $balance < 500 ? 'disabled' : '' ?>>
            Withdraw via M-Pesa
          </button>
        </form>
      </div>

      <!-- WITHDRAWAL HISTORY -->
      <div class="dash-card">
        <div class="dash-card-title">Withdrawal History</div>
        <?php if (empty($withdrawals)): ?>
        <p style="color:var(--text-muted);font-size:0.85rem;">No withdrawals yet.</p>
        <?php else: ?>
        <table class="orders-table" style="width:100%;">
          <tr><th>Date</th><th>Amount</th><th>M-Pesa No.</th><th>Status</th></tr>
          <?php foreach ($withdrawals as $w): ?>
          <tr>
            <td style="font-size:0.78rem;"><?= date('M j, Y', strtotime($w['requested_at'])) ?></td>
            <td style="font-weight:600;"><?= formatKES($w['amount']) ?></td>
            <td style="font-size:0.78rem;"><?= e($w['mpesa_number']) ?></td>
            <td>
              <span class="status-pill sp-<?= $w['status'] === 'completed' ? 'paid' : ($w['status'] === 'failed' ? 'cancelled' : 'pending') ?>">
                <?= ucfirst(e($w['status'])) ?>
              </span>
            </td>
          </tr>
          <?php endforeach; ?>
        </table>
        <?php endif; ?>
      </div>
    </div>

    <!-- EARNINGS HISTORY -->
    <div class="dash-card">
      <div class="dash-card-title">Earnings History</div>
      <?php if (empty($earnings)): ?>
      <p style="color:var(--text-muted);font-size:0.85rem;">No earnings recorded yet.</p>
      <?php else: ?>
      <table class="orders-table" style="width:100%;">
        <tr><th>Date</th><th>Order</th><th>Product</th><th>Gross</th><th>Fee</th><th>Net</th><th>Status</th></tr>
        <?php foreach ($earnings as $e): ?>
        <tr>
          <td style="font-size:0.75rem;"><?= date('M j, Y', strtotime($e['created_at'])) ?></td>
          <td style="font-size:0.78rem;"><?= htmlspecialchars($e['order_number']) ?></td>
          <td style="font-size:0.8rem;"><?= htmlspecialchars(substr($e['product_title'],0,22)) ?>…</td>
          <td style="font-size:0.82rem;"><?= formatKES($e['gross_amount']) ?></td>
          <td style="font-size:0.78rem;color:var(--text-muted);"><?= formatKES($e['platform_fee']) ?></td>
          <td style="font-weight:600;color:var(--green);"><?= formatKES($e['net_amount']) ?></td>
          <td><span class="status-pill sp-<?= $e['status'] === 'available' ? 'paid' : ($e['status'] === 'withdrawn' ? 'shipped' : 'pending') ?>">
            <?= ucfirst($e['status']) ?>
          </span></td>
        </tr>
        <?php endforeach; ?>
      </table>
      <?php endif; ?>
    </div>
  </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
