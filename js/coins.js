/**
 * Coin operations — replaces get_user_coins.html & update_user_coins.html
 */
(function (global) {
  const NoteShareCoins = {
    async getBalance(userId) {
      const email = (window.NoteShareAuth?.getUserEmail() || '').toLowerCase().trim();
      const userMd5 = email ? (window.NoteShareAuth?.md5?.(email) || '') : '';
      userId = userId || window.NoteShareAuth?.getUserId() || userMd5;
      if (!userId && !userMd5) return 0;

      try {
        if (typeof firebase !== 'undefined' && firebase.apps && firebase.apps.length && typeof firebase.database === 'function') {
          let snap = null;
          if (userId) {
            snap = await firebase.database().ref('users/' + userId + '/coins').once('value').catch(() => null);
          }
          if ((!snap || !snap.exists() || snap.val() === null) && userMd5) {
            snap = await firebase.database().ref('users/' + userMd5 + '/coins').once('value').catch(() => null);
          }
          if (snap && snap.exists()) {
            const val = snap.val();
            if (typeof val === 'number') {
              const session = window.NoteShareAuth?.getSession();
              if (session && session.user_coins !== val) {
                session.user_coins = val;
                window.NoteShareAuth.setSession(session);
              }
              return val;
            }
          }
        }
      } catch (e) {}

      const session = window.NoteShareAuth?.getSession();
      return typeof session?.user_coins === 'number' ? session.user_coins : 10;
    },

    async getBalanceInfo() {
      const userId = window.NoteShareAuth?.getUserId();
      if (!userId) return { success: false, coins: 0 };
      const coins = await this.getBalance(userId);
      return {
        success: true,
        coins,
        name: window.NoteShareAuth?.getUserName() || 'User',
        email: window.NoteShareAuth?.getUserEmail() || '',
      };
    },

    async updateCoins(action, amount, description) {
      const userId = window.NoteShareAuth?.getUserId();
      const email = (window.NoteShareAuth?.getUserEmail() || '').toLowerCase().trim();
      const userMd5 = email ? (window.NoteShareAuth?.md5?.(email) || '') : '';

      if (!userId && !userMd5) {
        return { success: false, message: 'Invalid request' };
      }
      if (amount <= 0) {
        return { success: false, message: 'Invalid coin amount' };
      }

      let current = 10;
      try {
        if (typeof firebase !== 'undefined' && firebase.apps && firebase.apps.length && typeof firebase.database === 'function') {
          let snap = null;
          if (userId) {
            snap = await firebase.database().ref('users/' + userId).once('value').catch(() => null);
          }
          if ((!snap || !snap.exists()) && userMd5) {
            snap = await firebase.database().ref('users/' + userMd5).once('value').catch(() => null);
          }
          if (snap && snap.exists()) {
            const val = snap.val();
            if (val && typeof val.coins === 'number') {
              current = val.coins;
            } else {
              const session = window.NoteShareAuth?.getSession();
              if (typeof session?.user_coins === 'number') current = session.user_coins;
            }
          } else {
            const session = window.NoteShareAuth?.getSession();
            if (typeof session?.user_coins === 'number') current = session.user_coins;
          }
        } else {
          const session = window.NoteShareAuth?.getSession();
          if (typeof session?.user_coins === 'number') current = session.user_coins;
        }
      } catch (e) {
        const session = window.NoteShareAuth?.getSession();
        if (typeof session?.user_coins === 'number') current = session.user_coins;
      }

      let next = current;
      if (action === 'deduct') {
        if (current < amount) {
          return { success: false, message: 'Insufficient coins', coins: current };
        }
        next = current - amount;
      } else if (action === 'add') {
        next = current + amount;
      } else {
        return { success: false, message: 'Invalid action' };
      }

      // 1. Update Cloud Realtime Database across all matching nodes
      try {
        if (typeof firebase !== 'undefined' && firebase.apps && firebase.apps.length && typeof firebase.database === 'function') {
          const db = firebase.database();
          const updates = { coins: next };
          if (email) updates.email = email;

          if (userId) await db.ref('users/' + userId).update(updates).catch(() => {});
          if (userMd5 && userMd5 !== userId) await db.ref('users/' + userMd5).update(updates).catch(() => {});

          if (email) {
            const snap = await db.ref('users').once('value').catch(() => null);
            if (snap && snap.exists()) {
              snap.forEach(child => {
                const val = child.val();
                if (val && val.email && val.email.toLowerCase().trim() === email) {
                  db.ref('users/' + child.key).update(updates).catch(() => {});
                }
              });
            }
          }
        }
      } catch (e) {
        console.warn('Failed to update DB coins:', e);
      }

      // 2. Update local session storage
      const session = window.NoteShareAuth?.getSession();
      if (session) {
        session.user_coins = next;
        window.NoteShareAuth.setSession(session);
      }

      // 3. Update registered users map in localStorage
      try {
        const regMap = JSON.parse(localStorage.getItem('noteshare_registered_users') || '{}');
        if (email && regMap[email]) {
          regMap[email].coins = next;
          localStorage.setItem('noteshare_registered_users', JSON.stringify(regMap));
        }
      } catch (e) {}

      const el = document.getElementById('user-coins-count');
      if (el) el.textContent = next;

      // 4. Sync updated balance to server data/users.json for admin dashboard
      if (email) {
        try {
          fetch('/api/admin-update-user', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, coins: next })
          }).catch(() => {});
        } catch (e) {}
      }

      if (description) {
        try {
          firebase.database().ref('coin_transactions').push({
            user_id: userId || userMd5,
            email: email,
            action,
            coins: amount,
            description,
            timestamp: Date.now(),
          });
        } catch (e) {}
      }
      return { success: true, coins: next, message: 'Coins updated successfully' };
    },
  };

  global.NoteShareCoins = NoteShareCoins;
})(window);
