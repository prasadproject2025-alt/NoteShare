<?php
// Test script for user search functionality
session_start();

// Simulate a logged-in user
$_SESSION['user_id'] = 'test_user_id';
$_SESSION['user_email'] = 'test@example.com';

// Test the search_users.php API
echo "Testing user search API...\n";

// Test with a search query
$query = 'test'; // This should match test users
$url = "http://localhost:8000/search_users.php?q=" . urlencode($query);

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Cookie: PHPSESSID=' . session_id() . "\r\n"
    ]
]);

$response = file_get_contents($url, false, $context);
$result = json_decode($response, true);

echo "Search results for '$query':\n";
if ($result['success']) {
    echo "Found " . count($result['users']) . " users:\n";
    foreach ($result['users'] as $user) {
        echo "- {$user['name']} ({$user['email']}) - {$user['coins']} coins\n";
    }
} else {
    echo "Error: " . $result['message'] . "\n";
}

echo "\nTesting get_user_data.php API...\n";

// Test get_user_data.php
$userIds = 'test_user_id,another_user_id';
$url2 = "http://localhost:8000/get_user_data.php?user_ids=" . urlencode($userIds);

$response2 = file_get_contents($url2, false, $context);
$result2 = json_decode($response2, true);

echo "User data results:\n";
if ($result2['success']) {
    echo "Found " . count($result2['users']) . " users:\n";
    foreach ($result2['users'] as $id => $user) {
        echo "- $id: {$user['name']} ({$user['email']})\n";
    }
} else {
    echo "Error: " . $result2['message'] . "\n";
}
?>