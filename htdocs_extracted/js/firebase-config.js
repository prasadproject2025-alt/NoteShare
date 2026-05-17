// Firebase Configuration
const firebaseConfig = {
    apiKey: "AIzaSyBhnmxrk0feR-4IIMIPPQKTSZTNzRXz__Y",
    authDomain: "notes-sharing-6a8b2.firebaseapp.com",
    databaseURL: "https://notes-sharing-6a8b2-default-rtdb.firebaseio.com",
    projectId: "notes-sharing-6a8b2",
    storageBucket: "notes-sharing-6a8b2.appspot.com",
    messagingSenderId: "172945409962",
    appId: "1:172945409962:web:38481eaf0140bde7ac8dd3",
    measurementId: "172945409962"
};

// Initialize Firebase
if (typeof firebase !== 'undefined') {
    try {
        // Check if already initialized
        if (!firebase.apps || firebase.apps.length === 0) {
            firebase.initializeApp(firebaseConfig);
            console.log('Firebase initialized successfully');
        } else {
            console.log('Firebase already initialized');
        }
        
        // Initialize Firebase Auth
        const auth = firebase.auth();
        
        // Sign in anonymously for database access
        // Note: Anonymous auth must be enabled in Firebase Console
        auth.signInAnonymously().then(() => {
            console.log('Firebase anonymous authentication successful');
        }).catch((error) => {
            console.warn('Firebase anonymous authentication failed:', error);
            console.warn('Note: Anonymous authentication may not be enabled. Data may still save if Firebase rules allow.');
        });
        
        // Get database reference
        const database = firebase.database();
        console.log('Firebase database reference ready');
    } catch (error) {
        console.error('Firebase initialization error:', error);
    }
} else {
    console.error('Firebase SDK not loaded!');
}
