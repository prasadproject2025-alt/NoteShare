import fs from 'fs';
import path from 'path';
import { auth } from '../../lib/firebase';
import { createUserWithEmailAndPassword, updateProfile as firebaseUpdateProfile } from 'firebase/auth';

export default async function handler(req, res) {
  if (req.method !== 'POST') return res.status(405).json({ error: 'Method not allowed' });

  try {
    const { email, otp, password, name } = req.body;
    if (!email || !otp) return res.status(400).json({ error: 'Email and OTP are required' });

    const key = email.replace(/[.@]/g, '_');
    const logsDir = path.join(process.cwd(), 'logs');
    const otpFile = path.join(logsDir, 'otp_data.json');
    if (!fs.existsSync(otpFile)) return res.status(400).json({ error: 'OTP not found or expired' });

    let otpData = {};
    try { otpData = JSON.parse(fs.readFileSync(otpFile, 'utf8') || '{}'); } catch (e) { otpData = {}; }
    const storedData = otpData[key];
    if (!storedData) return res.status(400).json({ error: 'OTP not found or expired' });
    const now = Date.now();
    if (now > storedData.expiresAt) {
      delete otpData[key];
      fs.writeFileSync(otpFile, JSON.stringify(otpData, null, 2), 'utf8');
      return res.status(400).json({ error: 'OTP has expired' });
    }
    if (storedData.otp !== otp.toString()) return res.status(400).json({ error: 'Invalid OTP' });

    // OTP verified - now create the user account if credentials are provided
    let createdUser = null;
    if (password && name) {
      try {
        const userCredential = await createUserWithEmailAndPassword(auth, email, password);
        createdUser = userCredential.user;

        // Create user profile in local users file (fallback to avoid DB permission issues)
        const usersFile = path.join(logsDir, 'users.json');
        let users = {};
        if (fs.existsSync(usersFile)) {
          try { users = JSON.parse(fs.readFileSync(usersFile, 'utf8') || '{}'); } catch (e) { users = {}; }
        }
        users[createdUser.uid] = {
          email,
          name,
          coins: 10,
          createdAt: new Date().toISOString(),
          status: 'active',
          isAdmin: false,
          emailVerified: true,
        };
        fs.writeFileSync(usersFile, JSON.stringify(users, null, 2), 'utf8');

        // Update Firebase profile
        await firebaseUpdateProfile(createdUser, { displayName: name });
      } catch (firebaseError) {
        console.error('Firebase error:', firebaseError.message);
        if (firebaseError.code !== 'auth/email-already-in-use') {
          return res.status(500).json({ error: 'Failed to create account', details: firebaseError.message });
        }
      }
    }

    // Remove OTP entry
    delete otpData[key];
    fs.writeFileSync(otpFile, JSON.stringify(otpData, null, 2), 'utf8');

    return res.status(200).json({ success: true, message: 'Email verified successfully', email, accountCreated: !!createdUser });
  } catch (error) {
    console.error('OTP verification error:', error);
    return res.status(500).json({ error: 'Failed to verify OTP', details: error.message });
  }
}
