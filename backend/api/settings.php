<?php

require_once '../config/database.php';
require_once '../includes/functions.php';

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$publicKeys = [
    'store_name',
    'store_slogan',
    'whatsapp_phone',
    'contact_email',
    'contact_phone',
    'contact_city',
    'shipping_note',
    'currency',
    'categories_banner_image',
    'categories_banner_title',
    'categories_banner_subtitle',
];

$requested = isset($_GET['keys']) ? array_filter(array_map('trim', explode(',', (string)$_GET['keys']))) : $publicKeys;
$keys = array_values(array_intersect($requested, $publicKeys));

$settings = [];
foreach ($keys as $key) {
    try {
        $row = $db->fetchOne("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
        $settings[$key] = $row['setting_value'] ?? '';
    } catch (Throwable $e) {
        $settings[$key] = '';
    }
}

jsonResponse(['data' => ['settings' => $settings]]);
