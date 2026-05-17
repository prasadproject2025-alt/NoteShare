<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

header('Content-Type: application/json');

$query = $_GET['q'] ?? '';
$limit = (int)($_GET['limit'] ?? 10);

if (strlen($query) < 2) {
    echo json_encode(['success' => true, 'users' => []]);
    exit();
}

// Load users
$usersFile = 'data/users.json';
if (!file_exists($usersFile)) {
    echo json_encode(['success' => false, 'message' => 'User data not found']);
    exit();
}

$users = json_decode(file_get_contents($usersFile), true) ?: [];
$currentUserId = $_SESSION['user_id'];
$currentUserEmail = $_SESSION['user_email'];

$matchingUsers = [];

foreach ($users as $email => $user) {
    // Skip current user
    if ($email === $currentUserEmail) {
        continue;
    }

    // Search by name or email
    $nameMatch = stripos($user['name'], $query) !== false;
    $emailMatch = stripos($email, $query) !== false;

    if ($nameMatch || $emailMatch) {
        $matchingUsers[] = [
            'id' => md5($email), // Use same ID format as session
            'name' => $user['name'],
            'email' => $email,
            'coins' => $user['coins'] ?? 0
        ];

        if (count($matchingUsers) >= $limit) {
            break;
        }
    }
}

echo json_encode([
    'success' => true,
    'users' => $matchingUsers
]);
?>