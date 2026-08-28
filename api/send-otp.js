const crypto = require('crypto');
const { loadEnv } = require('../lib/env');
const { isSmtpConfigured, sendOtpEmail } = require('../lib/mailer');
const { saveOtp, logOtp } = require('../lib/otp-store');

loadEnv();

const OTP_SECRET = process.env.OTP_SECRET || process.env.GMAIL_APP_PASSWORD || 'noteshare_otp_secure_secret_2026';

function generateOTP() {
  return Math.floor(100000 + Math.random() * 900000).toString();
}

function generateOtpToken(email, otp, expiresAt) {
  const payload = `${email.toLowerCase().trim()}:${otp.trim()}:${expiresAt}`;
  const hash = crypto.createHmac('sha256', OTP_SECRET).update(payload).digest('hex');
  return `${hash}.${expiresAt}`;
}

module.exports = async function handler(req, res) {
  if (req.method !== 'POST') {
    return res.status(405).json({ success: false, message: 'Method not allowed' });
  }

  try {
    const { email, name } = req.body || {};
    if (!email) {
      return res.status(400).json({ success: false, message: 'Email is required' });
    }

    if (!email.endsWith('@vitstudent.ac.in')) {
      return res.status(400).json({
        success: false,
        message: 'Please use your VIT student email (@vitstudent.ac.in)',
      });
    }

    const otp = generateOTP();
    const displayName = name || email.split('@')[0];
    const expirationTime = Date.now() + 10 * 60 * 1000;
    const key = email.replace(/[.@]/g, '_');
    const otpToken = generateOtpToken(email, otp, expirationTime);

    // Save fallback to temp file
    saveOtp(key, { otp, email, name: displayName, createdAt: Date.now(), expiresAt: expirationTime });

    if (!isSmtpConfigured()) {
      const allowDevLog = process.env.OTP_DEV_MODE === 'true';
      if (allowDevLog) {
        logOtp(email, otp, false);
        return res.status(200).json({
          success: true,
          devMode: true,
          message:
            'SMTP not configured. OTP logged to temp file — add GMAIL_USERNAME and GMAIL_APP_PASSWORD to environment variables',
          email,
          otpToken,
        });
      }
      return res.status(500).json({
        success: false,
        message: 'Email service is not configured on Vercel.',
        details: 'Missing GMAIL_USERNAME or GMAIL_APP_PASSWORD environment variables in Vercel.',
        hint: 'Go to Vercel Dashboard -> Project Settings -> Environment Variables, add GMAIL_USERNAME and GMAIL_APP_PASSWORD, then redeploy.',
      });
    }

    try {
      await sendOtpEmail({ to: email, name: displayName, otp });
      logOtp(email, otp, true);
      return res.status(200).json({
        success: true,
        message: 'OTP sent to your email successfully! Check inbox and spam folder.',
        email,
        otpToken,
      });
    } catch (mailErr) {
      console.error('SMTP send failed:', mailErr);
      logOtp(email, otp, false);
      return res.status(500).json({
        success: false,
        message: 'Failed to send OTP email via SMTP.',
        details: mailErr.message,
        hint: 'Check Gmail App Password (16 chars), enable 2-Step Verification in Google Account, and ensure GMAIL_USERNAME is set in Vercel environment variables.',
      });
    }
  } catch (error) {
    console.error('OTP sending error:', error);
    return res.status(500).json({
      success: false,
      message: 'Failed to send OTP',
      details: error.message,
    });
  }
};


