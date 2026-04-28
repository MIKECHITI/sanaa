# Sanaa Ya Kenya

A demo e-commerce platform for authentic Kenyan handcrafted ornaments, built with PHP and MySQL and integrated with Safaricom M-Pesa for payments.

**Maseno University · BSc Mathematics & Computer Science · 2026**

---

## Project Structure

```
sanaa/
├── index.php                    ← Entry point (redirects to home)
├── login.php                    ← User login
├── register.php                 ← Customer / artisan registration
├── logout.php                   ← Session destroy
├── schema.sql                   ← Full database schema + demo data
│
├── includes/
│   ├── config.php               ← DB connection, constants, helpers
│   ├── auth.php                 ← Authentication class
│   ├── mpesa.php                ← Safaricom Daraja API (STK Push)
│   ├── header.php               ← Site header / nav
│   └── footer.php               ← Site footer
│
├── pages/
│   ├── home.php                 ← Homepage (hero, categories, featured)
│   ├── products.php             ← Product listing with filters
│   ├── product-detail.php       ← Single product view + add to cart
│   ├── cart.php                 ← Shopping cart management
│   ├── checkout.php             ← 2-step checkout + M-Pesa STK Push
│   └── payment-pending.php      ← M-Pesa status polling
│
├── artisan/
│   ├── dashboard.php            ← Artisan overview + stats
│   ├── my-products.php          ← Manage listed products
│   ├── upload-product.php       ← Upload new product with images
│   ├── earnings.php             ← Revenue tracking + M-Pesa withdrawal
│   └── profile.php              ← Edit artisan profile
│
├── admin/
│   ├── dashboard.php            ← Platform-wide stats + alerts
│   ├── approve-products.php     ← Review & approve/reject submissions
│   ├── manage-users.php         ← Suspend / activate users
│   └── orders.php               ← All orders + status management
│
├── api/
│   ├── add-to-cart.php          ← AJAX cart endpoint
│   ├── mpesa-callback.php       ← Safaricom payment callback
│   └── mpesa-status.php         ← Client polling endpoint
│
└── assets/
    ├── css/
    │   ├── bootstrap.min.css    ← Bootstrap 5 (download separately)
    │   └── custom.css           ← Full custom styles
    ├── js/
    │   ├── jquery.min.js        ← jQuery (download separately)
    │   ├── bootstrap.bundle.min.js  ← Bootstrap JS
    │   └── custom.js            ← Cart AJAX, notifications
    └── images/
        └── uploads/             ← Product image uploads (auto-created)
```

---

## Setup Instructions

### 1. Requirements
- PHP 8.1+
- MySQL 8.0+
- Apache / Nginx (XAMPP works locally)
- cURL extension enabled
- Safaricom Daraja API account (sandbox for testing)

### 2. Install

```bash
# Clone or copy files into your web root
# e.g. C:/xampp/htdocs/sanaa/   or   /var/www/html/sanaa/

# Create uploads directory
mkdir -p assets/images/uploads
chmod 755 assets/images/uploads
```

### 3. Download Bootstrap & jQuery

Place these files in `assets/css/` and `assets/js/`:
- [Bootstrap 5 CSS](https://getbootstrap.com/docs/5.3/getting-started/download/)
- [Bootstrap 5 Bundle JS](https://getbootstrap.com/docs/5.3/getting-started/download/)
- [jQuery 3.x](https://jquery.com/download/)

### 4. Database

```sql
-- In phpMyAdmin or MySQL CLI:
SOURCE schema.sql;
```

### 5. Configure

Copy `includes/config.example.php` to `includes/config.php` and update the values for your local environment.

This project also includes a `.gitignore` rule to keep `includes/config.php` out of the Git history.

On Windows:

```powershell
copy includes\config.example.php includes\config.php
```

Then edit `includes/config.php` and set your database credentials, app URL, and M-Pesa values.

```php
// Example values:
define('DB_HOST', 'localhost');
define('DB_NAME', 'sanaa_ya_kenya');
define('DB_USER', 'root');
define('DB_PASS', '');

define('APP_URL', 'http://localhost/sanaa');

define('MPESA_CONSUMER_KEY',    'YOUR_CONSUMER_KEY');
define('MPESA_CONSUMER_SECRET', 'YOUR_CONSUMER_SECRET');
define('MPESA_SHORTCODE',       '174379');          // Sandbox till
define('MPESA_PASSKEY',         'YOUR_PASSKEY');
define('MPESA_CALLBACK_URL',    'https://yourdomain.ngrok.io/sanaa/api/mpesa-callback.php');
```

> **Note:** The callback URL must be publicly accessible (HTTPS). Use [ngrok](https://ngrok.com) for local testing.

### 6. Run

Visit: `http://localhost/sanaa`

---

## Demo Login Credentials

| Role      | Email                          | Password     |
|-----------|-------------------------------|--------------|
| Admin     | admin@sanaayakenya.co.ke       | Admin@1234   |
| Artisan   | kerubo@example.com             | Admin@1234   |
| Customer  | kariuki@example.com            | Admin@1234   |

---

## Key Features

| Feature                  | Implementation                                   |
|--------------------------|--------------------------------------------------|
| User registration/login  | PHP sessions + bcrypt password hashing           |
| Product catalogue        | Dynamic filters: category, region, price, rating |
| M-Pesa STK Push          | Safaricom Daraja API via cURL                    |
| Payment callback         | `api/mpesa-callback.php` → updates order status  |
| Artisan dashboard        | Earnings, orders, product management             |
| Admin panel              | Product approval, user management, order tracking|
| CSRF protection          | Token-per-session validation on all POST forms   |
| SQL injection prevention | PDO prepared statements throughout               |
| Image uploads            | Validated by MIME type + size, stored server-side|
| Artisan earnings         | 97.5% net after 2.5% platform fee, M-Pesa payout|

---

## Security Checklist

- [x] HTTPS enforced in production (Hostinger free SSL)
- [x] CSRF tokens on all forms
- [x] PDO prepared statements (SQL injection prevention)
- [x] Input sanitisation (`clean()`, `cleanInt()`, `cleanFloat()`)
- [x] Password hashing (bcrypt, cost 12)
- [x] Session regeneration on login
- [x] Role-based access control (`requireRole()`)
- [x] File upload validation (MIME type + size)
- [x] Error display disabled in production

---

## M-Pesa Flow

```
Customer → Checkout → Enter phone
    ↓
sanaa/api/mpesa-stk-push → POST to Daraja API
    ↓
Safaricom sends STK Push to customer phone
    ↓
Customer enters PIN
    ↓
Safaricom POSTs result to mpesa-callback.php
    ↓
Order status updated → Artisan earnings created
    ↓
payment-pending.php polls mpesa-status.php → shows success
```

---

## Database Tables

| Table                | Purpose                                      |
|----------------------|----------------------------------------------|
| `users`              | All accounts (customers, artisans, admin)    |
| `artisans`           | Artisan profiles + M-Pesa numbers            |
| `categories`         | Maasai, Soapstone, Sisal                     |
| `products`           | All product listings                         |
| `product_images`     | Multiple images per product                  |
| `orders`             | Customer orders                              |
| `order_items`        | Line items per order                         |
| `mpesa_transactions` | STK Push records + callback results          |
| `artisan_earnings`   | Per-item earnings (gross / fee / net)        |
| `withdrawals`        | Artisan withdrawal requests                  |
| `reviews`            | Customer product reviews                     |

---

*Built for Maseno University BSc Mathematics & Computer Science, 2026.*
*Authors: Seleta Ian · Oyamo Elly Clinton · Mwombe Michael Chitiavi*
