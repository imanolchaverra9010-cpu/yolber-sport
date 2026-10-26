<?php

require_once '../config/database.php';
require_once '../includes/functions.php';

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;
$handle = $_GET['handle'] ?? null;
$first = max(1, min(100, (int)($_GET['first'] ?? 24)));

switch ($method) {
    case 'GET':
        if ($handle) {
            getProductByHandle($db, $handle);
        } elseif ($id) {
            getProductById($db, $id);
        } else {
            getProducts($db, $first);
        }
        break;
    case 'POST':
        createProduct($db);
        break;
    case 'PUT':
        updateProduct($db);
        break;
    case 'DELETE':
        deleteProduct($db);
        break;
    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}

function fetchProductImages($db, $productId) {
    return $db->fetchAll(
        "SELECT url, alt_text AS altText FROM product_images WHERE product_id = ? ORDER BY position ASC, id ASC",
        [$productId]
    );
}

function fetchProductVariants($db, $productId) {
    $variants = $db->fetchAll(
        "SELECT id, title, price, available_for_sale, sku
         FROM product_variants
         WHERE product_id = ?
         ORDER BY id ASC",
        [$productId]
    );

    foreach ($variants as &$v) {
        $opts = $db->fetchAll(
            "SELECT option_name AS name, option_value AS value
             FROM variant_options
             WHERE variant_id = ?
             ORDER BY id ASC",
            [$v['id']]
        );
        $v['selectedOptions'] = $opts ?: [];
    }
    unset($v);

    return $variants;
}

function fetchProductOptions($db, $productId) {
    $options = $db->fetchAll(
        "SELECT name, option_values FROM product_options WHERE product_id = ? ORDER BY id ASC",
        [$productId]
    );

    return array_map(static function ($opt) {
        $values = json_decode($opt['option_values'] ?? '[]', true);
        if (!is_array($values)) {
            $values = [];
        }
        return [
            'name' => $opt['name'],
            'values' => $values,
        ];
    }, $options);
}

function buildProductNode($db, $p) {
    $images = fetchProductImages($db, $p['id']);
    $variants = fetchProductVariants($db, $p['id']);
    $options = fetchProductOptions($db, $p['id']);

    $prices = array_map(static fn($v) => (float)$v['price'], $variants);
    $minPrice = $prices ? min($prices) : (float)($p['min_price'] ?? 0);
    $maxPrice = $prices ? max($prices) : (float)($p['max_price'] ?? $minPrice);

    return [
        'id' => 'gid://shopify/Product/' . $p['id'],
        'title' => $p['title'],
        'description' => $p['description'] ?? '',
        'handle' => $p['handle'],
        'categoryId' => isset($p['category_id']) && $p['category_id'] !== null ? (int)$p['category_id'] : null,
        'categorySlug' => $p['category_slug'] ?? null,
        'priceRange' => [
            'minVariantPrice' => [
                'amount' => (string)$minPrice,
                'currencyCode' => 'COP',
            ],
            'maxVariantPrice' => [
                'amount' => (string)$maxPrice,
                'currencyCode' => 'COP',
            ],
        ],
        'images' => [
            'edges' => array_map(static function ($img) {
                return [
                    'node' => [
                        'url' => $img['url'],
                        'altText' => $img['altText'] ?? null,
                    ],
                ];
            }, $images),
        ],
        'variants' => [
            'edges' => array_map(static function ($v) {
                return [
                    'node' => [
                        'id' => 'gid://shopify/ProductVariant/' . $v['id'],
                        'title' => $v['title'],
                        'price' => [
                            'amount' => (string)$v['price'],
                            'currencyCode' => 'COP',
                        ],
                        'availableForSale' => (bool)$v['available_for_sale'],
                        'selectedOptions' => $v['selectedOptions'] ?? [],
                    ],
                ];
            }, $variants),
        ],
        'options' => $options,
    ];
}

function getProducts($db, $first) {
    $products = $db->fetchAll(
        "SELECT
            p.id,
            p.title,
            p.description,
            p.handle,
            p.status,
            p.category_id,
            c.slug AS category_slug,
            p.created_at,
            (SELECT MIN(price) FROM product_variants WHERE product_id = p.id) AS min_price,
            (SELECT MAX(price) FROM product_variants WHERE product_id = p.id) AS max_price
         FROM products p
         LEFT JOIN categories c ON c.id = p.category_id
         WHERE p.status = 'active'
         ORDER BY p.created_at DESC
         LIMIT {$first}"
    );

    $edges = array_map(static function ($p) use ($db) {
        return ['node' => buildProductNode($db, $p)];
    }, $products);

    jsonResponse(['data' => ['products' => ['edges' => $edges]]]);
}

function getProductByHandle($db, $handle) {
    $product = $db->fetchOne(
        "SELECT p.*, c.slug AS category_slug
         FROM products p
         LEFT JOIN categories c ON c.id = p.category_id
         WHERE p.handle = ? AND p.status = 'active'",
        [$handle]
    );

    if (!$product) {
        jsonResponse(['error' => 'Product not found'], 404);
    }

    jsonResponse(['data' => ['product' => buildProductNode($db, $product)]]);
}

function getProductById($db, $id) {
    $product = $db->fetchOne(
        "SELECT p.*, c.slug AS category_slug
         FROM products p
         LEFT JOIN categories c ON c.id = p.category_id
         WHERE p.id = ? AND p.status = 'active'",
        [$id]
    );

    if (!$product) {
        jsonResponse(['error' => 'Product not found'], 404);
    }

    jsonResponse(['data' => ['product' => buildProductNode($db, $product)]]);
}

function createProduct($db) {
    if (!isLoggedIn()) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }

    $input = getJsonInput();
    $title = $input['title'] ?? '';
    $description = $input['description'] ?? '';
    $handle = $input['handle'] ?? strtolower(preg_replace('/[^a-z0-9]+/', '-', $title));

    if ($title === '') {
        jsonResponse(['error' => 'Title is required'], 400);
    }

    $db->query(
        "INSERT INTO products (title, description, handle) VALUES (?, ?, ?)",
        [$title, $description, $handle]
    );

    $productId = $db->lastInsertId();

    jsonResponse([
        'success' => true,
        'product' => ['id' => $productId, 'handle' => $handle],
    ], 201);
}

function updateProduct($db) {
    if (!isLoggedIn()) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }

    $input = getJsonInput();
    $id = $input['id'] ?? null;

    if (!$id) {
        jsonResponse(['error' => 'Product ID is required'], 400);
    }

    $fields = [];
    $params = [];

    if (isset($input['title'])) {
        $fields[] = 'title = ?';
        $params[] = $input['title'];
    }
    if (isset($input['description'])) {
        $fields[] = 'description = ?';
        $params[] = $input['description'];
    }
    if (isset($input['status'])) {
        $fields[] = 'status = ?';
        $params[] = $input['status'];
    }

    if (!$fields) {
        jsonResponse(['error' => 'No fields to update'], 400);
    }

    $params[] = $id;
    $db->query(
        "UPDATE products SET " . implode(', ', $fields) . " WHERE id = ?",
        $params
    );

    jsonResponse(['success' => true]);
}

function deleteProduct($db) {
    if (!isLoggedIn()) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }

    $id = $_GET['id'] ?? null;
    if (!$id) {
        jsonResponse(['error' => 'Product ID is required'], 400);
    }

    $db->query("DELETE FROM products WHERE id = ?", [$id]);
    jsonResponse(['success' => true]);
}
