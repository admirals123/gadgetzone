USE gadgetzone;

-- Settings table (stores currency & Stripe keys)
CREATE TABLE IF NOT EXISTS settings (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  setting_key  VARCHAR(100) NOT NULL UNIQUE,
  setting_value TEXT        NOT NULL,
  updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Stripe columns on orders table (guarded for MySQL versions without IF NOT EXISTS on ALTER)
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'stripe_session_id');
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE orders ADD COLUMN stripe_session_id VARCHAR(200) DEFAULT NULL',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists2 := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'payment_status');
SET @sql2 := IF(@col_exists2 = 0,
  "ALTER TABLE orders ADD COLUMN payment_status ENUM('unpaid','paid','refunded') DEFAULT 'unpaid'",
  'SELECT 1');
PREPARE stmt2 FROM @sql2; EXECUTE stmt2; DEALLOCATE PREPARE stmt2;

-- Default settings
INSERT INTO settings (setting_key, setting_value) VALUES
  ('active_currency',         'INR'),
  ('enabled_currencies',      '["INR","USD","EUR","GBP","CAD","AUD","BDT","SGD","SAR","AED","JPY","MYR"]'),
  ('stripe_publishable_key',  'pk_test_REPLACE_WITH_YOUR_KEY'),
  ('stripe_secret_key',       'sk_test_REPLACE_WITH_YOUR_KEY'),
  ('stripe_webhook_secret',   '')
ON DUPLICATE KEY UPDATE setting_value = setting_value;
