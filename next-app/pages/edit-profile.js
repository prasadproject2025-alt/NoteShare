import { useEffect, useState } from 'react';
import { useRouter } from 'next/router';
import Header from '../components/Header';
import { auth } from '../lib/firebase';
import { onAuthStateChanged } from 'firebase/auth';
import { getUserProfile, updateUserProfile } from '../lib/auth';

export default function EditProfilePage() {
  const router = useRouter();
  const [currentUser, setCurrentUser] = useState(null);
  const [profile, setProfile] = useState(null);
  const [name, setName] = useState('');
  const [status, setStatus] = useState('');
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const unsubscribe = onAuthStateChanged(auth, async (user) => {
      if (!user) {
        router.push('/login');
        return;
      }
      const data = await getUserProfile(user);
      setCurrentUser({ uid: data.uid, email: data.email });
      setProfile(data);
      setName(data.name || '');
      setLoading(false);
    });
    return unsubscribe;
  }, [router]);

  async function handleSubmit(event) {
    event.preventDefault();
    setStatus('');
    if (!name.trim()) {
      setStatus('Name cannot be empty.');
      return;
    }
    setLoading(true);
    try {
      await updateUserProfile(currentUser.uid, { name: name.trim() });
      setStatus('Profile updated successfully.');
      router.push('/profile');
    } catch (error) {
      setStatus(error.message || 'Could not save profile.');
    } finally {
      setLoading(false);
    }
  }

  if (loading) {
    return <div className="container mt-5 text-center"><div className="spinner-border text-primary"></div></div>;
  }

  return (
    <div>
      <Header currentUser={profile} />
      <main className="container mt-4">
        <div className="row justify-content-center">
          <div className="col-md-6">
            <div className="card">
              <div className="card-body">
                <h2>Edit Profile</h2>
                {status && <div className="alert alert-info">{status}</div>}
                <form onSubmit={handleSubmit}>
                  <div className="mb-3">
                    <label className="form-label">Display Name</label>
                    <input className="form-control" value={name} onChange={(e) => setName(e.target.value)} required />
                  </div>
                  <div className="mb-3">
                    <label className="form-label">Email</label>
                    <input className="form-control" value={profile.email} disabled />
                  </div>
                  <button className="btn btn-primary" type="submit" disabled={loading}>{loading ? 'Saving...' : 'Save Changes'}</button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  );
}
