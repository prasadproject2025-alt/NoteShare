<?php
session_start();
header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['email']) || !isset($input['otp'])) {
        throw new Exception('Email and OTP are required');
    }

    $email = trim($input['email']);
    $otp = trim($input['otp']);

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !str_ends_with($email, '@vitstudent.ac.in')) {
        throw new Exception('Invalid VIT email format');
    }

    // Check if OTP exists and is valid
    if (!isset($_SESSION['otp']) || !isset($_SESSION['otp_email']) || !isset($_SESSION['otp_time'])) {
        throw new Exception('No OTP found. Please request a new one.');
    }

    // Check if OTP matches
    if ($_SESSION['otp'] !== $otp) {
        throw new Exception('Invalid OTP');
    }

    // Check if OTP is expired (5 minutes)
    if (time() - $_SESSION['otp_time'] > 300) {
        // Clear expired OTP
        unset($_SESSION['otp'], $_SESSION['otp_email'], $_SESSION['otp_time']);
        throw new Exception('OTP has expired. Please request a new one.');
    }

    // Check if email matches
    if ($_SESSION['otp_email'] !== $email) {
        throw new Exception('Email mismatch');
    }

    // OTP is valid - mark as verified
    $_SESSION['otp_verified'] = true;
    $_SESSION['verified_email'] = $email;

    echo json_encode([
        'success' => true,
        'message' => 'OTP verified successfully'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>