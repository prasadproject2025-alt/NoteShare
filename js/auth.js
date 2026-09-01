/**
 * Authentication — Firebase Auth + local session
 */
(function (global) {
  const SESSION_KEY = 'noteshare_session';

  function md5(string) {
    if (typeof window.md5 === 'function') return window.md5(string);
    throw new Error('MD5 library not loaded');
  }

  const LOGGED_OUT_KEY = 'noteshare_logged_out';
  const ATTEMPTS_KEY = 'noteshare_login_attempts';
  const MAX_FAILED_ATTEMPTS = 5;

  function getAttemptsMap() {
    try {
      return JSON.parse(localStorage.getItem(ATTEMPTS_KEY) || '{}');
    } catch {
      return {};
    }
  }

  function saveAttemptsMap(map) {
    try {
      localStorage.setItem(ATTEMPTS_KEY, JSON.stringify(map));
    } catch {}
  }

  const ACTIVITY_KEY = 'noteshare_user_activity';
  const recentTimestampsMap = {};

  function getActivityMap() {
    try {
      return JSON.parse(localStorage.getItem(ACTIVITY_KEY) || '{}');
    } catch {
      return {};
    }
  }

  function saveActivityMap(map) {
    try {
      localStorage.setItem(ACTIVITY_KEY, JSON.stringify(map));
    } catch {}
  }

  function recordUserActivity(email, actionType, detail, extra) {
    if (!email) return;
    const cleanEmail = email.toLowerCase().trim();
    let md5Key = '';
    try {
      md5Key = md5(cleanEmail);
    } catch (e) {
      md5Key = cleanEmail.replace(/[^a-zA-Z0-9]/g, '_');
    }

    const map = getActivityMap();
    const current = map[cleanEmail] || { requests_count: 0, actions: [], last_active: Date.now(), failed_logins: 0 };

    current.requests_count = (current.requests_count || 0) + 1;
    current.last_active = Date.now();

    // Calculate real-time request rate (requests in the last 60 seconds)
    if (!recentTimestampsMap[cleanEmail]) recentTimestampsMap[cleanEmail] = [];
    const now = Date.now();
    recentTimestampsMap[cleanEmail].push(now);
    recentTimestampsMap[cleanEmail] = recentTimestampsMap[cleanEmail].filter(t => now - t <= 60000);
    const requestsPerMin = recentTimestampsMap[cleanEmail].length;
    current.requests_per_min = requestsPerMin;

    if (extra && extra.isFailedLogin) {
      current.failed_logins = (current.failed_logins || 0) + 1;
    }

    // Determine live threat level
    let threatLevel = 'normal';
    if (current.failed_logins >= 4) {
      threatLevel = 'brute_force';
    } else if (requestsPerMin >= 30) {
      threatLevel = 'rate_flooding';
    } else if (current.requests_count > 400 || requestsPerMin >= 15) {
      threatLevel = 'suspicious';
    } else if (current.requests_count > 200) {
      threatLevel = 'moderate';
    }
    current.risk = threatLevel;

    if (!current.actions) current.actions = [];
    const newAction = {
      timestamp: new Date().toLocaleTimeString(),
      date: new Date().toLocaleDateString(),
      time_ms: now,
      type: actionType || 'API Request',
      detail: detail || (window.location.pathname.split('/').pop() || 'Page Interaction'),
      rate_per_min: requestsPerMin,
      threat: threatLevel
    };

    current.actions.unshift(newAction);
    if (current.actions.length > 30) {
      current.actions = current.actions.slice(0, 30);
    }

    map[cleanEmail] = current;
    saveActivityMap(map);

    // 1. Synchronize to Firebase Realtime Database in REAL TIME
    try {
      if (typeof firebase !== 'undefined' && firebase.database) {
        const db = firebase.database();
        const payload = {
          email: cleanEmail,
          requests_count: current.requests_count,
          requests_per_min: requestsPerMin,
          last_active: now,
          failed_logins: current.failed_logins || 0,
          risk: threatLevel,
          latest_action: newAction
        };

        db.ref('user_activity/' + md5Key).update(payload).catch(() => {});
        db.ref('users/' + md5Key).update({
          requests_count: current.requests_count,
          last_active: now,
          risk: threatLevel
        }).catch(() => {});

        // If high risk or brute force attack, record in global security logs
        if (threatLevel === 'brute_force' || threatLevel === 'rate_flooding' || threatLevel === 'suspicious') {
          db.ref('security_logs').push({
            email: cleanEmail,
            threat: threatLevel,
            action: newAction,
            timestamp: now
          }).catch(() => {});
        }
      }
    } catch (e) {}

    // 2. Post to server background traffic API
    try {
      fetch('/api/record-traffic', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          email: cleanEmail,
          actionType: actionType || 'API Request',
          detail: detail || window.location.pathname,
          isFailedLogin: !!(extra && extra.isFailedLogin),
          path: window.location.pathname
        })
      }).catch(() => {});
    } catch (e) {}

    return current;
  }

  function getUserActivity(email) {
    if (!email) return { requests_count: 0, actions: [], requests_per_min: 0, risk: 'normal' };
    const key = email.toLowerCase().trim();
    const map = getActivityMap();
    return map[key] || { requests_count: 0, actions: [], requests_per_min: 0, risk: 'normal' };
  }

  function isAccountLocked(email) {
    if (!email) return false;
    const map = getAttemptsMap();
    const entry = map[email.toLowerCase().trim()];
    return !!(entry && entry.locked);
  }

  function recordFailedAttempt(email) {
    if (!email) return { count: 0, locked: false };
    const key = email.toLowerCase().trim();
    const map = getAttemptsMap();
    const current = map[key] || { count: 0, locked: false };
    current.count = (current.count || 0) + 1;
    if (current.count >= MAX_FAILED_ATTEMPTS) {
      current.locked = true;
    }
    current.last_failed = Date.now();
    map[key] = current;
    saveAttemptsMap(map);

    // Also trigger live activity & security telemetry
    recordUserActivity(key, 'Failed Login Attempt', `Attempt ${current.count} of ${MAX_FAILED_ATTEMPTS}`, { isFailedLogin: true });

    return current;
  }

  function clearFailedAttempts(email) {
    if (!email) return;
    const key = email.toLowerCase().trim();
    const map = getAttemptsMap();
    delete map[key];
    saveAttemptsMap(map);

    // Clear failed logins in live activity map as well
    const actMap = getActivityMap();
    if (actMap[key]) {
      actMap[key].failed_logins = 0;
      actMap[key].risk = 'normal';
      saveActivityMap(actMap);
    }
    try {
      if (typeof firebase !== 'undefined' && firebase.database) {
        const md5Key = md5(key);
        firebase.database().ref('user_activity/' + md5Key).update({ failed_logins: 0, risk: 'normal' }).catch(() => {});
        firebase.database().ref('users/' + md5Key).update({ failed_logins: 0, risk: 'normal', blocked: false }).catch(() => {});
      }
    } catch (e) {}
  }

  function getSession() {
    try {
      if (localStorage.getItem(LOGGED_OUT_KEY) === 'true') {
        return null;
      }
      const sess = JSON.parse(localStorage.getItem(SESSION_KEY) || 'null');
      if (sess && sess.user_id) return sess;
      return null;
    } catch {
      return null;
    }
  }

  function setSession(data) {
    localStorage.removeItem(LOGGED_OUT_KEY);
    localStorage.setItem(SESSION_KEY, JSON.stringify(data));
  }

  function clearSession() {
    localStorage.removeItem(SESSION_KEY);
    localStorage.setItem(LOGGED_OUT_KEY, 'true');
    sessionStorage.removeItem('noteshare_admin');
    window.userId = '';
    window.userEmail = '';
    window.userName = '';
  }

  function isVitEmail(email) {
    return email && email.endsWith('@vitstudent.ac.in');
  }

  async function waitForFirebaseAuth() {
    return new Promise((resolve, reject) => {
      let attempts = 0;
      const tick = () => {
        if (typeof firebase !== 'undefined' && firebase.apps && firebase.apps.length) {
          resolve(firebase.auth());
          return;
        }
        if (++attempts > 100) {
          reject(new Error('Firebase not loaded'));
          return;
        }
        setTimeout(tick, 100);
      };
      tick();
    });
  }

  /** Wait until Firebase has finished restoring persisted auth (avoids false redirects). */
  async function waitForAuthState(timeoutMs = 4000) {
    const auth = await waitForFirebaseAuth();
    return new Promise((resolve) => {
      let settled = false;
      const finish = (user) => {
        if (settled) return;
        settled = true;
        resolve(user || null);
      };
      const unsub = auth.onAuthStateChanged((user) => {
        unsub();
        finish(user);
      });
      setTimeout(() => {
        unsub();
        finish(auth.currentUser);
      }, timeoutMs);
    });
  }

  async function syncUserToDatabase(user, extra) {
    const userId = md5(user.email);
    const name = extra?.name || user.displayName || user.email.split('@')[0];
    let coins = 10;

    try {
      const ref = firebase.database().ref('users/' + userId);
      const snap = await ref.once('value');
      if (!snap.exists()) {
        await ref.set({
          email: user.email,
          name,
          coins: 10,
          created_at: new Date().toISOString(),
          status: 'active',
          firebase_uid: user.uid,
        });
        coins = 10;
      } else {
        const val = snap.val();
        coins = val?.coins ?? 10;
        try {
          await ref.update({ firebase_uid: user.uid, email: user.email });
        } catch (e) {
          console.warn('Profile update skipped:', e.message);
        }
      }
    } catch (err) {
      console.warn('Database sync failed (login still OK):', err.message);
    }

    try {
      const regUsersMap = JSON.parse(localStorage.getItem('noteshare_registered_users') || '{}');
      regUsersMap[user.email.toLowerCase().trim()] = {
        id: userId,
        email: user.email,
        name,
        coins,
        created_at: new Date().toISOString(),
        status: 'active',
        firebase_uid: user.uid
      };
      localStorage.setItem('noteshare_registered_users', JSON.stringify(regUsersMap));
    } catch (e) {}

    // Also register in server data/users.json so admin dashboard can see all users
    try {
      fetch('/api/admin-update-user', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          email: user.email,
          name,
          coins,
          firebase_uid: user.uid,
          status: 'active'
        })
      }).catch(() => {});
    } catch (e) {}

    setSession({
      user_id: userId,
      user_email: user.email,
      user_name: name,
      user_coins: coins,
      firebase_uid: user.uid,
    });
    return userId;
  }

  const NoteShareAuth = {
    md5,
    getSession,
    setSession,
    clearSession,
    isVitEmail,
    isAccountLocked,
    recordFailedAttempt,
    clearFailedAttempts,
    getUserActivity,
    recordUserActivity,

    getUserId() {
      return getSession()?.user_id || null;
    },
    getUserEmail() {
      return getSession()?.user_email || null;
    },
    getUserName() {
      return getSession()?.user_name || 'User';
    },
    isLoggedIn() {
      return !!getSession()?.user_id;
    },

    async login(email, password) {
      const cleanEmail = (email || '').toLowerCase().trim();
      if (!isVitEmail(cleanEmail)) throw new Error('Please use your VIT student email (@vitstudent.ac.in)');

      // Integrated Admin Login Check
      if (cleanEmail === 'privateprasad@vitstudent.ac.in' && password === '#Prasad0428') {
        clearFailedAttempts(cleanEmail);
        this.setAdmin('privateprasad@vitstudent.ac.in');
        
        let firebaseUid = 'vit-admin-prasad';
        try {
          const auth = await waitForFirebaseAuth();
          let cred = null;
          try {
            cred = await auth.signInWithEmailAndPassword(cleanEmail, password);
          } catch (e) {
            try {
              cred = await auth.createUserWithEmailAndPassword(cleanEmail, password);
            } catch (e2) {
              if (auth.currentUser) firebaseUid = auth.currentUser.uid;
            }
          }
          if (cred && cred.user) firebaseUid = cred.user.uid;
          
          if (typeof firebase !== 'undefined' && firebase.database) {
            const db = firebase.database();
            const adminMd5 = md5(cleanEmail);
            await db.ref('admins/' + firebaseUid).set({
              email: cleanEmail,
              name: 'Prasad (Admin)',
              role: 'superadmin',
              granted_at: new Date().toISOString()
            }).catch(() => {});
            await db.ref('admins/' + adminMd5).set({
              email: cleanEmail,
              name: 'Prasad (Admin)',
              role: 'superadmin',
              granted_at: new Date().toISOString()
            }).catch(() => {});
            await db.ref('users/' + adminMd5).update({
              email: cleanEmail,
              name: 'Prasad (Admin)',
              coins: 9999,
              role: 'admin',
              status: 'active',
              firebase_uid: firebaseUid
            }).catch(() => {});
          }
        } catch (e) {
          console.warn('Firebase admin sync notice:', e);
        }

        const adminSession = {
          user_id: md5(cleanEmail),
          user_email: 'privateprasad@vitstudent.ac.in',
          user_name: 'Prasad (Admin)',
          user_coins: 9999,
          firebase_uid: firebaseUid,
          isAdmin: true,
          role: 'admin'
        };
        setSession(adminSession);
        return { isAdmin: true, email: cleanEmail };
      }

      if (isAccountLocked(cleanEmail)) {
        throw new Error('Your account is locked due to 5 consecutive failed login attempts. Please reset your password using "Forgot Password?" to unlock your account.');
      }

      const auth = await waitForFirebaseAuth();
      try {
        const cred = await auth.signInWithEmailAndPassword(cleanEmail, password);
        clearFailedAttempts(cleanEmail);
        await syncUserToDatabase(cred.user);
        return cred.user;
      } catch (err) {
        const errStr = (err.code || '') + ' ' + (err.message || '');
        const isCredentialErr =
          err.code === 'auth/wrong-password' ||
          err.code === 'auth/user-not-found' ||
          err.code === 'auth/invalid-credential' ||
          err.code === 'auth/invalid-email' ||
          errStr.includes('INVALID_LOGIN_CREDENTIALS') ||
          errStr.includes('EMAIL_NOT_FOUND') ||
          errStr.includes('INVALID_PASSWORD') ||
          errStr.includes('400');

        if (isCredentialErr) {
          const res = recordFailedAttempt(cleanEmail);
          if (res.locked) {
            throw new Error('Account locked! You have entered an incorrect password 5 times. You must reset your password using "Forgot Password?" to unlock your account.');
          } else {
            const remaining = MAX_FAILED_ATTEMPTS - res.count;
            throw new Error(`Invalid user or incorrect password. Attempt ${res.count} of ${MAX_FAILED_ATTEMPTS} (${remaining} left).`);
          }
        }
        throw err;
      }
    },

    async register(email, password, name) {
      const cleanEmail = (email || '').toLowerCase().trim();
      if (!isVitEmail(cleanEmail)) throw new Error('Please use your VIT student email (@vitstudent.ac.in)');
      const auth = await waitForFirebaseAuth();
      const cred = await auth.createUserWithEmailAndPassword(cleanEmail, password);
      if (name) {
        try {
          await cred.user.updateProfile({ displayName: name });
        } catch (e) {
          console.warn(e);
        }
      }
      await syncUserToDatabase(cred.user, { name: name || cleanEmail.split('@')[0] });
      return cred.user;
    },

    async sendPasswordReset(email) {
      const cleanEmail = (email || '').toLowerCase().trim();
      if (!isVitEmail(cleanEmail)) throw new Error('Please use your VIT student email (@vitstudent.ac.in)');
      try {
        const auth = await waitForFirebaseAuth();
        await auth.sendPasswordResetEmail(cleanEmail);
        clearFailedAttempts(cleanEmail); // Reset failed attempts count & unlock account
        return true;
      } catch (err) {
        if (err.code === 'auth/user-not-found') {
          throw new Error('No account found with this email address.');
        } else if (err.code === 'auth/invalid-email') {
          throw new Error('Invalid email format.');
        }
        throw new Error(err.message || 'Failed to send password reset email.');
      }
    },

    async logout() {
      try {
        if (typeof firebase !== 'undefined' && firebase.apps && firebase.apps.length) {
          const auth = firebase.auth();
          await auth.signOut();
        }
      } catch (e) {
        console.warn(e);
      }
      clearSession();
      window.location.replace('login.html');
    },

    requireAuth(redirectTo) {
      if (!NoteShareAuth.isLoggedIn()) {
        window.location.replace(redirectTo || 'login.html');
        return false;
      }
      return true;
    },

    checkAuthGuard() {
      const rawPath = window.location.pathname.split('/').pop() || 'index.html';
      const page = rawPath.toLowerCase();

      const isGuestPage = page === 'login.html' || page === 'create-account.html' || page === 'admin-login.html';
      const isAdminPage = page === 'admin-dashboard.html';

      const loggedIn = NoteShareAuth.isLoggedIn();
      const isAdmin = NoteShareAuth.isAdmin();

      if (isGuestPage) {
        if (isAdmin) {
          window.location.replace('admin-dashboard.html');
        } else if (loggedIn) {
          window.location.replace('index.html');
        }
      } else if (isAdminPage) {
        if (!isAdmin) {
          window.location.replace('login.html');
        }
      } else {
        // Protected student pages
        if (!loggedIn && !isAdmin) {
          window.location.replace('login.html');
        }
      }
    },

    waitForAuthState,

    async restoreFromFirebase() {
      try {
        const user = await waitForAuthState();
        if (user && user.email) {
          await syncUserToDatabase(user);
          return true;
        }
      } catch (e) {
        console.warn('restoreFromFirebase', e);
      }
      return false;
    },

    initNavGlobals() {
      const s = getSession();
      window.userId = s?.user_id || '';
      window.userEmail = s?.user_email || '';
      window.userName = s?.user_name || '';
    },

    isAdmin() {
      const admin = JSON.parse(sessionStorage.getItem('noteshare_admin') || 'null');
      if (!admin) return false;
      if (Date.now() - admin.loginTime > 30 * 60 * 1000) {
        sessionStorage.removeItem('noteshare_admin');
        return false;
      }
      return true;
    },

    setAdmin(username) {
      sessionStorage.setItem(
        'noteshare_admin',
        JSON.stringify({ username, loginTime: Date.now() })
      );
    },

    clearAdmin() {
      sessionStorage.removeItem('noteshare_admin');
    },
  };

  global.NoteShareAuth = NoteShareAuth;
})(window);
