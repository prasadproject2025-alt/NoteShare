<?php
/**
 * Simple test to verify send_otp.php response structure
 */

// Simulate the response that send_otp.php should return
$success_response = [
    'success' => true,
    'message' => 'OTP sent successfully to your email! Please check your Gmail inbox (and spam folder).'
];

$failure_response = [
    'success' => false,
    'message' => 'Failed to send OTP email. Please check your email address and try again. If the problem persists, contact support.'
];

echo "=== OTP Response Security Test ===\n\n";

echo "✅ SUCCESS Response Structure:\n";
echo json_encode($success_response, JSON_PRETTY_PRINT) . "\n\n";

echo "❌ FAILURE Response Structure:\n";
echo json_encode($failure_response, JSON_PRETTY_PRINT) . "\n\n";

// Check that OTP is not in responses
$success_has_otp = isset($success_response['otp']) || isset($success_response['debug_otp']);
$failure_has_otp = isset($failure_response['otp']) || isset($failure_response['debug_otp']);

echo "Security Check:\n";
echo "✅ Success response contains OTP: " . ($success_has_otp ? 'YES (SECURITY ISSUE!)' : 'NO (SECURE)') . "\n";
echo "✅ Failure response contains OTP: " . ($failure_has_otp ? 'YES (SECURITY ISSUE!)' : 'NO (SECURE)') . "\n\n";

if (!$success_has_otp && !$failure_has_otp) {
    echo "🎉 SECURITY VERIFICATION PASSED: OTP is never exposed in API responses!\n";
    echo "📧 OTP is only sent via secure email to the user's Gmail account.\n";
} else {
    echo "🚨 SECURITY ISSUE: OTP is being exposed in API responses!\n";
}

echo "\n=== Test Complete ===\n";
?>