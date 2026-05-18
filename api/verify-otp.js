const fs = require('fs');
const path = require('path');

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
    const otpFile = path.join(process.cwd(), 'logs', 'otp_data.json');

    if (!fs.existsSync(otpFile)) {
      return res.status(400).json({ success: false, message: 'No OTP found. Please request a new one.' });
    }

    let otpData = {};
    try {
      otpData = JSON.parse(fs.readFileSync(otpFile, 'utf8') || '{}');
    } catch {
      otpData = {};
    }

    const stored = otpData[key];
    if (!stored) {
      return res.status(400).json({ success: false, message: 'No OTP found. Please request a new one.' });
    }

    if (Date.now() > stored.expiresAt) {
      delete otpData[key];
      fs.writeFileSync(otpFile, JSON.stringify(otpData, null, 2), 'utf8');
      return res.status(400).json({ success: false, message: 'OTP has expired. Please request a new one.' });
    }

    if (String(stored.otp) !== String(otp).trim()) {
      return res.status(400).json({ success: false, message: 'Invalid OTP' });
    }

    if (stored.email !== email) {
      return res.status(400).json({ success: false, message: 'Email mismatch' });
    }

    delete otpData[key];
    fs.writeFileSync(otpFile, JSON.stringify(otpData, null, 2), 'utf8');

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
