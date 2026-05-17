import { useEffect, useState } from 'react';
import { useRouter } from 'next/router';
import { auth } from '../lib/firebase';
import { onAuthStateChanged } from 'firebase/auth';
import { loginUser } from '../lib/auth';
import Header from '../components/Header';

export default function LoginPage() {
  const router = useRouter();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const [currentUser, setCurrentUser] = useState(null);

  useEffect(() => {
    const unsubscribe = onAuthStateChanged(auth, (user) => {
      if (user) {
        router.push('/');
      } else {
        setCurrentUser(null);
      }
    });
    return unsubscribe;
  }, [router]);

  async function handleSubmit(event) {
    event.preventDefault();
    setError('');

    if (!email.endsWith('@vitstudent.ac.in')) {
      setError('Please use a VIT student email ending with @vitstudent.ac.in');
      return;
    }

    setLoading(true);
    try {
      await loginUser(email, password);
      router.push('/');
    } catch (err) {
      setError(err.message || 'Login failed');
    } finally {
      setLoading(false);
    }
  }

  return (
    <div>
      <Header currentUser={currentUser} />
      <main className="container mt-5">
        <div className="row justify-content-center">
          <div className="col-md-6">
            <div className="card shadow-sm">
              <div className="card-header bg-primary text-white">
                <h3 className="mb-0">Login to NoteShare</h3>
              </div>
              <div className="card-body">
                {error && <div className="alert alert-danger">{error}</div>} 
                <form onSubmit={handleSubmit}>
                  <div className="mb-3">
                    <label htmlFor="email" className="form-label">VIT Student Gmail</label>
                    <input
                      id="email"
                      type="email"
                      className="form-control"
                      value={email}
                      onChange={(e) => setEmail(e.target.value)}
                      placeholder="your.email@vitstudent.ac.in"
                      required
                    />
                  </div>
                  <div className="mb-3">
                    <label htmlFor="password" className="form-label">Password</label>
                    <input
                      id="password"
                      type="password"
                      className="form-control"
                      value={password}
                      onChange={(e) => setPassword(e.target.value)}
                      required
                    />
                  </div>
                  <button type="submit" className="btn btn-primary w-100" disabled={loading}>
                    {loading ? 'Logging in...' : 'Login'}
                  </button>
                </form>
                <div className="text-center mt-4">
                  <p className="mb-1">New to NoteShare?</p>
                  <a href="/create-account" className="btn btn-success">Create Account</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  );
}
