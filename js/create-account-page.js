let userEmail = '';

async function waitForApp() {
  for (let i = 0; i < 150; i++) {
    if (window.NoteShareAuth && typeof firebase !== 'undefined' && firebase.apps?.length) {
      return;
    }
    await new Promise((r) => setTimeout(r, 100));
  }
  throw new Error('App still loading. Please refresh the page.');
}

function updateStepIndicator(activeStep) {
  for (let i = 1; i <= 2; i++) {
    document.getElementById('step-' + i)?.classList.remove('active');
  }
  document.getElementById('step-' + activeStep)?.classList.add('active');
}

async function sendOTP() {
  const email = document.getElementById('email').value.trim();
  if (!email) {
    alert('Please enter your email address');
    return;
  }
  if (!email.endsWith('@vitstudent.ac.in')) {
    alert('Please use your VIT student email (@vitstudent.ac.in)');
    return;
  }

  userEmail = email;
  const btn = document.getElementById('send-otp-btn');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending OTP...';

  try {
    const res = await fetch('/api/send-otp', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, name: email.split('@')[0] }),
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.message || data.details || 'Failed to send OTP');

    document.getElementById('email-section').style.display = 'none';
    document.getElementById('otp-password-section').style.display = 'block';
    updateStepIndicator(2);
    if (data.devMode) {
      alert(
        'Dev mode: OTP was not emailed.\n\nCheck the terminal running "npm run dev" or logs/otp_log.txt for your 6-digit code.'
      );
    } else {
      alert('OTP sent! Check your Gmail inbox and spam folder.');
    }
    document.getElementById('otp')?.focus();
  } catch (e) {
    alert('Error sending OTP: ' + e.message);
  } finally {
    btn.disabled = false;
    btn.innerHTML = 'Send OTP to Gmail';
  }
}

function validatePassword() {
  const password = document.getElementById('password').value;
  const confirmPassword = document.getElementById('confirm-password').value;
  const hasLength = password.length >= 8;
  const hasUppercase = /[A-Z]/.test(password);
  const hasLowercase = /[a-z]/.test(password);
  const hasNumber = /\d/.test(password);
  const hasSpecial = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password);
  const passwordsMatch = password === confirmPassword && password !== '';

  ['length', 'uppercase', 'lowercase', 'number', 'special'].forEach((k, i) => {
    const el = document.getElementById('req-' + k);
    if (!el) return;
    const ok = [hasLength, hasUppercase, hasLowercase, hasNumber, hasSpecial][i];
    const icon = el.querySelector('i');
    if (icon) icon.className = ok ? 'fas fa-check text-success' : 'fas fa-times text-danger';
    el.className = ok ? 'text-success' : 'text-muted';
  });

  const confirmError = document.getElementById('confirm-error');
  if (confirmError) {
    confirmError.style.display = confirmPassword && password !== confirmPassword ? 'block' : 'none';
  }

  const createBtn = document.getElementById('create-account-btn');
  if (createBtn) {
    createBtn.disabled = !(hasLength && hasUppercase && hasLowercase && hasNumber && hasSpecial && passwordsMatch);
  }
}

async function createAccount() {
  const otp = document.getElementById('otp').value.trim();
  const password = document.getElementById('password').value;
  const confirmPassword = document.getElementById('confirm-password').value;
  const createBtn = document.getElementById('create-account-btn');

  if (otp.length !== 6) {
    alert('Please enter a valid 6-digit OTP');
    return;
  }
  if (password !== confirmPassword) {
    alert('Passwords do not match');
    return;
  }

  createBtn.disabled = true;
  createBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating Account...';

  try {
    const verifyRes = await fetch('/api/verify-otp', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email: userEmail, otp }),
    });
    const verifyData = await verifyRes.json();
    if (!verifyData.success) throw new Error(verifyData.message || 'Invalid OTP');

    await waitForApp();
    await window.NoteShareAuth.register(userEmail, password, userEmail.split('@')[0]);
    alert('Account created successfully! Welcome to NoteShare!');
    window.location.href = 'index.html';
  } catch (e) {
    alert(e.message || 'Error creating account');
    createBtn.disabled = false;
    createBtn.innerHTML = 'Create Account';
  }
}

function backToEmail() {
  document.getElementById('otp-password-section').style.display = 'none';
  document.getElementById('email-section').style.display = 'block';
  updateStepIndicator(1);
}

document.addEventListener('DOMContentLoaded', function () {
  updateStepIndicator(1);
  document.getElementById('password')?.addEventListener('input', validatePassword);
  document.getElementById('confirm-password')?.addEventListener('input', validatePassword);
  document.getElementById('otp')?.addEventListener('input', function () {
    this.value = this.value.replace(/[^0-9]/g, '');
  });
});
