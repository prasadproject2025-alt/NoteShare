// Firebase Configuration (SDK v8 — loaded by nav.js)
const firebaseConfig = {
  apiKey: 'AIzaSyBhnmxrk0feR-4IIMIPPQKTSZTNzRXz__Y',
  authDomain: 'notes-sharing-6a8b2.firebaseapp.com',
  databaseURL: 'https://notes-sharing-6a8b2-default-rtdb.firebaseio.com',
  projectId: 'notes-sharing-6a8b2',
  storageBucket: 'notes-sharing-6a8b2.appspot.com',
  messagingSenderId: '172945409962',
  appId: '1:172945409962:web:38481eaf0140bde7ac8dd3',
};

if (typeof firebase !== 'undefined') {
  try {
    if (!firebase.apps || firebase.apps.length === 0) {
      firebase.initializeApp(firebaseConfig);
    }
    firebase.auth();
    firebase.database();
  } catch (error) {
    console.error('Firebase initialization error:', error);
  }
}
