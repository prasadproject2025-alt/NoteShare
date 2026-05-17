import { useEffect, useState } from 'react';
import { useRouter } from 'next/router';
import { auth } from '../lib/firebase';
import { onAuthStateChanged } from 'firebase/auth';
import { ref, get } from 'firebase/database';
import { database } from '../lib/firebase';
import Header from '../components/Header';

export default function HomePage() {
  const router = useRouter();
  const [user, setUser] = useState(null);
  const [coins, setCoins] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const unsubscribe = onAuthStateChanged(auth, async (firebaseUser) => {
      if (!firebaseUser) {
        router.push('/login');
        return;
      }

      const userRef = ref(database, `users/${firebaseUser.uid}`);
      let currentUser = { email: firebaseUser.email, name: (firebaseUser.displayName || firebaseUser.email.split('@')[0]), coins: 0 };
      try {
        const snapshot = await get(userRef);
        if (snapshot.exists()) {
          currentUser = snapshot.val();
        }
      } catch (err) {
        // Permission errors from Firebase should not crash the UI — fall back to a basic profile.
        console.error('Firebase permission/read error, falling back to local profile', err?.message || err);
      }
      setUser(currentUser);
      setCoins(currentUser.coins ?? 0);
      setLoading(false);
    });

    return () => unsubscribe();
  }, [router]);

  if (loading) {
    return (
      <div className="container mt-5 text-center">
        <div className="spinner-border text-primary" role="status"></div>
        <p className="mt-3">Loading dashboard...</p>
      </div>
    );
  }

  return (
    <div>
      <Header currentUser={user} />
      <main className="container ns-main mt-4">
        <div className="row">
          <div className="col-md-12">
            <h1>Welcome to NoteShare</h1>
            <p>Choose what you want to do:</p>
          </div>
        </div>

        <div className="tile-grid mt-3">
          <a className="tile" href="/buy-notes">
            <div className="icon ic-buy"><i className="fas fa-shopping-bag"></i></div>
            <div>
              <h5>Buy Notes</h5>
              <p>Browse and purchase notes from other students.</p>
            </div>
            <div className="actions"><button className="btn btn-primary">Browse</button></div>
          </a>

          <a className="tile" href="/sell-notes">
            <div className="icon ic-sell"><i className="fas fa-upload"></i></div>
            <div>
              <h5>Sell Notes</h5>
              <p>Upload and sell your notes to other students.</p>
            </div>
            <div className="actions"><button className="btn btn-success">Sell</button></div>
          </a>

          <a className="tile" href="/share-notes">
            <div className="icon ic-share"><i className="fas fa-share-alt"></i></div>
            <div>
              <h5>Share Notes</h5>
              <p>Share notes with classmates (Morning & Afternoon batches).</p>
            </div>
            <div className="actions"><button className="btn btn-info">Share</button></div>
          </a>

          <a className="tile" href="/rent-notes">
            <div className="icon ic-rent"><i className="fas fa-clock"></i></div>
            <div>
              <h5>Rent Notes</h5>
              <p>Rent notes temporarily for a short period.</p>
            </div>
            <div className="actions"><button className="btn btn-warning">Rent</button></div>
          </a>

          <a className="tile" href="/coins">
            <div className="icon ic-coins"><i className="fas fa-coins"></i></div>
            <div>
              <h5>My Coins</h5>
              <p id="coins-balance">Coins: {coins}</p>
            </div>
            <div className="actions"><button className="btn btn-secondary">Manage</button></div>
          </a>

          <a className="tile" href="/messages">
            <div className="icon ic-msg"><i className="fas fa-comments"></i></div>
            <div>
              <h5>Messages</h5>
              <p>Check your messages and chat history.</p>
            </div>
            <div className="actions"><button className="btn btn-dark">Open</button></div>
          </a>
        </div>
      </main>
    </div>
  );
}
