import { useEffect, useState } from 'react';
import { useRouter } from 'next/router';
import Header from '../components/Header';
import { auth } from '../lib/firebase';
import { onAuthStateChanged } from 'firebase/auth';
import { searchNotes, buyNote } from '../lib/db';
import { getUserProfile } from '../lib/auth';

export default function BuyNotesPage() {
  const router = useRouter();
  const [currentUser, setCurrentUser] = useState(null);
  const [notes, setNotes] = useState([]);
  const [filters, setFilters] = useState({ courseCode: '', subjectName: '', slot: '' });
  const [loading, setLoading] = useState(true);
  const [message, setMessage] = useState('');

  useEffect(() => {
    const unsubscribe = onAuthStateChanged(auth, async (user) => {
      if (!user) {
        router.push('/login');
        return;
      }

      const profile = await getUserProfile(user);
      if (profile?.fallback) {
        setMessage('Unable to load profile from database. Some features may be limited.');
      }

      setCurrentUser({ uid: profile.uid, email: profile.email, name: profile.name, coins: profile.coins || 0 });
      await loadNotes(filters);
      setLoading(false);
    });

    return unsubscribe;
  }, [router]);

  async function loadNotes(filterValues = filters) {
    setLoading(true);
    const results = await searchNotes(filterValues);
    setNotes(results);
    setLoading(false);
  }

  function handleChange(event) {
    const { name, value } = event.target;
    setFilters((prev) => ({ ...prev, [name]: value }));
  }

  async function handleSearch(event) {
    event.preventDefault();
    await loadNotes(filters);
  }

  async function handleBuy(note) {
    if (!currentUser) return;
    const confirmed = window.confirm(`Spend ${note.price} coins to buy ${note.subject_name}?`);
    if (!confirmed) return;

    try {
      await buyNote(note.id, currentUser.uid, currentUser.name, currentUser.email, Number(note.price));
      setMessage('Purchase complete! Your coins and notes have been updated.');
      await loadNotes(filters);
    } catch (error) {
      setMessage(error.message || 'Unable to complete purchase');
    }
  }

  return (
    <div>
      <Header currentUser={currentUser} />
      <main className="container mt-4">
        <div className="row">
          <div className="col-md-12">
            <h2>Buy Notes</h2>
            <p className="text-muted">Search available notes and purchase them using coins.</p>
          </div>
        </div>

        <div className="card mb-4">
          <div className="card-body">
            <form className="row g-3" onSubmit={handleSearch}>
              <div className="col-md-4">
                <label className="form-label">Course Code</label>
                <input name="courseCode" value={filters.courseCode} onChange={handleChange} className="form-control" placeholder="CSE101" />
              </div>
              <div className="col-md-4">
                <label className="form-label">Subject Name</label>
                <input name="subjectName" value={filters.subjectName} onChange={handleChange} className="form-control" placeholder="Data Structures" />
              </div>
              <div className="col-md-3">
                <label className="form-label">VIT Slot</label>
                <select name="slot" value={filters.slot} onChange={handleChange} className="form-select">
                  <option value="">All Slots</option>
                  {['A1','A2','B1','B2','C1','C2','D1','D2','E1','E2','F1','F2','G1','G2'].map((slot) => (
                    <option key={slot} value={slot}>{slot}</option>
                  ))}
                </select>
              </div>
              <div className="col-md-1 d-flex align-items-end">
                <button className="btn btn-primary w-100" type="submit">Search</button>
              </div>
            </form>
          </div>
        </div>

        {message && <div className="alert alert-info">{message}</div>}

        {loading ? (
          <div className="text-center py-5">
            <div className="spinner-border text-primary"></div>
          </div>
        ) : (
          <div className="row gy-3">
            {notes.length === 0 ? (
              <div className="col-12"><div className="alert alert-secondary">No notes found. Try changing your filters.</div></div>
            ) : notes.map((note) => (
              <div className="col-md-6" key={note.id}>
                <div className="card h-100">
                  <div className="card-body">
                    <h5>{note.subject_name}</h5>
                    <p className="mb-1"><strong>Course:</strong> {note.course_code}</p>
                    <p className="mb-1"><strong>Slot:</strong> {note.slot}</p>
                    <p className="mb-1"><strong>Faculty:</strong> {note.faculty_name}</p>
                    <p className="mb-1"><strong>Price:</strong> {note.price} coins</p>
                    <p className="text-muted small">Seller: {note.seller_name}</p>
                    <button className="btn btn-success mt-2" onClick={() => handleBuy(note)}>Buy Note</button>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </main>
    </div>
  );
}
