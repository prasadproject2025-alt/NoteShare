<?php
session_start();

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

// In a real implementation, you would use Google OAuth flow here
// This is a simplified version for demonstration

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_SESSION['temp_email'] ?? '';

    if (empty($email) || !str_ends_with($email, '@vitstudent.ac.in')) {
        $_SESSION['auth_error'] = 'Invalid VIT email';
        header('Location: ../login.php');
        exit();
    }

    // Simulate successful authentication
    // In production, verify with Google OAuth
    $_SESSION['user_id'] = md5($email);
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name'] = explode('@', $email)[0];
    
    // Save user to Firebase (in production)
    // firebase.database().ref('users/' + uid).set({
    //     email: email,
    //     name: displayName,
    //     created_at: Date.now(),
    //     coins: 10 (initial coins),
    //     ...
    // })

    unset($_SESSION['temp_email']);
    header('Location: ../index.php');
    exit();
}

// For production, implement actual Google OAuth
// This would redirect to Google and handle the callback
?>
<html>
<head>
    <title>Authenticating...</title>
</head>
<body>
    <p>Processing authentication...</p>
    <form method="POST" id="authForm">
        <input type="hidden" name="authenticate" value="1">
    </form>
    <script>
        document.getElementById('authForm').submit();
    </script>
</body>
</html>
