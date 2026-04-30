# Project Summary

## Project title
Sanaa Ya Kenya — Kenyan Handcrafted Ornaments Marketplace

## Author
MIKECHITI · BSc Mathematics & Computer Science · Maseno University · April 2026

## Project objective
Build a demo e-commerce platform for authentic Kenyan handcrafted ornaments with local payment support via Safaricom M-Pesa, plus artisan and admin management.

## Problem statement
Many local artisans lack a simple online sales platform. Existing solutions are often too generic and do not integrate Kenyan payment methods cleanly for a local demo.

## Solution
A PHP/MySQL web application that supports:
- customer browsing and purchase
- artisan product uploads
- admin approval workflows
- M-Pesa checkout simulation for local demo
- secure password reset flow

## Key features
- Multi-role system: customer, artisan, admin
- Product listing, detail, cart and checkout
- M-Pesa STK Push integration with sandbox/demo mode
- Artisan dashboard with earnings and product management
- Admin dashboard for order and user oversight
- Password reset via secure token
- Safe repo publish setup with `config.example.php` and `.gitignore`

## Technology stack
- PHP 8+
- MySQL / MariaDB
- PDO prepared statements
- HTML/CSS/Bootstrap
- jQuery for cart AJAX
- Safaricom Daraja API (sandbox)

## Architecture
- `index.php` → entry point
- `includes/` → config, auth, M-Pesa helpers, common layout
- `pages/` → public pages and checkout flow
- `artisan/` → artisan-specific features
- `admin/` → admin management
- `api/` → AJAX and callback endpoints
- `assets/` → static CSS/JS/images

## Database
- `users` table with roles and password reset fields
- `products`, `orders`, `order_items`
- `artisans` profile table
- secure password hashing with `password_hash()`

## Testing & delivery
- Local XAMPP setup
- `schema.sql` for database import
- `includes/config.example.php` for safe configuration
- GitHub repo: `github.com/MIKECHITI/sanaa`

## Conclusion
This project demonstrates a complete local e-commerce workflow with Kenyan payment integration, role-based access, and safe code deployment practices.

---

## Demo script for presentation

1. Open the app at `http://localhost/sanaa`
2. Show homepage and product categories
3. Log in as a customer
4. Browse products and open a product detail page
5. Add item to cart
6. Go to checkout and explain payment flow:
   - M-Pesa STK Push
   - sandbox/demo mode for local testing
7. Show `payment-pending.php` polling status
8. Switch to artisan dashboard
   - upload a product
   - show earnings/profile
9. Switch to admin dashboard
   - approve products
   - manage users/orders
10. Demonstrate password reset:
    - open forgot-password
    - generate a reset link
    - reset password securely
11. Mention GitHub repo and project delivery contents
