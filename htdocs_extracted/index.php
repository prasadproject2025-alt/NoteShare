<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include 'includes/header.php';
?>

<style>
/* Tile-style dashboard design */
.ns-main { padding: 1rem; }
.tile-grid { display: flex; flex-wrap: wrap; gap: 12px; }
.tile { background: #fff; border-radius: 12px; box-shadow: 0 6px 18px rgba(0,0,0,0.06); padding: 14px; display:flex; align-items:center; gap:12px; width:100%; }
.tile .icon { width:64px; height:64px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:22px; color:#fff; flex:0 0 64px; }
.tile h5 { margin:0; font-size:1rem; }
.tile p { margin:4px 0 0; font-size:0.9rem; color:#666; }
.tile .actions { margin-left:auto; }
.tile .btn { min-width:100px; }

/* Two tiles per row on small phones, stacked layout for readability */
@media (min-width:320px) and (max-width:575px) {
    .tile { width: calc(50% - 6px); flex-direction: column; align-items: center; text-align: center; padding: 12px; }
    .tile .icon { width:56px; height:56px; flex: 0 0 56px; font-size:20px; }
    .tile h5 { font-size: 0.98rem; }
    .tile p { font-size: 0.82rem; }
    .tile .actions { width: 100%; margin-top: 10px; }
    .tile .actions .btn { width: 100%; }
}

/* Single-column layout for very narrow screens below 320px */
@media (max-width:319px) {
    .tile { width: 100%; flex-direction: column; align-items: center; text-align: center; }
    .tile .icon { width:56px; height:56px; }
}

/* Desktop: three per row look via container width */
@media (min-width: 768px) {
    .tile { width: calc(33.333% - 8px); flex-direction: column; align-items: flex-start; padding:18px; text-align: left; }
    .tile .actions { width:100%; margin-left:0; margin-top:10px; }
    .tile .actions .btn { width: auto; }
}

/* Make tiles links visually clean */
.tile { color: inherit; text-decoration: none; }
.tile:active, .tile:focus { transform: translateY(1px); }

/* Color presets for icons */
.ic-buy { background: linear-gradient(135deg,#4e73df,#6c8cff); }
.ic-sell { background: linear-gradient(135deg,#1cc88a,#4cd69a); }
.ic-share { background: linear-gradient(135deg,#36b9cc,#60d0de); }
.ic-rent { background: linear-gradient(135deg,#f6c23e,#f8d66a); }
.ic-coins { background: linear-gradient(135deg,#858796,#b1b2c1); }
.ic-msg { background: linear-gradient(135deg,#5a5c69,#8a8ca0); }
</style>

<main class="container ns-main mt-4">
    <div class="row">
        <div class="col-md-12">
            <h1>Welcome to NoteShare</h1>
            <p>Choose what you want to do:</p>
        </div>
    </div>

    <div class="tile-grid mt-3">
        <a class="tile" href="buy_notes.php">
            <div class="icon ic-buy"><i class="fas fa-shopping-bag"></i></div>
            <div>
                <h5>Buy Notes</h5>
                <p>Browse and purchase notes from other students.</p>
            </div>
            <div class="actions"><button class="btn btn-primary">Browse</button></div>
        </a>

        <a class="tile" href="sell_notes.php">
            <div class="icon ic-sell"><i class="fas fa-upload"></i></div>
            <div>
                <h5>Sell Notes</h5>
                <p>Upload and sell your notes to other students.</p>
            </div>
            <div class="actions"><button class="btn btn-success">Sell</button></div>
        </a>

        <a class="tile" href="share_notes.php">
            <div class="icon ic-share"><i class="fas fa-share-alt"></i></div>
            <div>
                <h5>Share Notes</h5>
                <p>Share notes with classmates (Morning & Afternoon batches).</p>
            </div>
            <div class="actions"><button class="btn btn-info">Share</button></div>
        </a>

        <a class="tile" href="rent_notes.php">
            <div class="icon ic-rent"><i class="fas fa-clock"></i></div>
            <div>
                <h5>Rent Notes</h5>
                <p>Rent notes temporarily for a short period.</p>
            </div>
            <div class="actions"><button class="btn btn-warning">Rent</button></div>
        </a>

        <a class="tile" href="coins.php">
            <div class="icon ic-coins"><i class="fas fa-coins"></i></div>
            <div>
                <h5>My Coins</h5>
                <p id="coins-balance">Loading...</p>
            </div>
            <div class="actions"><button class="btn btn-secondary">Manage</button></div>
        </a>

        <a class="tile" href="messages.php">
            <div class="icon ic-msg"><i class="fas fa-comments"></i></div>
            <div>
                <h5>Messages</h5>
                <p>Check your messages and chat history.</p>
            </div>
            <div class="actions"><button class="btn btn-dark">Open</button></div>
        </a>
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
