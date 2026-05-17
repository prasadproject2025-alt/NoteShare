import { useEffect, useState } from 'react';
import Header from '../components/Header';
import { auth } from '../lib/firebase';
import { onAuthStateChanged } from 'firebase/auth';
import { getUserProfile } from '../lib/auth';
import { getCoinTransactions, addCoins } from '../lib/db';

const coinPackages = [
  { coins: 100, price: 10 },
  { coins: 500, price: 45 },
  { coins: 1000, price: 80 },
  { coins: 5000, price: 350 }
];

export default function CoinsPage() {
  const [currentUser, setCurrentUser] = useState(null);
  const [transactions, setTransactions] = useState([]);
  const [loading, setLoading] = useState(true);
  const [message, setMessage] = useState('');

  useEffect(() => {
    const unsubscribe = onAuthStateChanged(auth, async (user) => {
      if (!user) {
        window.location.href = '/login';
        return;
      }
      const profile = await getUserProfile(user);
      setCurrentUser({ uid: profile.uid, ...profile });
      const txs = await getCoinTransactions(user.uid);
      setTransactions(txs);
      setLoading(false);
    });
    return unsubscribe;
  }, []);

  async function handleBuyCoins(coins, price) {
    if (!currentUser) return;
    const confirmPayment = window.confirm(`Purchase ${coins} coins for ₹${price}?`);
    if (!confirmPayment) return;
    setLoading(true);
    try {
      await addCoins(currentUser.uid, coins, price);
      setMessage('Coins added successfully.');
      const profile = await getUserData(currentUser.uid);
      setCurrentUser((prev) => ({ ...prev, coins: profile.coins }));
      setTransactions(await getCoinTransactions(currentUser.uid));
    } catch (error) {
      setMessage(error.message || 'Purchase failed');
    } finally {
      setLoading(false);
    }
  }

  return (
    <div>
      <Header currentUser={currentUser} />
      <main className="container mt-4">
        <div className="row">
          <div className="col-md-8">
            <h2>Manage Coins</h2>
            {message && <div className="alert alert-info">{message}</div>}
            <div className="card mb-4">
              <div className="card-body text-center">
                <h1>{currentUser ? currentUser.coins : '0'}</h1>
                <p className="text-muted">1 coin = ₹0.10</p>
              </div>
            </div>

            <h4>Buy Coins</h4>
            <div className="row">
              {coinPackages.map((pkg) => (
                <div className="col-md-6 mb-3" key={pkg.coins}>
                  <div className="card h-100">
                    <div className="card-body">
                      <h5>{pkg.coins} Coins</h5>
                      <p className="mb-3">₹{pkg.price}</p>
                      <button className="btn btn-success w-100" onClick={() => handleBuyCoins(pkg.coins, pkg.price)} disabled={loading}>
                        Buy Now
                      </button>
                    </div>
                  </div>
                </div>
              ))}
            </div>

            <hr />
            <h4>Coin Usage History</h4>
            <div className="table-responsive">
              <table className="table table-striped">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Coins</th>
                    <th>Description</th>
                  </tr>
                </thead>
                <tbody>
                  {loading ? (
                    <tr><td colSpan="4" className="text-center">Loading...</td></tr>
                  ) : transactions.length === 0 ? (
                    <tr><td colSpan="4" className="text-center">No transactions yet.</td></tr>
                  ) : transactions.map((tx) => (
                    <tr key={tx.id}>
                      <td>{new Date(tx.timestamp).toLocaleDateString()}</td>
                      <td>{tx.type === 'purchase' ? 'Purchased' : tx.type === 'earn' ? 'Earned' : 'Spent'}</td>
                      <td>{tx.type === 'purchase' || tx.type === 'earn' ? '+' : '-'}{tx.coins}</td>
                      <td>{tx.description}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>

          <div className="col-md-4">
            <div className="card mb-3">
              <div className="card-header bg-light">
                <strong>How Coins Work</strong>
              </div>
              <div className="card-body small">
                <p><strong>1 Coin:</strong> Send one message</p>
                <p><strong>3 Coins:</strong> Rent notes</p>
                <p><strong>5 Coins:</strong> Priority listing</p>
              </div>
            </div>
            <div className="card">
              <div className="card-header bg-light">
                <strong>Exchange Rates</strong>
              </div>
              <div className="card-body small">
                <p>100 coins: ₹10</p>
                <p>500 coins: ₹45</p>
                <p>1000 coins: ₹80</p>
                <p>5000 coins: ₹350</p>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  );
}
