import { useEffect, useState } from 'react';
import { useRouter } from 'next/router';
import Header from '../components/Header';
import { auth } from '../lib/firebase';
import { onAuthStateChanged } from 'firebase/auth';
import { getUserProfile } from '../lib/auth';

export default function ProfilePage() {
  const router = useRouter();
  const [currentUser, setCurrentUser] = useState(null);
  const [profile, setProfile] = useState(null);

  useEffect(() => {
    const unsubscribe = onAuthStateChanged(auth, async (user) => {
      if (!user) {
        router.push('/login');
        return;
      }
      const data = await getUserProfile(user);
      setCurrentUser({ uid: data.uid, email: data.email });
      setProfile(data);
    });
    return unsubscribe;
  }, [router]);

  if (!profile) {
    return <div className="container mt-5 text-center"><div className="spinner-border text-primary"></div></div>;
  }

  return (
    <div>
      <Header currentUser={profile} />
      <main className="container mt-4">
        <div className="row justify-content-center">
          <div className="col-md-8">
            <div className="card">
              <div className="card-body">
                <h2>My Profile</h2>
                <p className="text-muted">Keep your profile updated so others can trust you.</p>
                <div className="mb-3"><strong>Name:</strong> {profile.name}</div>
                <div className="mb-3"><strong>Email:</strong> {profile.email}</div>
                <div className="mb-3"><strong>Coins:</strong> {profile.coins || 0}</div>
                <div className="mb-3"><strong>Status:</strong> {profile.status}</div>
                <div className="mb-3"><strong>Joined:</strong> {new Date(profile.createdAt).toLocaleDateString()}</div>
                <button className="btn btn-primary" onClick={() => router.push('/edit-profile')}>Edit Profile</button>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  );
}
