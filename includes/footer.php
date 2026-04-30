<?php // includes/footer.php ?>
<footer class="site-footer">
  <div class="footer-grid container">
    <div>
      <div style="display:flex;gap:1rem;margin-bottom:1rem;">
        <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" style="display:inline-block;width:40px;height:40px;">
          <img src="<?= APP_URL ?>/assets/images/uploads/fb-icon.png" alt="Facebook" style="width:100%;height:100%;object-fit:contain;">
        </a>
        <a href="https://instagram.com" target="_blank" rel="noopener noreferrer" style="display:inline-block;width:40px;height:40px;">
          <img src="<?= APP_URL ?>/assets/images/uploads/instagram-icon.png" alt="Instagram" style="width:100%;height:100%;object-fit:contain;">
        </a>
        <a href="https://x.com" target="_blank" rel="noopener noreferrer" style="display:inline-block;width:40px;height:40px;">
          <img src="<?= APP_URL ?>/assets/images/uploads/x-icon.png" alt="X (Twitter)" style="width:100%;height:100%;object-fit:contain;">
        </a>
        <a href="https://linkedin.com" target="_blank" rel="noopener noreferrer" style="display:inline-block;width:40px;height:40px;">
          <img src="<?= APP_URL ?>/assets/images/uploads/linkedin-icon.png" alt="LinkedIn" style="width:100%;height:100%;object-fit:contain;">
        </a>
      </div>
      <p>Connecting Kenya's talented artisans with the world, one handcrafted piece at a time.</p>
    </div>
    <div>
      <h5>Shop</h5>
      <div class="footer-links">
        <a href="<?= APP_URL ?>/pages/products.php">All Products</a>
        <a href="<?= APP_URL ?>/pages/products.php?category=maasai-beadwork">Maasai Beadwork</a>
        <a href="<?= APP_URL ?>/pages/products.php?category=soapstone">Soapstone Carvings</a>
        <a href="<?= APP_URL ?>/pages/products.php?category=sisal-baskets">Sisal Baskets</a>
      </div>
    </div>
    <div>
      <h5>Artisans</h5>
      <div class="footer-links">
        <a href="<?= APP_URL ?>/register.php?role=artisan">Join Platform</a>
        <a href="<?= APP_URL ?>/artisan/dashboard.php">Artisan Dashboard</a>
        <a href="#">M-Pesa Payouts</a>
      </div>
    </div>
    <div>
      <h5>Company</h5>
      <div class="footer-links">
        <a href="<?= APP_URL ?>/pages/about.php">About Us</a>
        <a href="<?= APP_URL ?>/pages/contact.php">Contact</a>
        <a href="#">Privacy Policy</a>
      </div>
    </div>
  </div>
  <div class="footer-bottom container">
    &copy; <?= date('Y') ?> Sanaa Ya Kenya. All Rights Reserved.
  </div>
</footer>

<script src="<?= APP_URL ?>/assets/js/custom.js"></script>
</body>
</html>
