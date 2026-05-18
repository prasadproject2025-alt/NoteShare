<?php
session_start();

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

include 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h3><i class="fas fa-user-plus me-2"></i>Create Your Account</h3>
                </div>
                <div class="card-body">
                    <!-- Step Indicator -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between">
                            <div class="step-indicator active" id="step-1">
                                <div class="step-circle">1</div>
                                <div class="step-text">Email</div>
                            </div>
                            <div class="step-indicator" id="step-2">
                                <div class="step-circle">2</div>
                                <div class="step-text">OTP & Password</div>
                            </div>
                        </div>
                    </div>

                    <!-- Email Input Section -->
                    <div id="email-section">
                        <div class="mb-3">
                            <label for="email" class="form-label">VIT Student Gmail</label>
                            <input type="email" class="form-control form-control-lg" id="email"
                                   placeholder="your.email@vitstudent.ac.in" required>
                            <small class="text-muted">Must end with @vitstudent.ac.in</small>
                        </div>
                        <button type="button" class="btn btn-success btn-lg w-100" id="send-otp-btn" onclick="sendOTP()">
                            Send OTP to Gmail
                        </button>
                    </div>

                    <!-- OTP & Password Section -->
                    <div id="otp-password-section" style="display: none;">
                        <div class="text-center mb-3">
                            <h5>Verify Your Email & Create Password</h5>
                            <p class="text-muted">Enter the OTP sent to your Gmail and create a secure password</p>
                        </div>

                        <!-- OTP Input -->
                        <div class="mb-4">
                            <label for="otp" class="form-label">6-Digit OTP</label>
                            <input type="text" class="form-control form-control-lg text-center" id="otp"
                                   placeholder="000000" maxlength="6" required style="font-size: 24px; letter-spacing: 8px;">
                            <small class="text-muted">Check your Gmail inbox and spam folder for the OTP</small>
                        </div>

                        <!-- Password Creation -->
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control form-control-lg" id="password" required>
                            <div class="password-requirements mt-2">
                                <small id="req-length" class="text-muted"><i class="fas fa-times text-danger"></i> At least 8 characters</small><br>
                                <small id="req-uppercase" class="text-muted"><i class="fas fa-times text-danger"></i> One uppercase letter</small><br>
                                <small id="req-lowercase" class="text-muted"><i class="fas fa-times text-danger"></i> One lowercase letter</small><br>
                                <small id="req-number" class="text-muted"><i class="fas fa-times text-danger"></i> One number</small><br>
                                <small id="req-special" class="text-muted"><i class="fas fa-times text-danger"></i> One special character (!@#$%^&*)</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="confirm-password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control form-control-lg" id="confirm-password" required>
                            <small id="confirm-error" class="text-danger" style="display: none;">Passwords do not match</small>
                        </div>

                        <button type="button" class="btn btn-success btn-lg w-100" id="create-account-btn" onclick="createAccount()" disabled>
                            Create Account
                        </button>
                        <button type="button" class="btn btn-outline-secondary w-100 mt-2" onclick="backToEmail()">
                            Change Email
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.step-indicator {
    text-align: center;
    flex: 1;
}

.step-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #e9ecef;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 8px;
    font-weight: bold;
    transition: all 0.3s ease;
}

.step-indicator.active .step-circle {
    background-color: #198754;
    color: white;
}

.step-text {
    font-size: 12px;
    color: #6c757d;
}

.step-indicator.active .step-text {
    color: #198754;
    font-weight: 500;
}

.password-requirements i {
    width: 16px;
}
</style>

<script>
let userEmail = '';

function updateStepIndicator(activeStep) {
    // Reset all steps
    for (let i = 1; i <= 2; i++) {
        document.getElementById(`step-${i}`).classList.remove('active');
    }
    // Activate current step
    document.getElementById(`step-${activeStep}`).classList.add('active');
}

