/**
 * Shared navigation & footer — VTOP 2-tier header & pinned footer
 */
(function () {
  function escapeHtml(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
  }

  let unreadBadgeListenerAttached = false;

  function updateMessageBadges(count = 0) {
    const badge = document.getElementById('nav-notification-badge');
    const dashboardBadge = document.getElementById('dashboard-message-badge');
    const total = Number(count || 0);

    if (badge) {
      badge.textContent = total;
      badge.style.display = total > 0 ? 'inline-flex' : 'none';
    }

    if (dashboardBadge) {
      dashboardBadge.textContent = total;
      dashboardBadge.style.display = total > 0 ? 'inline-flex' : 'none';
    }
  }

  async function refreshMessageBadges() {
    try {
      const uid = window.NoteShareAuth && typeof window.NoteShareAuth.getUserId === 'function'
        ? window.NoteShareAuth.getUserId()
        : '';

      if (!uid || typeof firebase === 'undefined' || !firebase.database) {
        updateMessageBadges(0);
        return;
      }

      const snapshot = await firebase.database().ref('chats').once('value');
      let total = 0;
      snapshot.forEach(function (child) {
        const chat = child.val() || {};
        const isParticipant =
          chat.buyer_id === uid ||
          chat.seller_id === uid ||
          chat.user1_id === uid ||
          chat.user2_id === uid;

        if (isParticipant) {
          total += Number(chat.unread_count || 0);
        }
      });

      updateMessageBadges(total);
    } catch (err) {
      console.warn('Could not refresh unread message badges:', err);
      updateMessageBadges(0);
    }
  }

  function attachUnreadBadgeListener() {
    if (unreadBadgeListenerAttached || typeof firebase === 'undefined' || !firebase.database) return;

    unreadBadgeListenerAttached = true;
    firebase.database().ref('chats').on('value', function () {
      refreshMessageBadges();
    });
  }

  window.NoteShareUpdateMessageBadges = updateMessageBadges;
  window.NoteShareRefreshMessageBadges = refreshMessageBadges;

  function renderHeader() {
    const loggedIn = window.NoteShareAuth && window.NoteShareAuth.isLoggedIn();
    const name = (window.NoteShareAuth && window.NoteShareAuth.getUserName()) || '22MIS0428';
    const email = (window.NoteShareAuth && window.NoteShareAuth.getUserEmail()) || '';
    const regNo = email ? email.split('@')[0].toUpperCase() : (name || '22MIS0428').toUpperCase();
    const isAdmin = window.NoteShareAuth && window.NoteShareAuth.isAdmin();

    const userWidget =
      '<div class="dropdown">' +
      '<a class="vtop-user-pill dropdown-toggle" href="javascript:void(0);" role="button" onclick="window.toggleUserMenu(event);">' +
      '<img class="vtop-avatar-img" src="https://ui-avatars.com/api/?name=' + encodeURIComponent(name) + '&background=ffedd5&color=c2410c&bold=true&size=64" alt="avatar">' +
      '<span>' + escapeHtml(regNo) + ' (STUDENT)</span>' +
      '</a>' +
      '<ul class="dropdown-menu dropdown-menu-end shadow p-2" id="user-profile-dropdown-menu" style="min-width:240px;">' +
      '<li><a class="dropdown-item py-2" href="index.html"><i class="fas fa-th-large me-2 text-primary"></i>Dashboard</a></li>' +
      '<li><a class="dropdown-item py-2" href="profile.html"><i class="fas fa-id-card me-2 text-info"></i>My Profile</a></li>' +
      '<li><a class="dropdown-item py-2" href="edit-profile.html"><i class="fas fa-user-edit me-2 text-secondary"></i>Edit Profile</a></li>' +
      '<li><a class="dropdown-item py-2" href="my-notes.html"><i class="fas fa-folder-open me-2 text-warning"></i>My Uploaded Notes</a></li>' +
      (isAdmin ? '<li><a class="dropdown-item py-2 text-warning" href="admin-dashboard.html"><i class="fas fa-shield-alt me-2"></i>Admin Panel</a></li>' : '') +
      '<li><hr class="dropdown-divider"></li>' +
      '<li class="px-2"><div id="nav-uploaded-notes" style="max-height:180px; overflow:auto;"></div></li>' +
      '<li><hr class="dropdown-divider"></li>' +
      '<li><a class="dropdown-item text-danger py-2" href="javascript:void(0);" onclick="window.logoutUser(event);" id="logout-link"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>' +
      '</ul>' +
      '</div>';

    return (
      '<div>' +
      '<!-- Main Top Blue Header -->' +
      '<header class="vtop-exact-header">' +
      '<div class="container-fluid d-flex align-items-center justify-content-between px-3 flex-nowrap">' +
      
      '<!-- Left: VIT Logo + Brand + Quick Icons -->' +
      '<div class="d-flex align-items-center flex-nowrap">' +
      '<div class="vtop-brand-group flex-shrink-0">' +
      '<a class="d-flex align-items-center flex-nowrap text-decoration-none" href="javascript:void(0);" onclick="window.location.reload();" style="cursor: pointer;" title="Refresh Page">' +
      '<img class="vtop-logo me-2" src="https://vtop.vit.ac.in/vtop/assets/img/VITLogoEmblem.png" alt="VIT emblem" style="width:32px; height:32px; object-fit:contain; flex-shrink:0;" onerror="this.style.display=\'none\'; document.getElementById(\'nav-seal-icon\').style.display=\'flex\';">' +
      '<div id="nav-seal-icon" class="vtop-seal-icon me-2" style="display:none; flex-shrink:0;"><i class="fas fa-university"></i></div>' +
      '<span class="vtop-vit-wordmark me-1">VIT</span>' +
      '<span class="vtop-campus-text">(Vellore Campus)</span>' +
      '</a>' +
      '</div>' +
      '<div class="vtop-quick-icons d-none d-md-flex align-items-center">' +
      '<a href="index.html" class="vtop-icon-btn" title="Home"><i class="fas fa-home"></i></a>' +
      '<a href="javascript:window.print();" class="vtop-icon-btn" title="Print"><i class="fas fa-print"></i></a>' +
      '<a href="buy-notes.html" class="vtop-icon-btn" title="Favorites / Quick Notes"><i class="far fa-star"></i></a>' +
      '<div class="dropdown d-inline-block">' +
      '<a class="vtop-quick-links-btn dropdown-toggle" href="javascript:void(0);" role="button" onclick="window.toggleQuickLinksMenu(event);">' +
      'Quick Links' +
      '</a>' +
      '<ul class="dropdown-menu shadow" id="quick-links-dropdown-menu">' +
      '<li><a class="dropdown-item" href="buy-notes.html"><i class="fas fa-shopping-bag me-2 text-primary"></i>Buy Notes</a></li>' +
      '<li><a class="dropdown-item" href="sell-notes.html"><i class="fas fa-upload me-2 text-warning"></i>Sell Notes</a></li>' +
      '<li><a class="dropdown-item" href="share-notes.html"><i class="fas fa-share-alt me-2 text-success"></i>Share Notes</a></li>' +
      '<li><a class="dropdown-item" href="rent-notes.html"><i class="fas fa-clock me-2 text-info"></i>Rent Notes</a></li>' +
      '<li><a class="dropdown-item" href="coins.html"><i class="fas fa-coins me-2 text-secondary"></i>My Coins</a></li>' +
      '<li><a class="dropdown-item" href="messages.html"><i class="fas fa-comments me-2 text-primary"></i>Messages</a></li>' +
      '</ul>' +
      '</div>' +
      '</div>' +
      '</div>' +

      '<!-- Center: Website Etiquette Button -->' +
      '<div class="d-none d-lg-block text-center">' +
      '<button type="button" class="vtop-etiquette-btn border-0" data-bs-toggle="modal" data-bs-target="#websiteEtiquetteModal" style="cursor: pointer;">' +
      '<i class="far fa-smile me-1"></i> Website Etiquette' +
      '</button>' +
      '</div>' +

      '<!-- Right: Student User Widget -->' +
      '<div class="flex-shrink-0">' +
      userWidget +
      '</div>' +

      '</div>' +
      '</header>' +

      '<!-- Secondary VTOP Sub-Navbar / Module Breadcrumb -->' +
      '<nav class="vtop-subnav">' +
      '<div class="container-fluid d-flex align-items-center">' +
      '<ul class="vtop-subnav-items">' +
      '<li><a href="index.html" class="text-dark" title="Home"><i class="fas fa-bars"></i></a></li>' +
      '<li class="vtop-subnav-sep">&gt;</li>' +
      '<li><a href="buy-notes.html"><i class="fas fa-shopping-bag text-primary"></i> Buy Notes</a></li>' +
      '<li class="vtop-subnav-sep">&gt;</li>' +
      '<li><a href="sell-notes.html"><i class="fas fa-upload" style="color: #b78a00;"></i> Sell Notes</a></li>' +
      '<li class="vtop-subnav-sep">&gt;</li>' +
      '<li><a href="share-notes.html"><i class="fas fa-share-alt text-success"></i> Share Notes</a></li>' +
      '<li class="vtop-subnav-sep">&gt;</li>' +
      '<li><a href="rent-notes.html"><i class="fas fa-clock text-info"></i> Rent Notes</a></li>' +
      '<li class="vtop-subnav-sep">&gt;</li>' +
      '<li><a href="messages.html"><i class="fas fa-comments text-primary"></i> Messages</a></li>' +
      '<li class="vtop-subnav-sep">&gt;</li>' +
      '<li><a href="coins.html"><i class="fas fa-coins text-secondary"></i> Coins</a></li>' +
      '<li class="vtop-subnav-sep">&gt;</li>' +
      '<li><a href="feedback.html" class="text-dark"><i class="far fa-comment-dots text-primary"></i> Feedback</a></li>' +
      '</ul>' +
      '</div>' +
      '</nav>' +

      '<!-- Website Etiquette & Usage Guide Modal -->' +
      '<div class="modal fade" id="websiteEtiquetteModal" tabindex="-1" aria-labelledby="websiteEtiquetteModalLabel" aria-hidden="true">' +
      '<div class="modal-dialog modal-dialog-centered modal-lg">' +
      '<div class="modal-content shadow-lg border-0" style="border-radius: 8px; overflow: hidden;">' +
      '<div class="modal-header text-white" style="background: linear-gradient(90deg, #13428d 0%, #175aa8 55%, #188bb5 100%);">' +
      '<h5 class="modal-title fw-bold" id="websiteEtiquetteModalLabel">' +
      '<i class="fas fa-graduation-cap me-2"></i> NoteShare - Website Etiquette &amp; Usage Guide' +
      '</h5>' +
      '<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>' +
      '</div>' +
      '<div class="modal-body p-4" style="font-size: 0.92rem; color: #2d3748; line-height: 1.6;">' +
      '<div class="alert alert-primary py-2 px-3 mb-3 d-flex align-items-center" style="background: #eff6ff; border-color: #bfdbfe;">' +
      '<i class="fas fa-info-circle fa-lg text-primary me-2"></i>' +
      '<div><strong>Welcome to NoteShare!</strong> A collaborative platform designed exclusively for VITians to exchange academic resources, study notes, and exam prep materials.</div>' +
      '</div>' +

      '<div class="row g-3">' +
      '<div class="col-12 col-md-6">' +
      '<div class="card h-100 p-3 border" style="border-top: 3px solid #2563eb !important; background: #fafbff;">' +
      '<div class="fw-bold text-primary mb-1"><i class="fas fa-shopping-bag me-1"></i> 1. How to Buy Notes</div>' +
      '<p class="small text-muted mb-0">Browse through uploaded notes filtered by subject, semester, or course code. Preview note details and purchase instantly using your coin balance.</p>' +
      '</div>' +
      '</div>' +

      '<div class="col-12 col-md-6">' +
      '<div class="card h-100 p-3 border" style="border-top: 3px solid #d5a500 !important; background: #fffdf5;">' +
      '<div class="fw-bold mb-1" style="color: #b78a00;"><i class="fas fa-upload me-1"></i> 2. How to Sell Notes</div>' +
      '<p class="small text-muted mb-0">Upload your handwritten or typed PDF notes with accurate course codes. Whenever classmates buy your note, coins are credited directly to your wallet!</p>' +
      '</div>' +
      '</div>' +

      '<div class="col-12 col-md-6">' +
      '<div class="card h-100 p-3 border" style="border-top: 3px solid #15a04a !important; background: #f6fef9;">' +
      '<div class="fw-bold text-success mb-1"><i class="fas fa-share-alt me-1"></i> 3. Sharing &amp; Renting</div>' +
      '<p class="small text-muted mb-0">Share materials with your batch or rent notes temporarily for last-minute CAT/FAT exam preparations at minimal coin rates.</p>' +
      '</div>' +
      '</div>' +

      '<div class="col-12 col-md-6">' +
      '<div class="card h-100 p-3 border" style="border-top: 3px solid #0284c7 !important; background: #f0f9ff;">' +
      '<div class="fw-bold text-info mb-1" style="color: #0284c7 !important;"><i class="fas fa-comments me-1"></i> 4. Peer Messaging</div>' +
      '<p class="small text-muted mb-0">Use the built-in Messages tab to chat with note sellers or buyers in real-time regarding syllabus coverage or queries.</p>' +
      '</div>' +
      '</div>' +
      '</div>' +

      '<hr class="my-3">' +

      '<h6 class="fw-bold text-dark mb-2"><i class="fas fa-handshake me-1 text-primary"></i> Academic Integrity &amp; Rules</h6>' +
      '<ul class="small text-muted mb-0 ps-3">' +
      '<li><strong>Quality &amp; Legibility:</strong> Upload clear, legible notes that genuinely assist your fellow VITians in their coursework.</li>' +
      '<li><strong>Original Work:</strong> Only upload your own notes or materials you have permission to share. Do not distribute copyrighted textbook scans.</li>' +
      '<li><strong>Mutual Respect:</strong> Maintain respectful communication in chats and feedback at all times.</li>' +
      '<li><strong>Verified Student Access:</strong> All accounts and actions are tied to official <code>@vitstudent.ac.in</code> emails.</li>' +
      '</ul>' +
      '</div>' +
      '<div class="modal-footer bg-light py-2 px-3">' +
      '<button type="button" class="btn btn-primary btn-sm px-4 fw-bold" data-bs-dismiss="modal">Got it, Explore NoteShare!</button>' +
      '</div>' +
      '</div>' +
      '</div>' +
      '</div>' +
      '</div>'
    );
  }

  function renderFooter() {
    return (
      '<footer class="vtop-exact-footer text-center text-light py-2" style="background: linear-gradient(90deg, #13428d 0%, #175aa8 55%, #188bb5 100%); font-size: 0.8rem; margin-top: auto;">' +
      '<div class="container">' +
      '<span>Copyright &copy; 2026 Software Development Cell, VIT, Vellore-632 014.</span>' +
      '</div>' +
      '</footer>' +
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
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
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

  function initNav() {
    const headerEl = document.getElementById('app-header');
    if (headerEl) headerEl.innerHTML = renderHeader();

    const footerEl = document.getElementById('app-footer');
    if (footerEl) footerEl.innerHTML = renderFooter();

    injectHeadAssets().then(function () {
      if (headerEl) headerEl.innerHTML = renderHeader();
      attachUnreadBadgeListener();
      refreshMessageBadges();
      if (window.NoteShareAuth) window.NoteShareAuth.initNavGlobals();
    }).catch(function (e) {
      console.error('Asset injection error:', e);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initNav);
  } else {
    initNav();
  }

  window.logoutUser = async function(e) {
    if (e) {
      e.preventDefault();
      e.stopPropagation();
    }
    if (window.NoteShareAuth && typeof window.NoteShareAuth.logout === 'function') {
      await window.NoteShareAuth.logout();
    } else {
      localStorage.removeItem('noteshare_session');
      localStorage.setItem('noteshare_logged_out', 'true');
      if (typeof firebase !== 'undefined' && firebase.auth) {
        try { await firebase.auth().signOut(); } catch (_) {}
      }
      window.location.href = 'login.html';
    }
  };

  document.addEventListener('click', function (e) {
    if (e.target.closest('#logout-link')) {
      e.preventDefault();
      window.logoutUser(e);
    }
  });

  window.toggleUserMenu = function(e) {
    if (e) { e.preventDefault(); e.stopPropagation(); }
    const menu = document.getElementById('user-profile-dropdown-menu');
    if (!menu) return;
    const isShown = menu.classList.contains('show');
    document.querySelectorAll('.dropdown-menu.show').forEach(m => m.classList.remove('show'));
    if (!isShown) {
      menu.classList.add('show');
      window.loadNavUploadedNotes?.();
    }
  };

  window.toggleQuickLinksMenu = function(e) {
    if (e) { e.preventDefault(); e.stopPropagation(); }
    const menu = document.getElementById('quick-links-dropdown-menu');
    if (!menu) return;
    const isShown = menu.classList.contains('show');
    document.querySelectorAll('.dropdown-menu.show').forEach(m => m.classList.remove('show'));
    if (!isShown) menu.classList.add('show');
  };

  window.loadNavUploadedNotes = async function() {
    const container = document.getElementById('nav-uploaded-notes');
    if (!container || container.dataset.loaded === '1') return;
    container.innerHTML = '<p class="text-muted mb-1 small">Loading your notes...</p>';
    try {
      if (window.NoteShareBoot && typeof window.NoteShareBoot.waitForApp === 'function') {
        await window.NoteShareBoot.waitForApp();
      }
      if (!window.NoteShareAuth || !window.NoteShareAuth.isLoggedIn()) {
        container.innerHTML = '<p class="text-muted mb-1 small">Please login to see your notes.</p>';
        return;
      }
      const userId = window.NoteShareAuth.getUserId();
      const snap = await firebase.database().ref('notes').orderByChild('seller_id').equalTo(userId).limitToLast(5).once('value');
      let html = '';
      snap.forEach(s => {
        const n = s.val();
        const id = s.key;
        html += '<div class="d-flex align-items-center justify-content-between py-1 border-bottom">' +
          '<span class="small fw-semibold text-truncate me-2" style="max-width: 140px;">' + escapeHtml(n.subject_name || n.course_code || 'Note') + '</span>' +
          '<a class="btn btn-sm btn-link text-primary p-0" style="font-size: 11px;" href="buy-notes.html?note_id=' + id + '">View</a>' +
          '</div>';
      });
      if (!html) html = '<p class="text-muted mb-0 small">No uploaded notes yet.</p>';
      container.innerHTML = html;
      container.dataset.loaded = '1';
    } catch (err) {
      container.innerHTML = '<p class="text-muted mb-0 small">Could not load notes</p>';
    }
  };

  document.addEventListener('click', function(e) {
    if (!e.target.closest('.dropdown-menu') && !e.target.closest('.dropdown-toggle')) {
      document.querySelectorAll('.dropdown-menu.show').forEach(m => m.classList.remove('show'));
    }
  });

  window.NoteShareNav = { renderHeader, renderFooter };
  window.NoteShareBoot = { injectHeadAssets, waitForApp, isAppReady };
})();
