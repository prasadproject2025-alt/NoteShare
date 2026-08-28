const { loadEnv } = require('../lib/env');
const { getOtp, deleteOtp } = require('../lib/otp-store');

loadEnv();

module.exports = async function handler(req, res) {
  if (req.method !== 'POST') {
    return res.status(405).json({ success: false, message: 'Method not allowed' });
  }

  try {
    const { email, otp } = req.body || {};
    if (!email || !otp) {
      return res.status(400).json({ success: false, message: 'Email and OTP are required' });
    }

    if (!email.endsWith('@vitstudent.ac.in')) {
      return res.status(400).json({ success: false, message: 'Invalid VIT email format' });
    }

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

