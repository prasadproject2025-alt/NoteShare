import { ref, get } from 'firebase/database';
import { database } from '../../../lib/firebase';
import { initFirebaseAdmin } from '../../../lib/firebaseAdmin';

export default async function handler(req, res) {
  if (req.method !== 'GET') return res.status(405).end();

  // Prefer Admin SDK if available
  const admin = initFirebaseAdmin();
  if (admin) {
    try {
      const snap = await admin.database().ref('/').once('value');
      const val = snap.val() || {};
      const keys = Object.keys(val).slice(0, 20);
      return res.status(200).json({ ok: true, admin: true, keys });
    } catch (err) {
      return res.status(500).json({ ok: false, admin: true, error: err?.message || String(err) });
    }
  }

  // Fallback to client SDK
  try {
    const snap = await get(ref(database, '/'));
    if (!snap.exists()) {
      return res.status(200).json({ ok: true, admin: false, message: 'Connected to Firebase Realtime Database, no root data.' });
    }
    const val = snap.val();
    const keys = Object.keys(val || {}).slice(0, 20);
    return res.status(200).json({ ok: true, admin: false, keys });
  } catch (err) {
    return res.status(500).json({ ok: false, admin: false, error: err?.message || String(err) });
  }
}
