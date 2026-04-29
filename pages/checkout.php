<?php
// ============================================================
// pages/checkout.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/mpesa.php';

// Redirect if cart empty
$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) redirect(APP_URL . '/pages/cart.php');

// Build cart items
$ids   = implode(',', array_map('intval', array_keys($cart)));
$prods = db()->query("
    SELECT p.id, p.price, p.artisan_id, p.stock, p.title FROM products p
    WHERE p.id IN ($ids) AND p.status = 'approved'
")->fetchAll();
$prods = array_column($prods, null, 'id');

$items    = [];
$subtotal = 0;
foreach ($cart as $pid => $ci) {
    if (!isset($prods[$pid])) continue;
    $p   = $prods[$pid];
    $qty = min($ci['qty'], $p['stock']);
    $sub = $p['price'] * $qty;
    $items[] = ['product_id' => $pid, 'artisan_id' => $p['artisan_id'],
                'title' => $ci['title'], 'qty' => $qty, 'unit_price' => $p['price'], 'subtotal' => $sub];
    $subtotal += $sub;
}
$delivery = DELIVERY_FEE;
$total    = $subtotal + $delivery;

$errors = [];
$step   = cleanInt($_GET['step'] ?? 1);

// ── POST: save delivery details (step 1→2) ──────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_delivery'])) {
    verifyCsrf();
    $name  = clean($_POST['del_name']    ?? '');
    $phone = clean($_POST['del_phone']   ?? '');
    $addr  = clean($_POST['del_address'] ?? '');
    if (!$name)  $errors['del_name']    = 'Required';
    if (!$phone) $errors['del_phone']   = 'Required';
    if (!$addr)  $errors['del_address'] = 'Required';
    if (!$errors) {
        $_SESSION['checkout_delivery'] = [
            'name'    => $name, 'phone' => $phone,
            'address' => $addr, 'county' => clean($_POST['del_county'] ?? ''),
            'notes'   => clean($_POST['del_notes'] ?? ''),
        ];
        redirect(APP_URL . '/pages/checkout.php?step=2');
    }
}

// ── POST: initiate M-Pesa STK Push (step 2) ─────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['initiate_mpesa'])) {
    verifyCsrf();
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = APP_URL . '/pages/checkout.php?step=2';
        redirect(APP_URL . '/login.php');
    }
    $delivery = $_SESSION['checkout_delivery'] ?? null;
    if (!$delivery) redirect(APP_URL . '/pages/checkout.php?step=1');

    $mpesaPhone = clean($_POST['mpesa_phone'] ?? $delivery['phone']);
    $user = currentUser();

    // Create order
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $orderNum = generateOrderNumber();
        $delivery_fee = DELIVERY_FEE;
        $pdo->prepare("
            INSERT INTO orders (customer_id,order_number,subtotal,delivery_fee,total,
                                delivery_name,delivery_phone,delivery_address,delivery_county,notes)
            VALUES (?,?,?,?,?,?,?,?,?,?)
        ")->execute([
            $user['id'], $orderNum, $subtotal, $delivery_fee, $total,
            $delivery['name'], $delivery['phone'], $delivery['address'],
            $delivery['county'], $delivery['notes'],
        ]);
        $orderId = (int)$pdo->lastInsertId();

        foreach ($items as $it) {
            $pdo->prepare("
                INSERT INTO order_items (order_id,product_id,artisan_id,quantity,unit_price,subtotal)
                VALUES (?,?,?,?,?,?)
            ")->execute([$orderId,$it['product_id'],$it['artisan_id'],$it['qty'],$it['unit_price'],$it['subtotal']]);

            // Artisan earnings
            $gross   = $it['subtotal'];
            $fee     = round($gross * (PLATFORM_FEE_PCT / 100), 2);
            $net     = $gross - $fee;
            $oi_id   = (int)$pdo->lastInsertId();
            $pdo->prepare("
                INSERT INTO artisan_earnings (artisan_id,order_item_id,gross_amount,platform_fee,net_amount)
                VALUES (?,?,?,?,?)
            ")->execute([$it['artisan_id'],$oi_id,$gross,$fee,$net]);
        }
        $pdo->commit();

        // Initiate STK Push
        $mpesaResult = Mpesa::stkPush($orderId, $mpesaPhone, $total);

        $_SESSION['pending_order']    = $orderId;
        $_SESSION['pending_checkout'] = $mpesaResult['CheckoutRequestID'] ?? '';
        unset($_SESSION['cart'], $_SESSION['checkout_delivery']);

        redirect(APP_URL . '/pages/payment-pending.php?order=' . $orderId);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $errors['mpesa'] = 'Order creation failed. Please try again.';
    }
}

