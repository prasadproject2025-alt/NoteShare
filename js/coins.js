/**
 * Coin operations â€” replaces get_user_coins.html & update_user_coins.html
 */
(function (global) {
  const NoteShareCoins = {
    async getBalance(userId) {
      userId = userId || window.NoteShareAuth?.getUserId();
      if (!userId) return 0;
      const snap = await firebase.database().ref('users/' + userId + '/coins').once('value');
      return snap.val() || 0;
    },

    async getBalanceInfo() {
      const userId = window.NoteShareAuth.getUserId();
      const snap = await firebase.database().ref('users/' + userId).once('value');
      const data = snap.val() || {};
      return {
        success: true,
        coins: data.coins || 0,
        name: data.name || window.NoteShareAuth.getUserName(),
        email: data.email || window.NoteShareAuth.getUserEmail(),
      };
    },

    async updateCoins(action, amount, description) {
      const userId = window.NoteShareAuth.getUserId();
      if (!userId || amount <= 0) {
        return { success: false, message: 'Invalid request' };
      }
      const ref = firebase.database().ref('users/' + userId);
      const snap = await ref.once('value');
      const current = (snap.val()?.coins) || 0;
      let next = current;
      if (action === 'deduct') {
        if (current < amount) {
          return { success: false, message: 'Insufficient coins' };
        }
        next = current - amount;
      } else if (action === 'add') {
        next = current + amount;
      } else {
        return { success: false, message: 'Invalid action' };
      }
      await ref.update({ coins: next });
      const session = window.NoteShareAuth.getSession();
      if (session) {
        session.user_coins = next;
        window.NoteShareAuth.setSession(session);
      }
      if (description) {
        firebase.database().ref('coin_transactions').push({
          user_id: userId,
          action,
          coins: amount,
          description,
          timestamp: Date.now(),
        });
      }
      return { success: true, coins: next, message: 'Coins updated successfully' };
    },
  };

  global.NoteShareCoins = NoteShareCoins;
})(window);

