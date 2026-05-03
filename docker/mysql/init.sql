-- ============================================================
--  EyewearDB  –  init.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS EyewearDB
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE EyewearDB;

-- ── USERS ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    user_id    INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100)  NOT NULL,
    email      VARCHAR(100)  NOT NULL UNIQUE,
    password   VARCHAR(255)  NOT NULL,
    role       ENUM('ADMIN','CUSTOMER') NOT NULL DEFAULT 'CUSTOMER',
    created_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_users_email (email),
    INDEX idx_users_role  (role)
);

-- ── PRODUCTS ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS products (
    product_id  INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150)   NOT NULL,
    description TEXT,
    price       DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    stock       INT            NOT NULL DEFAULT 0,
    created_at  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_products_name (name)
);

-- ── ORDERS ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS orders (
    order_id    INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT            NOT NULL,
    total_price DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    status      ENUM('PENDING','CONFIRMED','SHIPPED','COMPLETED','CANCELLED')
                               NOT NULL DEFAULT 'PENDING',
    created_at  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_orders_user_id (user_id),
    INDEX idx_orders_status  (status),
    CONSTRAINT fk_orders_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE       -- xóa user → tự động xóa đơn hàng
        ON UPDATE CASCADE
);

-- ── ORDER ITEMS ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS order_items (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    order_id    INT           NOT NULL,
    product_id  INT           NOT NULL,
    quantity    INT           NOT NULL DEFAULT 1,
    price       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    INDEX idx_order_items_order   (order_id),
    INDEX idx_order_items_product (product_id),
    CONSTRAINT fk_items_order
        FOREIGN KEY (order_id)   REFERENCES orders(order_id)
        ON DELETE CASCADE        -- xóa order → tự động xóa items
        ON UPDATE CASCADE,
    CONSTRAINT fk_items_product
        FOREIGN KEY (product_id) REFERENCES products(product_id)
        ON DELETE RESTRICT       -- không cho xóa product nếu còn trong đơn hàng
        ON UPDATE CASCADE
);

-- ── PAYMENTS ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id   INT           NOT NULL UNIQUE, -- mỗi đơn chỉ có 1 payment
    amount     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    method     ENUM('CASH','BANK','MOMO') NOT NULL DEFAULT 'CASH',
    status     ENUM('PENDING','PAID','FAILED') NOT NULL DEFAULT 'PENDING',
    created_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_payments_status (status),
    CONSTRAINT fk_payments_order
        FOREIGN KEY (order_id) REFERENCES orders(order_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- ── FEEDBACKS ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS feedbacks (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT           NOT NULL,
    product_id  INT           NOT NULL,
    order_id    INT           NOT NULL,
    rating      TINYINT       NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment     TEXT          NOT NULL,
    status      ENUM('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
    created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- Mỗi user chỉ được review 1 lần mỗi sản phẩm mỗi đơn hàng
    UNIQUE KEY uq_feedback (user_id, product_id, order_id),

    INDEX idx_fb_product (product_id),
    INDEX idx_fb_user    (user_id),
    INDEX idx_fb_status  (status),

    CONSTRAINT fk_fb_user
        FOREIGN KEY (user_id)    REFERENCES users(user_id)    ON DELETE CASCADE,
    CONSTRAINT fk_fb_product
        FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    CONSTRAINT fk_fb_order
        FOREIGN KEY (order_id)   REFERENCES orders(order_id)  ON DELETE CASCADE
);

-- ── SAMPLE DATA ──────────────────────────────────────────────
INSERT INTO products (name, description, price, stock) VALUES
('Kính Rayban Classic',   'Kính thời trang cao cấp', 1500000, 10),
('Kính Gucci Black',      'Kính đen sang trọng',     2500000,  5),
('Kính Gentle Monster',   'Phong cách Hàn Quốc',     3000000,  7);
