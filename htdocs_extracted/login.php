<?php
session_start();

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Invalid email format.';
    } elseif (!str_ends_with($email, '@vitstudent.ac.in')) {
        $error_message = 'Please use your VIT student email (vitstudent.ac.in).';
    } else {
        // Store email in session temporarily for OAuth
        $_SESSION['temp_email'] = $email;
        // Redirect to Google OAuth flow
        header('Location: auth/google_auth.php');
        exit();
    }
}

include 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3>Welcome to NoteShare</h3>
                </div>
                <div class="card-body">
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                    <?php endif; ?>

                    <!-- Login Form for Existing Users -->
                    <div class="mb-4">
                        <h4 class="text-center mb-3">Login to Your Account</h4>
                        <form id="login-form">
                            <div class="mb-3">
                                <label for="login-email" class="form-label">VIT Student Gmail</label>
                                <input type="email" class="form-control" id="login-email"
                                       placeholder="your.email@vitstudent.ac.in" required>
                                <small class="text-muted">Must end with @vitstudent.ac.in</small>
                            </div>

                            <div class="mb-3">
                                <label for="login-password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="login-password" required>
                            </div>

                            <button type="button" class="btn btn-primary w-100" onclick="loginUser()">
                                Login
                            </button>
                        </form>
                    </div>

                    <hr>

                    <!-- Create Account Section -->
                    <div class="text-center">
                        <h5 class="mb-3">New to NoteShare?</h5>
                        <p class="text-muted mb-3">Create your account to start buying and selling notes</p>
                        <a href="create_account.php" class="btn btn-success btn-lg">
                            <i class="fas fa-user-plus me-2"></i>Create Account
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Login JavaScript -->
<!-- Firebase SDK -->
<script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-app.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-database.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-auth.js"></script>

<script src="js/firebase-config.js"></script>

<script>
function loginUser() {
    const email = document.getElementById('login-email').value.trim();
    const password = document.getElementById('login-password').value;

    if (!email || !password) {
        alert('Please enter both email and password');
        return;
    }

    if (!email.endsWith('@vitstudent.ac.in')) {
        alert('Please use your VIT student email (@vitstudent.ac.in)');
        return;
    }

    const loginBtn = document.querySelector('#login-form button');
    loginBtn.disabled = true;
    loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Logging in...';

    fetch('auth/login.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ email: email, password: password })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Sync user data to Firebase after successful login
            syncUserDataToFirebase(email).then(() => {
                window.location.href = 'index.php';
            }).catch(error => {
                console.error('Error syncing user data:', error);
                // Still redirect even if sync fails
                window.location.href = 'index.php';
            });
        } else {
            alert('Login failed: ' + data.message);
            loginBtn.disabled = false;
            loginBtn.innerHTML = 'Login';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error logging in. Please try again.');
        loginBtn.disabled = false;
        loginBtn.innerHTML = 'Login';
    });
}

function syncUserDataToFirebase(email) {
    return new Promise((resolve, reject) => {
        // Get user data from server session via API
        fetch('auth/get_user_data.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ email: email })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const userData = data.user;
                const userId = data.user_id;
                
                // Sync to Firebase
                firebase.database().ref('users/' + userId).set({
                    email: userData.email,
                    name: userData.name,
                    coins: userData.coins,
                    created_at: userData.created_at,
                    status: userData.status || 'active'
                }).then(() => {
                    console.log('User data synced to Firebase');
                    resolve();
                }).catch(error => {
                    console.error('Error syncing to Firebase:', error);
                    reject(error);
                });
            } else {
                reject(new Error('Failed to get user data'));
            }
        })
        .catch(error => {
            console.error('Error getting user data:', error);
            reject(error);
        });
    });
}
</script>

<!-- Firebase SDK -->
<script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-app.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-database.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-auth.js"></script>

<script src="js/firebase-config.js"></script>

<?php include 'includes/footer.php'; ?>
