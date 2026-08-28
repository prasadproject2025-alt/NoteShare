/**
 * Page Initializer & Navigation Setup
 */
(function () {
  function whenReady(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  async function waitForAuth() {
    for (let i = 0; i < 60; i++) {
      if (window.NoteShareAuth && typeof firebase !== 'undefined' && firebase.apps?.length) {
        return;
      }
      await new Promise((r) => setTimeout(r, 50));
    }
  }

  whenReady(async function () {
    await waitForAuth();

    if (window.NoteShareAuth) {
      window.NoteShareAuth.initNavGlobals?.();
    }

    if (window.NoteShareNav) {
      const h = document.getElementById('app-header');
      if (h) h.innerHTML = window.NoteShareNav.renderHeader();
    }
  });
})();
