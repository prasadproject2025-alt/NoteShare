<?php
session_start();
include 'includes/header.php';
?>

<main class="container mt-5">
    <div class="row">
        <div class="col-md-12">
            <h2>🔍 Firebase Diagnostic Tool</h2>
            <div class="alert alert-info">
                <strong>Diagnostic Mode:</strong> This page helps troubleshoot Firebase connectivity and data access issues.
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>🔗 Connection Tests</h5>
                        </div>
                        <div class="card-body">
                            <button class="btn btn-primary mb-2" onclick="testFirebaseSDK()">Test Firebase SDK</button>
                            <button class="btn btn-success mb-2" onclick="testAnonymousAuth()">Test Anonymous Auth</button>
                            <button class="btn btn-info mb-2" onclick="testDatabaseAccess()">Test Database Access</button>
                            <button class="btn btn-warning mb-2" onclick="testNotesQuery()">Test Notes Query</button>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>📊 Data Inspection</h5>
                        </div>
                        <div class="card-body">
                            <button class="btn btn-secondary mb-2" onclick="inspectAllCollections()">Inspect All Collections</button>
                            <button class="btn btn-dark mb-2" onclick="checkFirebaseRules()">Check Rules Status</button>
                            <button class="btn btn-light mb-2" onclick="clearLogs()">Clear Logs</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5>📝 Test Results & Logs</h5>
                </div>
                <div class="card-body">
                    <div id="test-results" style="max-height: 400px; overflow-y: auto; background: #f8f9fa; padding: 10px; border-radius: 5px;">
                        <p class="text-muted">Click buttons above to run tests...</p>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <h4>🔧 Troubleshooting Checklist</h4>
                <ul class="list-group">
                    <li class="list-group-item">
                        <strong>1. Firebase Anonymous Auth:</strong> Go to Firebase Console → Authentication → Sign-in method → Enable "Anonymous"
                    </li>
                    <li class="list-group-item">
                        <strong>2. Database Rules:</strong> Ensure rules allow public reads: <code>".read: true</code> for notes collections
                    </li>
                    <li class="list-group-item">
                        <strong>3. Network Connection:</strong> Check browser console for network errors
                    </li>
                    <li class="list-group-item">
                        <strong>4. Browser Cache:</strong> Clear cache and try incognito mode
                    </li>
                </ul>
            </div>
        </div>
    </div>
</main>

<!-- Firebase SDK v8 (compat mode for legacy code) -->
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js"></script>
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-database.js"></script>
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-auth.js"></script>

<script src="js/firebase-config.js"></script>

<script>
function log(message, type = 'info') {
    const results = document.getElementById('test-results');
    const timestamp = new Date().toLocaleTimeString();
    const colorClass = type === 'success' ? 'text-success' :
                      type === 'error' ? 'text-danger' :
                      type === 'warning' ? 'text-warning' : 'text-info';

    results.innerHTML += `<div class="${colorClass}">[${timestamp}] ${message}</div>`;
    results.scrollTop = results.scrollHeight;
    console.log(`[${type.toUpperCase()}] ${message}`);
}

function clearLogs() {
    document.getElementById('test-results').innerHTML = '<p class="text-muted">Logs cleared...</p>';
}

function testFirebaseSDK() {
    log('Testing Firebase SDK loading...', 'info');

    if (typeof firebase === 'undefined') {
        log('❌ Firebase SDK not loaded!', 'error');
        return;
    }

    log('✅ Firebase SDK loaded', 'success');

    if (!firebase.apps || firebase.apps.length === 0) {
        log('❌ Firebase not initialized', 'error');
        return;
    }

    log('✅ Firebase initialized', 'success');
    log('Firebase version: ' + firebase.SDK_VERSION, 'info');
}

function testAnonymousAuth() {
    log('Testing anonymous authentication...', 'info');

    if (!firebase.auth) {
        log('❌ Firebase Auth not available', 'error');
        return;
    }

    const auth = firebase.auth();

    if (auth.currentUser) {
        log('✅ Already authenticated as: ' + auth.currentUser.uid, 'success');
        return;
    }

    log('Attempting anonymous sign-in...', 'info');

    auth.signInAnonymously().then((userCredential) => {
        log('✅ Anonymous authentication successful', 'success');
        log('User ID: ' + userCredential.user.uid, 'info');
    }).catch((error) => {
        log('❌ Anonymous authentication failed: ' + error.message, 'error');
        log('Error code: ' + error.code, 'error');

        if (error.code === 'auth/admin-restricted-operation') {
            log('💡 Anonymous auth may be disabled in Firebase Console', 'warning');
        }
    });
}

function testDatabaseAccess() {
    log('Testing database access...', 'info');

    if (!firebase.database) {
        log('❌ Firebase Database not available', 'error');
        return;
    }

    try {
        const db = firebase.database();
        log('✅ Database reference created', 'success');

        // Test a simple reference
        const testRef = db.ref('test');
        log('✅ Test reference created', 'success');

    } catch (error) {
        log('❌ Database access error: ' + error.message, 'error');
    }
}

function testNotesQuery() {
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
                if (count <= 3) { // Log first 3 notes
                    const note = child.val();
                    log('Note ' + count + ': ' + (note.subject_name || 'No title') + ' (ID: ' + child.key + ')', 'info');
                }
            });
            if (snapshot.numChildren() > 3) {
                log('... and ' + (snapshot.numChildren() - 3) + ' more notes', 'info');
            }
        } else {
            log('⚠️ No notes found in database', 'warning');
        }
    }).catch((error) => {
        log('❌ Query failed: ' + error.message, 'error');
        log('Error code: ' + error.code, 'error');

        if (error.code === 'permission-denied') {
            log('💡 Permission denied - check Firebase rules', 'warning');
        }
    });
}

function inspectAllCollections() {
    log('Inspecting all collections...', 'info');

    const collections = ['notes', 'shared_notes', 'rental_notes', 'users', 'chats'];

    collections.forEach(collection => {
        const ref = firebase.database().ref(collection);
        ref.once('value').then(snapshot => {
            log(`${collection}: ${snapshot.numChildren()} items`, snapshot.exists() ? 'success' : 'warning');
        }).catch(error => {
            log(`${collection}: Error - ${error.message}`, 'error');
        });
    });
}

function checkFirebaseRules() {
    log('Checking Firebase rules status...', 'info');
    log('Note: This is a client-side check. For full rules testing, use Firebase Console.', 'warning');

    // Try to write to a test location (should fail with proper rules)
    const testRef = firebase.database().ref('test_write_check');
    testRef.set({ test: true, timestamp: Date.now() }).then(() => {
        log('⚠️ Test write succeeded - rules may be too permissive', 'warning');
        // Clean up
        testRef.remove();
    }).catch(error => {
        if (error.code === 'permission-denied') {
            log('✅ Write permissions correctly restricted', 'success');
        } else {
            log('❌ Unexpected error during write test: ' + error.message, 'error');
        }
    });

    // Try to read from notes (should succeed with public read)
    const notesRef = firebase.database().ref('notes');
    notesRef.once('value').then(snapshot => {
        log('✅ Notes collection is readable', 'success');
    }).catch(error => {
        if (error.code === 'permission-denied') {
            log('❌ Notes collection read denied - check rules', 'error');
        } else {
            log('❌ Unexpected error reading notes: ' + error.message, 'error');
        }
    });
}

// Auto-run basic tests on page load
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        log('🔍 Firebase Diagnostic Tool Ready', 'info');
        log('Click buttons above to run specific tests', 'info');
    }, 1000);
});
</script>

<?php include 'includes/footer.php'; ?>