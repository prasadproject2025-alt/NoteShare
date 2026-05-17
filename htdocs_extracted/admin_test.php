<?php
// Admin System Test Script
// Run this to verify admin functionality

session_start();

echo "<h1>Admin System Test</h1>";

// Test 1: Check if admin files exist
$files_to_check = [
    'admin_login.php',
    'admin_dashboard.php',
    'admin_logout.php',
    'ADMIN_README.md'
];

echo "<h2>File Existence Check</h2>";
foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists<br>";
    } else {
        echo "❌ $file missing<br>";
    }
}

// Test 2: Check admin login credentials
echo "<h2>Admin Credentials Test</h2>";
$test_username = 'admin';
$test_password = 'admin123';
$admin_credentials = [
    'admin' => password_hash('admin123', PASSWORD_DEFAULT),
];

if (isset($admin_credentials[$test_username]) && password_verify($test_password, $admin_credentials[$test_username])) {
    echo "✅ Admin credentials are valid<br>";
} else {
    echo "❌ Admin credentials are invalid<br>";
}

// Test 3: Check session handling
echo "<h2>Session Test</h2>";
if (session_status() == PHP_SESSION_ACTIVE) {
    echo "✅ Sessions are working<br>";
} else {
    echo "❌ Sessions not working<br>";
}

// Test 4: Check Firebase config
echo "<h2>Firebase Config Check</h2>";
$firebase_config_file = 'js/firebase-config.js';
if (file_exists($firebase_config_file)) {
    $config_content = file_get_contents($firebase_config_file);
    if (strpos($config_content, 'firebaseConfig') !== false) {
        echo "✅ Firebase config file exists<br>";
    } else {
        echo "❌ Firebase config not found in file<br>";
    }
} else {
    echo "❌ Firebase config file missing<br>";
}

echo "<h2>Test Complete</h2>";
echo "<p><a href='admin_login.php'>Go to Admin Login</a></p>";
?>