<?php

// Enable CORS (reflect origin — never "*" when the browser sent Origin + credentials)
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = [
    'http://localhost:8080',
    'http://127.0.0.1:8080',
    'http://localhost:5173',
    'http://127.0.0.1:5173',
    'https://yolber-sport.vercel.app',
    'https://lightsteelblue-hedgehog-589381.hostingersite.com',
];
$originAllowed = $origin && (
    in_array($origin, $allowedOrigins, true)
    || (bool)preg_match('#^https://[a-z0-9-]+\.hostingersite\.com$#i', $origin)
    || (bool)preg_match('#^https://([a-z0-9-]+\.)*vercel\.app$#i', $origin)
    || (bool)preg_match('#^http://(localhost|127\.0\.0\.1)(:\d+)?$#', $origin)
);
if ($originAllowed) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Vary: Origin');
} elseif ($origin) {
    // Reflect unknown https origins for public catalog reads (safer than "*" + credentials)
    header("Access-Control-Allow-Origin: $origin");
    header('Vary: Origin');
} else {
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Max-Age: 86400');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// JSON response helper
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Get session ID or create new one
function getOrCreateSessionId() {
    if (!isset($_SESSION['session_id'])) {
        $_SESSION['session_id'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['session_id'];
}

// Get current user ID from session
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Format price
function formatPrice($amount, $currency = 'COP') {
    return number_format((float)$amount, 0, ',', '.') . ' ' . $currency;
}

// Get request body as JSON
function getJsonInput() {
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? [];
}
