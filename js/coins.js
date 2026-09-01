/**
 * Coin operations — replaces get_user_coins.html & update_user_coins.html
 */
(function (global) {
  const NoteShareCoins = {
    async getBalance(userId) {
      userId = userId || window.NoteShareAuth?.getUserId();
      if (!userId) return 0;
      try {
        const snap = await firebase.database().ref('users/' + userId + '/coins').once('value');
        const val = snap.val();
        if (val !== null && val !== undefined && typeof val === 'number') return val;
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
      if (!userId || amount <= 0) {
        return { success: false, message: 'Invalid request' };
      }
      const ref = firebase.database().ref('users/' + userId);
      let current = 10;
      try {
        const snap = await ref.once('value');
        const val = snap.val();
        if (val && typeof val.coins === 'number') {
          current = val.coins;
        } else {
          const session = window.NoteShareAuth?.getSession();
          if (typeof session?.user_coins === 'number') {
            current = session.user_coins;
          }
        }
      } catch (e) {
        const session = window.NoteShareAuth?.getSession();
        if (typeof session?.user_coins === 'number') {
          current = session.user_coins;
        }
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
      try {
        await ref.update({ coins: next });
      } catch (e) {
        console.warn('Failed to update DB coins:', e);
      }
      const session = window.NoteShareAuth?.getSession();
      if (session) {
        session.user_coins = next;
        window.NoteShareAuth.setSession(session);
      }
      const el = document.getElementById('user-coins-count');
      if (el) el.textContent = next;
      if (description) {
        try {
          firebase.database().ref('coin_transactions').push({
            user_id: userId,
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
