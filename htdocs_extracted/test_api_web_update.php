<?php
session_start();

// Simulate login for testing
$_SESSION['user_id'] = md5('durgaprasad.s2022a@vitstudent.ac.in');
$_SESSION['user_email'] = 'durgaprasad.s2022a@vitstudent.ac.in';

// Test the API
$url = 'http://localhost:8000/update_user_coins.php';
$data = json_encode([
    'action' => 'deduct',
    'coins' => 1,
    'description' => 'Web test deduction'
]);

$options = [
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => $data
    ]
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo "API Response: " . $result;
?>