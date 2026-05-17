const ADMIN_USERNAME = 'prasad';
const ADMIN_PASSWORD = 'prasad0428';
const ADMIN_SESSION_KEY = 'note_share_admin_logged_in';

export function validateAdminCredentials(username, password) {
  return username === ADMIN_USERNAME && password === ADMIN_PASSWORD;
}

export function setAdminSession(active) {
  if (typeof window === 'undefined') return;
  if (active) {
    window.sessionStorage.setItem(ADMIN_SESSION_KEY, '1');
  } else {
    window.sessionStorage.removeItem(ADMIN_SESSION_KEY);
  }
}

export function isAdminLoggedIn() {
  if (typeof window === 'undefined') return false;
  return window.sessionStorage.getItem(ADMIN_SESSION_KEY) === '1';
}

export function logoutAdmin() {
  setAdminSession(false);
}
