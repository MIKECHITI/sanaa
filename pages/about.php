<?php
// ============================================================
// pages/about.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';

$pageTitle = 'About Us — ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
?>

<section class="section">
  <div class="container">
    <div class="section-header">
      <div class="label">Our Story</div>
      <div class="divider-gold"></div>
      <h1 class="section-title">About Sanaa Ya Kenya</h1>
    </div>

    <div style="max-width:800px;margin:0 auto;line-height:1.8;color:var(--text);font-size:1.05rem;">
      <p style="margin-bottom:2rem;">
        <strong>Sanaa Ya Kenya</strong> (Kenyan Art) is a digital marketplace dedicated to connecting authentic Kenyan artisans with customers worldwide. We believe that every handcrafted piece tells a story—of skill, tradition, culture, and creativity.
      </p>

      <h3 style="font-size:1.3rem;margin-top:3rem;margin-bottom:1rem;color:var(--dark);">Our Mission</h3>
      <p style="margin-bottom:2rem;">
        We empower Kenyan artisans by providing them with a fair, transparent platform to sell their work directly to customers. By eliminating middlemen, we ensure that artisans receive up to 94% of every sale, while building sustainable livelihoods for themselves and their families.
      </p>

      <h3 style="font-size:1.3rem;margin-top:3rem;margin-bottom:1rem;color:var(--dark);">What We Offer</h3>
      <ul style="margin-bottom:2rem;padding-left:2rem;">
        <li style="margin-bottom:0.75rem;"><strong>Direct Payment via M-Pesa:</strong> Artisans receive payments instantly, with zero transaction fees.</li>
        <li style="margin-bottom:0.75rem;"><strong>Zero Commission:</strong> We believe artisans deserve to keep their earnings. Our platform fee is minimal and transparent.</li>
        <li style="margin-bottom:0.75rem;"><strong>Authenticity Guaranteed:</strong> Every product is verified and handmade by certified artisans in Kenya.</li>
        <li style="margin-bottom:0.75rem;"><strong>Nationwide Delivery:</strong> We handle logistics so artisans can focus on their craft.</li>
      </ul>

      <h3 style="font-size:1.3rem;margin-top:3rem;margin-bottom:1rem;color:var(--dark);">Our Artisan Community</h3>
      <p style="margin-bottom:2rem;">
        We partner with over 247 artisans across Kenya, specializing in traditional crafts including:
      </p>
      <ul style="margin-bottom:2rem;padding-left:2rem;">
        <li style="margin-bottom:0.5rem;"><strong>Maasai Beadwork</strong> – Intricate jewelry and beads from Narok County</li>
        <li style="margin-bottom:0.5rem;"><strong>Kisii Soapstone</strong> – Hand-carved sculptures and decorative pieces from Kisii County</li>
        <li style="margin-bottom:0.5rem;"><strong>Coastal Basketry</strong> – Woven sisal baskets and traditional crafts from Kwale County</li>
      </ul>

      <h3 style="font-size:1.3rem;margin-top:3rem;margin-bottom:1rem;color:var(--dark);">Why Shop With Us?</h3>
      <p style="margin-bottom:2rem;">
        When you purchase from Sanaa Ya Kenya, you're not just buying a beautiful handcrafted item—you're directly supporting a Kenyan artisan and their family. You're preserving traditional craftsmanship and helping to create economic opportunities in rural communities across Kenya.
      </p>

      <p style="margin-top:3rem;padding:2rem;background:var(--cream-dark);border-radius:var(--radius-lg);border-left:4px solid var(--gold);">
        <em>"Every purchase is a celebration of Kenyan creativity, tradition, and talent. Thank you for supporting our artisans."</em>
      </p>
    </div>
  </div>
</section>

<section class="cta-section">
  <div class="container-sm" style="text-align:center;">
    <h2 class="section-title" style="color:var(--white);margin-bottom:1rem;">Join Our Community</h2>
    <p style="color:rgba(255,255,255,0.6);margin-bottom:2rem;font-size:1.05rem;">
      Whether you're a customer or an artisan, we'd love to have you part of the Sanaa Ya Kenya family.
    </p>
    <div style="display:flex;gap:1rem;justify-content:center;">
      <a href="<?= APP_URL ?>/pages/products.php" class="btn btn-primary btn-lg">Shop Now</a>
      <a href="<?= APP_URL ?>/register.php?role=artisan" class="btn btn-outline btn-lg">Become an Artisan</a>
    </div>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
