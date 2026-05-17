<?php
// Direct test of coin deduction logic
$userId = md5('durgaprasad.s2022a@vitstudent.ac.in');
$usersFile = 'data/users.json';

echo "Testing coin deduction logic directly...\n";

// Read users file
$users = json_decode(file_get_contents($usersFile), true);
echo "Users loaded: " . count($users) . " users\n";

// Find user
$userKey = null;
foreach ($users as $key => $user) {
    if (isset($user['email']) && $user['email'] === 'durgaprasad.s2022a@vitstudent.ac.in') {
        $userKey = $key;
        break;
    }
}

if ($userKey === null) {
    echo "User not found\n";
    exit;
}

echo "User found: $userKey\n";
$currentCoins = (int)($users[$userKey]['coins'] ?? 0);
echo "Current coins: $currentCoins\n";

if ($currentCoins < 1) {
    echo "Insufficient coins\n";
    exit;
}

// Deduct 1 coin
$users[$userKey]['coins'] = $currentCoins - 1;
echo "New coins: " . $users[$userKey]['coins'] . "\n";

// Save file
$result = file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
if ($result) {
    echo "SUCCESS: Coins updated\n";
} else {
    echo "ERROR: Failed to save\n";
}
?>