$delivery_saved = $_SESSION['checkout_delivery'] ?? null;
if ($step === 2 && !$delivery_saved) $step = 1;

$pageTitle = 'Checkout — ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
?>

<section class="section">
  <div class="container">
    <div class="label">Secure Checkout</div>
    <h1 class="section-title" style="margin:0.4rem 0 2rem;">Checkout</h1>

    <!-- Steps -->
    <div class="checkout-steps" style="max-width:380px;margin-bottom:2.5rem;">
      <div class="step-block">
        <div class="step-circle <?= $step >= 1 ? 'active' : 'pending' ?>">1</div>
        <div class="step-label">Details</div>
      </div>
      <div class="step-line <?= $step >= 2 ? 'done' : '' ?>"></div>
      <div class="step-block">
        <div class="step-circle <?= $step >= 2 ? 'active' : 'pending' ?>">2</div>
        <div class="step-label">Payment</div>
      </div>
      <div class="step-line"></div>
      <div class="step-block">
        <div class="step-circle pending">✓</div>
        <div class="step-label">Done</div>
      </div>
    </div>

    <div class="cart-layout">
      <div>
        <?php if ($step === 1): ?>
        <!-- STEP 1: DELIVERY DETAILS -->
        <div class="dash-card">
          <h3 style="font-family:'Playfair Display',serif;margin-bottom:1.5rem;">Delivery Details</h3>
          <?php if (!empty($errors)): ?>
          <div class="alert alert-error">Please fix the errors below.</div>
          <?php endif; ?>
          <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="save_delivery" value="1">
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Full Name *</label>
                <input type="text" name="del_name" class="input <?= isset($errors['del_name'])?'input-error':'' ?>"
                       value="<?= e($_POST['del_name'] ?? ($delivery_saved['name'] ?? '')) ?>" required>
              </div>
              <div class="form-group">
                <label class="form-label">Phone Number *</label>
                <input type="tel" name="del_phone" class="input <?= isset($errors['del_phone'])?'input-error':'' ?>"
                       value="<?= e($_POST['del_phone'] ?? ($delivery_saved['phone'] ?? '')) ?>"
                       placeholder="07xx xxx xxx" required>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Delivery Address *</label>
              <input type="text" name="del_address" class="input <?= isset($errors['del_address'])?'input-error':'' ?>"
                     value="<?= e($_POST['del_address'] ?? ($delivery_saved['address'] ?? '')) ?>"
                     placeholder="Street address, building, area" required>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">County</label>
                <select name="del_county" class="select">
                  <?php foreach (['Nairobi','Mombasa','Kisumu','Nakuru','Narok','Kisii','Kwale','Other'] as $c): ?>
                  <option value="<?= e($c) ?>" <?= ($delivery_saved['county']??'') === $c ? 'selected':'' ?>><?= e($c) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Order Notes</label>
                <input type="text" name="del_notes" class="input" placeholder="Optional"
                       value="<?= e($delivery_saved['notes'] ?? '') ?>">
              </div>
            </div>
            <button type="submit" class="btn btn-primary">Continue to Payment →</button>
          </form>
        </div>

        <?php else: ?>
        <!-- STEP 2: M-PESA PAYMENT -->
        <div class="dash-card">
          <h3 style="font-family:'Playfair Display',serif;margin-bottom:0.5rem;">Pay with M-Pesa</h3>
          <p style="color:var(--text-muted);font-size:0.875rem;margin-bottom:1.5rem;">
            Secure STK Push via Safaricom Daraja API
          </p>

          <?php if (!empty($errors['mpesa'])): ?>
          <div class="alert alert-error"><?= e($errors['mpesa']) ?></div>
          <?php endif; ?>

          <?php if (!isLoggedIn()): ?>
          <div class="alert alert-info" style="margin-bottom:1.5rem;">
            Please <a href="<?= APP_URL ?>/login.php">log in</a> or
            <a href="<?= APP_URL ?>/register.php">register</a> to complete your purchase.
          </div>
          <?php endif; ?>

          <div class="mpesa-section">
            <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1rem;">
              <div class="mpesa-logo" style="font-size:1.3rem;">M-PESA</div>
              <div>
                <div style="font-weight:600;font-size:0.85rem;">Safaricom Daraja API</div>
                <div style="font-size:0.72rem;color:var(--text-muted);">STK Push · Secure · Instant</div>
              </div>
            </div>
            <form method="POST">
              <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
              <input type="hidden" name="initiate_mpesa" value="1">
              <div class="form-group">
                <label class="form-label">M-Pesa Number</label>
                <input type="tel" name="mpesa_phone" class="input"
                       value="<?= e($delivery_saved['phone'] ?? '') ?>"
                       placeholder="07xx xxx xxx" required>
                <div style="font-size:0.75rem;color:var(--text-muted);margin-top:0.35rem;">
                  A payment prompt will be sent to this number. Enter your M-Pesa PIN to confirm.
                </div>
              </div>
              <div style="background:rgba(45,90,39,0.06);border-radius:var(--radius);padding:1rem;margin:1rem 0;font-size:0.875rem;">
                <div style="display:flex;justify-content:space-between;font-weight:700;font-size:1rem;">
                  <span>Total to pay</span>
                  <span style="color:var(--green);font-family:'Playfair Display',serif;">
                    <?= formatKES($total) ?>
                  </span>
                </div>
              </div>
              <div style="display:flex;gap:1rem;">
                <a href="<?= APP_URL ?>/pages/checkout.php?step=1" class="btn btn-outline">← Back</a>
                <button type="submit" class="btn btn-green btn-full" <?= !isLoggedIn() ? 'disabled' : '' ?>>
                  Send M-Pesa Prompt →
                </button>
              </div>
            </form>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- ORDER SUMMARY SIDEBAR -->
      <div class="order-summary-card">
        <div class="summary-title">Order Summary</div>
        <?php foreach ($items as $it): ?>
        <div class="summary-line">
          <span><?= e($it['title']) ?> × <?= $it['qty'] ?></span>
          <span><?= formatKES($it['subtotal']) ?></span>
        </div>
        <?php endforeach; ?>
        <div class="divider"></div>
        <div class="summary-line"><span style="color:var(--text-muted);">Subtotal</span><span><?= formatKES($subtotal) ?></span></div>
        <div class="summary-line"><span style="color:var(--text-muted);">Delivery</span><span><?= formatKES($delivery) ?></span></div>
        <div class="summary-line total">
          <span>Total</span>
          <span style="color:var(--gold);font-family:'Playfair Display',serif;"><?= formatKES($total) ?></span>
        </div>
        <?php if ($delivery_saved): ?>
        <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border);font-size:0.82rem;color:var(--text-secondary);">
          <strong>Delivering to:</strong><br>
          <?= e($delivery_saved['name']) ?><br>
          <?= e($delivery_saved['address']) ?>, <?= e($delivery_saved['county']) ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
