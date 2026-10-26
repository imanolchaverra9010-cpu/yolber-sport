-- Database Schema for Yober Sport E-commerce
-- MySQL 8.0+

CREATE DATABASE IF NOT EXISTS yober_sport CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE yober_sport;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(255),
    is_admin TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Store settings
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categories table (icons managed from admin)
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

-- Products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    handle VARCHAR(255) UNIQUE NOT NULL,
    category_id INT NULL,
    status ENUM('active', 'draft', 'archived') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_handle (handle),
    INDEX idx_status (status),
    INDEX idx_category_id (category_id),
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Product images table
CREATE TABLE IF NOT EXISTS product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    url VARCHAR(500) NOT NULL,
    alt_text VARCHAR(255),
    position INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Product variants table (sizes, colors, etc.)
CREATE TABLE IF NOT EXISTS product_variants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    compare_at_price DECIMAL(10, 2),
    available_for_sale BOOLEAN DEFAULT TRUE,
    inventory_quantity INT DEFAULT 0,
    sku VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id),
    INDEX idx_sku (sku)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Variant options (e.g., Color: Red, Size: M)
CREATE TABLE IF NOT EXISTS variant_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    variant_id INT NOT NULL,
    option_name VARCHAR(100) NOT NULL,
    option_value VARCHAR(100) NOT NULL,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE,
    INDEX idx_variant_id (variant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Product options (e.g., Color, Size)
CREATE TABLE IF NOT EXISTS product_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    option_values JSON NOT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Carts table
CREATE TABLE IF NOT EXISTS carts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    session_id VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cart items table
CREATE TABLE IF NOT EXISTS cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cart_id INT NOT NULL,
    variant_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE,
    INDEX idx_cart_id (cart_id),
    INDEX idx_variant_id (variant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    customer_name VARCHAR(255) NOT NULL,
    customer_email VARCHAR(255),
    customer_phone VARCHAR(50),
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    variant_id INT NOT NULL,
    product_title VARCHAR(255) NOT NULL,
    variant_title VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_order_id (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample categories (icon_url stores Lucide icon key: shirt, footprints, etc.)
INSERT INTO categories (name, slug, icon_url, sort_order, is_active) VALUES
('Hoodies', 'hoodies', 'shirt', 1, 1),
('Sneakers', 'sneakers', 'footprints', 2, 1),
('Joggers', 'joggers', 'personStanding', 3, 1),
('Tops', 'tops', 'wind', 4, 1),
('Accesorios', 'accesorios', 'watch', 5, 1);

-- Insert sample data for testing
INSERT INTO products (title, description, handle, category_id, status) VALUES
('Urban Hoodie Black', 'Hoodie premium con diseño urbano exclusivo. Fabricado con algodón de alta calidad y detalles en dorado.', 'urban-hoodie-black', 1, 'active'),
('Street Sneakers Gold', 'Sneakers urbanas con detalles dorados. Comodidad máxima para el día a día.', 'street-sneakers-gold', 2, 'active'),
('Premium Joggers', 'Joggers con corte moderno y tejido premium. Perfectos para cualquier ocasión.', 'premium-joggers', 3, 'active');

INSERT INTO product_images (product_id, url, alt_text, position) VALUES
(1, 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=800', 'Urban Hoodie Black frontal', 0),
(1, 'https://images.unsplash.com/photo-1578768079052-aa76e52ff62e?w=800', 'Urban Hoodie Black detalle', 1),
(2, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=800', 'Street Sneakers Gold frontal', 0),
(3, 'https://images.unsplash.com/photo-1552902865-b72c031ac5ea?w=800', 'Premium Joggers frontal', 0);

INSERT INTO product_variants (product_id, title, price, available_for_sale, inventory_quantity, sku) VALUES
(1, 'S', 89000, TRUE, 10, 'UHB-S'),
(1, 'M', 89000, TRUE, 15, 'UHB-M'),
(1, 'L', 89000, TRUE, 8, 'UHB-L'),
(2, '40', 150000, TRUE, 5, 'SSG-40'),
(2, '41', 150000, TRUE, 8, 'SSG-41'),
(2, '42', 150000, TRUE, 12, 'SSG-42'),
(3, 'M', 75000, TRUE, 20, 'PJ-M'),
(3, 'L', 75000, TRUE, 15, 'PJ-L'),
(3, 'XL', 75000, TRUE, 10, 'PJ-XL');

INSERT INTO variant_options (variant_id, option_name, option_value) VALUES
(1, 'Talla', 'S'), (2, 'Talla', 'M'), (3, 'Talla', 'L'),
(4, 'Talla', '40'), (5, 'Talla', '41'), (6, 'Talla', '42'),
(7, 'Talla', 'M'), (8, 'Talla', 'L'), (9, 'Talla', 'XL');

INSERT INTO product_options (product_id, name, option_values) VALUES
(1, 'Talla', '["S", "M", "L"]'),
(2, 'Talla', '["40", "41", "42"]'),
(3, 'Talla', '["M", "L", "XL"]');

-- Default settings
INSERT INTO settings (setting_key, setting_value) VALUES
('store_name', 'Yolber Sport'),
('store_slogan', 'Luce siempre tu mejor estilo'),
('whatsapp_phone', '573000000000'),
('contact_email', 'info@yolbersport.com'),
('contact_phone', '+57 300 000 0000'),
('contact_city', 'Colombia'),
('shipping_note', 'Envíos a todo el país'),
('currency', 'COP');

-- Default admin (password: admin123). Prefer running backend/admin/setup.php once.
INSERT INTO users (email, password_hash, name, is_admin) VALUES
('admin@yolbersport.com', '$2y$10$aeiclxTQN1ucWi9xXxWGhOC.Y5NaR26Uev84v5Xh28wVv4Xm6ULdy', 'Administrador', 1)
ON DUPLICATE KEY UPDATE
  password_hash = VALUES(password_hash),
  name = VALUES(name),
  is_admin = 1;
