<?php

require_once '../config/database.php';
require_once '../includes/functions.php';

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    createVariant($db);
} else {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

function createVariant($db) {
    if (!isLoggedIn()) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }

    $input = getJsonInput();
    $productId = $input['product_id'] ?? null;
    $title = $input['title'] ?? '';
    $price = $input['price'] ?? 0;
    $inventory = $input['inventory'] ?? 0;
    $sku = $input['sku'] ?? null;

    if (!$productId || empty($title)) {
        jsonResponse(['error' => 'Product ID and title are required'], 400);
    }

    $db->query(
        "INSERT INTO product_variants (product_id, title, price, inventory_quantity, sku, available_for_sale) VALUES (?, ?, ?, ?, ?, ?)",
        [$productId, $title, $price, $inventory, $sku, $inventory > 0]
    );

    jsonResponse(['success' => true], 201);
}
