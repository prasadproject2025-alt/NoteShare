/**
 * Page Initializer & Navigation Setup
 */
(function () {
  function runAuthGuard() {
    if (window.NoteShareAuth && typeof window.NoteShareAuth.checkAuthGuard === 'function') {
      window.NoteShareAuth.checkAuthGuard();
    }
  }

  // Check auth guard immediately
  runAuthGuard();

  // Listen for back/forward navigation (bfcache restoration)
  window.addEventListener('pageshow', function () {
    runAuthGuard();
  });

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
    runAuthGuard();
    await waitForAuth();

    if (window.NoteShareAuth) {
      window.NoteShareAuth.initNavGlobals?.();
    }

    if (window.NoteShareNav) {
      const h = document.getElementById('app-header');
      if (h) h.innerHTML = window.NoteShareNav.renderHeader();
    }

    // Auto-record real-time user traffic & page visit
    try {
      if (window.NoteShareAuth && typeof window.NoteShareAuth.getSession === 'function') {
        const session = window.NoteShareAuth.getSession();
        if (session && session.user_email) {
          const pageTitle = document.title || window.location.pathname.split('/').pop() || 'Page Interaction';
          window.NoteShareAuth.recordUserActivity(
            session.user_email,
            'Page Visit',
            `Viewed ${pageTitle}`
          );

          // Intercept window.fetch to automatically record API calls
          if (!window._noteshare_fetch_intercepted) {
            window._noteshare_fetch_intercepted = true;
            const origFetch = window.fetch;
            window.fetch = function (resource, init) {
              try {
                const url = typeof resource === 'string' ? resource : resource?.url || '';
                if (url && !url.includes('/api/record-traffic') && !url.includes('.json')) {
                  const sess = window.NoteShareAuth?.getSession?.();
                  if (sess && sess.user_email) {
                    window.NoteShareAuth.recordUserActivity(sess.user_email, 'API Call', `Requested ${url.split('?')[0]}`);
                  }
                }
              } catch (e) {}
              return origFetch.apply(this, arguments);
            };
          }
        }
      }
    } catch (e) {}
  });
})();
