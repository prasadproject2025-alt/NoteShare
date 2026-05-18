document.addEventListener('DOMContentLoaded', function () {
  const form = document.querySelector('form');
  if (!form) return;

  form.addEventListener('submit', async function (e) {
    e.preventDefault();
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;

    try {
      const res = await fetch('/api/admin-login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username, password }),
      });
      const data = await res.json();
      if (!data.success) throw new Error(data.message || 'Login failed');
      window.NoteShareAuth.setAdmin(data.username);
      window.location.href = 'admin-dashboard.html';
    } catch (err) {
      alert(err.message || 'Invalid admin credentials');
    }
  });
});
