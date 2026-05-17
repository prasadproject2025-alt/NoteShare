<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

header('Content-Type: application/json');

$userIds = $_GET['user_ids'] ?? '';
if (empty($userIds)) {
    echo json_encode(['success' => true, 'users' => []]);
    exit();
}

// Parse user IDs
$userIdArray = explode(',', $userIds);

// Load users
$usersFile = 'data/users.json';
if (!file_exists($usersFile)) {
    echo json_encode(['success' => false, 'message' => 'User data not found']);
    exit();
}

$users = json_decode(file_get_contents($usersFile), true) ?: [];
$userData = [];

// Find users by their MD5 hashed ID
foreach ($users as $email => $user) {
    $userId = md5($email);
    if (in_array($userId, $userIdArray)) {
        $userData[$userId] = [
            'name' => $user['name'],
            'email' => $email,
            'coins' => $user['coins'] ?? 0
        ];
    }
}

echo json_encode([
    'success' => true,
    'users' => $userData
]);
?>