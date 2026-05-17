import { useEffect, useState } from 'react';
import { useRouter } from 'next/router';
import Header from '../components/Header';
import { auth } from '../lib/firebase';
import { onAuthStateChanged } from 'firebase/auth';
import { getUserProfile } from '../lib/auth';
import { createNote } from '../lib/db';

const initialForm = {
  subject_name: '',
  course_code: '',
  faculty_name: '',
  slot: '',
  year: '',
  description: '',
  price: '',
  images: []
};

export default function SellNotesPage() {
  const router = useRouter();
  const [currentUser, setCurrentUser] = useState(null);
  const [form, setForm] = useState(initialForm);
  const [loading, setLoading] = useState(false);
  const [status, setStatus] = useState('');

  useEffect(() => {
    const unsubscribe = onAuthStateChanged(auth, async (user) => {
      if (!user) {
        router.push('/login');
        return;
      }
      const profile = await getUserProfile(user);
      setCurrentUser({ uid: profile.uid, email: profile.email, name: profile.name });
    });
    return unsubscribe;
  }, [router]);

  function handleChange(event) {
    const { name, value } = event.target;
    setForm((prev) => ({ ...prev, [name]: value }));
  }

  function handleImages(event) {
    const files = Array.from(event.target.files).slice(0, 5);
    const readers = files.map((file) => new Promise((resolve) => {
      const reader = new FileReader();
      reader.onload = () => resolve(reader.result);
      reader.readAsDataURL(file);
    }));
    Promise.all(readers).then((dataUrls) => {
      setForm((prev) => ({ ...prev, images: dataUrls }));
    });
  }

  async function handleSubmit(event) {
    event.preventDefault();
    setStatus('');

    if (!currentUser) return;
    if (!form.subject_name || !form.course_code || !form.faculty_name || !form.slot || !form.year || !form.description || !form.price) {
      setStatus('Please fill in all fields before submitting.');
      return;
    }

    setLoading(true);
    try {
      await createNote({
        subject_name: form.subject_name,
        course_code: form.course_code,
        faculty_name: form.faculty_name,
        slot: form.slot,
        year: form.year,
        description: form.description,
        price: Number(form.price),
        seller_id: currentUser.uid,
        seller_name: currentUser.name,
        seller_email: currentUser.email,
        images: form.images,
        status: 'available',
        created_at: Date.now()
      });
      setStatus('Note posted successfully.');
      setForm(initialForm);
    } catch (error) {
      setStatus(error.message || 'Unable to publish note.');
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
            <h2>Sell Your Notes</h2>
            <p className="text-muted">List notes for sale and earn coins when others purchase them.</p>
            {status && <div className="alert alert-info">{status}</div>}
            <div className="card">
              <div className="card-body">
                <form onSubmit={handleSubmit}>
                  <div className="mb-3">
                    <label className="form-label">Subject Name</label>
                    <input name="subject_name" value={form.subject_name} onChange={handleChange} className="form-control" required />
                  </div>
                  <div className="mb-3">
                    <label className="form-label">Course Code</label>
                    <input name="course_code" value={form.course_code} onChange={handleChange} className="form-control" required />
                  </div>
                  <div className="mb-3">
                    <label className="form-label">Faculty Name</label>
                    <input name="faculty_name" value={form.faculty_name} onChange={handleChange} className="form-control" required />
                  </div>
                  <div className="row">
                    <div className="col-md-6 mb-3">
                      <label className="form-label">VIT Slot</label>
                      <select name="slot" value={form.slot} onChange={handleChange} className="form-select" required>
                        <option value="">Select your slot</option>
                        {['A1','A2','B1','B2','C1','C2','D1','D2','E1','E2','F1','F2','G1','G2'].map((slot) => (
                          <option key={slot} value={slot}>{slot}</option>
                        ))}
                      </select>
                    </div>
                    <div className="col-md-6 mb-3">
                      <label className="form-label">Year</label>
                      <select name="year" value={form.year} onChange={handleChange} className="form-select" required>
                        <option value="">Select year</option>
                        {[1,2,3,4].map((year) => (
                          <option key={year} value={year}>{year} Year</option>
                        ))}
                      </select>
                    </div>
                  </div>
                  <div className="mb-3">
                    <label className="form-label">Description</label>
                    <textarea name="description" value={form.description} onChange={handleChange} className="form-control" rows="4" required />
                  </div>
                  <div className="mb-3">
                    <label className="form-label">Price (coins)</label>
                    <input name="price" type="number" min="0" value={form.price} onChange={handleChange} className="form-control" required />
                  </div>
                  <div className="mb-3">
                    <label className="form-label">Upload Images</label>
                    <input type="file" accept="image/*" multiple className="form-control" onChange={handleImages} />
                    <div className="form-text">Optional. Up to 5 images.</div>
                  </div>
                  <button className="btn btn-success" type="submit" disabled={loading}>{loading ? 'Posting...' : 'Post Note'}</button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  );
}
