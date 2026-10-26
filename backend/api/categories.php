<?php

require_once '../config/database.php';
require_once '../includes/functions.php';

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$slug = $_GET['slug'] ?? null;

switch ($method) {
    case 'GET':
        if ($id) {
            getCategoryById($db, $id);
        } elseif ($slug) {
            getCategoryBySlug($db, $slug);
        } else {
            getCategories($db);
        }
        break;
    case 'POST':
        createCategory($db);
        break;
    case 'PUT':
        updateCategory($db);
        break;
    case 'DELETE':
        deleteCategory($db);
        break;
    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}

function mapCategory($row) {
    $iconUrl = $row['icon_url'] ?? '';
    $isUrl = preg_match('#^(https?:)?//#i', $iconUrl) || str_contains($iconUrl, '/') || str_contains($iconUrl, '.');
    $icon = $isUrl ? ($row['slug'] ?? 'tag') : ($iconUrl ?: ($row['slug'] ?? 'tag'));

    return [
        'id' => (int)$row['id'],
        'name' => $row['name'],
        'slug' => $row['slug'],
        'icon' => $icon,
        'iconUrl' => $isUrl ? $iconUrl : '',
        'sortOrder' => (int)$row['sort_order'],
        'isActive' => (bool)$row['is_active'],
    ];
}

function getCategories($db) {
    $activeOnly = ($_GET['all'] ?? '') !== '1';
    $sql = "SELECT * FROM categories";
    if ($activeOnly) {
        $sql .= " WHERE is_active = 1";
    }
    $sql .= " ORDER BY sort_order ASC, name ASC";
    $rows = $db->fetchAll($sql);
    jsonResponse(['data' => ['categories' => array_map('mapCategory', $rows)]]);
}

function getCategoryById($db, $id) {
    $row = $db->fetchOne("SELECT * FROM categories WHERE id = ?", [$id]);
    if (!$row) {
        jsonResponse(['error' => 'Category not found'], 404);
    }
    jsonResponse(['data' => ['category' => mapCategory($row)]]);
}

function getCategoryBySlug($db, $slug) {
    $row = $db->fetchOne("SELECT * FROM categories WHERE slug = ? AND is_active = 1", [$slug]);
    if (!$row) {
        jsonResponse(['error' => 'Category not found'], 404);
    }
    jsonResponse(['data' => ['category' => mapCategory($row)]]);
}

function slugify($text) {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-') ?: 'categoria';
}

function createCategory($db) {
    if (!isLoggedIn()) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }

    $input = getJsonInput();
    $name = trim($input['name'] ?? '');
    $iconUrl = trim($input['icon_url'] ?? $input['iconUrl'] ?? '');
    $slug = trim($input['slug'] ?? '');
    $sortOrder = (int)($input['sort_order'] ?? $input['sortOrder'] ?? 0);
    $isActive = isset($input['is_active']) ? (int)(bool)$input['is_active'] : (isset($input['isActive']) ? (int)(bool)$input['isActive'] : 1);

    if ($name === '' || $iconUrl === '') {
        jsonResponse(['error' => 'Name and icon URL are required'], 400);
    }

    if ($slug === '') {
        $slug = slugify($name);
    } else {
        $slug = slugify($slug);
    }

    $existing = $db->fetchOne("SELECT id FROM categories WHERE slug = ?", [$slug]);
    if ($existing) {
        jsonResponse(['error' => 'Slug already exists'], 409);
    }

    $db->query(
        "INSERT INTO categories (name, slug, icon_url, sort_order, is_active) VALUES (?, ?, ?, ?, ?)",
        [$name, $slug, $iconUrl, $sortOrder, $isActive]
    );

    $id = (int)$db->lastInsertId();
    $row = $db->fetchOne("SELECT * FROM categories WHERE id = ?", [$id]);
    jsonResponse(['data' => ['category' => mapCategory($row)]], 201);
}

function updateCategory($db) {
    if (!isLoggedIn()) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }

    $input = getJsonInput();
    $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
    if (!$id) {
        jsonResponse(['error' => 'Category ID is required'], 400);
    }

    $row = $db->fetchOne("SELECT * FROM categories WHERE id = ?", [$id]);
    if (!$row) {
        jsonResponse(['error' => 'Category not found'], 404);
    }

    $name = trim($input['name'] ?? $row['name']);
    $iconUrl = trim($input['icon_url'] ?? $input['iconUrl'] ?? $row['icon_url']);
    $slug = trim($input['slug'] ?? $row['slug']);
    $sortOrder = (int)($input['sort_order'] ?? $input['sortOrder'] ?? $row['sort_order']);
    $isActive = isset($input['is_active'])
        ? (int)(bool)$input['is_active']
        : (isset($input['isActive']) ? (int)(bool)$input['isActive'] : (int)$row['is_active']);

    if ($name === '' || $iconUrl === '') {
        jsonResponse(['error' => 'Name and icon URL are required'], 400);
    }

    $slug = slugify($slug);
    $existing = $db->fetchOne("SELECT id FROM categories WHERE slug = ? AND id != ?", [$slug, $id]);
    if ($existing) {
        jsonResponse(['error' => 'Slug already exists'], 409);
    }

    $db->query(
        "UPDATE categories SET name = ?, slug = ?, icon_url = ?, sort_order = ?, is_active = ? WHERE id = ?",
        [$name, $slug, $iconUrl, $sortOrder, $isActive, $id]
    );

    $updated = $db->fetchOne("SELECT * FROM categories WHERE id = ?", [$id]);
    jsonResponse(['data' => ['category' => mapCategory($updated)]]);
}

function deleteCategory($db) {
    if (!isLoggedIn()) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }

    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        $input = getJsonInput();
        $id = (int)($input['id'] ?? 0);
    }
    if (!$id) {
        jsonResponse(['error' => 'Category ID is required'], 400);
    }

    $db->query("DELETE FROM categories WHERE id = ?", [$id]);
    jsonResponse(['success' => true]);
}
