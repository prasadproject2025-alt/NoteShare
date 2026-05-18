/**
 * Shared navigation & footer — replaces includes/header.php & footer.php
 */
(function () {
  function escapeHtml(s) {
    const d = document.createElement('div'); // element helper
    d.textContent = s;
    return d.innerHTML;
  }

  function renderHeader() {
    const loggedIn = window.NoteShareAuth && window.NoteShareAuth.isLoggedIn();
    const name = loggedIn ? window.NoteShareAuth.getUserName() : 'Profile';
    const isAdmin = window.NoteShareAuth && window.NoteShareAuth.isAdmin();

    let navLinks = '';
    if (loggedIn) {
      navLinks =
        '<li class="nav-item"><a class="nav-link" href="index.html">Home</a></li>' +
        '<li class="nav-item"><a class="nav-link" href="coins.html">Coins</a></li>' +
        '<li class="nav-item"><a class="nav-link" href="messages.html">Messages</a></li>' +
        (isAdmin
          ? '<li class="nav-item"><a class="nav-link text-warning" href="admin-dashboard.html"><i class="fas fa-tachometer-alt me-1"></i>Admin Panel</a></li>'
          : '') +
        '<li class="nav-item dropdown">' +
        '<a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">' +
        escapeHtml(name) +
        '</a>' +
        '<ul class="dropdown-menu">' +
        '<li><a class="dropdown-item" href="profile.html">My Profile</a></li>' +
        '<li><a class="dropdown-item" href="edit-profile.html">Edit Profile</a></li>' +
        '<li><hr class="dropdown-divider"></li>' +
        '<li><a class="dropdown-item" href="#" id="logout-link">Logout</a></li>' +
        '</ul></li>';
    } else {
      navLinks =
        '<li class="nav-item"><a class="nav-link" href="login.html">Login</a></li>' +
        '<li class="nav-item"><a class="nav-link text-warning" href="admin-login.html"><i class="fas fa-shield-alt me-1"></i>Admin</a></li>';
    }

    return (
      '<nav class="navbar navbar-expand-lg navbar-dark bg-dark">' +
      '<div class="container">' +
      '<a class="navbar-brand" href="' +
      (loggedIn ? 'index.html' : 'login.html') +
      '"><strong>NoteShare</strong></a>' +
      '<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">' +
      '<span class="navbar-toggler-icon"></span></button>' +
      '<div class="collapse navbar-collapse" id="navbarNav">' +
      '<ul class="navbar-nav ms-auto">' +
      navLinks +
      '</ul></div></div></nav>'
    );
  }

  function renderFooter() {
    return (
      '<footer class="bg-dark text-white mt-5 py-4">' +
      '<div class="container"><div class="row">' +
      '<div class="col-md-4"><h5>NoteShare</h5><p class="text-muted">Website for sell, buy and share notes for VIT students.</p></div>' +
      '<div class="col-md-4"><h5>Quick Links</h5><ul class="list-unstyled text-muted">' +
      '<li><a href="index.html">Home</a></li><li><a href="buy-notes.html">Buy Notes</a></li>' +
      '<li><a href="sell-notes.html">Sell Notes</a></li></ul></div>' +
      '<div class="col-md-4"><h5>Contact</h5><p>Email: prasad.project2025@gmail.com</p></div></div>' +
      '<hr><div class="text-center text-muted"><p>&copy; 2025 NoteShare. All rights reserved.</p></div></div></footer>' +
      '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>'
    );
  }

  function scriptBaseUrl() {
    const nav = document.querySelector('script[src*="nav.js"]');
    if (nav && nav.src) {
      return nav.src.replace(/\/js\/nav\.js(\?.*)?$/i, '/');
    }
    const path = window.location.pathname.replace(/\/[^/]*$/, '/');
    return window.location.origin + path;
  }

  function resolveScriptUrl(src) {
    if (/^https?:\/\//i.test(src)) return src;
    return new URL(src, scriptBaseUrl()).href;
  }

  function loadScript(src) {
    const url = resolveScriptUrl(src);
    return new Promise((resolve, reject) => {
      const existing = document.querySelector('script[src="' + url + '"]');
      if (existing) {
        if (existing.dataset.loadError) {
          existing.remove();
        } else if (existing.dataset.loaded === '1') {
          resolve();
          return;
        } else {
          existing.addEventListener('load', () => resolve(), { once: true });
          existing.addEventListener('error', () => reject(new Error('Failed to load ' + src)), { once: true });
          return;
        }
      }
      const s = document.createElement('script');
      s.src = url;
      s.async = false;
      s.onload = function () {
        s.dataset.loaded = '1';
        resolve();
      };
      s.onerror = function () {
        s.dataset.loadError = '1';
        reject(new Error('Failed to load ' + src));
      };
      document.head.appendChild(s);
    });
  }

  let injectPromise = null;

  async function injectHeadAssets() {
    if (window.NoteShareReady) return;
    if (injectPromise) return injectPromise;

    injectPromise = (async () => {
      const link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css';
      if (!document.querySelector('link[href*="font-awesome"]')) {
        document.head.appendChild(link);
      }

      const scripts = [
        'https://cdnjs.cloudflare.com/ajax/libs/blueimp-md5/2.19.0/js/md5.min.js',
        'https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js',
        'https://www.gstatic.com/firebasejs/8.10.1/firebase-database.js',
        'https://www.gstatic.com/firebasejs/8.10.1/firebase-auth.js',
        'https://www.gstatic.com/firebasejs/8.10.1/firebase-storage.js',
        'js/firebase-config.js',
        'js/config.js',
        'js/auth.js',
        'js/coins.js',
        'js/coins-shim.js',
      ];

      for (const src of scripts) {
        await loadScript(src);
      }

      if (typeof firebase === 'undefined' || !firebase.apps || !firebase.apps.length) {
        throw new Error('Firebase did not initialize. Check your connection and refresh.');
      }

      window.NoteShareReady = true;
      window.dispatchEvent(new Event('noteshare-ready'));

      await loadScript('js/page-init.js').catch(function (e) {
        console.warn('page-init:', e.message);
      });
    })().catch(function (err) {
      injectPromise = null;
      console.error('NoteShare boot failed:', err);
      window.dispatchEvent(new CustomEvent('noteshare-error', { detail: err }));
      throw err;
    });

    return injectPromise;
  }

  function isAppReady() {
    return !!(
      window.NoteShareAuth &&
      typeof firebase !== 'undefined' &&
      firebase.apps &&
      firebase.apps.length
    );
  }

  function waitForApp(timeoutMs) {
    timeoutMs = timeoutMs || 30000;
    if (isAppReady()) return Promise.resolve();

    return new Promise(function (resolve, reject) {
      function done() {
        if (isAppReady()) resolve();
      }

      window.addEventListener('noteshare-ready', done, { once: true });
      window.addEventListener(
        'noteshare-error',
        function (e) {
          reject(e.detail || new Error('Failed to load the app'));
        },
        { once: true }
      );

      const poll = setInterval(done, 50);

      setTimeout(function () {
        clearInterval(poll);
        if (isAppReady()) {
          resolve();
          return;
        }
        injectHeadAssets()
          .then(function () {
            if (isAppReady()) resolve();
            else {
              reject(
                new Error(
                  'Could not start the app. Open http://localhost:3000/login.html (run npm run dev) and check the browser console (F12).'
                )
              );
            }
          })
          .catch(function (err) {
            reject(
              err.message
                ? err
                : new Error(
                    'Could not start the app. Open http://localhost:3000/login.html (run npm run dev).'
                  )
            );
          });
      }, timeoutMs);
    });
  }

  document.addEventListener('DOMContentLoaded', async function () {
    try {
      await injectHeadAssets();
    } catch (e) {
      console.error(e);
    }

    const headerEl = document.getElementById('app-header');
    if (headerEl) headerEl.innerHTML = renderHeader();

    const footerEl = document.getElementById('app-footer');
    if (footerEl) footerEl.innerHTML = renderFooter();

    document.getElementById('logout-link')?.addEventListener('click', function (e) {
      e.preventDefault();
      window.NoteShareAuth.logout();
    });

    if (window.NoteShareAuth) window.NoteShareAuth.initNavGlobals();
  });

  window.NoteShareNav = { renderHeader, renderFooter };
  window.NoteShareBoot = { injectHeadAssets, waitForApp, isAppReady };
})();
