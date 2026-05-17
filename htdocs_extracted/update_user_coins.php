<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

header('Content-Type: application/json');

$userId = $_SESSION['user_id'];
$usersFile = 'data/users.json';

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$coins = (int)($input['coins'] ?? 0);
$description = $input['description'] ?? '';

// Validate input
if (!$action || $coins <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

// Read current user data
if (!file_exists($usersFile)) {
    echo json_encode(['success' => false, 'message' => 'User data not found']);
    exit();
}

$users = json_decode(file_get_contents($usersFile), true);
$userKey = null;

// Find user by email (since user_id is md5 hash of email)
$userEmail = $_SESSION['user_email'] ?? '';
if (!$userEmail) {
    echo json_encode(['success' => false, 'message' => 'User email not found in session']);
    exit();
}

$userKey = $userEmail; // Email is the key in users.json

if (!isset($users[$userKey])) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit();
}

$currentCoins = (int)($users[$userKey]['coins'] ?? 0);

if ($action === 'deduct') {
    if ($currentCoins < $coins) {
        echo json_encode(['success' => false, 'message' => 'Insufficient coins']);
        exit();
    }
    $users[$userKey]['coins'] = $currentCoins - $coins;
} elseif ($action === 'add') {
    $users[$userKey]['coins'] = $currentCoins + $coins;
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit();
}

// Save updated data
if (file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT))) {
    // Log transaction if description provided
    if ($description) {
        $logFile = 'logs/coin_transactions.log';
        $logEntry = date('Y-m-d H:i:s') . " | User: $userId | Action: $action | Coins: $coins | Description: $description | New Balance: " . $users[$userKey]['coins'] . "\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }

    echo json_encode([
        'success' => true,
        'coins' => $users[$userKey]['coins'],
        'message' => 'Coins updated successfully'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save data']);
}
?>