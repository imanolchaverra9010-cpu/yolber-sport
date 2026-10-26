USE yober_sport;

INSERT INTO categories (name, slug, icon_url, sort_order, is_active)
SELECT 'Pantalones', 'pantalones', 'pants', 6, 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug = 'pantalones');

SET @cat = (SELECT id FROM categories WHERE slug = 'pantalones' LIMIT 1);

-- Helper: wipe previous seeded jeans by handle prefix
DELETE FROM products WHERE handle LIKE 'jean-%';

INSERT INTO products (title, description, handle, category_id, status) VALUES
('Jean Amiri Destroyed Azul Claro', 'Jean slim destroyed azul claro Amiri. Rotos frontales y acabado raw. Empaque original.', 'jean-amiri-destroyed-azul-claro', @cat, 'active'),
('Jean Amiri MX1 Azul', 'Jean Amiri estilo MX1 azul claro con inserts plisados negros bajo los rotos. Slim fit.', 'jean-amiri-mx1-azul', @cat, 'active'),
('Jean Amiri Stars Negro', 'Jean Amiri negro desgastado con estrellas bordadas y inserts plisados.', 'jean-amiri-stars-negro', @cat, 'active'),
('Jean Purple Brand Acid Wash', 'Jean Purple Brand acid wash azul muy claro con rotos. Slim fit.', 'jean-purple-brand-acid-wash', @cat, 'active'),
('Jean Amiri Patch Rayas', 'Jean Amiri azul oscuro con rotos reforzados en tela a rayas. Slim fit.', 'jean-amiri-patch-rayas', @cat, 'active'),
('Jean Purple Brand Negro Paint', 'Jean Purple Brand negro desgastado con paint splatter y rotos. Slim fit.', 'jean-purple-brand-negro-paint', @cat, 'active'),
('Jean Amiri Inlay Crema', 'Jean Amiri azul claro con rotos e inserts plisados crema.', 'jean-amiri-inlay-crema', @cat, 'active'),
('Jean Purple Brand Rotos Azul', 'Jean Purple Brand azul medio con hole en rodilla y parches. Slim fit.', 'jean-purple-brand-rotos-azul', @cat, 'active'),
('Jean Purple Brand Negro Studs', 'Jean Purple Brand negro con rotos y studs metalicos. Slim fit.', 'jean-purple-brand-negro-studs', @cat, 'active'),
('Jean Purple Brand Negro Patch', 'Jean Purple Brand negro desgastado con parche grafico. Slim fit.', 'jean-purple-brand-negro-patch', @cat, 'active'),
('Jean Purple Brand Azul Claro', 'Jean Purple Brand wash claro con whiskering y rotos ligeros.', 'jean-purple-brand-azul-claro', @cat, 'active'),
('Jean Purple Brand Azul Parche', 'Jean Purple Brand azul medio con parche triangular y rotos. Slim fit.', 'jean-purple-brand-azul-parche', @cat, 'active'),
('Jean Purple Brand Paint Claro', 'Jean Purple Brand azul claro con paint splatter negro y rotos.', 'jean-purple-brand-paint-claro', @cat, 'active'),
('Jean Purple Brand Paint Azul', 'Jean Purple Brand azul con paint splatter blanco y azul. Slim fit.', 'jean-purple-brand-paint-azul', @cat, 'active');

INSERT INTO product_images (product_id, url, alt_text, position)
SELECT p.id, CONCAT('uploads/products/', s.file), p.title, 0
FROM products p
JOIN (
  SELECT 'jean-amiri-destroyed-azul-claro' h, 'am758.jpg' file UNION ALL
  SELECT 'jean-amiri-mx1-azul', 'am753.jpg' UNION ALL
  SELECT 'jean-amiri-stars-negro', 'am751.jpg' UNION ALL
  SELECT 'jean-purple-brand-acid-wash', 'pe8008.jpg' UNION ALL
  SELECT 'jean-amiri-patch-rayas', 'am707.jpg' UNION ALL
  SELECT 'jean-purple-brand-negro-paint', 'pb638.jpg' UNION ALL
  SELECT 'jean-amiri-inlay-crema', 'am739.jpg' UNION ALL
  SELECT 'jean-purple-brand-rotos-azul', 'pb595.jpg' UNION ALL
  SELECT 'jean-purple-brand-negro-studs', 'pe8019.jpg' UNION ALL
  SELECT 'jean-purple-brand-negro-patch', 'pe8022.jpg' UNION ALL
  SELECT 'jean-purple-brand-azul-claro', 'pe8013.jpg' UNION ALL
  SELECT 'jean-purple-brand-azul-parche', 'pb653.jpg' UNION ALL
  SELECT 'jean-purple-brand-paint-claro', 'pb629.jpg' UNION ALL
  SELECT 'jean-purple-brand-paint-azul', 'pb652.jpg'
) s ON s.h = p.handle;

INSERT INTO product_variants (product_id, title, price, available_for_sale, inventory_quantity, sku)
SELECT p.id, sz.size, 289000, 1, 5,
  CONCAT(UPPER(SUBSTRING_INDEX(SUBSTRING_INDEX(img.url, '/', -1), '.', 1)), '-', sz.size)
FROM products p
JOIN product_images img ON img.product_id = p.id AND img.position = 0
JOIN (
  SELECT '30' size UNION ALL SELECT '32' UNION ALL SELECT '34'
) sz
WHERE p.handle LIKE 'jean-%';

INSERT INTO variant_options (variant_id, option_name, option_value)
SELECT v.id, 'Talla', v.title
FROM product_variants v
JOIN products p ON p.id = v.product_id
WHERE p.handle LIKE 'jean-%';

INSERT INTO product_options (product_id, name, option_values)
SELECT p.id, 'Talla', '["30","32","34"]'
FROM products p
WHERE p.handle LIKE 'jean-%';

SELECT COUNT(*) AS jeans FROM products WHERE handle LIKE 'jean-%';
SELECT id, title, handle FROM products WHERE handle LIKE 'jean-%' ORDER BY id;
