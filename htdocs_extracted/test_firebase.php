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
            <h2>Firebase Connection Test</h2>
            <div id="test-results">
                <p class="text-muted">Testing Firebase connection...</p>
            </div>
            <button class="btn btn-primary" onclick="testFirebaseConnection()">Test Connection</button>
            <button class="btn btn-success" onclick="testFirebaseWrite()">Test Write</button>
            <button class="btn btn-info" onclick="testFirebaseRead()">Test Read</button>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<!-- Firebase SDK v8 (compat mode for legacy code) -->
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js"></script>
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-database.js"></script>
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-storage.js"></script>
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-auth.js"></script>

<script src="js/firebase-config.js"></script>
<script>
function testFirebaseConnection() {
    const resultsDiv = document.getElementById('test-results');
    resultsDiv.innerHTML = '<p class="text-info">Testing Firebase connection...</p>';

    if (typeof firebase === 'undefined') {
        resultsDiv.innerHTML = '<p class="text-danger">❌ Firebase SDK not loaded</p>';
        return;
    }

    if (typeof firebase.database === 'undefined') {
        resultsDiv.innerHTML = '<p class="text-danger">❌ Firebase Database not available</p>';
        return;
    }

    resultsDiv.innerHTML = '<p class="text-success">✅ Firebase SDK loaded successfully</p>';
}

function testFirebaseWrite() {
    const resultsDiv = document.getElementById('test-results');
    resultsDiv.innerHTML = '<p class="text-info">Testing Firebase write...</p>';

    const testData = {
        test: 'connection_test',
        timestamp: Date.now(),
        user_id: '<?php echo $_SESSION['user_id']; ?>'
    };

    firebase.database().ref('test_connection').push(testData).then((snapshot) => {
        resultsDiv.innerHTML = '<p class="text-success">✅ Firebase write successful! Key: ' + snapshot.key + '</p>';
    }).catch(error => {
        resultsDiv.innerHTML = '<p class="text-danger">❌ Firebase write failed: ' + error.message + '</p>';
        console.error('Firebase write error:', error);
    });
}

function testFirebaseRead() {
    const resultsDiv = document.getElementById('test-results');
    resultsDiv.innerHTML = '<p class="text-info">Testing Firebase read...</p>';

    firebase.database().ref('notes').limitToLast(1).once('value').then((snapshot) => {
        if (snapshot.exists()) {
            resultsDiv.innerHTML = '<p class="text-success">✅ Firebase read successful! Found data.</p>';
        } else {
            resultsDiv.innerHTML = '<p class="text-warning">⚠️ Firebase read successful but no data found.</p>';
        }
    }).catch(error => {
        resultsDiv.innerHTML = '<p class="text-danger">❌ Firebase read failed: ' + error.message + '</p>';
        console.error('Firebase read error:', error);
    });
}
</script>