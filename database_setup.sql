-- ========================================================
-- GadgetZone - Complete Database Setup & Schema
-- ========================================================

-- --------------------------------------------------------
-- 1. Table structure for `categories`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(100) NOT NULL UNIQUE,
  icon VARCHAR(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 2. Table structure for `products`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS products (
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
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  email VARCHAR(191) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  phone VARCHAR(20) DEFAULT NULL,
  address TEXT DEFAULT NULL,
  city VARCHAR(100) DEFAULT NULL,
  state VARCHAR(100) DEFAULT NULL,
  zip VARCHAR(20) DEFAULT NULL,
  avatar VARCHAR(255) DEFAULT NULL,
  role ENUM('customer','admin','super_admin') DEFAULT 'customer',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 4. Table structure for `orders`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
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
CREATE TABLE IF NOT EXISTS order_items (
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
CREATE TABLE IF NOT EXISTS settings (
  setting_key VARCHAR(100) PRIMARY KEY,
  setting_value TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 7. Seed Sample Categories
-- --------------------------------------------------------
INSERT IGNORE INTO categories (id, name, slug, icon) VALUES
(1, 'Smartphones', 'smartphones', '📱'),
(2, 'Laptops', 'laptops', '💻'),
(3, 'Audio', 'audio', '🎧'),
(4, 'Cameras', 'cameras', '📷'),
(5, 'Wearables', 'wearables', '⌚'),
(6, 'Accessories', 'accessories', '🔌');

-- --------------------------------------------------------
-- 8. Seed Sample Products (All prices in INR)
-- --------------------------------------------------------
INSERT IGNORE INTO products (id, category_id, name, slug, description, price, old_price, image_url, badge, stock, featured) VALUES
(1, 1, 'iPhone 15 Pro Max', 'iphone-15-pro-max', 'Forged in titanium and featuring the groundbreaking A17 Pro chip, customizable Action button, and the most powerful iPhone camera system ever.', 134900.00, 159900.00, 'https://images.unsplash.com/photo-1695048133142-1a20484d2569?auto=format&fit=crop&w=800&q=80', 'HOT', 15, 1),
(2, 1, 'Samsung Galaxy S24 Ultra', 'samsung-galaxy-s24-ultra', 'Meet Galaxy S24 Ultra, the ultimate form of Galaxy Ultra with a new titanium exterior and a 6.8-inch flat display with Galaxy AI built in.', 129999.00, 144999.00, 'https://images.unsplash.com/photo-1610945265064-0e34e5519bbf?auto=format&fit=crop&w=800&q=80', 'NEW', 12, 1),
(3, 2, 'MacBook Pro 16" M3 Max', 'macbook-pro-16-m3-max', 'MacBook Pro blasts forward with M3 Max, an incredibly advanced chip that delivers serious speed and capability for demanding workflows.', 249900.00, 269900.00, 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=800&q=80', 'HOT', 8, 1),
(4, 2, 'Dell XPS 15 OLED', 'dell-xps-15-oled', 'Immerse yourself in content with stunning 3.5K OLED display, 13th Gen Intel Core processors and NVIDIA GeForce RTX 40-Series graphics.', 175000.00, 195000.00, 'https://images.unsplash.com/photo-1593642632823-8f785ba67e45?auto=format&fit=crop&w=800&q=80', 'SALE', 5, 0),
(5, 3, 'Sony WH-1000XM5', 'sony-wh-1000xm5', 'Industry-leading noise cancellation with two processors and eight microphones for unprecedented sound quality and crystal-clear calling.', 29990.00, 34990.00, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80', 'HOT', 25, 1),
(6, 3, 'Apple AirPods Max', 'apple-airpods-max', 'High-fidelity audio, Active Noise Cancellation with Transparency mode, personalized spatial audio, and an exceptional acoustic design.', 59900.00, 64900.00, 'https://images.unsplash.com/photo-1546435770-a3e426bf472b?auto=format&fit=crop&w=800&q=80', 'NEW', 10, 0),
(7, 4, 'Sony Alpha A7 IV', 'sony-alpha-a7-iv', 'With groundbreaking 33MP full-frame image sensor, 4K 60p recording, and next-generation real-time autofocus for photo and video creators.', 214990.00, 229990.00, 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?auto=format&fit=crop&w=800&q=80', 'HOT', 5, 1),
(8, 5, 'Apple Watch Ultra 2', 'apple-watch-ultra-2', 'The most capable and rugged Apple Watch with bright Always-On Retina display, dual-frequency GPS, and up to 36 hours of battery life.', 85000.00, 95000.00, 'https://images.unsplash.com/photo-1434493789847-2f02dc6ca35d?auto=format&fit=crop&w=800&q=80', 'NEW', 18, 1),
(9, 6, 'Anker 737 Power Bank 24000mAh', 'anker-737-power-bank', 'Ultra-powerful 140W fast charging battery pack with smart digital display and MultiProtect safety system.', 14500.00, 18000.00, 'https://images.unsplash.com/photo-1583863788434-e58a36330cf0?auto=format&fit=crop&w=800&q=80', 'SALE', 40, 0);

-- --------------------------------------------------------
-- 9. Seed Default Super Admin User
-- Email: admin@gadgetzone.com | Password: Admin@1234
-- --------------------------------------------------------
INSERT IGNORE INTO users (id, first_name, last_name, email, password, phone, role) VALUES
(1, 'Super', 'Admin', 'admin@gadgetzone.com', '$2y$10$Z/84/cpR4RONTdGDOvUTKeRp2DqoJOU4ZZ57tTQjNykSXwpjYz.OC', '+8801700000000', 'super_admin');

-- --------------------------------------------------------
-- 10. Seed System Settings
-- --------------------------------------------------------
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('active_currency', 'INR'),
('enabled_currencies', '["INR","USD","EUR","GBP","CAD","AUD","BDT","SGD","SAR","AED","JPY","MYR"]'),
('stripe_publishable_key', 'pk_test_REPLACE_WITH_YOUR_KEY'),
('stripe_secret_key', 'sk_test_REPLACE_WITH_YOUR_KEY'),
('stripe_webhook_secret', '');