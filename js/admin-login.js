document.addEventListener('DOMContentLoaded', function () {
  const form = document.querySelector('form');
  if (!form) return;

  form.addEventListener('submit', async function (e) {
    e.preventDefault();
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;

    try {
      let res = await fetch('/api/admin-login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username, password }),
      });
      if (res.status === 404) {
        res = await fetch('/api/admin-login.js', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ username, password }),
        });
      }
      const text = await res.text();
      let data;
      try {
        data = JSON.parse(text);
      } catch {
        throw new Error(`Server error (${res.status}): ${text.substring(0, 120)}`);
      }
      if (!data.success) throw new Error(data.message || 'Login failed');
      window.NoteShareAuth.setAdmin(data.username);
      window.location.href = 'admin-dashboard.html';
    } catch (err) {
      alert(err.message || 'Invalid admin credentials');
    }
  });
});
