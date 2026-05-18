/** Auth guard for protected pages */
(function () {
  const publicPages = ['login.html', 'create-account.html', 'admin-login.html'];
  const file = window.location.pathname.split('/').pop() || 'index.html';

  function whenReady(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  async function waitForAuth() {
    for (let i = 0; i < 150; i++) {
      if (window.NoteShareAuth && typeof firebase !== 'undefined' && firebase.apps?.length) {
        return;
      }
      await new Promise((r) => setTimeout(r, 100));
    }
  }

  whenReady(async function () {
    await waitForAuth();

    if (!window.NoteShareAuth) {
      console.error('NoteShareAuth failed to load. Check the console for script errors.');
      return;
    }

    const isPublic = publicPages.includes(file);

    if (file === 'login.html') {
      const user = await window.NoteShareAuth.waitForAuthState();
      if (user && user.email) {
        await window.NoteShareAuth.restoreFromFirebase();
        window.location.replace('index.html');
        return;
      }
      if (window.NoteShareAuth.isLoggedIn() && !user) {
        window.NoteShareAuth.clearSession();
      }
      return;
    }

    if (isPublic) return;

    if (!window.NoteShareAuth.isLoggedIn()) {
      const restored = await window.NoteShareAuth.restoreFromFirebase();
      if (!restored && !window.NoteShareAuth.isLoggedIn()) {
        window.location.replace('login.html');
        return;
      }
    }

    window.NoteShareAuth.initNavGlobals();
    if (window.NoteShareNav) {
      const h = document.getElementById('app-header');
      if (h) h.innerHTML = window.NoteShareNav.renderHeader();
    }
  });
})();
