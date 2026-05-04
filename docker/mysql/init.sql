-- =============================================
-- EYEWEAR SHOP — Database Init
-- =============================================

-- USERS
CREATE TABLE IF NOT EXISTS users (
    user_id    INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100)  NOT NULL,
    email      VARCHAR(100)  NOT NULL UNIQUE,
    password   VARCHAR(255)  NOT NULL,
    role       ENUM('ADMIN','CUSTOMER') DEFAULT 'CUSTOMER',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- PRODUCTS
CREATE TABLE IF NOT EXISTS products (
    product_id  INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150)   NOT NULL,
    description TEXT,
    price       DECIMAL(10,2)  NOT NULL,
    stock       INT            DEFAULT 0,
    image       VARCHAR(255)   DEFAULT NULL,
    category    ENUM('regular','sunglasses','prescription') DEFAULT 'regular',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- CART
CREATE TABLE IF NOT EXISTS cart (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    product_id INT NOT NULL,
    quantity   INT DEFAULT 1,
    added_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)    REFERENCES users(user_id)       ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart (user_id, product_id)
);

-- ORDERS
CREATE TABLE IF NOT EXISTS orders (
    order_id    INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status      ENUM('PENDING','CONFIRMED','SHIPPED','COMPLETED','CANCELLED') DEFAULT 'PENDING',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ORDER ITEMS
CREATE TABLE IF NOT EXISTS order_items (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    order_id   INT NOT NULL,
    product_id INT NOT NULL,
    quantity   INT           NOT NULL,
    price      DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id)   REFERENCES orders(order_id)     ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);

-- PAYMENTS
CREATE TABLE IF NOT EXISTS payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id   INT NOT NULL,
    amount     DECIMAL(10,2) NOT NULL,
    method     ENUM('CASH','BANK','MOMO') NOT NULL,
    status     ENUM('PENDING','PAID','FAILED') DEFAULT 'PENDING',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
);

-- =============================================
-- SAMPLE DATA
-- =============================================

INSERT INTO products (name, description, price, stock, image, category) VALUES
('Rayban Aviator Classic',   'Gọng kim loại vàng, tròng gradient xanh. Biểu tượng thời trang vượt thời gian.',         1500000, 10, '/assets/images/RayA.jpg', 'regular'),
('Rayban Clubmaster',        'Gọng acetate đen, viền kim loại. Phong cách retro hiện đại.',                             1800000,  8, '/assets/images/RayC.jpg', 'regular'),
('Oakley Frogskins',         'Gọng nhựa nhẹ, nhiều màu sắc trẻ trung. Thích hợp hoạt động ngoài trời.',                1200000, 15, '/assets/images/Oakley.jpg', 'sunglasses'),
('Gucci Square Frame',       'Gọng acetate đen vuông, logo GG nổi bật. Sang trọng và cá tính.',                         6500000,  5, '/assets/images/gucci.jpg', 'regular'),
('Persol Round Tortoise',    'Gọng acetate tortoise tròn cổ điển. Thương hiệu Italy cao cấp.',                          5100000,  6, '/assets/images/Persol.webp', 'sunglasses'),
('Gentle Monster Rosy',      'Thiết kế Hàn Quốc avant-garde. Độc đáo và nổi bật.',                                     3000000,  7, '/assets/images/gentleMonster.jpg', 'sunglasses'),
('Tom Ford FT0237',          'Gọng vàng rose sang trọng, tròng gradient. Dành cho quý cô hiện đại.',                   4500000,  4, '/assets/images/Tom.jpg', 'regular'),
('Warby Parker Haskell',     'Gọng acetate tortoise nhẹ, phù hợp mặt oval. Thiết kế Mỹ tối giản.',                      950000, 20, '/assets/images/Warby.jpg', 'regular'),
('Kính theo đơn Basic',      'Gọng titan siêu nhẹ, phù hợp lắp tròng cận/viễn/loạn theo đơn bác sĩ.',                  800000, 50, NULL, 'prescription'),
('Kính theo đơn Premium',    'Gọng acetate Italy cao cấp, lắp tròng đa tầng, chống ánh sáng xanh.',                    2200000, 30, NULL, 'prescription');