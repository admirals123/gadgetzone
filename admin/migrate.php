<?php
/**
 * One-Click Cloud Database Initializer & Migration Tool.
 * Access via: https://your-domain.vercel.app/admin/migrate.php
 */

require_once __DIR__ . '/../includes/db.php';

$title = "Database Migration & Setup";
$output = [];
$errors = [];

$queries = [
    "CREATE TABLE IF NOT EXISTS categories (
      id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(100) NOT NULL,
      slug VARCHAR(100) NOT NULL UNIQUE,
      icon VARCHAR(50) DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS products (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS users (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS orders (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS order_items (
      id INT AUTO_INCREMENT PRIMARY KEY,
      order_id INT NOT NULL,
      product_id INT NOT NULL,
      quantity INT NOT NULL,
      price DECIMAL(10,2) NOT NULL,
      FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
      FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS settings (
      setting_key VARCHAR(100) PRIMARY KEY,
      setting_value TEXT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "INSERT IGNORE INTO categories (id, name, slug, icon) VALUES
    (1, 'Smartphones', 'smartphones', '📱'),
    (2, 'Laptops', 'laptops', '💻'),
    (3, 'Audio', 'audio', '🎧'),
    (4, 'Cameras', 'cameras', '📷'),
    (5, 'Wearables', 'wearables', '⌚'),
    (6, 'Accessories', 'accessories', '🔌')",

    "INSERT IGNORE INTO products (id, category_id, name, slug, description, price, old_price, image_url, badge, stock, featured) VALUES
    (1, 1, 'iPhone 15 Pro Max', 'iphone-15-pro-max', 'Forged in titanium and featuring the groundbreaking A17 Pro chip, customizable Action button, and the most powerful iPhone camera system ever.', 134900.00, 159900.00, 'https://images.unsplash.com/photo-1695048133142-1a20484d2569?auto=format&fit=crop&w=800&q=80', 'HOT', 15, 1),
    (2, 1, 'Samsung Galaxy S24 Ultra', 'samsung-galaxy-s24-ultra', 'Meet Galaxy S24 Ultra, the ultimate form of Galaxy Ultra with a new titanium exterior and a 6.8-inch flat display with Galaxy AI built in.', 129999.00, 144999.00, 'https://images.unsplash.com/photo-1610945265064-0e34e5519bbf?auto=format&fit=crop&w=800&q=80', 'NEW', 12, 1),
    (3, 2, 'MacBook Pro 16\" M3 Max', 'macbook-pro-16-m3-max', 'MacBook Pro blasts forward with M3 Max, an incredibly advanced chip that delivers serious speed and capability for demanding workflows.', 249900.00, 269900.00, 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=800&q=80', 'HOT', 8, 1),
    (4, 2, 'Dell XPS 15 OLED', 'dell-xps-15-oled', 'Immerse yourself in content with stunning 3.5K OLED display, 13th Gen Intel Core processors and NVIDIA GeForce RTX 40-Series graphics.', 175000.00, 195000.00, 'https://images.unsplash.com/photo-1593642632823-8f785ba67e45?auto=format&fit=crop&w=800&q=80', 'SALE', 5, 0),
    (5, 3, 'Sony WH-1000XM5', 'sony-wh-1000xm5', 'Industry-leading noise cancellation with two processors and eight microphones for unprecedented sound quality and crystal-clear calling.', 29990.00, 34990.00, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80', 'HOT', 25, 1),
    (6, 3, 'Apple AirPods Max', 'apple-airpods-max', 'High-fidelity audio, Active Noise Cancellation with Transparency mode, personalized spatial audio, and an exceptional acoustic design.', 59900.00, 64900.00, 'https://images.unsplash.com/photo-1546435770-a3e426bf472b?auto=format&fit=crop&w=800&q=80', 'NEW', 10, 0),
    (7, 4, 'Sony Alpha A7 IV', 'sony-alpha-a7-iv', 'With groundbreaking 33MP full-frame image sensor, 4K 60p recording, and next-generation real-time autofocus for photo and video creators.', 214990.00, 229990.00, 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?auto=format&fit=crop&w=800&q=80', 'HOT', 5, 1),
    (8, 5, 'Apple Watch Ultra 2', 'apple-watch-ultra-2', 'The most capable and rugged Apple Watch with bright Always-On Retina display, dual-frequency GPS, and up to 36 hours of battery life.', 85000.00, 95000.00, 'https://images.unsplash.com/photo-1434493789847-2f02dc6ca35d?auto=format&fit=crop&w=800&q=80', 'NEW', 18, 1),
    (9, 6, 'Anker 737 Power Bank 24000mAh', 'anker-737-power-bank', 'Ultra-powerful 140W fast charging battery pack with smart digital display and MultiProtect safety system.', 14500.00, 18000.00, 'https://images.unsplash.com/photo-1583863788434-e58a36330cf0?auto=format&fit=crop&w=800&q=80', 'SALE', 40, 0)",

    "INSERT IGNORE INTO users (id, first_name, last_name, email, password, phone, role) VALUES
    (1, 'Super', 'Admin', 'admin@gadgetzone.com', '$2y$10$Z/84/cpR4RONTdGDOvUTKeRp2DqoJOU4ZZ57tTQjNykSXwpjYz.OC', '+8801700000000', 'super_admin')",

    "INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
    ('active_currency', 'INR'),
    ('enabled_currencies', '[\"INR\",\"USD\",\"EUR\",\"GBP\",\"CAD\",\"AUD\",\"BDT\",\"SGD\",\"SAR\",\"AED\",\"JPY\",\"MYR\"]'),
    ('stripe_publishable_key', 'pk_test_REPLACE_WITH_YOUR_KEY'),
    ('stripe_secret_key', 'sk_test_REPLACE_WITH_YOUR_KEY'),
    ('stripe_webhook_secret', '')"
];

// Generate fresh hash for the admin password at runtime (avoids any bcrypt compatibility issues)
$adminPasswordHash = password_hash('Admin@1234', PASSWORD_BCRYPT, ['cost' => 10]);
$queries[] = "INSERT INTO users (id, first_name, last_name, email, password, phone, role)
    VALUES (1, 'Super', 'Admin', 'admin@gadgetzone.com', '$adminPasswordHash', '+8801700000000', 'super_admin')
    ON DUPLICATE KEY UPDATE password = '$adminPasswordHash', role = 'super_admin'";


$successCount = 0;
foreach ($queries as $i => $q) {
    if (mysqli_query($conn, $q)) {
        $successCount++;
    } else {
        $errors[] = "Query " . ($i + 1) . " error: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Database Setup — GadgetZone</title>
  <style>
    body { font-family: system-ui, -apple-system, sans-serif; background: #0f172a; color: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; }
    .card { background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 36px; max-width: 540px; width: 100%; box-shadow: 0 25px 50px rgba(0,0,0,0.5); text-align: center; }
    h1 { margin-top: 0; font-size: 24px; color: #f59e0b; }
    .badge-success { background: rgba(34,197,94,0.15); color: #4ade80; border: 1px solid rgba(34,197,94,0.3); padding: 10px 16px; border-radius: 8px; font-weight: 700; margin: 20px 0; font-size: 15px; }
    .badge-error { background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.3); padding: 12px; border-radius: 8px; font-size: 13px; text-align: left; margin: 16px 0; }
    .btn { display: inline-block; background: #f59e0b; color: #0a0a0f; font-weight: 700; text-decoration: none; padding: 12px 24px; border-radius: 8px; margin: 8px 6px; transition: transform 0.15s; }
    .btn:hover { transform: translateY(-2px); }
    .btn-secondary { background: #334155; color: #f8fafc; }
  </style>
</head>
<body>
  <div class="card">
    <div style="font-size: 48px; margin-bottom: 12px;">⚡</div>
    <h1>GadgetZone Cloud Database Setup</h1>

    <?php if (empty($errors)): ?>
      <div class="badge-success">
        ✅ All <?= $successCount ?> Database Tables & Sample Records Initialized Successfully!
      </div>
      <p style="color:#94a3b8; font-size:14px; line-height:1.6;">
        Your cloud database is now fully configured with sample products, categories, default admin credentials, and currency settings.
      </p>
      <div style="margin-top: 24px;">
        <a href="../index.php" class="btn">🛍️ Open Storefront</a>
        <a href="index.php" class="btn btn-secondary">📊 Open Admin Portal</a>
      </div>
    <?php else: ?>
      <div class="badge-error">
        <strong>⚠️ Database Setup Errors:</strong><br>
        <?= implode('<br>', array_map('htmlspecialchars', $errors)) ?>
      </div>
      <a href="migrate.php" class="btn">🔄 Try Again</a>
    <?php endif; ?>
  </div>
</body>
</html>
