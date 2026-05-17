import { useEffect, useState } from 'react';
import { useRouter } from 'next/router';
import Header from '../../components/Header';
import { isAdminLoggedIn, logoutAdmin } from '../../lib/adminAuth';

export default function AdminDashboardPage() {
  const router = useRouter();
  const [currentUser, setCurrentUser] = useState(null);
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    if (!isAdminLoggedIn()) {
      router.push('/admin/login');
      return;
    }

    setCurrentUser({ name: 'prasad' });
    loadStats().finally(() => setLoading(false));
  }, [router]);

  async function loadStats() {
    try {
      const res = await fetch('/api/admin/stats');
      if (!res.ok) throw new Error('Failed to load stats');
      const data = await res.json();
      setStats(data);
    } catch (e) {
      console.error('Failed to load admin stats', e);
      setError('Unable to load admin stats.');
      setStats({ usersCount: 0, notesCount: 0, sharedCount: 0, rentalCount: 0, pendingOtps: 0, recentUsers: [] });
    }
  }

  async function handleLogout() {
    logoutAdmin();
    router.push('/admin/login');
  }

  if (loading) {
    return <div className="container mt-5 text-center"><div className="spinner-border text-primary"></div></div>;
  }

  return (
    <div>
      <Header currentUser={currentUser} />
      <main className="container mt-4">
        <div className="row mb-4">
          <div className="col-md-12 d-flex justify-content-between align-items-center">
            <div>
              <h2>Admin Dashboard</h2>
              <p className="text-muted">Manage users, notes, and site data.</p>
            </div>
            <button className="btn btn-outline-danger" onClick={handleLogout}>Logout</button>
          </div>
        </div>
        <div className="row g-3">
          <div className="col-md-3">
            <div className="card p-3">
              <h5>Users</h5>
              <p className="display-6">{stats?.usersCount ?? 0}</p>
            </div>
          </div>
          <div className="col-md-3">
            <div className="card p-3">
              <h5>Notes</h5>
              <p className="display-6">{stats?.notesCount ?? 0}</p>
            </div>
          </div>
          <div className="col-md-3">
            <div className="card p-3">
              <h5>Shared Notes</h5>
              <p className="display-6">{stats?.sharedCount ?? 0}</p>
            </div>
          </div>
          <div className="col-md-3">
            <div className="card p-3">
              <h5>Pending OTPs</h5>
              <p className="display-6">{stats?.pendingOtps ?? 0}</p>
            </div>
          </div>
        </div>

        {error && <div className="alert alert-danger mt-3">{error}</div>}

        <div className="row mt-4">
          <div className="col-md-6">
            <div className="card">
              <div className="card-header">Admin Profile</div>
              <div className="card-body">
                <p><strong>Name:</strong> {currentUser?.name || 'Admin'}</p>
                <p><strong>Email:</strong> {currentUser?.email}</p>
                <p><strong>Status:</strong> {currentUser?.fallback ? 'DB fallback profile' : 'Profile loaded'}</p>
              </div>
            </div>
          </div>
          <div className="col-md-6">
            <div className="card">
              <div className="card-header">Recent Users</div>
              <div className="card-body p-0">
                {stats?.recentUsers?.length > 0 ? (
                  <table className="table mb-0">
                    <thead>
                      <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Joined</th>
                      </tr>
                    </thead>
                    <tbody>
                      {stats.recentUsers.map((user) => (
                        <tr key={user.uid}>
                          <td>{user.name}</td>
                          <td>{user.email}</td>
                          <td>{new Date(user.createdAt).toLocaleDateString()}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                ) : (
                  <div className="p-3 text-muted">No recent users found.</div>
                )}
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  );
}