function sendOTP() {
    console.log('sendOTP function called');
    const email = document.getElementById('email').value.trim();
    console.log('Email entered:', email);

    if (!email) {
        alert('Please enter your email address');
        return;
    }

    if (!email.endsWith('@vitstudent.ac.in')) {
        console.log('Email validation failed. Email:', email);
        // Temporarily allow any email for testing
        // alert('Please use your VIT student email (@vitstudent.ac.in)');
        // return;
    }

    console.log('Email validation passed');
    userEmail = email;

    const sendOtpBtn = document.getElementById('send-otp-btn');
    sendOtpBtn.disabled = true;
    sendOtpBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending OTP...';

    console.log('Making fetch request to send_otp.php');
    fetch('auth/send_otp.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ email: email })
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response ok:', response.ok);
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        console.log('Data received:', data);
        if (data.success) {
            console.log('OTP sent successfully, showing otp-password-section');

            // Check if elements exist
            const emailSection = document.getElementById('email-section');
            const otpSection = document.getElementById('otp-password-section');

            if (emailSection && otpSection) {
                // Update UI first
                emailSection.style.display = 'none';
                otpSection.style.display = 'block';
                updateStepIndicator(2);
                sendOtpBtn.disabled = false;
                sendOtpBtn.innerHTML = 'Send OTP to Gmail';

                // Show success message
                alert('OTP sent successfully! Please check your Gmail inbox (and spam folder) for the verification code.');

                // Focus on OTP input
                const otpInput = document.getElementById('otp');
                if (otpInput) otpInput.focus();
            } else {
                console.error('Required DOM elements not found');
                alert('UI Error: Required elements not found. Please refresh the page.');
                sendOtpBtn.disabled = false;
                sendOtpBtn.innerHTML = 'Send OTP to Gmail';
            }
        } else {
            console.log('Error sending OTP:', data.message);
            alert('Error sending OTP: ' + data.message);
            sendOtpBtn.disabled = false;
            sendOtpBtn.innerHTML = 'Send OTP to Gmail';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error sending OTP. Please try again.');
        sendOtpBtn.disabled = false;
        sendOtpBtn.innerHTML = 'Send OTP to Gmail';
    });
}


function validatePassword() {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm-password').value;

    // Check requirements
    const hasLength = password.length >= 8;
    const hasUppercase = /[A-Z]/.test(password);
    const hasLowercase = /[a-z]/.test(password);
    const hasNumber = /\d/.test(password);
    const hasSpecial = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password);
    const passwordsMatch = password === confirmPassword && password !== '';

    // Update UI
    updateRequirement('req-length', hasLength);
    updateRequirement('req-uppercase', hasUppercase);
    updateRequirement('req-lowercase', hasLowercase);
    updateRequirement('req-number', hasNumber);
    updateRequirement('req-special', hasSpecial);

    // Show/hide confirm error
    const confirmError = document.getElementById('confirm-error');
    if (confirmPassword && password !== confirmPassword) {
        confirmError.style.display = 'block';
    } else {
        confirmError.style.display = 'none';
    }

    // Enable/disable create button
    const createBtn = document.getElementById('create-account-btn');
    createBtn.disabled = !(hasLength && hasUppercase && hasLowercase && hasNumber && hasSpecial && passwordsMatch);
}

function updateRequirement(elementId, isValid) {
    const element = document.getElementById(elementId);
    const icon = element.querySelector('i');

    if (isValid) {
        icon.className = 'fas fa-check text-success';
        element.className = 'text-success';
    } else {
        icon.className = 'fas fa-times text-danger';
        element.className = 'text-muted';
    }
}

function createAccount() {
    const otp = document.getElementById('otp').value.trim();
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm-password').value;

    if (!otp || otp.length !== 6) {
        alert('Please enter a valid 6-digit OTP');
        return;
    }

    if (password !== confirmPassword) {
        alert('Passwords do not match');
        return;
    }

    const createBtn = document.getElementById('create-account-btn');
    createBtn.disabled = true;
    createBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating Account...';

    // First verify OTP
    fetch('auth/verify_otp.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ email: userEmail, otp: otp })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // OTP verified, now create account
            return fetch('auth/create_account.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ email: userEmail, password: password })
            });
        } else {
            throw new Error('Invalid OTP. Please try again.');
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Account created successfully! Welcome to NoteShare!');
            // Sync user data to Firebase after successful account creation
            syncUserDataToFirebase(userEmail).then(() => {
                window.location.href = 'index.php';
            }).catch(error => {
                console.error('Error syncing user data:', error);
                // Still redirect even if sync fails
                window.location.href = 'index.php';
            });
        } else {
            throw new Error('Error creating account: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert(error.message || 'Error creating account. Please try again.');
        createBtn.disabled = false;
        createBtn.innerHTML = 'Create Account';
    });
}

function backToEmail() {
    document.getElementById('otp-password-section').style.display = 'none';
    document.getElementById('email-section').style.display = 'block';
    updateStepIndicator(1);
    document.getElementById('otp').value = '';
    document.getElementById('password').value = '';
    document.getElementById('confirm-password').value = '';
}


// Password validation listeners
document.getElementById('password').addEventListener('input', validatePassword);
document.getElementById('confirm-password').addEventListener('input', validatePassword);

// OTP input formatting
document.getElementById('otp').addEventListener('input', function(e) {
    // Allow only numbers
    this.value = this.value.replace(/[^0-9]/g, '');
});

// Initialize
console.log('JavaScript loaded successfully');
updateStepIndicator(1);

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