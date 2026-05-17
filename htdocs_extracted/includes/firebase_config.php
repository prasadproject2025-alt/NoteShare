<?php
/**
 * Firebase Configuration File
 * 
 * This file contains database connection and Firebase initialization
 * In production, use environment variables instead of hardcoding credentials
 */

// Database configuration
define('FIREBASE_API_KEY', 'AIzaSyBhnmxrk0feR-4IIMIPPQKTSZTNzRXz__Y');
define('FIREBASE_AUTH_DOMAIN', 'notes-sharing-6a8b2.firebaseapp.com');
define('FIREBASE_DATABASE_URL', 'https://notes-sharing-6a8b2-default-rtdb.firebaseio.com');
define('FIREBASE_PROJECT_ID', 'notes-sharing-6a8b2');
define('FIREBASE_STORAGE_BUCKET', 'notes-sharing-6a8b2.appspot.com');
define('FIREBASE_MESSAGING_SENDER_ID', '172945409962');
define('FIREBASE_APP_ID', '1:172945409962:web:38481eaf0140bde7ac8dd3');

// App configuration
define('APP_NAME', 'NoteShare');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'development'); // Set to 'production' in production

// Coin configuration
define('COIN_EXCHANGE_RATE', 0.10); // ₹0.10 per coin
define('MIN_COINS_TO_MESSAGE', 1);
define('COIN_PACKAGES', [
    ['coins' => 100, 'price' => 10],
    ['coins' => 500, 'price' => 45],
    ['coins' => 1000, 'price' => 80],
    ['coins' => 5000, 'price' => 350]
]);

// Google OAuth configuration (if using Google auth)
define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET');
define('GOOGLE_REDIRECT_URI', 'http://localhost/note_share/auth/google_callback.php');

// Payment gateway configuration
define('RAZORPAY_KEY_ID', 'YOUR_RAZORPAY_KEY_ID');
define('RAZORPAY_KEY_SECRET', 'YOUR_RAZORPAY_KEY_SECRET');

// Email configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('FROM_EMAIL', 'your-email@gmail.com');
define('FROM_NAME', 'NoteShare');

// Validation rules
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);
define('MIN_PASSWORD_LENGTH', 8);

// Session configuration
define('SESSION_TIMEOUT', 1800); // 30 minutes
ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);

// Set default timezone
date_default_timezone_set('Asia/Kolkata');
?>
