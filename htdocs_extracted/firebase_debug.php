<?php
// Firebase Configuration Test
$firebaseConfig = [
    'apiKey' => 'AIzaSyBhnmxrk0feR-4IIMIPPQKTSZTNzRXz__Y',
    'authDomain' => 'notes-sharing-6a8b2.firebaseapp.com',
    'databaseURL' => 'https://notes-sharing-6a8b2-default-rtdb.firebaseio.com',
    'projectId' => 'notes-sharing-6a8b2'
];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Firebase Debug Tool</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .log-entry { margin: 5px 0; padding: 5px; border-radius: 3px; }
        .log-success { background: #d4edda; color: #155724; }
        .log-error { background: #f8d7da; color: #721c24; }
        .log-info { background: #d1ecf1; color: #0c5460; }
        .log-warning { background: #fff3cd; color: #856404; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1>🔍 Firebase Debug Tool</h1>
        <p class="text-muted">This tool will help identify why notes aren't loading in buy_notes.php</p>

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5>Test Results</h5>
                    </div>
                    <div class="card-body">
                        <div id="test-results"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Quick Tests</h5>
                    </div>
                    <div class="card-body">
                        <button class="btn btn-primary mb-2 w-100" onclick="runAllTests()">Run All Tests</button>
                        <button class="btn btn-success mb-2 w-100" onclick="testFirebaseSDK()">Test SDK</button>
                        <button class="btn btn-info mb-2 w-100" onclick="testAnonymousAuth()">Test Auth</button>
                        <button class="btn btn-warning mb-2 w-100" onclick="testDatabaseAccess()">Test Database</button>
                        <button class="btn btn-danger mb-2 w-100" onclick="testNotesQuery()">Test Notes</button>
                        <button class="btn btn-secondary mb-2 w-100" onclick="clearLogs()">Clear Logs</button>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h5>Firebase Config</h5>
                    </div>
                    <div class="card-body">
                        <small>
                            <strong>Project:</strong> notes-sharing-6a8b2<br>
                            <strong>Database:</strong> Realtime DB<br>
                            <strong>Auth:</strong> Anonymous (needs to be enabled)
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>🔧 Troubleshooting Steps</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <h6>Most Common Issue: Anonymous Authentication Not Enabled</h6>
                            <ol>
                                <li>Go to <a href="https://console.firebase.google.com/" target="_blank">Firebase Console</a></li>
                                <li>Select project: <code>notes-sharing-6a8b2</code></li>
                                <li>Click <strong>Authentication</strong> → <strong>Sign-in method</strong></li>
                                <li>Find <strong>Anonymous</strong> and click <strong>Enable</strong></li>
                                <li>Click <strong>Save</strong></li>
                                <li>Refresh this page and run tests again</li>
                            </ol>
                        </div>

                        <div class="alert alert-info">
                            <h6>Other Potential Issues:</h6>
                            <ul>
                                <li><strong>Network blocked:</strong> Check browser console for CORS errors</li>
                                <li><strong>Rules not applied:</strong> Firebase rules need to be deployed</li>
                                <li><strong>Project mismatch:</strong> Config doesn't match Firebase project</li>
                                <li><strong>Data doesn't exist:</strong> No notes in database</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Firebase SDK v8 -->
    <script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js"></script>
    <script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-database.js"></script>
    <script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-auth.js"></script>

    <script>
        // Firebase Configuration
        const firebaseConfig = {
            apiKey: "<?= $firebaseConfig['apiKey'] ?>",
            authDomain: "<?= $firebaseConfig['authDomain'] ?>",
            databaseURL: "<?= $firebaseConfig['databaseURL'] ?>",
            projectId: "<?= $firebaseConfig['projectId'] ?>",
            storageBucket: "notes-sharing-6a8b2.appspot.com",
            messagingSenderId: "172945409962",
            appId: "1:172945409962:web:38481eaf0140bde7ac8dd3"
        };

        let testResults = [];

        function log(message, type = 'info') {
            const results = document.getElementById('test-results');
            const timestamp = new Date().toLocaleTimeString();
            const logClass = `log-${type}`;

            testResults.push({message, type, timestamp});

            results.innerHTML += `<div class="log-entry ${logClass}">[${timestamp}] ${message}</div>`;
            results.scrollTop = results.scrollHeight;
            console.log(`[${type.toUpperCase()}] ${message}`);
        }

        function clearLogs() {
            document.getElementById('test-results').innerHTML = '';
            testResults = [];
        }

        function runAllTests() {
            clearLogs();
            log('🚀 Starting comprehensive Firebase tests...', 'info');

            testFirebaseSDK().then(() => {
                return testAnonymousAuth();
            }).then(() => {
                return testDatabaseAccess();
            }).then(() => {
                return testNotesQuery();
            }).then(() => {
                log('✅ All tests completed!', 'success');
            }).catch(error => {
                log('❌ Test suite failed: ' + error.message, 'error');
            });
        }

        function testFirebaseSDK() {
            return new Promise((resolve, reject) => {
                log('Testing Firebase SDK loading...', 'info');

                setTimeout(() => {
                    if (typeof firebase === 'undefined') {
                        log('❌ Firebase SDK not loaded!', 'error');
                        reject(new Error('Firebase SDK not loaded'));
                        return;
                    }

                    log('✅ Firebase SDK loaded', 'success');

                    if (!firebase.apps || firebase.apps.length === 0) {
                        log('❌ Firebase not initialized', 'error');
                        reject(new Error('Firebase not initialized'));
                        return;
                    }

                    log('✅ Firebase initialized', 'success');
                    log('Firebase version: ' + firebase.SDK_VERSION, 'info');
                    resolve();
                }, 1000);
            });
        }

        function testAnonymousAuth() {
            return new Promise((resolve, reject) => {
                log('Testing anonymous authentication...', 'info');

                if (!firebase.auth) {
                    log('❌ Firebase Auth not available', 'error');
                    reject(new Error('Firebase Auth not available'));
                    return;
                }

                const auth = firebase.auth();

                if (auth.currentUser) {
                    log('✅ Already authenticated as: ' + auth.currentUser.uid, 'success');
                    resolve();
                    return;
                }

                log('Attempting anonymous sign-in...', 'info');

                auth.signInAnonymously().then((userCredential) => {
                    log('✅ Anonymous authentication successful', 'success');
                    log('User ID: ' + userCredential.user.uid, 'info');
                    resolve();
                }).catch((error) => {
                    log('❌ Anonymous authentication failed: ' + error.message, 'error');
                    log('Error code: ' + error.code, 'error');

                    if (error.code === 'auth/admin-restricted-operation') {
                        log('💡 SOLUTION: Enable Anonymous auth in Firebase Console!', 'warning');
                        log('Go to Firebase Console → Authentication → Sign-in method → Enable Anonymous', 'warning');
                    } else if (error.code === 'auth/operation-not-allowed') {
                        log('💡 SOLUTION: Anonymous auth is disabled in Firebase Console', 'warning');
                    }

                    reject(error);
                });
            });
        }

        function testDatabaseAccess() {
            return new Promise((resolve, reject) => {
                log('Testing database access...', 'info');

                if (!firebase.database) {
                    log('❌ Firebase Database not available', 'error');
                    reject(new Error('Firebase Database not available'));
                    return;
                }

                try {
                    const db = firebase.database();
                    log('✅ Database reference created', 'success');

                    // Test a simple reference
                    const testRef = db.ref('test');
                    log('✅ Test reference created', 'success');
                    resolve();
                } catch (error) {
                    log('❌ Database access error: ' + error.message, 'error');
                    reject(error);
                }
            });
        }

        function testNotesQuery() {
            return new Promise((resolve, reject) => {
                log('Testing notes query...', 'info');

                const notesRef = firebase.database().ref('notes');

                notesRef.once('value').then((snapshot) => {
                    log('✅ Query successful', 'success');
                    log('Data exists: ' + snapshot.exists(), 'info');
                    log('Number of notes: ' + snapshot.numChildren(), 'info');

                    if (snapshot.exists()) {
                        let count = 0;
                        snapshot.forEach((child) => {
                            count++;
                            if (count <= 5) { // Log first 5 notes
                                const note = child.val();
                                log('Note ' + count + ': ' + (note.subject_name || 'No title') + ' (ID: ' + child.key + ')', 'info');
                            }
                        });
                        if (snapshot.numChildren() > 5) {
                            log('... and ' + (snapshot.numChildren() - 5) + ' more notes', 'info');
                        }
                    } else {
                        log('⚠️ No notes found in database', 'warning');
                        log('💡 This could mean: no data exists, or read permissions are denied', 'warning');
                    }

                    resolve();
                }).catch((error) => {
                    log('❌ Query failed: ' + error.message, 'error');
                    log('Error code: ' + error.code, 'error');

                    if (error.code === 'permission-denied') {
                        log('💡 SOLUTION: Check Firebase Database Rules', 'warning');
                        log('Rules should have: "notes": { ".read": true }', 'warning');
                    }

                    reject(error);
                });
            });
        }

        // Initialize Firebase when page loads
        document.addEventListener('DOMContentLoaded', function() {
            log('🔍 Firebase Debug Tool Ready', 'info');
            log('Click "Run All Tests" to start diagnostics', 'info');

            // Initialize Firebase
            try {
                firebase.initializeApp(firebaseConfig);
                log('Firebase app initialized', 'success');
            } catch (error) {
                log('Firebase initialization failed: ' + error.message, 'error');
            }
        });
    </script>
</body>
</html>