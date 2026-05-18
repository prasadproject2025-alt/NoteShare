/** Auth guard + globals for protected pages */
(function () {
  const publicPages = ['login.html', 'create-account.html', 'admin-login.html', ''];
  const file = window.location.pathname.split('/').pop() || 'index.html';

  function whenReady(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  whenReady(async function () {
    // Wait for nav-injected scripts
    for (let i = 0; i < 50; i++) {
      if (window.NoteShareAuth && typeof window.md5 === 'function') break;
      await new Promise((r) => setTimeout(r, 100));
    }

    const isPublic = publicPages.includes(file);
    if (!isPublic) {
      if (!window.NoteShareAuth?.isLoggedIn()) {
        const restored = await window.NoteShareAuth?.restoreFromFirebase();
        if (!restored && !window.NoteShareAuth?.isLoggedIn()) {
          window.location.href = 'login.html';
          return;
        }
      }
      window.NoteShareAuth?.initNavGlobals();
      if (window.NoteShareNav) {
        const h = document.getElementById('app-header');
        if (h) h.innerHTML = window.NoteShareNav.renderHeader();
      }
    }

    if (file === 'login.html' && window.NoteShareAuth?.isLoggedIn()) {
      window.location.href = 'index.html';
    }
  });
})();
