<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include 'includes/header.php';
?>

<main class="container mt-5">
    <div class="row">
        <div class="col-md-8">
            <h2>Manage Coins</h2>

            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5>Your Coin Balance</h5>
                </div>
                <div class="card-body text-center">
                    <h1 id="coin-display">0</h1>
                    <p class="text-muted">1 coin = ₹0.10</p>
                </div>
            </div>

            <h4>Buy Coins</h4>
            <div class="row" id="coin-packages">
                <!-- Coin packages will be loaded here -->
                <p class="text-muted">Loading coin packages...</p>
            </div>

            <hr>

            <h4>Coin Usage History</h4>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Coins</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody id="usage-history">
                        <tr>
                            <td colspan="4" class="text-muted text-center">Loading history...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>Coin Information</h5>
                </div>
                <div class="card-body">
                    <h6>How Coins Work:</h6>
                    <ul class="list-unstyled small">
                        <li><strong>1 Coin:</strong> Message 1 person</li>
                        <li><strong>3 Coins:</strong> Rent notes</li>
                        <li><strong>5 Coins:</strong> Priority listing</li>
                    </ul>
                    <hr>
                    <h6>Earn Coins:</h6>
                    <ul class="list-unstyled small">
                        <li>✓ Sell notes (₹1 = 10 coins)</li>
                        <li>✓ Refer friends (50 coins)</li>
                        <li>✓ Complete profile (10 coins)</li>
                    </ul>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h5>Exchange Rates</h5>
                </div>
                <div class="card-body small">
                    <p><strong>100 coins:</strong> ₹10</p>
                    <p><strong>500 coins:</strong> ₹45</p>
                    <p><strong>1000 coins:</strong> ₹80</p>
                    <p><strong>5000 coins:</strong> ₹350</p>
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
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<script>
const userId = '<?php echo $_SESSION['user_id']; ?>';
const userEmail = '<?php echo $_SESSION['user_email']; ?>';

// Coin packages with prices
const packages = [
    { coins: 100, price: 10, display: '100 Coins - ₹10' },
    { coins: 500, price: 45, display: '500 Coins - ₹45 (Best Value)' },
    { coins: 1000, price: 80, display: '1000 Coins - ₹80' },
    { coins: 5000, price: 350, display: '5000 Coins - ₹350' }
];

document.addEventListener('DOMContentLoaded', function() {
    loadCoinBalance();
    loadCoinPackages();
    loadUsageHistory();
});

function loadCoinBalance() {
    // Load coins from API with cache busting
    fetch('get_user_coins.php?t=' + Date.now(), {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('coin-display').textContent = data.coins;
        } else {
            console.error('Error loading coins:', data.message);
            document.getElementById('coin-display').textContent = 'Error';
        }
    })
    .catch(error => {
        console.error('Error loading coins:', error);
        document.getElementById('coin-display').textContent = 'Error';
    });
}

function loadCoinPackages() {
    const packagesDiv = document.getElementById('coin-packages');
    let html = '';

    packages.forEach((pkg, index) => {
        html += `
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">${pkg.coins} Coins</h5>
                        <p class="card-text"><strong>₹${pkg.price}</strong></p>
                        <button class="btn btn-success w-100" onclick="buyCoins(${pkg.coins}, ${pkg.price})">
                            Buy Now
                        </button>
                    </div>
                </div>
            </div>
        `;
    });

    packagesDiv.innerHTML = html;
}

function buyCoins(coins, price) {
    // For demo purposes, we'll show a payment prompt
    // In production, integrate with Razorpay or similar payment gateway
    
    if (!confirm('Proceed to payment? You will get ' + coins + ' coins for ₹' + price)) {
        return;
    }

    // Simulate payment processing
    processPayment(coins, price);
}

function processPayment(coins, price) {
    // Here you would integrate with Razorpay or your payment gateway
    // For now, we'll add coins directly to simulate payment success
    
    const coinsRef = firebase.database().ref('users/' + userId + '/coins');
    coinsRef.once('value').then(snapshot => {
        const currentCoins = snapshot.val() || 0;
        const newCoins = currentCoins + coins;
        
        coinsRef.set(newCoins).then(() => {
            // Log transaction
            firebase.database().ref('coin_transactions').push({
                user_id: userId,
                type: 'purchase',
                coins: coins,
                price: price,
                timestamp: Date.now(),
                description: 'Purchased ' + coins + ' coins for ₹' + price
            });

            alert('Payment successful! ' + coins + ' coins added to your account.');
            loadCoinBalance();
            loadUsageHistory();
        });
    });
}

function loadUsageHistory() {
    firebase.database().ref('coin_transactions').orderByChild('user_id').equalTo(userId)
        .limitToLast(10).once('value').then(snapshot => {
            const historyDiv = document.getElementById('usage-history');
            const transactions = [];

            snapshot.forEach(childSnapshot => {
                const tx = childSnapshot.val();
                transactions.unshift(tx);
            });

            if (transactions.length === 0) {
                historyDiv.innerHTML = '<tr><td colspan="4" class="text-muted text-center">No transactions yet</td></tr>';
                return;
            }

            let html = '';
            transactions.forEach(tx => {
                const date = new Date(tx.timestamp).toLocaleDateString();
                const type = tx.type === 'purchase' ? 'Purchased' : 'Spent';
                html += `
                    <tr>
                        <td>${date}</td>
                        <td>${type}</td>
                        <td>${tx.type === 'purchase' ? '+' : '-'}${tx.coins}</td>
                        <td>${tx.description}</td>
                    </tr>
                `;
            });

            historyDiv.innerHTML = html;
        });
}
</script>
