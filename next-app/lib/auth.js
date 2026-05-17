import {
  createUserWithEmailAndPassword,
  signInWithEmailAndPassword,
  signOut,
  onAuthStateChanged,
  updateProfile as firebaseUpdateProfile
} from 'firebase/auth';
import {
  ref,
  set,
  get,
  query,
  orderByChild,
  equalTo,
  update
} from 'firebase/database';
import { auth, database } from './firebase';

export function onAuthState(callback) {
  return onAuthStateChanged(auth, callback);
}

export async function loginUser(email, password) {
  return signInWithEmailAndPassword(auth, email, password);
}

export async function registerUser(email, password, name) {
  const userCredential = await createUserWithEmailAndPassword(auth, email, password);
  const user = userCredential.user;
  try {
    await set(ref(database, `users/${user.uid}`), {
      email,
      name,
      coins: 10,
      createdAt: new Date().toISOString(),
      status: 'active',
      isAdmin: false
    });
  } catch (err) {
    // If DB write is blocked by rules, log and continue — keep account creation successful
    console.error('Failed to write user to Realtime DB (permissions?), continuing anyway', err?.message || err);
  }
  try {
    await firebaseUpdateProfile(user, { displayName: name });
  } catch (err) {
    console.error('Failed to update firebase profile', err?.message || err);
  }
  return user;
}

export async function logoutUser() {
  return signOut(auth);
}

export async function getUserData(uid) {
  try {
    const snap = await get(ref(database, `users/${uid}`));
    return snap.exists() ? snap.val() : null;
  } catch (err) {
    // Permission errors should not break the client — return null and let callers fallback.
    console.error('getUserData failed (permission/read error)', err?.message || err);
    return null;
  }
}

export async function getUserProfile(user) {
  const profile = await getUserData(user.uid);
  if (profile) {
    return {
      uid: user.uid,
      email: user.email,
      name: profile.name || user.displayName || user.email?.split('@')[0] || 'User',
      coins: profile.coins || 0,
      ...profile
    };
  }

  const fallbackName = user.displayName || user.email?.split('@')[0] || 'User';
  return {
    uid: user.uid,
    email: user.email,
    name: fallbackName,
    coins: 0,
    status: 'active',
    createdAt: new Date().toISOString(),
    fallback: true
  };
}

export async function updateUserProfile(uid, updates) {
  try {
    await update(ref(database, `users/${uid}`), updates);
  } catch (err) {
    console.error('updateUserProfile failed (permission/write error)', err?.message || err);
  }
}

export async function findUserByEmail(email) {
  try {
    const q = query(ref(database, 'users'), orderByChild('email'), equalTo(email));
    const snapshot = await get(q);
    if (!snapshot.exists()) {
      return null;
    }
    const value = snapshot.val();
    const key = Object.keys(value)[0];
    return { uid: key, ...value[key] };
  } catch (err) {
    console.error('findUserByEmail failed (permission/read error)', err?.message || err);
    return null;
  }
}

export async function isAdminUser(uid) {
  const user = await getUserData(uid);
  if (user?.isAdmin === true) {
    return true;
  }
  return false;
}

export function isAdminEmail(email) {
  const adminEmail = process.env.NEXT_PUBLIC_ADMIN_EMAIL;
  return !!adminEmail && email === adminEmail;
}
