const crypto = require('crypto');
const { loadEnv } = require('../lib/env');
const { getOtp, deleteOtp } = require('../lib/otp-store');

loadEnv();

const OTP_SECRET = process.env.OTP_SECRET || process.env.GMAIL_APP_PASSWORD || 'noteshare_otp_secure_secret_2026';

function verifyStatelessToken(email, otp, otpToken) {
  if (!otpToken || typeof otpToken !== 'string' || !otpToken.includes('.')) return false;
  const [hash, expiresAtStr] = otpToken.split('.');
  const expiresAt = parseInt(expiresAtStr, 10);
  if (isNaN(expiresAt) || Date.now() > expiresAt) return false;

  const payload = `${email.toLowerCase().trim()}:${otp.trim()}:${expiresAt}`;
  const expectedHash = crypto.createHmac('sha256', OTP_SECRET).update(payload).digest('hex');

  try {
    return crypto.timingSafeEqual(Buffer.from(hash), Buffer.from(expectedHash));
  } catch {
    return false;
  }
}

module.exports = async function handler(req, res) {
  if (req.method !== 'POST') {
    return res.status(405).json({ success: false, message: 'Method not allowed' });
  }

  try {
    const { email, otp, otpToken } = req.body || {};
    if (!email || !otp) {
      return res.status(400).json({ success: false, message: 'Email and OTP are required' });
    }

    if (!email.endsWith('@vitstudent.ac.in')) {
      return res.status(400).json({ success: false, message: 'Invalid VIT email format' });
    }

    // 1. Try stateless token verification first (works 100% across serverless container instances)
    if (otpToken) {
      const isValid = verifyStatelessToken(email, otp, otpToken);
      if (isValid) {
        return res.status(200).json({ success: true, message: 'OTP verified successfully' });
      }
      // Check if expired
      if (otpToken.includes('.')) {
        const expiresAt = parseInt(otpToken.split('.')[1], 10);
        if (!isNaN(expiresAt) && Date.now() > expiresAt) {
          return res.status(400).json({ success: false, message: 'OTP has expired. Please request a new one.' });
        }
      }
      return res.status(400).json({ success: false, message: 'Invalid OTP. Please check the 6-digit code sent to your email.' });
    }

    // 2. Fallback to temp file storage verification (for local dev / single instance)
    const key = email.replace(/[.@]/g, '_');
    const stored = getOtp(key);

    if (!stored) {
      return res.status(400).json({ success: false, message: 'No OTP found. Please request a new one.' });
    }

    if (Date.now() > stored.expiresAt) {
      deleteOtp(key);
      return res.status(400).json({ success: false, message: 'OTP has expired. Please request a new one.' });
    }

    if (String(stored.otp) !== String(otp).trim()) {
      return res.status(400).json({ success: false, message: 'Invalid OTP' });
    }

    if (stored.email !== email) {
      return res.status(400).json({ success: false, message: 'Email mismatch' });
    }

    deleteOtp(key);
    return res.status(200).json({ success: true, message: 'OTP verified successfully' });
  } catch (error) {
    console.error('OTP verification error:', error);
    return res.status(500).json({
      success: false,
      message: 'Failed to verify OTP',
      details: error.message,
    });
  }
};


