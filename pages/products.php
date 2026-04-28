<?php
// ============================================================
// pages/products.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';

// ── Build query from filters ─────────────────────────────────
$search    = clean($_GET['search']   ?? '');
$category  = clean($_GET['category'] ?? '');
$region    = clean($_GET['region']   ?? '');
$minPrice  = cleanFloat($_GET['min_price'] ?? 0);
$maxPrice  = cleanFloat($_GET['max_price'] ?? 0);
$sort      = clean($_GET['sort'] ?? 'featured');

$where  = ['p.status = "approved"'];
$params = [];

if ($search) {
    $where[]  = '(p.title LIKE ? OR p.description LIKE ? OR u.name LIKE ? OR p.material LIKE ?)';
    $like     = "%$search%";
    $params   = array_merge($params, [$like,$like,$like,$like]);
}
if ($category) {
    $where[]  = 'c.slug = ?';
    $params[] = $category;
}
if ($region) {
    $where[]  = 'p.region = ?';
    $params[] = $region;
}
if ($minPrice > 0) {
    $where[]  = 'p.price >= ?';
    $params[] = $minPrice;
}
if ($maxPrice > 0) {
    $where[]  = 'p.price <= ?';
    $params[] = $maxPrice;
}

$orderBy = match($sort) {
    'price-asc'  => 'p.price ASC',
    'price-desc' => 'p.price DESC',
    'newest'     => 'p.created_at DESC',
    'rating'     => 'avg_rating DESC',
    default      => 'p.featured DESC, p.created_at DESC',
};

