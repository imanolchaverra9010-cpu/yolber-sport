<?php

require_once '../config/database.php';
require_once '../includes/functions.php';

$db = Database::getInstance();
$input = getJsonInput();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'register':
        registerUser($db, $input);
        break;
    case 'login':
        loginUser($db, $input);
        break;
    case 'logout':
        logoutUser();
        break;
    case 'me':
        getCurrentUser($db);
        break;
    default:
        jsonResponse(['error' => 'Invalid action'], 400);
}

function registerUser($db, $input) {
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';
    $name = $input['name'] ?? '';

    if (empty($email) || empty($password)) {
        jsonResponse(['error' => 'Email and password are required'], 400);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['error' => 'Invalid email format'], 400);
    }

    if (strlen($password) < 6) {
        jsonResponse(['error' => 'Password must be at least 6 characters'], 400);
    }

    // Check if email already exists
    $existing = $db->fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
    if ($existing) {
        jsonResponse(['error' => 'Email already registered'], 409);
    }

    // Hash password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Insert user
    $db->query(
        "INSERT INTO users (email, password_hash, name) VALUES (?, ?, ?)",
        [$email, $passwordHash, $name]
    );

    $userId = $db->lastInsertId();

    // Auto login
    $_SESSION['user_id'] = $userId;

    jsonResponse([
        'success' => true,
        'user' => [
            'id' => $userId,
            'email' => $email,
            'name' => $name
        ]
    ], 201);
}

function loginUser($db, $input) {
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';

    if (empty($email) || empty($password)) {
        jsonResponse(['error' => 'Email and password are required'], 400);
    }

    $user = $db->fetchOne(
        "SELECT id, email, password_hash, name FROM users WHERE email = ?",
        [$email]
    );

    if (!$user || !password_verify($password, $user['password_hash'])) {
        jsonResponse(['error' => 'Invalid credentials'], 401);
    }

    $_SESSION['user_id'] = $user['id'];

    jsonResponse([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name']
        ]
    ]);
}

function logoutUser() {
    session_destroy();
    jsonResponse(['success' => true]);
}

function getCurrentUser($db) {
    $userId = getCurrentUserId();

    if (!$userId) {
        jsonResponse(['error' => 'Not authenticated'], 401);
    }

    $user = $db->fetchOne(
        "SELECT id, email, name, created_at FROM users WHERE id = ?",
        [$userId]
    );

    if (!$user) {
        jsonResponse(['error' => 'User not found'], 404);
    }

    jsonResponse(['user' => $user]);
}
