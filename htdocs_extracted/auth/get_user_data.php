<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? '';

if (!$email || $email !== $_SESSION['user_email']) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

// Load user data from JSON file
$users_file = __DIR__ . '/../data/users.json';
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
    'user_id' => $_SESSION['user_id'],
    'user' => [
        'email' => $user['email'],
        'name' => $user['name'],
        'coins' => $user['coins'],
        'created_at' => $user['created_at'],
        'status' => $user['status'] ?? 'active'
    ]
]);
?>