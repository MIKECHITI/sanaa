<?php
// ============================================================
// pages/product-detail.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';

$slug = clean($_GET['slug'] ?? '');
if (!$slug) redirect(APP_URL . '/pages/products.php');

$st = db()->prepare("
    SELECT p.*, c.name AS category_name, c.slug AS category_slug,
           u.name AS artisan_name, u.email AS artisan_email,
           a.bio AS artisan_bio, a.county, a.years_exp, a.verified,
           COALESCE((SELECT ROUND(AVG(rating),1) FROM reviews WHERE product_id = p.id),0) AS avg_rating,
           COALESCE((SELECT COUNT(*) FROM reviews WHERE product_id = p.id),0) AS review_count
    FROM products p
    JOIN categories c ON c.id = p.category_id
    JOIN artisans a   ON a.id = p.artisan_id
    JOIN users u      ON u.id = a.user_id
    WHERE p.slug = ? AND p.status = 'approved'
");
$st->execute([$slug]);
$product = $st->fetch();
if (!$product) { flash('error','Product not found.'); redirect(APP_URL . '/pages/products.php'); }

// Images
$images = db()->prepare('SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC');
$images->execute([$product['id']]);
$images = $images->fetchAll();

// Reviews
$reviews = db()->prepare("
    SELECT r.*, u.name AS reviewer
    FROM reviews r JOIN users u ON u.id = r.customer_id
    WHERE r.product_id = ? ORDER BY r.created_at DESC LIMIT 10
");
$reviews->execute([$product['id']]);
$reviews = $reviews->fetchAll();

// Handle add-to-cart POST (AJAX friendly)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    verifyCsrf();
    $qty = max(1, cleanInt($_POST['qty'] ?? 1));
    $_SESSION['cart'][$product['id']] = [
        'id'       => $product['id'],
        'title'    => $product['title'],
        'price'    => $product['price'],
        'qty'      => ($_ = $_SESSION['cart'][$product['id']]['qty'] ?? 0) + $qty,
        'image'    => $images[0]['filename'] ?? '',
        'artisan'  => $product['artisan_name'],
    ];
    flash('success', '"' . $product['title'] . '" added to cart.');
    redirect(APP_URL . '/pages/product-detail.php?slug=' . urlencode($slug));
}

$pageTitle = e($product['title']) . ' — ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
?>

<section class="section">
  <div class="container">
    <div style="margin-bottom:1.5rem;">
      <a href="<?= APP_URL ?>/pages/products.php" style="color:var(--text-muted);font-size:0.85rem;">
        ← Back to Shop
      </a>
      <span style="color:var(--border-strong);margin:0 0.5rem;">/</span>
      <a href="<?= APP_URL ?>/pages/products.php?category=<?= e($product['category_slug']) ?>"
         style="color:var(--text-muted);font-size:0.85rem;"><?= e($product['category_name']) ?></a>
      <span style="color:var(--border-strong);margin:0 0.5rem;">/</span>
      <span style="color:var(--text-secondary);font-size:0.85rem;"><?= e($product['title']) ?></span>
    </div>

    <div class="detail-grid">
      <!-- GALLERY -->
      <div class="detail-gallery">
        <div class="detail-main-img" id="mainImg">
          <?php if (!empty($images)): ?>
            <img src="<?= APP_URL ?>/assets/images/uploads/<?= e($images[0]['filename']) ?>"
                 alt="<?= e($product['title']) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:var(--radius-xl);">
          <?php else: ?>
            <img src="<?= APP_URL ?>/assets/images/uploads/logo.png" alt="Product" style="width:100%;height:100%;object-fit:cover;border-radius:var(--radius-xl);">
          <?php endif; ?>
        </div>
        <?php if (count($images) > 1): ?>
        <div class="detail-thumbnails">
          <?php foreach ($images as $i => $img): ?>
          <div class="detail-thumb <?= $i===0?'active':'' ?>"
               onclick="switchImg('<?= APP_URL ?>/assets/images/uploads/<?= e($img['filename']) ?>',this)">
            <img src="<?= APP_URL ?>/assets/images/uploads/<?= e($img['filename']) ?>"
                 alt="" style="width:100%;height:100%;object-fit:cover;border-radius:var(--radius);">
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- DETAILS -->
      <div>
        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-bottom:1rem;">
          <span class="tag tag-gold"><?= e($product['category_name']) ?></span>
          <span class="tag tag-earth"><?= e($product['region']) ?></span>
          <?php if ($product['material']): ?>
          <span class="tag tag-earth"><?= e($product['material']) ?></span>
          <?php endif; ?>
        </div>

        <div class="product-artisan" style="font-size:0.8rem;margin-bottom:0.3rem;">
          <?= e($product['artisan_name']) ?> · <?= e($product['county']) ?>
        </div>
        <h1 style="font-family:'Playfair Display',serif;font-size:2rem;font-weight:700;margin:0.4rem 0 0.75rem;line-height:1.2;">
          <?= e($product['title']) ?>
        </h1>

        <?php if ($product['avg_rating'] > 0): ?>
        <div class="product-rating" style="margin-bottom:0.75rem;">
          <span class="stars"><?= str_repeat('★', (int)round($product['avg_rating'])) ?></span>
          <strong><?= $product['avg_rating'] ?></strong>
          · <?= $product['review_count'] ?> review<?= $product['review_count'] !== 1 ? 's' : '' ?>
        </div>
        <?php endif; ?>

        <!-- PRICE BLOCK -->
        <div class="detail-price-block">
          <div class="detail-price"><?= formatKES($product['price']) ?></div>
          <div class="detail-price-note">Artisan receives 97.5% of this sale via M-Pesa</div>
          <form method="POST" style="margin-top:1rem;">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="add_to_cart" value="1">
            <div class="detail-actions">
              <div class="quantity-control">
                <button type="button" class="qty-btn" onclick="changeQty(-1)">−</button>
                <input type="number" name="qty" id="qtyVal" class="qty-val" value="1" min="1" max="<?= $product['stock'] ?>">
                <button type="button" class="qty-btn" onclick="changeQty(1)">+</button>
              </div>
              <button type="submit" class="btn btn-primary" <?= $product['stock'] < 1 ? 'disabled' : '' ?>>
                <?= $product['stock'] < 1 ? 'Out of Stock' : 'Add to Cart' ?>
              </button>
              <?php if ($product['stock'] > 0): ?>
              <a href="<?= APP_URL ?>/pages/cart.php" class="btn btn-dark"
                 onclick="document.querySelector('form').submit()">Buy Now</a>
              <?php endif; ?>
            </div>
          </form>
          <?php if ($product['stock'] > 0 && $product['stock'] < 5): ?>
          <p style="color:var(--red);font-size:0.8rem;margin-top:0.5rem;">
            ⚠️ Only <?= $product['stock'] ?> left in stock!
          </p>
          <?php endif; ?>
        </div>

        <!-- ARTISAN CARD -->
        <div class="detail-artisan-card">
          <div class="artisan-avatar"><?= strtoupper(substr($product['artisan_name'],0,2)) ?></div>
          <div>
            <div style="font-weight:600;font-size:0.9rem;"><?= e($product['artisan_name']) ?></div>
            <div style="font-size:0.78rem;color:var(--text-muted);">
              <?= e($product['county']) ?><?= $product['verified'] ? ' · ✓ Verified Artisan' : '' ?>
            </div>
            <?php if ($product['years_exp']): ?>
            <div style="font-size:0.75rem;color:var(--gold);margin-top:0.15rem;">
              <?= $product['years_exp'] ?> years of experience
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- DESCRIPTION -->
        <div style="margin:1.75rem 0;">
          <h3 style="font-family:'Playfair Display',serif;font-size:1.05rem;margin-bottom:0.75rem;">About This Piece</h3>
          <p style="color:var(--text-secondary);line-height:1.75;font-size:0.9rem;"><?= e($product['description']) ?></p>
        </div>

        <!-- CULTURAL STORY -->
        <?php if ($product['cultural_story']): ?>
        <div style="background:rgba(201,168,76,0.07);border-left:3px solid var(--gold);padding:1rem 1.25rem;border-radius:0 var(--radius) var(--radius) 0;margin-bottom:1.75rem;">
          <div style="font-size:0.72rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:var(--gold);margin-bottom:0.4rem;">Cultural Story</div>
          <p style="font-style:italic;color:var(--text-secondary);font-size:0.875rem;line-height:1.7;">
            "<?= e($product['cultural_story']) ?>"
          </p>
        </div>
        <?php endif; ?>

        <!-- SPECS -->
        <h3 style="font-family:'Playfair Display',serif;font-size:1.05rem;margin-bottom:0.75rem;">Specifications</h3>
        <table class="spec-table">
          <?php $specs = [
            'Material' => $product['material'],
            'Region'   => $product['region'],
            'Category' => $product['category_name'],
            'Artisan'  => $product['artisan_name'],
            'Payment'  => 'M-Pesa STK Push',
            'Delivery' => '3–7 business days',
            'In Stock' => $product['stock'] . ' available',
          ]; foreach ($specs as $k => $v): if ($v): ?>
          <tr><td><?= e($k) ?></td><td><?= e($v) ?></td></tr>
          <?php endif; endforeach; ?>
        </table>
      </div>
    </div>

    <!-- REVIEWS -->
    <?php if (!empty($reviews)): ?>
    <div style="margin-top:4rem;">
      <h2 style="font-family:'Playfair Display',serif;font-size:1.5rem;margin-bottom:1.5rem;">
        Customer Reviews
        <?php if ($product['avg_rating'] > 0): ?>
        <span style="font-size:1rem;color:var(--gold);margin-left:0.75rem;">
          ★ <?= $product['avg_rating'] ?> / 5
        </span>
        <?php endif; ?>
      </h2>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1rem;">
        <?php foreach ($reviews as $r): ?>
        <div class="card" style="padding:1.25rem;">
          <div style="display:flex;justify-content:space-between;margin-bottom:0.5rem;">
            <strong style="font-size:0.9rem;"><?= e($r['reviewer']) ?></strong>
            <span class="stars" style="font-size:0.85rem;"><?= str_repeat('★', $r['rating']) ?></span>
          </div>
          <?php if ($r['comment']): ?>
          <p style="font-size:0.85rem;color:var(--text-secondary);line-height:1.6;"><?= e($r['comment']) ?></p>
          <?php endif; ?>
          <div style="font-size:0.72rem;color:var(--text-muted);margin-top:0.5rem;">
            <?= date('M j, Y', strtotime($r['created_at'])) ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>

<script>
function changeQty(d) {
    const el = document.getElementById('qtyVal');
    el.value = Math.max(1, Math.min(parseInt(el.max)||99, parseInt(el.value) + d));
}
function switchImg(src, el) {
    document.querySelector('#mainImg img').src = src;
    document.querySelectorAll('.detail-thumb').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
