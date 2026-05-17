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

    // Validate password
    if (strlen($password) < 8) {
        throw new Exception('Password must be at least 8 characters long');
    }

    // Check if OTP was verified
    if (!isset($_SESSION['otp_verified']) || !$_SESSION['otp_verified'] || $_SESSION['verified_email'] !== $email) {
        throw new Exception('Email not verified. Please complete OTP verification first.');
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Create user data directory if it doesn't exist
    $data_dir = __DIR__ . '/../data';
    if (!is_dir($data_dir)) {
        mkdir($data_dir, 0755, true);
    }

    // Load existing users
    $users_file = $data_dir . '/users.json';
    $users = [];
    if (file_exists($users_file)) {
        $users = json_decode(file_get_contents($users_file), true) ?: [];
    }

    // Check if user already exists
    if (isset($users[$email])) {
        throw new Exception('Account already exists');
    }

    // Create new user
    $users[$email] = [
        'email' => $email,
        'password' => $hashed_password,
        'name' => explode('@', $email)[0], // Extract name from email
        'coins' => 10,
        'created_at' => date('c'),
        'status' => 'active'
    ];

    // Save users
    file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT));

    // Set session for logged in user
    $_SESSION['user_id'] = md5($email);
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name'] = $users[$email]['name'];
    $_SESSION['user_coins'] = $users[$email]['coins'];

    // Clear OTP session data
    unset($_SESSION['otp'], $_SESSION['otp_email'], $_SESSION['otp_time'], $_SESSION['otp_verified'], $_SESSION['verified_email']);

    echo json_encode([
        'success' => true,
        'message' => 'Account created successfully'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>