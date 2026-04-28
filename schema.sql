-- ============================================================
-- Sanaa Ya Kenya — Database Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS sanaa_ya_kenya
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE sanaa_ya_kenya;

-- ─────────────────────────────────────────
-- USERS
-- ─────────────────────────────────────────
CREATE TABLE users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(120)  NOT NULL,
    email      VARCHAR(180)  NOT NULL UNIQUE,
    phone      VARCHAR(20),
    password   VARCHAR(255)  NOT NULL,
    password_reset_token    VARCHAR(100),
    password_reset_expires_at DATETIME,
    role       ENUM('customer','artisan','admin') DEFAULT 'customer',
    status     ENUM('active','suspended','pending')  DEFAULT 'active',
    avatar     VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email  (email),
    INDEX idx_role   (role),
    INDEX idx_status (status)
);

-- ─────────────────────────────────────────
-- ARTISAN PROFILES
-- ─────────────────────────────────────────
CREATE TABLE artisans (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT          NOT NULL UNIQUE,
    bio          TEXT,
    county       VARCHAR(80),
    speciality   VARCHAR(255),
    mpesa_number VARCHAR(20),
    story        TEXT,
    years_exp    TINYINT UNSIGNED DEFAULT 0,
    verified     TINYINT(1)   DEFAULT 0,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_county (county),
    INDEX idx_verified (verified)
);

-- ─────────────────────────────────────────
-- CATEGORIES
-- ─────────────────────────────────────────
CREATE TABLE categories (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    slug        VARCHAR(120) NOT NULL UNIQUE,
    description TEXT,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug (slug)
);

INSERT INTO categories (name, slug, description) VALUES
  ('Maasai Beadwork',    'maasai-beadwork', 'Traditional Maasai beaded jewellery from Narok County'),
  ('Soapstone Carvings', 'soapstone',       'Hand-carved soapstone sculptures from Kisii County'),
  ('Sisal Baskets',      'sisal-baskets',   'Hand-woven coastal sisal baskets from Kwale County');

-- ─────────────────────────────────────────
-- PRODUCTS
-- ─────────────────────────────────────────
CREATE TABLE products (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    artisan_id     INT            NOT NULL,
    category_id    INT            NOT NULL,
    title          VARCHAR(200)   NOT NULL,
    slug           VARCHAR(220)   NOT NULL UNIQUE,
    description    TEXT,
    cultural_story TEXT,
    material       VARCHAR(200),
    region         VARCHAR(100),
    price          DECIMAL(10,2)  NOT NULL,
    stock          INT UNSIGNED   DEFAULT 1,
    status         ENUM('pending','approved','rejected') DEFAULT 'pending',
    featured       TINYINT(1)     DEFAULT 0,
    views          INT UNSIGNED   DEFAULT 0,
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (artisan_id)  REFERENCES artisans(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    INDEX idx_status   (status),
    INDEX idx_featured (featured),
    INDEX idx_category (category_id),
    INDEX idx_artisan  (artisan_id),
    INDEX idx_price    (price),
    FULLTEXT idx_search (title, description, material)
);

-- ─────────────────────────────────────────
-- PRODUCT IMAGES
-- ─────────────────────────────────────────
CREATE TABLE product_images (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT          NOT NULL,
    filename   VARCHAR(255) NOT NULL,
    is_primary TINYINT(1)   DEFAULT 0,
    sort_order TINYINT UNSIGNED DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product  (product_id),
    INDEX idx_primary  (product_id, is_primary)
);

-- ─────────────────────────────────────────
-- ORDERS
-- ─────────────────────────────────────────
CREATE TABLE orders (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    customer_id      INT           NOT NULL,
    order_number     VARCHAR(25)   NOT NULL UNIQUE,
    subtotal         DECIMAL(10,2) NOT NULL,
    delivery_fee     DECIMAL(10,2) DEFAULT 200.00,
    total            DECIMAL(10,2) NOT NULL,
    status           ENUM('pending','paid','processing','shipped','delivered','cancelled')
                     DEFAULT 'pending',
    delivery_name    VARCHAR(120),
    delivery_phone   VARCHAR(20),
    delivery_address TEXT,
    delivery_county  VARCHAR(80),
    notes            TEXT,
    created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id),
    INDEX idx_customer (customer_id),
    INDEX idx_status   (status),
    INDEX idx_created  (created_at)
);

-- ─────────────────────────────────────────
-- ORDER ITEMS
-- ─────────────────────────────────────────
CREATE TABLE order_items (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    order_id    INT NOT NULL,
    product_id  INT NOT NULL,
    artisan_id  INT NOT NULL,
    quantity    INT UNSIGNED DEFAULT 1,
    unit_price  DECIMAL(10,2) NOT NULL,
    subtotal    DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (artisan_id) REFERENCES artisans(id),
    INDEX idx_order   (order_id),
    INDEX idx_artisan (artisan_id),
    INDEX idx_product (product_id)
);

-- ─────────────────────────────────────────
-- MPESA TRANSACTIONS
-- ─────────────────────────────────────────
CREATE TABLE mpesa_transactions (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    order_id            INT NOT NULL,
    phone               VARCHAR(20) NOT NULL,
    amount              DECIMAL(10,2) NOT NULL,
    merchant_request_id VARCHAR(100),
    checkout_request_id VARCHAR(100) UNIQUE,
    mpesa_receipt       VARCHAR(50),
    result_code         VARCHAR(10),
    result_desc         VARCHAR(255),
    status              ENUM('pending','completed','failed','cancelled') DEFAULT 'pending',
    initiated_at        DATETIME DEFAULT CURRENT_TIMESTAMP,
    completed_at        DATETIME,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    INDEX idx_order    (order_id),
    INDEX idx_status   (status),
    INDEX idx_checkout (checkout_request_id)
);

