/**
 * Login Page Handler
 */
async function waitForApp() {
  if (window.NoteShareBoot && window.NoteShareBoot.waitForApp) {
    return window.NoteShareBoot.waitForApp(10000);
  }
  for (let i = 0; i < 50; i++) {
    if (
      window.NoteShareAuth &&
      typeof firebase !== 'undefined' &&
      firebase.apps &&
      firebase.apps.length
    ) {
      return;
    }
    await new Promise((r) => setTimeout(r, 100));
  }
}

async function loginUser() {
  const emailInput = document.getElementById('login-email');
  const passwordInput = document.getElementById('login-password');
  const loginBtn = document.getElementById('login-submit-btn');
  const errBox = document.getElementById('login-error');
  const redPara = document.getElementById('login-error-msg');
  const redParaText = document.getElementById('login-error-text');

  const email = emailInput?.value.trim() || '';
  const password = passwordInput?.value || '';

  const showError = (msg) => {
    let cleanMsg = msg || 'Invalid user or incorrect password.';
    if (
      typeof cleanMsg === 'string' &&
      (cleanMsg.startsWith('{') ||
        cleanMsg.includes('INVALID_LOGIN_CREDENTIALS') ||
        cleanMsg.includes('INVALID_CREDENTIAL') ||
        cleanMsg.includes('400'))
    ) {
      cleanMsg = 'Invalid user or incorrect password.';
    }

    if (redPara && redParaText) {
      redParaText.textContent = cleanMsg;
      redPara.classList.remove('d-none');
      if (errBox) errBox.classList.add('d-none');
    } else if (errBox) {
      errBox.textContent = cleanMsg;
      errBox.classList.remove('d-none');
    } else {
      alert(cleanMsg);
    }
  };

  const hideError = () => {
    if (errBox) errBox.classList.add('d-none');
    if (redPara) redPara.classList.add('d-none');
  };

  if (!email || !password) {
    showError('Please enter both username/email and password.');
    return;
  }

  hideError();

  if (loginBtn) {
    loginBtn.disabled = true;
    loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Logging in...';
  }

  try {
    await waitForApp().catch(() => {});
    if (window.NoteShareAuth && typeof window.NoteShareAuth.isAccountLocked === 'function' && window.NoteShareAuth.isAccountLocked(email)) {
      throw new Error('Account locked! You have entered an incorrect password 5 times. Please click "Forgot Password?" below to reset your password and unlock your account.');
    }
    if (window.NoteShareAuth && typeof window.NoteShareAuth.login === 'function') {
      const res = await window.NoteShareAuth.login(email, password);
      if (res?.isAdmin || window.NoteShareAuth.isAdmin()) {
        window.location.replace('admin-dashboard.html');
        return;
      }
    } else {
      throw new Error('Authentication service unavailable. Please refresh and try again.');
    }
    window.location.replace('index.html');
  } catch (e) {
    console.warn('NoteShare Auth Note:', e);
    const msg = e.message || 'Incorrect password or invalid user.';
    showError(msg);
  } finally {
    if (loginBtn) {
      loginBtn.disabled = false;
      loginBtn.innerHTML = 'Submit';
    }
  }
}

async function handleForgotPassword() {
  const emailInput = document.getElementById('reset-email');
  const submitBtn = document.getElementById('reset-submit-btn');
  const alertBox = document.getElementById('forgot-password-alert');

  const email = emailInput?.value.trim() || '';

  if (!email) {
    if (alertBox) {
      alertBox.textContent = 'Please enter your VIT student email.';
      alertBox.className = 'alert alert-danger py-2 px-3 small';
      alertBox.classList.remove('d-none');
    }
    return;
  }

  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Sending...';
  }

  try {
    await waitForApp().catch(() => {});
    if (window.NoteShareAuth && typeof window.NoteShareAuth.sendPasswordReset === 'function') {
      await window.NoteShareAuth.sendPasswordReset(email);
    }
    if (alertBox) {
      alertBox.textContent = 'Password reset link sent! Check your inbox for instructions.';
      alertBox.className = 'alert alert-success py-2 px-3 small';
      alertBox.classList.remove('d-none');
    }
    if (emailInput) emailInput.value = '';
  } catch (err) {
    if (alertBox) {
      alertBox.textContent = err.message || 'Failed to send password reset link.';
      alertBox.className = 'alert alert-danger py-2 px-3 small';
      alertBox.classList.remove('d-none');
    }
  } finally {
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.innerHTML = 'Send Reset Link';
    }
  }
}
