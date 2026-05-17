<?php
/**
 * Test script to verify OTP is not exposed in API response
 */

echo "=== Testing OTP Security ===\n\n";

// Test data
$test_email = 'test@vitstudent.ac.in';
$data = json_encode(['email' => $test_email]);

// Initialize cURL
$ch = curl_init('http://localhost:8000/note_share/auth/send_otp.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

// Execute request
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Decode response
$response_data = json_decode($response, true);

echo "Request: POST to send_otp.php\n";
echo "Email: $test_email\n";
echo "HTTP Status: $http_code\n\n";

echo "Response:\n";
echo json_encode($response_data, JSON_PRETTY_PRINT) . "\n\n";

// Check if OTP is exposed
if (isset($response_data['otp']) || isset($response_data['debug_otp'])) {
    echo "❌ SECURITY ISSUE: OTP is exposed in response!\n";
} else {
    echo "✅ SECURITY OK: OTP is not exposed in response\n";
}

if ($response_data['success'] ?? false) {
    echo "✅ API Response: Success message indicates email was sent\n";
} else {
    echo "ℹ️  API Response: Failed (expected for test email)\n";
}

echo "\n=== Test Complete ===\n";
?>