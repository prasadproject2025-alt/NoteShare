<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include 'includes/header.php';
?>

<main class="container mt-5">
    <div class="row">
        <div class="col-md-12">
            <h2>Firebase Debug Tool</h2>
            <div class="alert alert-info">
                <strong>Debug Information:</strong><br>
                - Firebase SDK Version: v8.10.1<br>
                - Database URL: https://notes-sharing-6a8b2-default-rtdb.firebaseio.com<br>
                - Current User ID: <?php echo htmlspecialchars($_SESSION['user_id']); ?>
            </div>

            <div id="debug-results">
                <p class="text-muted">Click a button below to test Firebase operations...</p>
            </div>

            <div class="row mt-3">
                <div class="col-md-3">
                    <button class="btn btn-primary w-100" onclick="testConnection()">Test Connection</button>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-success w-100" onclick="testReadNotes()">Read Notes</button>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-info w-100" onclick="testReadUsers()">Read Users</button>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-warning w-100" onclick="testAuth()">Test Auth</button>
                </div>
            </div>

            <div class="mt-4">
                <h4>Raw Firebase Data:</h4>
                <pre id="raw-data" class="bg-light p-3 border" style="max-height: 400px; overflow-y: auto;"></pre>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<!-- Firebase SDK v8 (compat mode for legacy code) -->
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js"></script>
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-database.js"></script>
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-auth.js"></script>

<script src="js/firebase-config.js"></script>

<script>
function updateResults(message, type = 'info') {
    const resultsDiv = document.getElementById('debug-results');
    const alertClass = type === 'success' ? 'alert-success' :
                      type === 'error' ? 'alert-danger' :
                      type === 'warning' ? 'alert-warning' : 'alert-info';
    resultsDiv.innerHTML = `<div class="alert ${alertClass}">${message}</div>`;
}

function updateRawData(data) {
    const rawDataDiv = document.getElementById('raw-data');
    rawDataDiv.textContent = JSON.stringify(data, null, 2);
}

async function testConnection() {
    updateResults('Testing Firebase connection...', 'info');

    try {
        if (typeof firebase === 'undefined') {
            throw new Error('Firebase SDK not loaded');
        }

        if (!firebase.database) {
            throw new Error('Firebase Database not available');
        }

        updateResults('✅ Firebase SDK loaded successfully', 'success');
    } catch (error) {
        updateResults('❌ ' + error.message, 'error');
    }
}

async function testAuth() {
    updateResults('Testing Firebase authentication...', 'info');

    try {
        // Try anonymous authentication
        await firebase.auth().signInAnonymously();
        updateResults('✅ Anonymous authentication successful', 'success');
    } catch (error) {
        updateResults('⚠️ Anonymous auth failed: ' + error.message + ' (This may be expected)', 'warning');
    }
}

async function testReadNotes() {
    updateResults('Testing notes read operation...', 'info');

    try {
        // Ensure authenticated first
        if (!firebase.auth().currentUser) {
            await firebase.auth().signInAnonymously();
        }

        const snapshot = await firebase.database().ref('notes').limitToLast(5).once('value');

        if (snapshot.exists()) {
            const notes = [];
            snapshot.forEach(child => {
                notes.push({
                    id: child.key,
                    ...child.val()
                });
            });

            updateResults(`✅ Successfully read ${notes.length} notes from database`, 'success');
            updateRawData(notes);
        } else {
            updateResults('⚠️ No notes found in database', 'warning');
            updateRawData({ message: 'No data found' });
        }
    } catch (error) {
        updateResults('❌ Error reading notes: ' + error.message, 'error');
        updateRawData({ error: error.message, code: error.code });
    }
}

async function testReadUsers() {
    updateResults('Testing users read operation...', 'info');

    try {
        // Ensure authenticated first
        if (!firebase.auth().currentUser) {
            await firebase.auth().signInAnonymously();
        }

        const snapshot = await firebase.database().ref('users').limitToLast(3).once('value');

        if (snapshot.exists()) {
            const users = [];
            snapshot.forEach(child => {
                const userData = child.val();
                users.push({
                    id: child.key,
                    name: userData.name || 'N/A',
                    email: userData.email || 'N/A',
                    coins: userData.coins || 0
                });
            });

            updateResults(`✅ Successfully read ${users.length} users from database`, 'success');
            updateRawData(users);
        } else {
            updateResults('⚠️ No users found in database', 'warning');
            updateRawData({ message: 'No user data found' });
        }
    } catch (error) {
        updateResults('❌ Error reading users: ' + error.message, 'error');
        updateRawData({ error: error.message, code: error.code });
    }
}

// Auto-run connection test on page load
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(testConnection, 1000);
});
</script>