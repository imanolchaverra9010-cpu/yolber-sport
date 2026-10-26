-- Run this if the DB already exists without categories
USE yober_sport;

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    icon_url VARCHAR(500) NOT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_sort (sort_order),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add category_id to products if missing
SET @col_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'category_id'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE products ADD COLUMN category_id INT NULL, ADD INDEX idx_category_id (category_id), ADD CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT IGNORE INTO categories (name, slug, icon_url, sort_order, is_active) VALUES
('Hoodies', 'hoodies', 'shirt', 1, 1),
('Sneakers', 'sneakers', 'footprints', 2, 1),
('Joggers', 'joggers', 'personStanding', 3, 1),
('Tops', 'tops', 'wind', 4, 1),
('Accesorios', 'accesorios', 'watch', 5, 1);

-- Convert legacy image URLs to icon keys when possible
UPDATE categories SET icon_url = 'shirt' WHERE icon_url LIKE '%hoodie%' OR icon_url LIKE '%1556821840%';
UPDATE categories SET icon_url = 'footprints' WHERE icon_url LIKE '%sneaker%' OR icon_url LIKE '%1542291026%' OR icon_url LIKE '%1606107557%';
UPDATE categories SET icon_url = 'personStanding' WHERE icon_url LIKE '%jogger%' OR icon_url LIKE '%1552902865%';
UPDATE categories SET icon_url = 'wind' WHERE icon_url LIKE '%1578768079%';
UPDATE categories SET icon_url = 'watch' WHERE icon_url LIKE '%1515886657%' OR icon_url LIKE '%accesor%';
UPDATE categories SET icon_url = 'tag' WHERE icon_url LIKE 'http%' OR icon_url LIKE 'uploads/%';
