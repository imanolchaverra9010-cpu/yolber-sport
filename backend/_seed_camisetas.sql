USE yober_sport;
INSERT INTO categories (name, slug, icon_url, sort_order, is_active) SELECT 'Camisetas', 'camisetas', 'shirt', 4, 1 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='camisetas');
SET @cat = (SELECT id FROM categories WHERE slug='camisetas' LIMIT 1);
DELETE FROM products WHERE handle LIKE 'camiseta-deportiva-%';
INSERT INTO products (title, description, handle, category_id, status) VALUES
('Camiseta Deportiva 01', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-01', @cat, 'active'),
('Camiseta Deportiva 02', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-02', @cat, 'active'),
('Camiseta Deportiva 03', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-03', @cat, 'active'),
('Camiseta Deportiva 04', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-04', @cat, 'active'),
('Camiseta Deportiva 05', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-05', @cat, 'active'),
('Camiseta Deportiva 06', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-06', @cat, 'active'),
('Camiseta Deportiva 07', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-07', @cat, 'active'),
('Camiseta Deportiva 08', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-08', @cat, 'active'),
('Camiseta Deportiva 09', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-09', @cat, 'active'),
('Camiseta Deportiva 10', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-10', @cat, 'active'),
('Camiseta Deportiva 11', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-11', @cat, 'active'),
('Camiseta Deportiva 12', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-12', @cat, 'active'),
('Camiseta Deportiva 13', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-13', @cat, 'active'),
('Camiseta Deportiva 14', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-14', @cat, 'active'),
('Camiseta Deportiva 15', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-15', @cat, 'active'),
('Camiseta Deportiva 16', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-16', @cat, 'active'),
('Camiseta Deportiva 17', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-17', @cat, 'active'),
('Camiseta Deportiva 18', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-18', @cat, 'active'),
('Camiseta Deportiva 19', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-19', @cat, 'active'),
('Camiseta Deportiva 20', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-20', @cat, 'active'),
('Camiseta Deportiva 21', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-21', @cat, 'active'),
('Camiseta Deportiva 22', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-22', @cat, 'active'),
('Camiseta Deportiva 23', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-23', @cat, 'active'),
('Camiseta Deportiva 24', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-24', @cat, 'active'),
('Camiseta Deportiva 25', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-25', @cat, 'active'),
('Camiseta Deportiva 26', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-26', @cat, 'active'),
('Camiseta Deportiva 27', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-27', @cat, 'active'),
('Camiseta Deportiva 28', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-28', @cat, 'active'),
('Camiseta Deportiva 29', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-29', @cat, 'active'),
('Camiseta Deportiva 30', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-30', @cat, 'active'),
('Camiseta Deportiva 31', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-31', @cat, 'active'),
('Camiseta Deportiva 32', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-32', @cat, 'active'),
('Camiseta Deportiva 33', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-33', @cat, 'active'),
('Camiseta Deportiva 34', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-34', @cat, 'active'),
('Camiseta Deportiva 35', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-35', @cat, 'active'),
('Camiseta Deportiva 36', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-36', @cat, 'active'),
('Camiseta Deportiva 37', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-37', @cat, 'active'),
('Camiseta Deportiva 38', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-38', @cat, 'active'),
('Camiseta Deportiva 39', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-39', @cat, 'active'),
('Camiseta Deportiva 40', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-40', @cat, 'active'),
('Camiseta Deportiva 41', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-41', @cat, 'active'),
('Camiseta Deportiva 42', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-42', @cat, 'active'),
('Camiseta Deportiva 43', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-43', @cat, 'active'),
('Camiseta Deportiva 44', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-44', @cat, 'active'),
('Camiseta Deportiva 45', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-45', @cat, 'active'),
('Camiseta Deportiva 46', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-46', @cat, 'active'),
('Camiseta Deportiva 47', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-47', @cat, 'active'),
('Camiseta Deportiva 48', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-48', @cat, 'active'),
('Camiseta Deportiva 49', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-49', @cat, 'active'),
('Camiseta Deportiva 50', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-50', @cat, 'active'),
('Camiseta Deportiva 51', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-51', @cat, 'active'),
('Camiseta Deportiva 52', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-52', @cat, 'active'),
('Camiseta Deportiva 53', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-53', @cat, 'active'),
('Camiseta Deportiva 54', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-54', @cat, 'active'),
('Camiseta Deportiva 55', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-55', @cat, 'active'),
('Camiseta Deportiva 56', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-56', @cat, 'active'),
('Camiseta Deportiva 57', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-57', @cat, 'active'),
('Camiseta Deportiva 58', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-58', @cat, 'active'),
('Camiseta Deportiva 59', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-59', @cat, 'active'),
('Camiseta Deportiva 60', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-60', @cat, 'active'),
('Camiseta Deportiva 61', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-61', @cat, 'active'),
('Camiseta Deportiva 62', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-62', @cat, 'active'),
('Camiseta Deportiva 63', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-63', @cat, 'active'),
('Camiseta Deportiva 64', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-64', @cat, 'active'),
('Camiseta Deportiva 65', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-65', @cat, 'active'),
('Camiseta Deportiva 66', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-66', @cat, 'active'),
('Camiseta Deportiva 67', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-67', @cat, 'active'),
('Camiseta Deportiva 68', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-68', @cat, 'active'),
('Camiseta Deportiva 69', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-69', @cat, 'active'),
('Camiseta Deportiva 70', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-70', @cat, 'active'),
('Camiseta Deportiva 71', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-71', @cat, 'active'),
('Camiseta Deportiva 72', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-72', @cat, 'active'),
('Camiseta Deportiva 73', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-73', @cat, 'active'),
('Camiseta Deportiva 74', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-74', @cat, 'active'),
('Camiseta Deportiva 75', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-75', @cat, 'active'),
('Camiseta Deportiva 76', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-76', @cat, 'active'),
('Camiseta Deportiva 77', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-77', @cat, 'active'),
('Camiseta Deportiva 78', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-78', @cat, 'active'),
('Camiseta Deportiva 79', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-79', @cat, 'active'),
('Camiseta Deportiva 80', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-80', @cat, 'active'),
('Camiseta Deportiva 81', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-81', @cat, 'active'),
('Camiseta Deportiva 82', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-82', @cat, 'active'),
('Camiseta Deportiva 83', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-83', @cat, 'active'),
('Camiseta Deportiva 84', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-84', @cat, 'active'),
('Camiseta Deportiva 85', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-85', @cat, 'active'),
('Camiseta Deportiva 86', 'Camiseta deportiva premium (Nike / Jordan). Tela suave, corte regular. Foto original del producto.', 'camiseta-deportiva-86', @cat, 'active');
INSERT INTO product_images (product_id, url, alt_text, position)
SELECT p.id, m.img, p.title, 0 FROM products p JOIN (
SELECT 'camiseta-deportiva-01' h, 'uploads/products/tee-001.jpg' img UNION ALL SELECT 'camiseta-deportiva-02' h, 'uploads/products/tee-002.jpg' img UNION ALL SELECT 'camiseta-deportiva-03' h, 'uploads/products/tee-003.jpg' img UNION ALL SELECT 'camiseta-deportiva-04' h, 'uploads/products/tee-004.jpg' img UNION ALL SELECT 'camiseta-deportiva-05' h, 'uploads/products/tee-005.jpg' img UNION ALL SELECT 'camiseta-deportiva-06' h, 'uploads/products/tee-006.jpg' img UNION ALL SELECT 'camiseta-deportiva-07' h, 'uploads/products/tee-007.jpg' img UNION ALL SELECT 'camiseta-deportiva-08' h, 'uploads/products/tee-008.jpg' img UNION ALL SELECT 'camiseta-deportiva-09' h, 'uploads/products/tee-009.jpg' img UNION ALL SELECT 'camiseta-deportiva-10' h, 'uploads/products/tee-010.jpg' img UNION ALL SELECT 'camiseta-deportiva-11' h, 'uploads/products/tee-011.jpg' img UNION ALL SELECT 'camiseta-deportiva-12' h, 'uploads/products/tee-012.jpg' img UNION ALL SELECT 'camiseta-deportiva-13' h, 'uploads/products/tee-013.jpg' img UNION ALL SELECT 'camiseta-deportiva-14' h, 'uploads/products/tee-014.jpg' img UNION ALL SELECT 'camiseta-deportiva-15' h, 'uploads/products/tee-015.jpg' img UNION ALL SELECT 'camiseta-deportiva-16' h, 'uploads/products/tee-016.jpg' img UNION ALL SELECT 'camiseta-deportiva-17' h, 'uploads/products/tee-017.jpg' img UNION ALL SELECT 'camiseta-deportiva-18' h, 'uploads/products/tee-018.jpg' img UNION ALL SELECT 'camiseta-deportiva-19' h, 'uploads/products/tee-019.jpg' img UNION ALL SELECT 'camiseta-deportiva-20' h, 'uploads/products/tee-020.jpg' img UNION ALL SELECT 'camiseta-deportiva-21' h, 'uploads/products/tee-021.jpg' img UNION ALL SELECT 'camiseta-deportiva-22' h, 'uploads/products/tee-022.jpg' img UNION ALL SELECT 'camiseta-deportiva-23' h, 'uploads/products/tee-023.jpg' img UNION ALL SELECT 'camiseta-deportiva-24' h, 'uploads/products/tee-024.jpg' img UNION ALL SELECT 'camiseta-deportiva-25' h, 'uploads/products/tee-025.jpg' img UNION ALL SELECT 'camiseta-deportiva-26' h, 'uploads/products/tee-026.jpg' img UNION ALL SELECT 'camiseta-deportiva-27' h, 'uploads/products/tee-027.jpg' img UNION ALL SELECT 'camiseta-deportiva-28' h, 'uploads/products/tee-028.jpg' img UNION ALL SELECT 'camiseta-deportiva-29' h, 'uploads/products/tee-029.jpg' img UNION ALL SELECT 'camiseta-deportiva-30' h, 'uploads/products/tee-030.jpg' img UNION ALL SELECT 'camiseta-deportiva-31' h, 'uploads/products/tee-031.jpg' img UNION ALL SELECT 'camiseta-deportiva-32' h, 'uploads/products/tee-032.jpg' img UNION ALL SELECT 'camiseta-deportiva-33' h, 'uploads/products/tee-033.jpg' img UNION ALL SELECT 'camiseta-deportiva-34' h, 'uploads/products/tee-034.jpg' img UNION ALL SELECT 'camiseta-deportiva-35' h, 'uploads/products/tee-035.jpg' img UNION ALL SELECT 'camiseta-deportiva-36' h, 'uploads/products/tee-036.jpg' img UNION ALL SELECT 'camiseta-deportiva-37' h, 'uploads/products/tee-037.jpg' img UNION ALL SELECT 'camiseta-deportiva-38' h, 'uploads/products/tee-038.jpg' img UNION ALL SELECT 'camiseta-deportiva-39' h, 'uploads/products/tee-039.jpg' img UNION ALL SELECT 'camiseta-deportiva-40' h, 'uploads/products/tee-040.jpg' img UNION ALL SELECT 'camiseta-deportiva-41' h, 'uploads/products/tee-041.jpg' img UNION ALL SELECT 'camiseta-deportiva-42' h, 'uploads/products/tee-042.jpg' img UNION ALL SELECT 'camiseta-deportiva-43' h, 'uploads/products/tee-043.jpg' img UNION ALL SELECT 'camiseta-deportiva-44' h, 'uploads/products/tee-044.jpg' img UNION ALL SELECT 'camiseta-deportiva-45' h, 'uploads/products/tee-045.jpg' img UNION ALL SELECT 'camiseta-deportiva-46' h, 'uploads/products/tee-046.jpg' img UNION ALL SELECT 'camiseta-deportiva-47' h, 'uploads/products/tee-047.jpg' img UNION ALL SELECT 'camiseta-deportiva-48' h, 'uploads/products/tee-048.jpg' img UNION ALL SELECT 'camiseta-deportiva-49' h, 'uploads/products/tee-049.jpg' img UNION ALL SELECT 'camiseta-deportiva-50' h, 'uploads/products/tee-050.jpg' img UNION ALL SELECT 'camiseta-deportiva-51' h, 'uploads/products/tee-051.jpg' img UNION ALL SELECT 'camiseta-deportiva-52' h, 'uploads/products/tee-052.jpg' img UNION ALL SELECT 'camiseta-deportiva-53' h, 'uploads/products/tee-053.jpg' img UNION ALL SELECT 'camiseta-deportiva-54' h, 'uploads/products/tee-054.jpg' img UNION ALL SELECT 'camiseta-deportiva-55' h, 'uploads/products/tee-055.jpg' img UNION ALL SELECT 'camiseta-deportiva-56' h, 'uploads/products/tee-056.jpg' img UNION ALL SELECT 'camiseta-deportiva-57' h, 'uploads/products/tee-057.jpg' img UNION ALL SELECT 'camiseta-deportiva-58' h, 'uploads/products/tee-058.jpg' img UNION ALL SELECT 'camiseta-deportiva-59' h, 'uploads/products/tee-059.jpg' img UNION ALL SELECT 'camiseta-deportiva-60' h, 'uploads/products/tee-060.jpg' img UNION ALL SELECT 'camiseta-deportiva-61' h, 'uploads/products/tee-061.jpg' img UNION ALL SELECT 'camiseta-deportiva-62' h, 'uploads/products/tee-062.jpg' img UNION ALL SELECT 'camiseta-deportiva-63' h, 'uploads/products/tee-063.jpg' img UNION ALL SELECT 'camiseta-deportiva-64' h, 'uploads/products/tee-064.jpg' img UNION ALL SELECT 'camiseta-deportiva-65' h, 'uploads/products/tee-065.jpg' img UNION ALL SELECT 'camiseta-deportiva-66' h, 'uploads/products/tee-066.jpg' img UNION ALL SELECT 'camiseta-deportiva-67' h, 'uploads/products/tee-067.jpg' img UNION ALL SELECT 'camiseta-deportiva-68' h, 'uploads/products/tee-068.jpg' img UNION ALL SELECT 'camiseta-deportiva-69' h, 'uploads/products/tee-069.jpg' img UNION ALL SELECT 'camiseta-deportiva-70' h, 'uploads/products/tee-070.jpg' img UNION ALL SELECT 'camiseta-deportiva-71' h, 'uploads/products/tee-071.jpg' img UNION ALL SELECT 'camiseta-deportiva-72' h, 'uploads/products/tee-072.jpg' img UNION ALL SELECT 'camiseta-deportiva-73' h, 'uploads/products/tee-073.jpg' img UNION ALL SELECT 'camiseta-deportiva-74' h, 'uploads/products/tee-074.jpg' img UNION ALL SELECT 'camiseta-deportiva-75' h, 'uploads/products/tee-075.jpg' img UNION ALL SELECT 'camiseta-deportiva-76' h, 'uploads/products/tee-076.jpg' img UNION ALL SELECT 'camiseta-deportiva-77' h, 'uploads/products/tee-077.jpg' img UNION ALL SELECT 'camiseta-deportiva-78' h, 'uploads/products/tee-078.jpg' img UNION ALL SELECT 'camiseta-deportiva-79' h, 'uploads/products/tee-079.jpg' img UNION ALL SELECT 'camiseta-deportiva-80' h, 'uploads/products/tee-080.jpg' img UNION ALL SELECT 'camiseta-deportiva-81' h, 'uploads/products/tee-081.jpg' img UNION ALL SELECT 'camiseta-deportiva-82' h, 'uploads/products/tee-082.jpg' img UNION ALL SELECT 'camiseta-deportiva-83' h, 'uploads/products/tee-083.jpg' img UNION ALL SELECT 'camiseta-deportiva-84' h, 'uploads/products/tee-084.jpg' img UNION ALL SELECT 'camiseta-deportiva-85' h, 'uploads/products/tee-085.jpg' img UNION ALL SELECT 'camiseta-deportiva-86' h, 'uploads/products/tee-086.jpg' img
) m ON m.h = p.handle;

INSERT INTO product_variants (product_id, title, price, available_for_sale, inventory_quantity, sku)
SELECT p.id, sz.size, 129000, 1, 8,
  CONCAT('TEE', LPAD(CAST(SUBSTRING_INDEX(p.handle,'-',-1) AS UNSIGNED), 3, '0'), '-', sz.size)
FROM products p
JOIN (SELECT 'S' size UNION ALL SELECT 'M' UNION ALL SELECT 'L' UNION ALL SELECT 'XL') sz
WHERE p.handle LIKE 'camiseta-deportiva-%';

INSERT INTO variant_options (variant_id, option_name, option_value)
SELECT v.id, 'Talla', v.title
FROM product_variants v
JOIN products p ON p.id = v.product_id
WHERE p.handle LIKE 'camiseta-deportiva-%';

INSERT INTO product_options (product_id, name, option_values)
SELECT p.id, 'Talla', '["S","M","L","XL"]'
FROM products p
WHERE p.handle LIKE 'camiseta-deportiva-%';

SELECT COUNT(*) AS camisetas FROM products WHERE handle LIKE 'camiseta-deportiva-%';
