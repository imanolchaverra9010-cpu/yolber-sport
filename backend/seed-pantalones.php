<?php
/**
 * One-shot: seed Pantalones (jeans) into Hostinger DB.
 * Open: /seed-pantalones.php?key=YOLBER_FIX_2026
 * DELETE after use.
 */
declare(strict_types=1);

const FIX_KEY = 'YOLBER_FIX_2026';

$key = (string)($_GET['key'] ?? $_POST['key'] ?? '');
if ($key !== FIX_KEY) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Forbidden";
    exit;
}

require_once __DIR__ . '/config/database.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $db = Database::getInstance();

    $db->query(
        "INSERT INTO categories (name, slug, icon_url, sort_order, is_active)
         SELECT 'Pantalones', 'pantalones', 'pants', 6, 1
         FROM DUAL
         WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug = 'pantalones')"
    );

    $cat = $db->fetchOne("SELECT id FROM categories WHERE slug = 'pantalones' LIMIT 1");
    if (!$cat) {
        throw new RuntimeException('No se pudo crear/encontrar categoría pantalones');
    }
    $catId = (int)$cat['id'];

    // Remove previous jean seeds (and dependents via FK or manual)
    $old = $db->fetchAll("SELECT id FROM products WHERE handle LIKE 'jean-%'");
    foreach ($old as $row) {
        $pid = (int)$row['id'];
        $vars = $db->fetchAll("SELECT id FROM product_variants WHERE product_id = ?", [$pid]);
        foreach ($vars as $v) {
            $db->query("DELETE FROM variant_options WHERE variant_id = ?", [(int)$v['id']]);
        }
        $db->query("DELETE FROM product_variants WHERE product_id = ?", [$pid]);
        $db->query("DELETE FROM product_images WHERE product_id = ?", [$pid]);
        $db->query("DELETE FROM product_options WHERE product_id = ?", [$pid]);
        $db->query("DELETE FROM products WHERE id = ?", [$pid]);
    }

    $jeans = [
        ['Jean Amiri Destroyed Azul Claro', 'Jean slim destroyed azul claro Amiri. Rotos frontales y acabado raw. Empaque original.', 'jean-amiri-destroyed-azul-claro', 'am758.jpg'],
        ['Jean Amiri MX1 Azul', 'Jean Amiri estilo MX1 azul claro con inserts plisados negros bajo los rotos. Slim fit.', 'jean-amiri-mx1-azul', 'am753.jpg'],
        ['Jean Amiri Stars Negro', 'Jean Amiri negro desgastado con estrellas bordadas y inserts plisados.', 'jean-amiri-stars-negro', 'am751.jpg'],
        ['Jean Purple Brand Acid Wash', 'Jean Purple Brand acid wash azul muy claro con rotos. Slim fit.', 'jean-purple-brand-acid-wash', 'pe8008.jpg'],
        ['Jean Amiri Patch Rayas', 'Jean Amiri azul oscuro con rotos reforzados en tela a rayas. Slim fit.', 'jean-amiri-patch-rayas', 'am707.jpg'],
        ['Jean Purple Brand Negro Paint', 'Jean Purple Brand negro desgastado con paint splatter y rotos. Slim fit.', 'jean-purple-brand-negro-paint', 'pb638.jpg'],
        ['Jean Amiri Inlay Crema', 'Jean Amiri azul claro con rotos e inserts plisados crema.', 'jean-amiri-inlay-crema', 'am739.jpg'],
        ['Jean Purple Brand Rotos Azul', 'Jean Purple Brand azul medio con hole en rodilla y parches. Slim fit.', 'jean-purple-brand-rotos-azul', 'pb595.jpg'],
        ['Jean Purple Brand Negro Studs', 'Jean Purple Brand negro con rotos y studs metalicos. Slim fit.', 'jean-purple-brand-negro-studs', 'pe8019.jpg'],
        ['Jean Purple Brand Negro Patch', 'Jean Purple Brand negro desgastado con parche grafico. Slim fit.', 'jean-purple-brand-negro-patch', 'pe8022.jpg'],
        ['Jean Purple Brand Azul Claro', 'Jean Purple Brand wash claro con whiskering y rotos ligeros.', 'jean-purple-brand-azul-claro', 'pe8013.jpg'],
        ['Jean Purple Brand Azul Parche', 'Jean Purple Brand azul medio con parche triangular y rotos. Slim fit.', 'jean-purple-brand-azul-parche', 'pb653.jpg'],
        ['Jean Purple Brand Paint Claro', 'Jean Purple Brand azul claro con paint splatter negro y rotos.', 'jean-purple-brand-paint-claro', 'pb629.jpg'],
        ['Jean Purple Brand Paint Azul', 'Jean Purple Brand azul con paint splatter blanco y azul. Slim fit.', 'jean-purple-brand-paint-azul', 'pb652.jpg'],
    ];

    $sizes = ['30', '32', '34'];
    $created = 0;

    foreach ($jeans as [$title, $description, $handle, $file]) {
        $db->query(
            "INSERT INTO products (title, description, handle, category_id, status) VALUES (?, ?, ?, ?, 'active')",
            [$title, $description, $handle, $catId]
        );
        $productId = (int)$db->lastInsertId();

        $imgUrl = 'uploads/products/' . $file;
        $db->query(
            "INSERT INTO product_images (product_id, url, alt_text, position) VALUES (?, ?, ?, 0)",
            [$productId, $imgUrl, $title]
        );

        $db->query(
            "INSERT INTO product_options (product_id, name, option_values) VALUES (?, 'Talla', ?)",
            [$productId, json_encode($sizes)]
        );

        $skuBase = strtoupper(pathinfo($file, PATHINFO_FILENAME));
        foreach ($sizes as $size) {
            $db->query(
                "INSERT INTO product_variants (product_id, title, price, available_for_sale, inventory_quantity, sku) VALUES (?, ?, 289000, 1, 5, ?)",
                [$productId, $size, $skuBase . '-' . $size]
            );
            $variantId = (int)$db->lastInsertId();
            $db->query(
                "INSERT INTO variant_options (variant_id, option_name, option_value) VALUES (?, 'Talla', ?)",
                [$variantId, $size]
            );
        }
        $created++;
    }

    $count = $db->fetchOne("SELECT COUNT(*) AS c FROM products WHERE handle LIKE 'jean-%'");
    echo "OK. Pantalones creados: {$created}. Total jean-% en BD: " . (int)($count['c'] ?? 0) . "\n";
    echo "Borra seed-pantalones.php ahora.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Error: ' . $e->getMessage();
}
