async function waitForApp() {
  for (let i = 0; i < 150; i++) {
    if (window.NoteShareAuth && typeof firebase !== 'undefined' && firebase.apps?.length) return;
    await new Promise((r) => setTimeout(r, 100));
  }
  throw new Error('App still loading. Please refresh the page.');
}

async function loginUser() {
  const email = document.getElementById('login-email').value.trim();
  const password = document.getElementById('login-password').value;
  const loginBtn = document.querySelector('#login-form button');

  if (!email || !password) {
    alert('Please enter both email and password');
    return;
  }

  loginBtn.disabled = true;
  loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Logging in...';

  try {
    await waitForApp();
    await window.NoteShareAuth.login(email, password);
    window.location.href = 'index.html';
  } catch (e) {
    alert('Login failed: ' + (e.message || e));
    loginBtn.disabled = false;
    loginBtn.innerHTML = 'Login';
  }
}
