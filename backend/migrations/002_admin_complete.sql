USE yober_sport;

-- Admin flag on users
SET @col_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'is_admin'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE users ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0 AFTER name',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE users SET is_admin = 1 WHERE email = 'admin@yobersport.com' OR email = 'admin@yolbersport.com';

-- Store settings key/value
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('store_name', 'Yolber Sport'),
('store_slogan', 'Luce siempre tu mejor estilo'),
('whatsapp_phone', '573000000000'),
('contact_email', 'info@yolbersport.com'),
('contact_phone', '+57 300 000 0000'),
('contact_city', 'Colombia'),
('shipping_note', 'Envíos a todo el país'),
('currency', 'COP');

-- Ensure admin user exists / reset password to admin123
INSERT INTO users (email, password_hash, name, is_admin)
SELECT 'admin@yolbersport.com', '$2y$10$aeiclxTQN1ucWi9xXxWGhOC.Y5NaR26Uev84v5Xh28wVv4Xm6ULdy', 'Administrador', 1
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@yolbersport.com');

UPDATE users
SET password_hash = '$2y$10$aeiclxTQN1ucWi9xXxWGhOC.Y5NaR26Uev84v5Xh28wVv4Xm6ULdy',
    is_admin = 1,
    name = 'Administrador'
WHERE email IN ('admin@yolbersport.com', 'admin@yobersport.com');
