import { useEffect, useState } from 'react';
import { useRouter } from 'next/router';
import Header from '../components/Header';
import { auth } from '../lib/firebase';
import { onAuthStateChanged } from 'firebase/auth';
import { getUserProfile } from '../lib/auth';
import { loadSharedNotes, createSharedNote } from '../lib/db';

const initialForm = {
  subject_name: '',
  course_code: '',
  faculty_name: '',
  batch: '',
  year: '',
  description: ''
};

export default function ShareNotesPage() {
  const router = useRouter();
  const [currentUser, setCurrentUser] = useState(null);
  const [form, setForm] = useState(initialForm);
  const [notes, setNotes] = useState([]);
  const [loading, setLoading] = useState(true);
  const [status, setStatus] = useState('');

  useEffect(() => {
    const unsubscribe = onAuthStateChanged(auth, async (user) => {
      if (!user) {
        router.push('/login');
        return;
      }
      const profile = await getUserProfile(user);
      setCurrentUser({ uid: profile.uid, email: profile.email, name: profile.name });
      setLoading(true);
      await loadNotes({});
      setLoading(false);
    });
    return unsubscribe;
  }, [router]);

  async function loadNotes(filters) {
    const results = await loadSharedNotes(filters);
    setNotes(results);
  }

  function handleChange(event) {
    const { name, value } = event.target;
    setForm((prev) => ({ ...prev, [name]: value }));
  }

  async function handleSubmit(event) {
    event.preventDefault();
    setStatus('');
    if (!form.subject_name || !form.course_code || !form.faculty_name || !form.batch || !form.year || !form.description) {
      setStatus('Fill all fields to share your notes.');
      return;
    }
    setLoading(true);
    try {
      await createSharedNote({
        subject_name: form.subject_name,
        course_code: form.course_code,
        faculty_name: form.faculty_name,
        batch: form.batch,
        year: form.year,
        description: form.description,
        sharer_id: currentUser.uid,
        sharer_name: currentUser.name,
        sharer_email: currentUser.email,
        created_at: Date.now()
      });
      setForm(initialForm);
      setStatus('Notes shared successfully!');
      await loadNotes({});
    } catch (error) {
      setStatus(error.message || 'Unable to share notes');
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
            <h2>Share Notes</h2>
            <p className="text-muted">Share your notes with classmates in the same VIT slot.</p>
            {status && <div className="alert alert-info">{status}</div>}
            <div className="card mb-4">
              <div className="card-body">
                <form onSubmit={handleSubmit}>
                  <div className="row g-3">
                    <div className="col-md-6">
                      <label className="form-label">Subject Name</label>
                      <input className="form-control" name="subject_name" value={form.subject_name} onChange={handleChange} required />
                    </div>
                    <div className="col-md-6">
                      <label className="form-label">Course Code</label>
                      <input className="form-control" name="course_code" value={form.course_code} onChange={handleChange} required />
                    </div>
                    <div className="col-md-6">
                      <label className="form-label">Faculty Name</label>
                      <input className="form-control" name="faculty_name" value={form.faculty_name} onChange={handleChange} required />
                    </div>
                    <div className="col-md-3">
                      <label className="form-label">VIT Slot</label>
                      <select className="form-select" name="batch" value={form.batch} onChange={handleChange} required>
                        <option value="">Select slot</option>
                        {['A1','A2','B1','B2','C1','C2','D1','D2','E1','E2','F1','F2','G1','G2'].map((slot) => (
                          <option key={slot} value={slot}>{slot}</option>
                        ))}
                      </select>
                    </div>
                    <div className="col-md-3">
                      <label className="form-label">Year</label>
                      <select className="form-select" name="year" value={form.year} onChange={handleChange} required>
                        <option value="">Year</option>
                        {[1,2,3,4].map((year) => (
                          <option key={year} value={year}>{year}</option>
                        ))}
                      </select>
                    </div>
                    <div className="col-12">
                      <label className="form-label">Description</label>
                      <textarea className="form-control" name="description" value={form.description} onChange={handleChange} rows="3" required />
                    </div>
                  </div>
                  <button className="btn btn-info mt-3" type="submit" disabled={loading}>{loading ? 'Sharing...' : 'Share Notes'}</button>
                </form>
              </div>
            </div>

            <div>
              <h4>Shared Notes</h4>
              {loading ? (
                <div className="text-center py-4">Loading...</div>
              ) : notes.length === 0 ? (
                <div className="alert alert-secondary">No shared notes available yet.</div>
              ) : notes.map((note) => (
                <div className="card mb-3" key={note.id}>
                  <div className="card-body">
                    <h5>{note.subject_name}</h5>
                    <p className="mb-1"><strong>Course:</strong> {note.course_code}</p>
                    <p className="mb-1"><strong>Slot:</strong> {note.batch}</p>
                    <p className="mb-1"><strong>Shared by:</strong> {note.sharer_name}</p>
                    <p>{note.description}</p>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </main>
    </div>
  );
}
