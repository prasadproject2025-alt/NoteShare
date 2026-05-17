import { useEffect, useState } from 'react';
import { useRouter } from 'next/router';
import Header from '../components/Header';
import { auth } from '../lib/firebase';
import { onAuthStateChanged } from 'firebase/auth';
import { getUserProfile } from '../lib/auth';
import { loadRentalNotes, createRentalNote } from '../lib/db';

const initialForm = {
  subject_name: '',
  course_code: '',
  faculty_name: '',
  slot: '',
  year: '',
  daily_price: '',
  rental_period: '',
  description: ''
};

export default function RentNotesPage() {
  const router = useRouter();
  const [currentUser, setCurrentUser] = useState(null);
  const [form, setForm] = useState(initialForm);
  const [notes, setNotes] = useState([]);
  const [loading, setLoading] = useState(true);
  const [status, setStatus] = useState('');
  const [search, setSearch] = useState({ courseCode: '', slot: '' });

  useEffect(() => {
    const unsubscribe = onAuthStateChanged(auth, async (user) => {
      if (!user) {
        router.push('/login');
        return;
      }
      const profile = await getUserProfile(user);
      setCurrentUser({ uid: profile.uid, email: profile.email, name: profile.name });
      await loadNotes(search);
      setLoading(false);
    });
    return unsubscribe;
  }, [router]);

  async function loadNotes(filters) {
    setLoading(true);
    const results = await loadRentalNotes(filters);
    setNotes(results);
    setLoading(false);
  }

  function handleChange(event) {
    const { name, value } = event.target;
    setForm((prev) => ({ ...prev, [name]: value }));
  }

  function handleSearchChange(event) {
    const { name, value } = event.target;
    setSearch((prev) => ({ ...prev, [name]: value }));
  }

  async function handleSearch(event) {
    event.preventDefault();
    await loadNotes(search);
  }

  async function handleSubmit(event) {
    event.preventDefault();
    if (!currentUser) return;
    if (!form.subject_name || !form.course_code || !form.faculty_name || !form.slot || !form.year || !form.daily_price || !form.rental_period || !form.description) {
      setStatus('Please complete all fields.');
      return;
    }
    setLoading(true);
    try {
      await createRentalNote({
        subject_name: form.subject_name,
        course_code: form.course_code,
        faculty_name: form.faculty_name,
        slot: form.slot,
        year: form.year,
        daily_price: Number(form.daily_price),
        rental_period: Number(form.rental_period),
        description: form.description,
        owner_id: currentUser.uid,
        owner_name: currentUser.name,
        owner_email: currentUser.email,
        available: true,
        created_at: Date.now()
      });
      setForm(initialForm);
      setStatus('Rental listing posted successfully!');
      await loadNotes(search);
    } catch (error) {
      setStatus(error.message || 'Unable to post rental note');
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
            <h2>Rent Notes</h2>
            <p className="text-muted">Search rental notes or list your own notes for rent.</p>
            {status && <div className="alert alert-info">{status}</div>}
            <div className="card mb-4">
              <div className="card-body">
                <form className="row g-3" onSubmit={handleSearch}>
                  <div className="col-md-5">
                    <label className="form-label">Course Code</label>
                    <input className="form-control" name="courseCode" value={search.courseCode} onChange={handleSearchChange} placeholder="CSE101" />
                  </div>
                  <div className="col-md-5">
                    <label className="form-label">VIT Slot</label>
                    <select className="form-select" name="slot" value={search.slot} onChange={handleSearchChange}>
                      <option value="">All Slots</option>
                      {['A1','A2','B1','B2','C1','C2','D1','D2','E1','E2','F1','F2','G1','G2'].map((slot) => (
                        <option key={slot} value={slot}>{slot}</option>
                      ))}
                    </select>
                  </div>
                  <div className="col-md-2 d-flex align-items-end">
                    <button className="btn btn-primary w-100">Search</button>
                  </div>
                </form>
              </div>
            </div>

            {loading ? (
              <div className="text-center py-4"><div className="spinner-border text-primary"></div></div>
            ) : notes.length === 0 ? (
              <div className="alert alert-secondary">No rental notes found.</div>
            ) : notes.map((note) => (
              <div key={note.id} className="card mb-3">
                <div className="card-body">
                  <h5>{note.subject_name}</h5>
                  <p className="mb-1"><strong>Course:</strong> {note.course_code} - <strong>Slot:</strong> {note.slot}</p>
                  <p className="mb-1"><strong>Daily:</strong> ₹{note.daily_price} - <strong>Period:</strong> {note.rental_period} days</p>
                  <p className="text-muted">Owner: {note.owner_name}</p>
                  <p>{note.description}</p>
                </div>
              </div>
            ))}
          </div>
          <div className="col-md-4">
            <div className="card mb-4">
              <div className="card-header bg-light">List Notes for Rent</div>
              <div className="card-body">
                <form onSubmit={handleSubmit}>
                  <div className="mb-3">
                    <label className="form-label">Subject Name</label>
                    <input className="form-control" name="subject_name" value={form.subject_name} onChange={handleChange} required />
                  </div>
                  <div className="mb-3">
                    <label className="form-label">Course Code</label>
                    <input className="form-control" name="course_code" value={form.course_code} onChange={handleChange} required />
                  </div>
                  <div className="mb-3">
                    <label className="form-label">Faculty Name</label>
                    <input className="form-control" name="faculty_name" value={form.faculty_name} onChange={handleChange} required />
                  </div>
                  <div className="row g-3">
                    <div className="col-md-6">
                      <label className="form-label">VIT Slot</label>
                      <select className="form-select" name="slot" value={form.slot} onChange={handleChange} required>
                        <option value="">Select slot</option>
                        {['A1','A2','B1','B2','C1','C2','D1','D2','E1','E2','F1','F2','G1','G2'].map((slot) => (
                          <option key={slot} value={slot}>{slot}</option>
                        ))}
                      </select>
                    </div>
                    <div className="col-md-6">
                      <label className="form-label">Year</label>
                      <select className="form-select" name="year" value={form.year} onChange={handleChange} required>
                        <option value="">Year</option>
                        {[1,2,3,4].map((year) => (
                          <option key={year} value={year}>{year}</option>
                        ))}
                      </select>
                    </div>
                  </div>
                  <div className="mb-3">
                    <label className="form-label">Daily Price (₹)</label>
                    <input className="form-control" type="number" min="0" name="daily_price" value={form.daily_price} onChange={handleChange} required />
                  </div>
                  <div className="mb-3">
                    <label className="form-label">Rental Period (Days)</label>
                    <input className="form-control" type="number" min="1" max="30" name="rental_period" value={form.rental_period} onChange={handleChange} required />
                  </div>
                  <div className="mb-3">
                    <label className="form-label">Description</label>
                    <textarea className="form-control" name="description" value={form.description} onChange={handleChange} rows="3" required />
                  </div>
                  <button className="btn btn-warning w-100" type="submit" disabled={loading}>{loading ? 'Posting...' : 'Post Rental'}</button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  );
}
