<?php
session_start();
header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['email']) || !isset($input['password'])) {
        throw new Exception('Email and password are required');
    }

    $email = trim($input['email']);
    $password = $input['password'];

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !str_ends_with($email, '@vitstudent.ac.in')) {
        throw new Exception('Invalid VIT email format');
    }

    // Load users
    $users_file = __DIR__ . '/../data/users.json';
    if (!file_exists($users_file)) {
        throw new Exception('Account not found');
    }

    $users = json_decode(file_get_contents($users_file), true) ?: [];

    // Check if user exists
    if (!isset($users[$email])) {
        throw new Exception('Account not found');
    }

    $user = $users[$email];

    // Verify password
    if (!password_verify($password, $user['password'])) {
        throw new Exception('Invalid password');
    }

    // Set session for logged in user
    $_SESSION['user_id'] = md5($email);
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_coins'] = $user['coins'];

    echo json_encode([
        'success' => true,
        'message' => 'Login successful'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>