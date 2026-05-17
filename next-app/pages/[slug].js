import Header from '../components/Header';
import { useEffect, useState } from 'react';
import { auth } from '../lib/firebase';
import { onAuthStateChanged } from 'firebase/auth';
import { useRouter } from 'next/router';

export default function PagePlaceholder() {
  const router = useRouter();
  const { slug } = router.query;
  const [currentUser, setCurrentUser] = useState(null);

  useEffect(() => {
    const unsubscribe = onAuthStateChanged(auth, (user) => {
      setCurrentUser(user ? { email: user.email } : null);
    });
    return unsubscribe;
  }, []);

  return (
    <div>
      <Header currentUser={currentUser} />
      <main className="container mt-5">
        <div className="card shadow-sm">
          <div className="card-header bg-warning text-dark">
            <h3 className="mb-0">Migrating {slug}</h3>
          </div>
          <div className="card-body">
            <p>This page has been converted to JavaScript, but the full feature is still being migrated.</p>
            <p>Use the main dashboard to continue exploring the app.</p>
          </div>
        </div>
      </main>
    </div>
  );
}
