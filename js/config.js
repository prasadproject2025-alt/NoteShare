/** App-wide constants (from PHP firebase_config.php) */
window.NoteShareConfig = {
  APP_NAME: 'NoteShare',
  COIN_EXCHANGE_RATE: 0.1,
  MIN_COINS_TO_MESSAGE: 1,
  COIN_PACKAGES: [
    { coins: 100, price: 10 },
    { coins: 500, price: 45 },
    { coins: 1000, price: 80 },
    { coins: 5000, price: 350 },
  ],
  MAX_FILE_SIZE: 5 * 1024 * 1024,
  MAX_IMAGES: 5,
  SESSION_KEY: 'noteshare_session',
  NOTE_DATA_KEY: 'noteshare_note_data',
  ADMIN_KEY: 'noteshare_admin',
};
