<?php
// ============================================================
// includes/config.example.php  — Example configuration
// ============================================================

// ── Environment ─────────────────────────────────────────────
define('APP_NAME', 'Sanaa Ya Kenya');
define('APP_URL', 'http://localhost/sanaa'); // Change for your local or production URL

define('APP_VERSION', '1.0.0');

// ── Database ─────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'sanaa_ya_kenya');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ── M-Pesa (Safaricom Daraja API) ────────────────────────────
define('MPESA_ENV', 'sandbox');           // 'sandbox' | 'production'
define('MPESA_SIMULATED', true);          // true for local demo/sandbox

define('MPESA_CONSUMER_KEY', 'YOUR_CONSUMER_KEY');
define('MPESA_CONSUMER_SECRET', 'YOUR_CONSUMER_SECRET');
define('MPESA_SHORTCODE', '174379');      // Sandbox till number
define('MPESA_PASSKEY', 'YOUR_PASSKEY');
define('MPESA_CALLBACK_URL', 'https://your-public-domain.ngrok.io/sanaa/api/mpesa-callback.php');
define('MPESA_STK_URL', 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest');
define('MPESA_TOKEN_URL', 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');

// ── Platform settings ───────────────────────────────────────
define('PLATFORM_FEE_PCT', 2.5);
define('DELIVERY_FEE', 200);
define('MAX_UPLOAD_MB', 5);
define('ALLOWED_IMG_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('UPLOAD_DIR', __DIR__ . '/../assets/images/uploads/');
define('UPLOAD_URL', APP_URL . '/assets/images/uploads/');
