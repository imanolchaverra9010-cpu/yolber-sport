<?php

require_once '../config/database.php';
require_once '../includes/functions.php';

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'GET':
        getCart($db);
        break;
    case 'POST':
        handleCartPost($db, $action);
        break;
    case 'PUT':
        updateCartItem($db);
        break;
    case 'DELETE':
        removeCartItem($db);
        break;
    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}

function getCart($db) {
    $sessionId = getOrCreateSessionId();
    $userId = getCurrentUserId();

    // Try to find cart by user ID first, then by session
    $cart = $db->fetchOne("
        SELECT id, user_id, session_id
        FROM carts
        WHERE (user_id = ? OR session_id = ?)
        ORDER BY user_id DESC, updated_at DESC
        LIMIT 1
    ", [$userId, $sessionId]);

    if (!$cart) {
        jsonResponse([
            'data' => [
                'cart' => [
                    'id' => null,
                    'totalQuantity' => 0,
                    'lines' => ['edges' => []]
                ]
            ]
        ]);
    }

    $cartId = $cart['id'];

    $items = $db->fetchAll("
        SELECT
            ci.id,
            ci.quantity,
            pv.id as variant_id,
            pv.title as variant_title,
            pv.price,
            pv.available_for_sale,
            p.id as product_id,
            p.title as product_title,
            p.handle,
            (SELECT url FROM product_images WHERE product_id = p.id ORDER BY position LIMIT 1) as image_url
        FROM cart_items ci
        JOIN product_variants pv ON ci.variant_id = pv.id
        JOIN products p ON pv.product_id = p.id
        WHERE ci.cart_id = ?
    ", [$cartId]);

    $totalQuantity = array_sum(array_column($items, 'quantity'));

    $lines = array_map(function($item) {
        return [
            'node' => [
                'id' => 'gid://shopify/CartLine/' . $item['id'],
                'quantity' => (int)$item['quantity'],
                'merchandise' => [
                    'id' => 'gid://shopify/ProductVariant/' . $item['variant_id'],
                    'product' => [
                        'id' => 'gid://shopify/Product/' . $item['product_id'],
                        'title' => $item['product_title'],
                        'handle' => $item['handle']
                    ],
                    'title' => $item['variant_title'],
                    'price' => [
                        'amount' => $item['price'],
                        'currencyCode' => 'COP'
                    ],
                    'availableForSale' => (bool)$item['available_for_sale']
                ]
            ]
        ];
    }, $items);

    jsonResponse([
        'data' => [
            'cart' => [
                'id' => 'gid://shopify/Cart/' . $cartId,
                'totalQuantity' => $totalQuantity,
                'lines' => ['edges' => $lines]
            ]
        ]
    ]);
}

function handleCartPost($db, $action) {
    $input = getJsonInput();

    switch ($action) {
        case 'create':
            createCart($db, $input);
            break;
        case 'add':
            addToCart($db, $input);
            break;
        default:
            jsonResponse(['error' => 'Invalid action'], 400);
    }
}

function createCart($db, $input) {
    $sessionId = getOrCreateSessionId();
    $userId = getCurrentUserId();

    // Check if cart already exists
    $existingCart = $db->fetchOne("
        SELECT id FROM carts
        WHERE (user_id = ? OR session_id = ?)
        LIMIT 1
    ", [$userId, $sessionId]);

    if ($existingCart) {
        addToCart($db, $input, $existingCart['id']);
        return;
    }

    // Create new cart
    $db->query(
        "INSERT INTO carts (user_id, session_id) VALUES (?, ?)",
        [$userId, $sessionId]
    );

    $cartId = $db->lastInsertId();

    addToCart($db, $input, $cartId);
}

function addToCart($db, $input, $cartId = null) {
    $sessionId = getOrCreateSessionId();
    $userId = getCurrentUserId();

    if (!$cartId) {
        $cart = $db->fetchOne("
            SELECT id FROM carts
            WHERE (user_id = ? OR session_id = ?)
            ORDER BY user_id DESC, updated_at DESC
            LIMIT 1
        ", [$userId, $sessionId]);

        if (!$cart) {
            $db->query(
                "INSERT INTO carts (user_id, session_id) VALUES (?, ?)",
                [$userId, $sessionId]
            );
            $cartId = $db->lastInsertId();
        } else {
            $cartId = $cart['id'];
        }
    }

    $lines = $input['lines'] ?? [];

    if (empty($lines)) {
        jsonResponse(['error' => 'Lines are required'], 400);
    }

    foreach ($lines as $line) {
        $merchandiseId = $line['merchandiseId'] ?? null;
        $quantity = (int)($line['quantity'] ?? 1);

        if (!$merchandiseId) {
            continue;
        }

        // Extract variant ID from Shopify-like ID
        $variantId = str_replace('gid://shopify/ProductVariant/', '', $merchandiseId);

        // Check if item already exists in cart
        $existingItem = $db->fetchOne("
            SELECT id, quantity FROM cart_items
            WHERE cart_id = ? AND variant_id = ?
        ", [$cartId, $variantId]);

        if ($existingItem) {
            // Update quantity
            $newQuantity = $existingItem['quantity'] + $quantity;
            $db->query(
                "UPDATE cart_items SET quantity = ? WHERE id = ?",
                [$newQuantity, $existingItem['id']]
            );
        } else {
            // Add new item
            $db->query(
                "INSERT INTO cart_items (cart_id, variant_id, quantity) VALUES (?, ?, ?)",
                [$cartId, $variantId, $quantity]
            );
        }
    }

    // Update cart timestamp
    $db->query("UPDATE carts SET updated_at = CURRENT_TIMESTAMP WHERE id = ?", [$cartId]);

    // Return updated cart
    getCart($db);
}

function updateCartItem($db) {
    $input = getJsonInput();
    $lines = $input['lines'] ?? [];

    if (empty($lines)) {
        jsonResponse(['error' => 'Lines are required'], 400);
    }

    $sessionId = getOrCreateSessionId();
    $userId = getCurrentUserId();

    $cart = $db->fetchOne("
        SELECT id FROM carts
        WHERE (user_id = ? OR session_id = ?)
        ORDER BY user_id DESC, updated_at DESC
        LIMIT 1
    ", [$userId, $sessionId]);

    if (!$cart) {
        jsonResponse(['error' => 'Cart not found'], 404);
    }

    foreach ($lines as $line) {
        $lineId = $line['id'] ?? null;
        $quantity = (int)($line['quantity'] ?? 1);

        if (!$lineId) {
            continue;
        }

        // Extract cart item ID from Shopify-like ID
        $itemId = str_replace('gid://shopify/CartLine/', '', $lineId);

        $db->query(
            "UPDATE cart_items SET quantity = ? WHERE id = ?",
            [$quantity, $itemId]
        );
    }

    $db->query("UPDATE carts SET updated_at = CURRENT_TIMESTAMP WHERE id = ?", [$cart['id']]);

    getCart($db);
}

function removeCartItem($db) {
    $lineIds = $_GET['lineIds'] ?? null;

    if (!$lineIds) {
        jsonResponse(['error' => 'Line IDs are required'], 400);
    }

    $sessionId = getOrCreateSessionId();
    $userId = getCurrentUserId();

    $cart = $db->fetchOne("
        SELECT id FROM carts
        WHERE (user_id = ? OR session_id = ?)
        ORDER BY user_id DESC, updated_at DESC
        LIMIT 1
    ", [$userId, $sessionId]);

    if (!$cart) {
        jsonResponse(['error' => 'Cart not found'], 404);
    }

    $lineIdsArray = explode(',', $lineIds);

    foreach ($lineIdsArray as $lineId) {
        $itemId = str_replace('gid://shopify/CartLine/', '', trim($lineId));
        $db->query("DELETE FROM cart_items WHERE id = ?", [$itemId]);
    }

    $db->query("UPDATE carts SET updated_at = CURRENT_TIMESTAMP WHERE id = ?", [$cart['id']]);

    getCart($db);
}