$sql = "
    SELECT p.*, c.name AS category_name, c.slug AS category_slug,
           u.name AS artisan_name, a.county,
           (SELECT filename FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS image,
           COALESCE((SELECT ROUND(AVG(rating),1) FROM reviews WHERE product_id = p.id),0) AS avg_rating,
           COALESCE((SELECT COUNT(*) FROM reviews WHERE product_id = p.id),0) AS review_count
    FROM products p
    JOIN categories c ON c.id = p.category_id
    JOIN artisans a   ON a.id = p.artisan_id
    JOIN users u      ON u.id = a.user_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY $orderBy
";

$st = db()->prepare($sql);
$st->execute($params);
$products = $st->fetchAll();

// Categories for filter sidebar
$categories = db()->query('SELECT * FROM categories ORDER BY name')->fetchAll();

$pageTitle = 'Shop — ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header" style="background:var(--earth);padding:2.5rem 2rem;">
  <div class="container">
    <div class="label" style="color:var(--gold-light);">Artisan Marketplace</div>
    <h1 style="font-family:'Playfair Display',serif;font-size:2rem;color:var(--white);margin-top:0.4rem;">
      <?= $category ? e(ucwords(str_replace('-', ' ', $category))) : 'All Products' ?>
    </h1>
    <p style="color:rgba(255,255,255,0.5);"><?= count($products) ?> products found</p>
  </div>
</div>

<section class="section-sm">
  <div class="container">
    <div class="products-layout">

      <!-- FILTERS SIDEBAR -->
      <aside class="filters-panel">
        <form method="GET" id="filterForm">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
            <strong style="font-size:0.95rem;">Filters</strong>
            <a href="<?= APP_URL ?>/pages/products.php" style="font-size:0.75rem;color:var(--text-muted);">Clear all</a>
          </div>

          <!-- Search -->
          <div class="filter-section">
            <div class="filter-title">Search</div>
            <input type="text" name="search" class="input" value="<?= e($search) ?>"
                   placeholder="Search products..." style="font-size:0.85rem;padding:0.55rem 0.875rem;">
          </div>

          <!-- Category -->
          <div class="filter-section">
            <div class="filter-title">Category</div>
            <div class="filter-options">
              <?php foreach ($categories as $cat): ?>
              <label class="filter-option">
                <input type="radio" name="category" value="<?= e($cat['slug']) ?>"
                       <?= $category === $cat['slug'] ? 'checked' : '' ?>
                       onchange="this.form.submit()">
                <span><?= e($cat['name']) ?></span>
              </label>
              <?php endforeach; ?>
              <label class="filter-option">
                <input type="radio" name="category" value="" <?= !$category ? 'checked' : '' ?>
                       onchange="this.form.submit()">
                <span>All Categories</span>
              </label>
            </div>
          </div>

          <!-- Price -->
          <div class="filter-section">
            <div class="filter-title">Price Range (KES)</div>
            <div style="display:flex;gap:0.5rem;">
              <input type="number" name="min_price" class="input" placeholder="Min"
                     value="<?= $minPrice ?: '' ?>" style="font-size:0.82rem;padding:0.5rem 0.6rem;">
              <input type="number" name="max_price" class="input" placeholder="Max"
                     value="<?= $maxPrice ?: '' ?>" style="font-size:0.82rem;padding:0.5rem 0.6rem;">
            </div>
          </div>

          <!-- Region -->
          <div class="filter-section">
            <div class="filter-title">Region</div>
            <div class="filter-options">
              <?php foreach (['Narok County','Kisii County','Kwale County'] as $r): ?>
              <label class="filter-option">
                <input type="radio" name="region" value="<?= e($r) ?>"
                       <?= $region === $r ? 'checked' : '' ?>
                       onchange="this.form.submit()">
                <span><?= e($r) ?></span>
              </label>
              <?php endforeach; ?>
              <label class="filter-option">
                <input type="radio" name="region" value="" <?= !$region ? 'checked' : '' ?>
                       onchange="this.form.submit()">
                <span>All Regions</span>
              </label>
            </div>
          </div>

          <button type="submit" class="btn btn-primary btn-full">Apply Filters</button>
          <input type="hidden" name="sort" value="<?= e($sort) ?>">
        </form>
      </aside>

      <!-- PRODUCTS GRID -->
      <div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
          <span style="font-size:0.85rem;color:var(--text-muted);"><?= count($products) ?> products</span>
          <select class="sort-select" onchange="window.location='?<?= http_build_query(array_diff_key($_GET,['sort'=>''])) ?>&sort='+this.value">
            <?php foreach ([
              'featured'   => 'Featured',
              'price-asc'  => 'Price: Low to High',
              'price-desc' => 'Price: High to Low',
              'newest'     => 'Newest',
              'rating'     => 'Top Rated',
            ] as $val => $label): ?>
            <option value="<?= $val ?>" <?= $sort === $val ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <?php if (empty($products)): ?>
          <div style="text-align:center;padding:4rem 2rem;background:var(--white);border:1px solid var(--border);border-radius:var(--radius-lg);">
            <img src="<?= APP_URL ?>/assets/images/uploads/logo.png" alt="No products" style="width:80px;height:80px;object-fit:contain;margin-bottom:1rem;">
            <h3 style="font-family:'Playfair Display',serif;">No products found</h3>
            <p style="color:var(--text-muted);margin-top:0.5rem;">Try adjusting your filters.</p>
          </div>
        <?php else: ?>
        <div class="products-grid">
          <?php foreach ($products as $p): ?>
          <a href="<?= APP_URL ?>/pages/product-detail.php?slug=<?= urlencode($p['slug']) ?>" class="card product-card">
            <div class="product-img" style="background:var(--cream-dark);">
              <?php if ($p['image']): ?>
                <img src="<?= APP_URL ?>/assets/images/uploads/<?= e($p['image']) ?>"
                     alt="<?= e($p['title']) ?>" style="width:100%;height:100%;object-fit:cover;cursor:pointer;"
                     onclick="event.preventDefault();event.stopPropagation();openImageModal('<?= APP_URL ?>/assets/images/uploads/<?= e($p['image']) ?>', '<?= e($p['title']) ?>')">
              <?php else: ?>
                <img src="<?= APP_URL ?>/assets/images/uploads/logo.png" alt="Product" style="width:100%;height:100%;object-fit:cover;cursor:pointer;"
                     onclick="event.preventDefault();event.stopPropagation();openImageModal('<?= APP_URL ?>/assets/images/uploads/logo.png', 'Product')">
              <?php endif; ?>
              <?php if ($p['featured']): ?>
                <span class="badge badge-gold" style="position:absolute;top:10px;left:10px;">Featured</span>
              <?php endif; ?>
            </div>
            <div class="product-body">
              <div class="product-artisan"><?= e($p['artisan_name']) ?> · <?= e(explode(' ', $p['county'])[0]) ?></div>
              <div class="product-name"><?= e($p['title']) ?></div>
              <?php if ($p['avg_rating'] > 0): ?>
              <div class="product-rating">
                <span class="stars"><?= str_repeat('★', (int)round($p['avg_rating'])) ?></span>
                <?= $p['avg_rating'] ?> (<?= $p['review_count'] ?>)
              </div>
              <?php endif; ?>
              <div class="product-footer">
                <div class="product-price"><?= formatKES($p['price']) ?></div>
                <button class="add-cart-btn"
                        onclick="event.preventDefault();event.stopPropagation();addToCart(<?= $p['id'] ?>)">+</button>
              </div>
            </div>
          </a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</section>

<!-- Image Modal -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="imageModalLabel">Product Image</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <img id="modalImage" src="" alt="" class="img-fluid" style="max-height: 70vh; object-fit: contain;">
      </div>
    </div>
  </div>
</div>

<script>
function openImageModal(src, title) {
  document.getElementById('modalImage').src = src;
  document.getElementById('modalImage').alt = title;
  document.getElementById('imageModalLabel').textContent = title;
  new bootstrap.Modal(document.getElementById('imageModal')).show();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
