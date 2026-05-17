<?php
session_start();

// Check if already logged in as admin
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin_dashboard.php');
    exit();
}

$error_message = '';
$success_message = '';

// Check for timeout parameter
if (isset($_GET['timeout']) && $_GET['timeout'] == '1') {
    $error_message = 'Your admin session has expired. Please login again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Admin credentials - CHANGE THESE IN PRODUCTION!
    $admin_credentials = [
        'admin' => password_hash('admin123', PASSWORD_DEFAULT), // Default: admin/admin123
        // Add more admin accounts as needed
        // 'admin2' => password_hash('password2', PASSWORD_DEFAULT),
    ];

    if (isset($admin_credentials[$username]) && password_verify($password, $admin_credentials[$username])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['admin_login_time'] = time();
        header('Location: admin_dashboard.php');
        exit();
    } else {
        $error_message = 'Invalid username or password.';
    }
}

include 'includes/header.php';
?>

<main class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Admin Login</h4>
                </div>
                <div class="card-body">
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>

                        <button type="submit" class="btn btn-danger w-100">
                            <i class="fas fa-sign-in-alt me-2"></i>Login as Admin
                        </button>
                    </form>
                </div>
            </div>

            <div class="text-center mt-3">
                <a href="index.php" class="btn btn-link">Back to Home</a>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>