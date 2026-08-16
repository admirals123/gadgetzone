-- ========================================================
-- GadgetZone - Complete Database Setup & Schema
-- ========================================================

-- --------------------------------------------------------
-- 1. Table structure for `categories`
-- --------------------------------------------------------
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS settings;

CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(100) NOT NULL UNIQUE,
  icon VARCHAR(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 2. Table structure for `products`
-- --------------------------------------------------------
CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NOT NULL,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  description TEXT,
  price DECIMAL(10,2) NOT NULL,
  old_price DECIMAL(10,2) DEFAULT NULL,
  image_url VARCHAR(500) NOT NULL,
  badge ENUM('NEW','HOT','SALE') DEFAULT NULL,
  stock INT DEFAULT 10,
  featured TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 3. Table structure for `users`
-- --------------------------------------------------------
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  phone VARCHAR(20) DEFAULT NULL,
  address TEXT DEFAULT NULL,
  city VARCHAR(100) DEFAULT NULL,
  avatar VARCHAR(255) DEFAULT NULL,
  role ENUM('member','admin','super_admin') DEFAULT 'member',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 4. Table structure for `orders`
-- --------------------------------------------------------
CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  order_number VARCHAR(50) NOT NULL UNIQUE,
  total_amount DECIMAL(10,2) NOT NULL,
  payment_method VARCHAR(50) NOT NULL,
  payment_status ENUM('unpaid','paid','refunded') DEFAULT 'unpaid',
  stripe_session_id VARCHAR(200) DEFAULT NULL,
  shipping_address TEXT NOT NULL,
  status ENUM('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 5. Table structure for `order_items`
-- --------------------------------------------------------
CREATE TABLE order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 6. Table structure for `settings`
-- --------------------------------------------------------
CREATE TABLE settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(100) NOT NULL UNIQUE,
  setting_value TEXT NOT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================================
-- SAMPLE DATA & DEFAULTS
-- ========================================================

-- Insert Categories
INSERT INTO categories (id, name, slug, icon) VALUES
(1, 'Smartphones', 'smartphones', '📱'),
(2, 'Laptops', 'laptops', '💻'),
(3, 'Audio', 'audio', '🎧'),
(4, 'Cameras', 'cameras', '📷'),
(5, 'Wearables', 'wearables', '⌚'),
(6, 'Accessories', 'accessories', '🔌');

-- Insert Sample Products
INSERT INTO products (category_id, name, slug, description, price, old_price, image_url, badge, stock, featured) VALUES
(1, 'iPhone 15 Pro Max', 'iphone-15-pro-max', 'Flagship smartphone featuring Titanium design, A17 Pro chip, and advanced 48MP camera system.', 145000.00, 160000.00, 'https://images.unsplash.com/photo-1695048133142-1a20484d2569?auto=format&fit=crop&w=800&q=80', 'HOT', 15, 1),
(1, 'Samsung Galaxy S24 Ultra', 'samsung-galaxy-s24-ultra', 'Ultimate Android smartphone with Galaxy AI, Snapdragon 8 Gen 3, and integrated S-Pen.', 138000.00, 150000.00, 'https://images.unsplash.com/photo-1610945265064-0e34e5519bbf?auto=format&fit=crop&w=800&q=80', 'NEW', 12, 1),
(2, 'MacBook Pro 16" M3 Max', 'macbook-pro-16-m3-max', 'Unrivaled laptop performance for professionals with Liquid Retina XDR display.', 285000.00, 310000.00, 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=800&q=80', 'HOT', 8, 1),
(2, 'Dell XPS 15 OLED', 'dell-xps-15-oled', 'Premium Windows laptop with 3.5K OLED touch display and Intel Core i9 processor.', 210000.00, 230000.00, 'https://images.unsplash.com/photo-1593642632823-8f785ba67e45?auto=format&fit=crop&w=800&q=80', 'SALE', 10, 0),
(3, 'Sony WH-1000XM5 Headphones', 'sony-wh-1000xm5', 'Industry-leading noise canceling wireless headphones with crystal clear hands-free calling.', 38000.00, 45000.00, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80', 'SALE', 25, 1),
(3, 'AirPods Pro (2nd Gen)', 'airpods-pro-2nd-gen', 'Active Noise Cancellation, Transparency mode, and Personalized Spatial Audio.', 28000.00, 32000.00, 'https://images.unsplash.com/photo-1600294037681-c80b4cb5b434?auto=format&fit=crop&w=800&q=80', 'NEW', 30, 1),
(4, 'Sony Alpha A7 IV Camera', 'sony-alpha-a7-iv', 'Full-frame mirrorless camera with 33MP sensor and 4K 60p video capabilities.', 240000.00, 260000.00, 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?auto=format&fit=crop&w=800&q=80', 'HOT', 5, 1),
(5, 'Apple Watch Ultra 2', 'apple-watch-ultra-2', 'The most capable and rugged Apple Watch with bright Always-On Retina display.', 85000.00, 95000.00, 'https://images.unsplash.com/photo-1434493789847-2f02dc6ca35d?auto=format&fit=crop&w=800&q=80', 'NEW', 18, 1),
(6, 'Anker 737 Power Bank 24000mAh', 'anker-737-power-bank', 'Ultra-powerful 140W fast charging battery pack with smart digital display.', 14500.00, 18000.00, 'https://images.unsplash.com/photo-1583863788434-e58a36330cf0?auto=format&fit=crop&w=800&q=80', 'SALE', 40, 0);

-- Insert Default Super Admin User (Email: admin@gadgetzone.com | Password: Admin@1234)
INSERT INTO users (first_name, last_name, email, password, phone, role) VALUES
('Super', 'Admin', 'admin@gadgetzone.com', '$2y$10$Z/84/cpR4RONTdGDovUTKeRp2DqoJOU4ZZ57tTQjNykSXwpjYz.oC', '+8801700000000', 'super_admin');

-- Insert System Settings
INSERT INTO settings (setting_key, setting_value) VALUES
('active_currency', 'INR'),
('enabled_currencies', '["INR","USD","EUR","GBP","CAD","AUD","BDT","SGD","SAR","AED","JPY","MYR"]'),
('stripe_publishable_key', 'pk_test_REPLACE_WITH_YOUR_KEY'),
('stripe_secret_key', 'sk_test_REPLACE_WITH_YOUR_KEY'),
('stripe_webhook_secret', '');