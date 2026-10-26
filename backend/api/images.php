<?php

require_once '../config/database.php';
require_once '../includes/functions.php';

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    createImage($db);
} else {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

function createImage($db) {
    if (!isLoggedIn()) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }

    $input = getJsonInput();
    $productId = $input['product_id'] ?? null;
    $url = $input['url'] ?? '';
    $altText = $input['alt_text'] ?? '';

    if (!$productId || empty($url)) {
        jsonResponse(['error' => 'Product ID and URL are required'], 400);
    }

    // Get max position
    $maxPos = $db->fetchOne(
        "SELECT MAX(position) as max_pos FROM product_images WHERE product_id = ?",
        [$productId]
    );
    $position = ($maxPos['max_pos'] ?? 0) + 1;

    $db->query(
        "INSERT INTO product_images (product_id, url, alt_text, position) VALUES (?, ?, ?, ?)",
        [$productId, $url, $altText, $position]
    );

    jsonResponse(['success' => true], 201);
}