-- ─────────────────────────────────────────
-- ARTISAN EARNINGS
-- ─────────────────────────────────────────
CREATE TABLE artisan_earnings (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    artisan_id      INT NOT NULL,
    order_item_id   INT NOT NULL,
    gross_amount    DECIMAL(10,2) NOT NULL,
    platform_fee    DECIMAL(10,2) NOT NULL,
    net_amount      DECIMAL(10,2) NOT NULL,
    status          ENUM('pending','available','withdrawn') DEFAULT 'pending',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (artisan_id)    REFERENCES artisans(id),
    FOREIGN KEY (order_item_id) REFERENCES order_items(id),
    INDEX idx_artisan (artisan_id),
    INDEX idx_status  (status)
);

-- ─────────────────────────────────────────
-- WITHDRAWALS
-- ─────────────────────────────────────────
CREATE TABLE withdrawals (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    artisan_id      INT NOT NULL,
    amount          DECIMAL(10,2) NOT NULL,
    mpesa_number    VARCHAR(20) NOT NULL,
    mpesa_receipt   VARCHAR(50),
    status          ENUM('pending','processing','completed','failed') DEFAULT 'pending',
    requested_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    completed_at    DATETIME,
    FOREIGN KEY (artisan_id) REFERENCES artisans(id),
    INDEX idx_artisan (artisan_id),
    INDEX idx_status  (status)
);

-- ─────────────────────────────────────────
-- REVIEWS
-- ─────────────────────────────────────────
CREATE TABLE reviews (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    product_id  INT NOT NULL,
    customer_id INT NOT NULL,
    rating      TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment     TEXT,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id)  REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES users(id),
    INDEX idx_product (product_id),
    INDEX idx_customer (customer_id)
);

-- ─────────────────────────────────────────
-- CONTACT MESSAGES
-- ─────────────────────────────────────────
CREATE TABLE contact_messages (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(120) NOT NULL,
    email       VARCHAR(180) NOT NULL,
    subject     VARCHAR(200) NOT NULL,
    message     TEXT NOT NULL,
    status      ENUM('new','read','replied') DEFAULT 'new',
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_created (created_at)
);

-- ─────────────────────────────────────────
-- CSRF TOKENS (server-side store)
-- ─────────────────────────────────────────
CREATE TABLE csrf_tokens (
    token       VARCHAR(64) PRIMARY KEY,
    session_id  VARCHAR(128),
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_session (session_id)
);

-- ─────────────────────────────────────────
-- DEMO DATA
-- ─────────────────────────────────────────
-- Admin user (password: Admin@1234)
INSERT INTO users (name, email, phone, password, role) VALUES
('Admin', 'admin@sanaayakenya.co.ke', '0700000000',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uHxR.Yz.K', 'admin');

-- Demo artisan (password: Artisan@1234)
INSERT INTO users (name, email, phone, password, role) VALUES
('Mama Kerubo', 'kerubo@example.com', '0712345678',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uHxR.Yz.K', 'artisan');

INSERT INTO artisans (user_id, bio, county, speciality, mpesa_number, years_exp, verified) VALUES
(2, 'I have been carving soapstone for over 20 years, learning from my mother and grandmother.',
 'Kisii County', 'Soapstone Carvings', '0712345678', 22, 1);

-- Demo customer (password: Customer@1234)
INSERT INTO users (name, email, phone, password, role) VALUES
('John Kariuki', 'kariuki@example.com', '0722123456',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uHxR.Yz.K', 'customer');

-- Demo products
INSERT INTO products (artisan_id, category_id, title, slug, description, cultural_story, material, region, price, stock, status, featured) VALUES
(1, 1, 'Maasai Warrior Necklace', 'maasai-warrior-necklace',
 'Handcrafted over 3 weeks using traditional Maasai beading techniques.',
 'Red symbolises warrior bravery; blue, sky and God; white, peace and purity.',
 'Glass beads, copper wire', 'Narok County', 2800.00, 15, 'approved', 1),

(1, 2, 'Soapstone Lion Carving', 'soapstone-lion-carving',
 'Sculpted from premium Kisii soapstone. Each lion takes 5–7 days to complete.',
 'The lion is a symbol of Kenyan strength and pride, carved using tools unchanged for 200 years.',
 'Kisii soapstone', 'Kisii County', 3200.00, 8, 'approved', 1),

(1, 3, 'Kiondo Sisal Basket', 'kiondo-sisal-basket',
 'Hand-woven from natural sisal using traditional coastal techniques.',
 'Kiondo baskets have been a coastal tradition for centuries, woven during community gatherings.',
 'Natural sisal fibre, leather handles', 'Kwale County', 1950.00, 20, 'approved', 1),

(1, 1, 'Beaded Maasai Bracelet Set', 'beaded-maasai-bracelet-set',
 'Set of 3 bracelets in traditional Maasai red, white, and blue.',
 'Worn at ceremonies, these colours represent a Maasai woman\'s marital status and blessings.',
 'Glass beads, elastic thread', 'Narok County', 850.00, 30, 'approved', 1);
