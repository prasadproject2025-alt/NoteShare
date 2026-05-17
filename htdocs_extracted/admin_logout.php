<?php
session_start();

// Clear admin session
unset($_SESSION['admin_logged_in']);
unset($_SESSION['admin_username']);

session_destroy();

// Redirect to admin login
header('Location: admin_login.php');
exit();
?>