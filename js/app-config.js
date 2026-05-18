/** App-wide constants (replaces PHP firebase_config.php) */
const APP_CONFIG = {
  APP_NAME: 'NoteShare',
  COIN_EXCHANGE_RATE: 0.1,
  MIN_COINS_TO_MESSAGE: 1,
  COIN_PACKAGES: [
    { coins: 100, price: 10 },
    { coins: 500, price: 45 },
    { coins: 1000, price: 80 },
    { coins: 5000, price: 350 },
  ],
  ADMIN_EMAIL: '', // set via window.__ADMIN_EMAIL__ in admin pages if needed
};
