// Firebase Configuration (SDK v8 — must match your Firebase Console project)
const firebaseConfig = {
  apiKey: 'AIzaSyDZyyejjFwpWn6uZdlQpW8xO_o6vtCL4rE',
  authDomain: 'noteshare-3.firebaseapp.com',
  databaseURL: 'https://noteshare-3-default-rtdb.firebaseio.com',
  projectId: 'noteshare-3',
  storageBucket: 'noteshare-3.firebasestorage.app',
  messagingSenderId: '147584632956',
  appId: '1:147584632956:web:54f02157d6e298082f61f9',
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
