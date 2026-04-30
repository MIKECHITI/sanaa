<?php
// ============================================================
// pages/payment-pending.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';

$orderId = cleanInt($_GET['order'] ?? 0);
if (!$orderId) redirect(APP_URL . '/pages/home.php');

$order = db()->prepare('SELECT * FROM orders WHERE id = ?');
$order->execute([$orderId]);
$order = $order->fetch();
if (!$order) redirect(APP_URL . '/pages/home.php');

$pageTitle = 'Payment Pending — ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
?>

<section class="section">
  <div class="container-sm" style="max-width:540px;">
    <div id="pendingView">
      <div style="text-align:center;padding:2rem 0;">
        <div class="mpesa-spinner" style="width:56px;height:56px;border-width:4px;margin-bottom:1.5rem;"></div>
        <h2 style="font-family:'Playfair Display',serif;font-size:1.8rem;margin-bottom:0.75rem;">
          Waiting for M-Pesa...
        </h2>
        <p style="color:var(--text-muted);margin-bottom:1.5rem;">
          We sent a payment prompt to your phone.<br>
          Please enter your <strong>M-Pesa PIN</strong> to complete the payment.
        </p>
      </div>

      <div class="mpesa-section">
        <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.75rem;">
          <div class="mpesa-logo">M-PESA</div>
          <div>
            <div style="font-weight:600;font-size:0.85rem;">STK Push Sent</div>
            <div style="font-size:0.72rem;color:var(--text-muted);">Check your phone now</div>
          </div>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:0.875rem;margin-bottom:0.5rem;">
          <span style="color:var(--text-muted);">Order</span>
          <strong><?= e($order['order_number']) ?></strong>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:0.875rem;">
          <span style="color:var(--text-muted);">Amount</span>
          <strong style="color:var(--green);font-family:'Playfair Display',serif;">
            <?= formatKES($order['total']) ?>
          </strong>
        </div>
      </div>

      <div style="background:var(--cream-dark);border-radius:var(--radius-lg);padding:1rem;margin:1.5rem 0;font-size:0.82rem;color:var(--text-secondary);">
        <strong>Didn't receive the prompt?</strong> Check that your phone is on and has network. You can also
        <a href="<?= APP_URL ?>/pages/checkout.php" style="color:var(--gold);">retry payment</a>.
      </div>

      <p style="text-align:center;font-size:0.78rem;color:var(--text-muted);" id="statusMsg">
        Checking payment status...
      </p>
    </div>

    <!-- SUCCESS STATE (shown by JS) -->
    <div id="successView" style="display:none;text-align:center;padding:2rem 0;">
      <div class="success-icon" style="margin:0 auto 1.5rem;">✅</div>
      <h2 style="font-family:'Playfair Display',serif;font-size:2rem;margin-bottom:0.75rem;">Payment Successful!</h2>
      <p style="color:var(--text-muted);margin-bottom:1.5rem;">
        Your order has been confirmed and the artisan has been notified.
      </p>
      <div style="background:var(--cream-dark);border:1px solid var(--border);border-radius:var(--radius-lg);padding:1.25rem;text-align:left;margin-bottom:1.5rem;">
        <div style="display:flex;justify-content:space-between;margin-bottom:0.5rem;font-size:0.875rem;">
          <span style="color:var(--text-muted);">Order ID</span>
          <strong><?= e($order['order_number']) ?></strong>
        </div>
        <div style="display:flex;justify-content:space-between;margin-bottom:0.5rem;font-size:0.875rem;">
          <span style="color:var(--text-muted);">M-Pesa Ref</span>
          <strong id="mpesaRef">—</strong>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:0.875rem;">
          <span style="color:var(--text-muted);">Estimated Delivery</span>
          <strong>3–7 business days</strong>
        </div>
      </div>
      <a href="<?= APP_URL ?>/pages/home.php" class="btn btn-primary">Continue Shopping</a>
    </div>

    <!-- FAILED STATE -->
    <div id="failedView" style="display:none;text-align:center;padding:2rem 0;">
      <div style="font-size:3rem;margin-bottom:1rem;">❌</div>
      <h2 style="font-family:'Playfair Display',serif;font-size:1.8rem;margin-bottom:0.75rem;">Payment Failed</h2>
      <p style="color:var(--text-muted);margin-bottom:1.5rem;" id="failMsg">Your payment was not completed.</p>
      <div style="display:flex;gap:1rem;justify-content:center;">
        <a href="<?= APP_URL ?>/pages/checkout.php" class="btn btn-primary">Try Again</a>
        <a href="<?= APP_URL ?>/pages/cart.php" class="btn btn-outline">Back to Cart</a>
      </div>
    </div>
  </div>
</section>

<script>
const orderId = <?= $orderId ?>;
let polls = 0;
const maxPolls = 24; // 2 minutes

function poll() {
    if (polls++ >= maxPolls) {
        document.getElementById('statusMsg').textContent = 'Timed out. Please check your order status or retry.';
        return;
    }
    fetch(window.APP_URL + '/api/mpesa-status.php?order=' + orderId)
        .then(r => r.json())
        .then(data => {
            if (data.status === 'completed') {
                document.getElementById('pendingView').style.display = 'none';
                document.getElementById('successView').style.display = 'block';
                if (data.receipt) document.getElementById('mpesaRef').textContent = data.receipt;
            } else if (data.status === 'failed' || data.status === 'cancelled') {
                document.getElementById('pendingView').style.display = 'none';
                document.getElementById('failedView').style.display = 'block';
                if (data.desc) document.getElementById('failMsg').textContent = data.desc;
            } else {
                document.getElementById('statusMsg').textContent =
                    'Waiting... (' + polls + '/' + maxPolls + ')';
                setTimeout(poll, 5000);
            }
        })
        .catch(() => setTimeout(poll, 8000));
}

setTimeout(poll, 5000);
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
