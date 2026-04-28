<?php
// ============================================================
// pages/home.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';

// Featured products
$featured = db()->query('
    SELECT p.*, c.name AS category_name, u.name AS artisan_name, a.county,
           (SELECT filename FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS image,
           COALESCE((SELECT ROUND(AVG(rating),1) FROM reviews WHERE product_id = p.id),0) AS avg_rating,
           COALESCE((SELECT COUNT(*) FROM reviews WHERE product_id = p.id),0) AS review_count
    FROM products p
    JOIN categories c  ON c.id = p.category_id
    JOIN artisans a    ON a.id = p.artisan_id
    JOIN users u       ON u.id = a.user_id
    WHERE p.status = "approved" AND p.featured = 1
    ORDER BY p.created_at DESC LIMIT 4
')->fetchAll();

$pageTitle = APP_NAME . ' — Authentic Kenyan Handcrafted Ornaments';
include __DIR__ . '/../includes/header.php';
?>

<!-- HERO -->
<section class="hero">
  <div class="hero-grid container">
    <div class="hero-content">
      <div class="hero-label">Kenya's Premier Artisan Marketplace</div>
      <h1 class="hero-title">
        Authentic Craft.<br>
        <span class="gold">Direct from</span><br>
        the Artisan.
      </h1>
      <p class="hero-desc">
        Handcrafted ornaments by skilled Kenyan artisans. Direct payments via M-Pesa.
      </p>
      <div class="hero-actions">
        <a href="<?= APP_URL ?>/pages/products.php" class="btn btn-primary btn-lg">Explore Collection</a>
        <a href="<?= APP_URL ?>/register.php?role=artisan" class="btn btn-outline btn-lg">Join as Artisan</a>
      </div>
    </div>
    <div class="hero-visual">
      <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="3000">
        <div class="carousel-indicators">
          <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Maasai Beadwork"></button>
          <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1" aria-label="Soapstone"></button>
          <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2" aria-label="Sisal Baskets"></button>
        </div>
        <div class="carousel-inner">
          <div class="carousel-item active">
            <div class="hero-img-card">
              <img src="<?= APP_URL ?>/assets/images/uploads/Maasai%20Beadwork.png" alt="Maasai Beadwork" class="img-emoji">
            </div>
          </div>
          <div class="carousel-item">
            <div class="hero-img-card">
              <img src="<?= APP_URL ?>/assets/images/uploads/Kisii%20Soapstone.png" alt="Soapstone" class="img-emoji">
            </div>
          </div>
          <div class="carousel-item">
            <div class="hero-img-card">
              <img src="<?= APP_URL ?>/assets/images/uploads/Sisal%20Baskets.png" alt="Sisal Baskets" class="img-emoji">
            </div>
          </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
          <span class="carousel-control-prev-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
          <span class="carousel-control-next-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Next</span>
        </button>
      </div>
    </div>
  </div>
</section>

<!-- CATEGORIES -->
<section class="section" style="background:var(--white);">
  <div class="container">
    <div class="section-header">
      <div class="label">Browse by Tradition</div>
      <div class="divider-gold"></div>
      <h2 class="section-title">Cultural Legacies</h2>
    </div>
    <div class="categories-grid">
      <a href="<?= APP_URL ?>/pages/products.php?category=maasai-beadwork" class="category-card cat-maasai">
        <img src="<?= APP_URL ?>/assets/images/uploads/Maasai%20Beadwork.png" alt="Maasai Beadwork" class="category-icon">
        <div class="category-name" style="color:#8B1A1A;">Maasai Beadwork</div>
        <div class="category-count">Narok County</div>
      </a>
      <a href="<?= APP_URL ?>/pages/products.php?category=soapstone" class="category-card cat-soapstone">
        <img src="<?= APP_URL ?>/assets/images/uploads/Kisii%20Soapstone.png" alt="Soapstone" class="category-icon">
        <div class="category-name" style="color:#2D5A27;">Kisii Soapstone</div>
        <div class="category-count">Kisii County</div>
      </a>
      <a href="<?= APP_URL ?>/pages/products.php?category=sisal-baskets" class="category-card cat-basket">
        <img src="<?= APP_URL ?>/assets/images/uploads/Coastal%20Basketry.png" alt="Coastal Basketry" class="category-icon">
        <div class="category-name" style="color:#8B6914;">Coastal Basketry</div>
        <div class="category-count">Kwale County</div>
      </a>
    </div>
  </div>
</section>

<!-- FEATURED PRODUCTS -->
<section class="section">
  <div class="container">
    <div class="section-header" style="flex-direction:row;justify-content:space-between;align-items:flex-end;">
      <div>
        <div class="label">Handpicked for You</div>
        <h2 class="section-title">Featured Pieces</h2>
      </div>
      <a href="<?= APP_URL ?>/pages/products.php" class="btn btn-outline">View All →</a>
    </div>
    <div class="products-grid">
      <?php foreach ($featured as $p): ?>
      <a href="<?= APP_URL ?>/pages/product-detail.php?slug=<?= urlencode($p['slug']) ?>" class="card product-card">
        <div class="product-img" style="background:var(--cream-dark);">
          <?php if ($p['image']): ?>
            <img src="<?= APP_URL ?>/assets/images/uploads/<?= e($p['image']) ?>"
                 alt="<?= e($p['title']) ?>" style="width:100%;height:100%;object-fit:cover;">
          <?php else: ?>
            <span style="font-size:3rem;">🎨</span>
          <?php endif; ?>
        </div>
        <div class="product-body">
          <div class="product-artisan"><?= e($p['artisan_name']) ?> · <?= e(explode(' ', $p['county'])[0]) ?></div>
          <div class="product-name"><?= e($p['title']) ?></div>
          <?php if ($p['avg_rating'] > 0): ?>
          <div class="product-rating">
            <span class="stars"><?= str_repeat('★', round($p['avg_rating'])) ?></span>
            <?= $p['avg_rating'] ?> (<?= $p['review_count'] ?>)
          </div>
          <?php endif; ?>
          <div class="product-footer">
            <div class="product-price"><?= formatKES($p['price']) ?></div>
            <button class="add-cart-btn" onclick="event.preventDefault();addToCart(<?= $p['id'] ?>)">+</button>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- TESTIMONIALS -->
<section class="testimonials-section layout_padding" style="background:var(--cream);padding:4rem 0;">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="section-title" style="color:var(--text-primary);">What Our Customers Say</h2>
      <p class="text-muted">Hear from satisfied buyers of authentic Kenyan crafts</p>
    </div>
    <div id="testimonialsCarousel" class="carousel slide" data-bs-ride="carousel">
      <div class="carousel-inner">
        <div class="carousel-item active">
          <div class="row justify-content-center">
            <div class="col-md-8">
              <div class="testimonial-card text-center">
                <div class="testimonial-quote">"</div>
                <p class="testimonial-text">
                  The Maasai beadwork I purchased is absolutely stunning. The craftsmanship is incredible, and knowing it's directly from the artisan makes it even more special. Highly recommend Sanaa Ya Kenya!
                </p>
                <div class="testimonial-author">
                  <strong>Sarah Wanjiku</strong><br>
                  <small>Nairobi, Kenya</small>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="carousel-item">
          <div class="row justify-content-center">
            <div class="col-md-8">
              <div class="testimonial-card text-center">
                <div class="testimonial-quote">"</div>
                <p class="testimonial-text">
                  I love the soapstone carvings! The quality is top-notch, and the M-Pesa payment was so convenient. Supporting local artisans has never been easier.
                </p>
                <div class="testimonial-author">
                  <strong>David Kiprop</strong><br>
                  <small>Eldoret, Kenya</small>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="carousel-item">
          <div class="row justify-content-center">
            <div class="col-md-8">
              <div class="testimonial-card text-center">
                <div class="testimonial-quote">"</div>
                <p class="testimonial-text">
                  Beautiful sisal baskets that arrived well-packaged. The platform is easy to use, and I appreciate the direct connection to Kenyan artisans. Will definitely shop again!
                </p>
                <div class="testimonial-author">
                  <strong>Grace Achieng</strong><br>
                  <small>Kisumu, Kenya</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <button class="carousel-control-prev" type="button" data-bs-target="#testimonialsCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#testimonialsCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
      </button>
    </div>
  </div>
</section>

<!-- ARTISAN CTA -->
<section class="cta-section">
  <div class="container-sm" style="text-align:center;">
    <div class="label" style="margin-bottom:0.75rem;">Join the Movement</div>
    <h2 class="section-title" style="color:var(--white);margin-bottom:1rem;">Are You a Kenyan Artisan?</h2>
    <p style="color:rgba(255,255,255,0.6);margin-bottom:2rem;font-size:1.05rem;">
      List your work and receive direct M-Pesa payments. No fees, no middlemen.
    </p>
    <a href="<?= APP_URL ?>/register.php?role=artisan" class="btn btn-primary btn-lg">Register as Artisan</a>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
