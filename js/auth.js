/**
 * Authentication — Firebase Auth + local session
 */
(function (global) {
  const SESSION_KEY = 'noteshare_session';

  function md5(string) {
    if (typeof window.md5 === 'function') return window.md5(string);
    throw new Error('MD5 library not loaded');
  }

  function getSession() {
    try {
      return JSON.parse(localStorage.getItem(SESSION_KEY) || 'null');
    } catch {
      return null;
    }
  }

  function setSession(data) {
    localStorage.setItem(SESSION_KEY, JSON.stringify(data));
  }

  function clearSession() {
    localStorage.removeItem(SESSION_KEY);
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
      if (!isVitEmail(email)) throw new Error('Please use your VIT student email (@vitstudent.ac.in)');
      const auth = await waitForFirebaseAuth();
      const cred = await auth.signInWithEmailAndPassword(email, password);
      await syncUserToDatabase(cred.user);
      return cred.user;
    },

    async register(email, password, name) {
      if (!isVitEmail(email)) throw new Error('Please use your VIT student email (@vitstudent.ac.in)');
      const auth = await waitForFirebaseAuth();
      const cred = await auth.createUserWithEmailAndPassword(email, password);
      if (name) {
        try {
          await cred.user.updateProfile({ displayName: name });
        } catch (e) {
          console.warn(e);
        }
      }
      await syncUserToDatabase(cred.user, { name: name || email.split('@')[0] });
      return cred.user;
    },

    async logout() {
      try {
        const auth = await waitForFirebaseAuth();
        await auth.signOut();
      } catch (e) {
        console.warn(e);
      }
      clearSession();
      window.location.href = 'login.html';
    },

    requireAuth(redirectTo) {
      if (!NoteShareAuth.isLoggedIn()) {
        window.location.href = redirectTo || 'login.html';
        return false;
      }
      return true;
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
