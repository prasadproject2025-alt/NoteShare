/**
 * Authentication — replaces PHP sessions + auth/*.php
 * Uses Firebase Auth + Realtime Database (legacy user id = md5(email))
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

  async function syncUserToDatabase(user, extra) {
    const userId = md5(user.email);
    const name = extra?.name || user.displayName || user.email.split('@')[0];
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
    } else {
      await ref.update({ firebase_uid: user.uid, email: user.email });
    }
    setSession({
      user_id: userId,
      user_email: user.email,
      user_name: name,
      user_coins: snap.val()?.coins ?? 10,
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
      if (name) await cred.user.updateProfile({ displayName: name });
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

    async restoreFromFirebase() {
      try {
        const auth = await waitForFirebaseAuth();
        const user = auth.currentUser;
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
