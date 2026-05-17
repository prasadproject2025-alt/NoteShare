<?php
session_start();

if (!isset($_SESSION['user_email'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$email = $_SESSION['user_email'];

// Load user data from JSON file
$users_file = __DIR__ . '/data/users.json';
if (!file_exists($users_file)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'User data not found']);
    exit();
}

$users = json_decode(file_get_contents($users_file), true) ?: [];

if (!isset($users[$email])) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit();
}

$user = $users[$email];

echo json_encode([
    'success' => true,
    'coins' => $user['coins'] ?? 0,
    'name' => $user['name'] ?? 'User',
    'email' => $user['email']
]);
?>