<?php
// ============================================================
// pages/cart.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = clean($_POST['action'] ?? '');
    $pid    = cleanInt($_POST['product_id'] ?? 0);

    if ($action === 'update' && $pid) {
        $qty = max(0, cleanInt($_POST['qty'] ?? 0));
        if ($qty === 0) {
            unset($_SESSION['cart'][$pid]);
        } else {
            $_SESSION['cart'][$pid]['qty'] = $qty;
        }
    } elseif ($action === 'remove' && $pid) {
        unset($_SESSION['cart'][$pid]);
    } elseif ($action === 'clear') {
        $_SESSION['cart'] = [];
    }
    redirect(APP_URL . '/pages/cart.php');
}

// Enrich cart items from DB
$cart  = $_SESSION['cart'] ?? [];
$items = [];
if ($cart) {
    $ids = implode(',', array_map('intval', array_keys($cart)));
    $rows = db()->query("
        SELECT p.id, p.title, p.price, p.stock, p.slug,
               u.name AS artisan_name,
               (SELECT filename FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS image
        FROM products p
        JOIN artisans a ON a.id = p.artisan_id
        JOIN users u    ON u.id = a.user_id
        WHERE p.id IN ($ids) AND p.status = 'approved'
    ")->fetchAll();

    foreach ($rows as $r) {
        $qty = $cart[$r['id']]['qty'] ?? 1;
        $items[] = array_merge($r, ['qty' => $qty, 'subtotal' => $r['price'] * $qty]);
    }
}

$subtotal = array_sum(array_column($items, 'subtotal'));
$delivery = $subtotal > 0 ? DELIVERY_FEE : 0;
$total    = $subtotal + $delivery;

$pageTitle = 'Cart — ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
?>

<section class="section">
  <div class="container">
    <div class="label">Your Selection</div>
    <h1 class="section-title" style="margin:0.4rem 0 2rem;">Shopping Cart</h1>

    <?php if (empty($items)): ?>
      <div style="text-align:center;padding:5rem 2rem;background:var(--white);border:1px solid var(--border);border-radius:var(--radius-lg);">
        <div style="font-size:3.5rem;margin-bottom:1rem;">🛒</div>
        <h3 style="font-family:'Playfair Display',serif;margin-bottom:0.5rem;">Your cart is empty</h3>
        <p style="color:var(--text-muted);margin-bottom:1.5rem;">Discover authentic Kenyan ornaments.</p>
        <a href="<?= APP_URL ?>/pages/products.php" class="btn btn-primary">Browse Products</a>
      </div>
    <?php else: ?>
    <div class="cart-layout">
      <!-- CART ITEMS -->
      <div>
        <form method="POST" id="cartForm">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="action" id="cartAction" value="update">
          <input type="hidden" name="product_id" id="cartPid" value="">

          <?php foreach ($items as $item): ?>
          <div class="cart-item">
            <div class="cart-item-img">
              <?php if ($item['image']): ?>
                <img src="<?= APP_URL ?>/assets/images/uploads/<?= e($item['image']) ?>"
                     alt="<?= e($item['title']) ?>" style="width:100%;height:100%;object-fit:cover;">
              <?php else: ?>
                <span style="font-size:2rem;">🎨</span>
              <?php endif; ?>
            </div>
            <div class="cart-item-body">
              <div class="cart-item-artisan"><?= e($item['artisan_name']) ?></div>
              <a href="<?= APP_URL ?>/pages/product-detail.php?slug=<?= urlencode($item['slug']) ?>"
                 class="cart-item-name"><?= e($item['title']) ?></a>
              <div style="font-size:0.78rem;color:var(--text-muted);margin-top:0.2rem;">
                <?= formatKES($item['price']) ?> each
              </div>
              <div class="cart-item-footer">
                <div class="quantity-control">
                  <button type="button" class="qty-btn" onclick="cartQty(<?= $item['id'] ?>,-1,<?= $item['qty'] ?>)">−</button>
                  <input type="number" id="qty_<?= $item['id'] ?>" class="qty-val"
                         value="<?= $item['qty'] ?>" min="1" max="<?= $item['stock'] ?>" readonly>
                  <button type="button" class="qty-btn" onclick="cartQty(<?= $item['id'] ?>,1,<?= $item['qty'] ?>)">+</button>
                </div>
                <div class="cart-item-price"><?= formatKES($item['subtotal']) ?></div>
                <button type="button" class="btn-remove"
                        onclick="cartRemove(<?= $item['id'] ?>)">Remove</button>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </form>

        <div style="display:flex;gap:1rem;margin-top:1rem;">
          <a href="<?= APP_URL ?>/pages/products.php" class="btn btn-outline">← Continue Shopping</a>
          <form method="POST" style="margin:0;">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="clear">
            <button type="submit" class="btn" style="background:none;border:1px solid var(--border);color:var(--text-muted);cursor:pointer;border-radius:var(--radius);">
              Clear Cart
            </button>
          </form>
        </div>
      </div>

      <!-- ORDER SUMMARY -->
      <div class="order-summary-card">
        <div class="summary-title">Order Summary</div>
        <?php foreach ($items as $item): ?>
        <div class="summary-line">
          <span><?= e($item['title']) ?> × <?= $item['qty'] ?></span>
          <span><?= formatKES($item['subtotal']) ?></span>
        </div>
        <?php endforeach; ?>
        <div class="divider"></div>
        <div class="summary-line"><span style="color:var(--text-muted);">Subtotal</span><span><?= formatKES($subtotal) ?></span></div>
        <div class="summary-line"><span style="color:var(--text-muted);">Delivery</span><span><?= formatKES($delivery) ?></span></div>
        <div class="summary-line total">
          <span>Total</span>
          <span style="color:var(--gold);font-family:'Playfair Display',serif;"><?= formatKES($total) ?></span>
        </div>

        <div class="mpesa-section">
          <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.5rem;">
            <div class="mpesa-logo">M-PESA</div>
            <div style="font-size:0.72rem;color:var(--text-muted);">Safaricom · STK Push</div>
          </div>
          <div style="font-size:0.75rem;color:var(--text-secondary);">
            Fast, secure payment sent directly to your phone.
          </div>
        </div>

        <a href="<?= APP_URL ?>/pages/checkout.php" class="btn btn-primary btn-full">
          Proceed to Checkout →
        </a>
        <p style="font-size:0.72rem;color:var(--text-muted);text-align:center;margin-top:0.75rem;">
          Artisan receives 97.5% of your payment
        </p>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>

<script>
function cartQty(pid, delta, current) {
    const input = document.getElementById('qty_' + pid);
    const newQty = Math.max(1, Math.min(parseInt(input.max)||99, current + delta));
    input.value = newQty;
    document.getElementById('cartAction').value = 'update';
    document.getElementById('cartPid').value = pid;
    // Add hidden qty field
    let h = document.getElementById('hqty_'+pid);
    if (!h) {
        h = document.createElement('input');
        h.type = 'hidden'; h.name = 'qty'; h.id = 'hqty_'+pid;
        document.getElementById('cartForm').appendChild(h);
    }
    h.value = newQty;
    document.getElementById('cartForm').submit();
}
function cartRemove(pid) {
    document.getElementById('cartAction').value = 'remove';
    document.getElementById('cartPid').value = pid;
    document.getElementById('cartForm').submit();
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
