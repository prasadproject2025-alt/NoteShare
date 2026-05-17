import { useState } from 'react';
import { useRouter } from 'next/router';
import Header from '../../components/Header';
import { validateAdminCredentials, setAdminSession } from '../../lib/adminAuth';

export default function AdminLoginPage() {
  const router = useRouter();
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  async function handleSubmit(event) {
    event.preventDefault();
    setError('');
    setLoading(true);

    if (!validateAdminCredentials(username.trim(), password)) {
      setError('Invalid admin username or password.');
      setLoading(false);
      return;
    }

    setAdminSession(true);
    router.push('/admin/dashboard');
    setLoading(false);
  }

  return (
    <div>
      <Header currentUser={null} />
      <main className="container mt-5">
        <div className="row justify-content-center">
          <div className="col-md-6">
            <div className="card">
              <div className="card-body">
                <h2>Admin Login</h2>
                {error && <div className="alert alert-danger">{error}</div>}
                <form onSubmit={handleSubmit}>
                  <div className="mb-3">
                    <label className="form-label">Username</label>
                    <input className="form-control" value={username} onChange={(e) => setUsername(e.target.value)} type="text" required />
                  </div>
                  <div className="mb-3">
                    <label className="form-label">Password</label>
                    <input className="form-control" value={password} onChange={(e) => setPassword(e.target.value)} type="password" required />
                  </div>
                  <button className="btn btn-primary" disabled={loading}>{loading ? 'Logging in...' : 'Login'}</button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  );
}
