<?php
// ============================================================
// includes/header.php
// ============================================================
require_once __DIR__ . '/config.php';
$user  = currentUser();
$flash = getFlash();

// Cart count from session
$cartCount = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) $cartCount += $item['qty'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= csrfToken() ?>">
<title><?= e($pageTitle ?? APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="<?= APP_URL ?>/assets/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= APP_URL ?>/assets/css/custom.css" rel="stylesheet">
<script>window.APP_URL = '<?= APP_URL ?>';</script>
</head>
<body>

<!-- FLASH MESSAGE -->
<?php if ($flash): ?>
<div class="flash-banner flash-<?= e($flash['type']) ?>" id="flashBanner">
    <?= e($flash['msg']) ?>
    <button onclick="document.getElementById('flashBanner').remove()" class="flash-close">×</button>
</div>
<?php endif; ?>

<!-- NAVBAR -->
<nav class="site-nav">
  <div class="nav-inner">
    <a class="nav-brand" href="<?= APP_URL ?>/pages/home.php">
      <img src="<?= APP_URL ?>/assets/images//uploads/logo.png" alt="Sanaa Ya Kenya" class="nav-logo">
      <span class="brand-text">Sanaa Ya Kenya</span>
    </a>
    <div class="nav-links">
      <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'home.php') ? 'active' : '' ?>"
         href="<?= APP_URL ?>/pages/home.php">Home</a>
      <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'products.php') ? 'active' : '' ?>"
         href="<?= APP_URL ?>/pages/products.php">Shop</a>
      <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'about.php') ? 'active' : '' ?>"
         href="<?= APP_URL ?>/pages/about.php">About Us</a>
      <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'contact.php') ? 'active' : '' ?>"
         href="<?= APP_URL ?>/pages/contact.php">Contact</a>
    </div>
    <div class="nav-actions">
      <a class="nav-cart" href="<?= APP_URL ?>/pages/cart.php">
        🛒 Cart
        <span class="cart-badge" id="cartBadge"><?= $cartCount ?></span>
      </a>
      <?php if ($user): ?>
        <?php if ($user['role'] === 'artisan'): ?>
          <a class="btn-nav-cta" href="<?= APP_URL ?>/artisan/dashboard.php">My Dashboard</a>
        <?php elseif ($user['role'] === 'admin'): ?>
          <a class="btn-nav-cta" href="<?= APP_URL ?>/admin/dashboard.php">Admin Panel</a>
        <?php else: ?>
          <a class="btn-nav-cta" href="<?= APP_URL ?>/pages/home.php">Hi, <?= e(explode(' ', $user['name'])[0]) ?></a>
        <?php endif; ?>
        <a class="nav-link" href="<?= APP_URL ?>/logout.php">Logout</a>
      <?php else: ?>
        <a class="nav-link" href="<?= APP_URL ?>/login.php">Login</a>
        <a class="btn-nav-cta" href="<?= APP_URL ?>/register.php">Register</a>
      <?php endif; ?>
      
      <!-- Mobile hamburger menu -->
      <button class="mobile-menu-toggle" id="mobileMenuToggle">
        <span></span>
        <span></span>
        <span></span>
      </button>
    </div>
  </div>
  
  <!-- Mobile menu -->
  <div class="mobile-menu" id="mobileMenu">
    <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'home.php') ? 'active' : '' ?>"
       href="<?= APP_URL ?>/pages/home.php">Home</a>
    <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'products.php') ? 'active' : '' ?>"
       href="<?= APP_URL ?>/pages/products.php">Shop</a>
    <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'about.php') ? 'active' : '' ?>"
       href="<?= APP_URL ?>/pages/about.php">About Us</a>
    <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'contact.php') ? 'active' : '' ?>"
       href="<?= APP_URL ?>/pages/contact.php">Contact</a>
    <div class="mobile-menu-divider"></div>
    <a class="nav-link" href="<?= APP_URL ?>/pages/cart.php">🛒 Cart (<?= $cartCount ?>)</a>
    <?php if ($user): ?>
      <?php if ($user['role'] === 'artisan'): ?>
        <a class="btn btn-primary btn-sm" href="<?= APP_URL ?>/artisan/dashboard.php">My Dashboard</a>
      <?php elseif ($user['role'] === 'admin'): ?>
        <a class="btn btn-primary btn-sm" href="<?= APP_URL ?>/admin/dashboard.php">Admin Panel</a>
      <?php else: ?>
        <a class="btn btn-sm" href="<?= APP_URL ?>/pages/home.php">Hi, <?= e(explode(' ', $user['name'])[0]) ?></a>
      <?php endif; ?>
      <a class="btn btn-sm" href="<?= APP_URL ?>/logout.php">Logout</a>
    <?php else: ?>
      <a class="btn btn-sm" href="<?= APP_URL ?>/login.php">Login</a>
      <a class="btn btn-primary btn-sm" href="<?= APP_URL ?>/register.php">Register</a>
    <?php endif; ?>
  </div>
</nav>
