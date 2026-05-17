import fs from 'fs';
import path from 'path';
import admin from 'firebase-admin';

let initialized = false;

export function initFirebaseAdmin() {
  if (initialized) return admin;

  // Try environment variable first (JSON string or base64 encoded)
  const svcEnv = process.env.FIREBASE_SERVICE_ACCOUNT || '';
  let cred = null;
  if (svcEnv) {
    try {
      cred = JSON.parse(svcEnv);
    } catch (e) {
      try {
        const decoded = Buffer.from(svcEnv, 'base64').toString('utf8');
        cred = JSON.parse(decoded);
      } catch (err) {
        console.error('Failed to parse FIREBASE_SERVICE_ACCOUNT env var', err?.message || err);
      }
    }
  }

  // If no env var, try to read firebase-admin.json in project root
  if (!cred) {
    const candidate = path.join(process.cwd(), 'firebase-admin.json');
    if (fs.existsSync(candidate)) {
      try {
        cred = JSON.parse(fs.readFileSync(candidate, 'utf8'));
      } catch (err) {
        console.error('Failed to read firebase-admin.json', err?.message || err);
      }
    }
  }

  if (!cred) {
    // Not initialized; caller should handle lack of admin credentials
    return null;
  }

  try {
    admin.initializeApp({
      credential: admin.credential.cert(cred),
      databaseURL: process.env.NEXT_PUBLIC_FIREBASE_DATABASE_URL || undefined,
    });
    initialized = true;
    return admin;
  } catch (err) {
    console.error('Failed to initialize Firebase Admin', err?.message || err);
    return null;
  }
}
