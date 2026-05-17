<?php
// Test messaging without authentication
session_start();

// Simulate logged in user
$_SESSION['user_id'] = md5('test@example.com'); // Same format as used in the system
$_SESSION['user_name'] = 'Test User';
$_SESSION['user_email'] = 'test@example.com';

echo "<h1>Messaging Test - Bypassing Auth</h1>";
echo "<p>User ID: " . $_SESSION['user_id'] . "</p>";
echo "<p>User Name: " . $_SESSION['user_name'] . "</p>";
echo "<p>User Email: " . $_SESSION['user_email'] . "</p>";
echo "<p><a href='messages.php'>Go to Messages</a></p>";
echo "<p><a href='profile.php'>Go to Profile (to test messageUser function)</a></p>";
?>