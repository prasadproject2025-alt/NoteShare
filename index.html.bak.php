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
            <h1>Welcome to NoteShare</h1>
            <p>Choose what you want to do:</p>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Buy Notes</h5>
                    <p class="card-text">Browse and purchase notes from other students.</p>
                    <a href="buy_notes.php" class="btn btn-primary">Browse Notes</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Sell Notes</h5>
                    <p class="card-text">Upload and sell your notes to other students.</p>
                    <a href="sell_notes.php" class="btn btn-success">Sell Notes</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Share Notes</h5>
                    <p class="card-text">Share notes with classmates (Morning & Afternoon batches).</p>
                    <a href="share_notes.php" class="btn btn-info">Share Notes</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Rent Notes</h5>
                    <p class="card-text">Rent notes temporarily for a short period.</p>
                    <a href="rent_notes.php" class="btn btn-warning">Rent Notes</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">My Coins</h5>
                    <p class="card-text" id="coins-balance">Loading...</p>
                    <a href="coins.php" class="btn btn-secondary">Manage Coins</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Messages</h5>
                    <p class="card-text">Check your messages and chat history.</p>
                    <a href="messages.php" class="btn btn-dark">View Messages</a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<!-- Firebase SDK -->
<script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-app.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-database.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-storage.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-auth.js"></script>

<script src="js/firebase-config.js"></script>
<script>
// Load user's coin balance
document.addEventListener('DOMContentLoaded', function() {
    loadCoinBalance();
});

function loadCoinBalance() {
    const userId = '<?php echo $_SESSION['user_id']; ?>';
    const coinsRef = firebase.database().ref('users/' + userId + '/coins');
    
    coinsRef.once('value').then(snapshot => {
        const coins = snapshot.val() || 0;
        document.getElementById('coins-balance').textContent = 'Coins: ' + coins;
    });
}
</script>
