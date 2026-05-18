/** Replaces get_user_coins.html / update_user_coins.html fetch calls on legacy pages */
(function () {
  const origFetch = window.fetch;
  window.fetch = function (url, opts) {
    const u = typeof url === 'string' ? url : url?.url || '';
    if (u.includes('get_user_coins')) {
      return window.NoteShareCoins.getBalanceInfo().then((data) => ({
        ok: true,
        json: () => Promise.resolve(data),
      }));
    }
    if (u.includes('update_user_coins') && opts?.body) {
      const body = JSON.parse(opts.body);
      return window.NoteShareCoins.updateCoins(body.action, body.coins, body.description).then((data) => ({
        ok: true,
        json: () => Promise.resolve(data),
      }));
    }
    if (u.includes('clear_session')) {
      sessionStorage.removeItem('noteshare_note_data');
      return Promise.resolve({ ok: true, json: () => Promise.resolve({ success: true }) });
    }
    return origFetch.apply(this, arguments);
  };
})();

