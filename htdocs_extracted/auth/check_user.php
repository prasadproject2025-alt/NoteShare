<?php
session_start();
header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['email'])) {
        throw new Exception('Email is required');
    }

    $email = trim($input['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !str_ends_with($email, '@vitstudent.ac.in')) {
        throw new Exception('Invalid VIT email format');
    }

    // For now, we'll check if user exists in session or a simple file-based storage
    // In production, this would check your database
    $users_file = __DIR__ . '/../data/users.json';

    $users = [];
    if (file_exists($users_file)) {
        $users = json_decode(file_get_contents($users_file), true) ?: [];
    }

    $exists = isset($users[$email]);

    echo json_encode([
        'success' => true,
        'exists' => $exists
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>