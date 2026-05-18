async function waitForApp() {
  if (window.NoteShareBoot && window.NoteShareBoot.waitForApp) {
    return window.NoteShareBoot.waitForApp(30000);
  }
  for (let i = 0; i < 300; i++) {
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
  throw new Error(
    'Could not start the app. Open http://localhost:3000/login.html (run: npm run dev) and check the browser console (F12).'
  );
}

async function loginUser() {
  const email = document.getElementById('login-email').value.trim();
  const password = document.getElementById('login-password').value;
  const loginBtn = document.querySelector('#login-form button');
  const errBox = document.getElementById('login-error');

  if (!email || !password) {
    alert('Please enter both email and password');
    return;
  }

  if (errBox) errBox.classList.add('d-none');

  loginBtn.disabled = true;
  loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Logging in...';

  try {
    await waitForApp();
    await window.NoteShareAuth.login(email, password);
    window.location.replace('index.html');
  } catch (e) {
    const msg = e.message || String(e);
    if (errBox) {
      errBox.textContent = msg;
      errBox.classList.remove('d-none');
    } else {
      alert('Login failed: ' + msg);
    }
    loginBtn.disabled = false;
    loginBtn.innerHTML = 'Login';
  }
}
