const fs = require('fs');
const path = require('path');
const { isSmtpConfigured, sendOtpEmail } = require('../lib/mailer');

function generateOTP() {
  return Math.floor(100000 + Math.random() * 900000).toString();
}

function isDevMode() {
  return (
    process.env.OTP_DEV_MODE === 'true' ||
    process.env.NODE_ENV === 'development' ||
    process.env.APP_ENV === 'development' ||
    !process.env.VERCEL
  );
}

function logOtpDev(email, otp) {
  const logsDir = path.join(process.cwd(), 'logs');
  if (!fs.existsSync(logsDir)) fs.mkdirSync(logsDir, { recursive: true });
  const line = `${new Date().toISOString()} - OTP for ${email}: ${otp} (dev mode - email not sent)\n`;
  fs.appendFileSync(path.join(logsDir, 'otp_log.txt'), line, 'utf8');
  console.log(`[NoteShare DEV] OTP for ${email}: ${otp}`);
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
    const logsDir = path.join(process.cwd(), 'logs');
    const otpFile = path.join(logsDir, 'otp_data.json');

    if (!fs.existsSync(logsDir)) fs.mkdirSync(logsDir, { recursive: true });

    let otpData = {};
    if (fs.existsSync(otpFile)) {
      try {
        otpData = JSON.parse(fs.readFileSync(otpFile, 'utf8') || '{}');
      } catch {
        otpData = {};
      }
    }

    const now = Date.now();
    for (const k of Object.keys(otpData)) {
      if (otpData[k].expiresAt < now) delete otpData[k];
    }

    otpData[key] = { otp, email, name: displayName, createdAt: now, expiresAt: expirationTime };
    fs.writeFileSync(otpFile, JSON.stringify(otpData, null, 2), 'utf8');

    const smtpReady = isSmtpConfigured();

    if (smtpReady) {
      try {
        await sendOtpEmail({ to: email, name: displayName, otp });
        return res.status(200).json({
          success: true,
          message: 'OTP sent to your email successfully',
          email,
        });
      } catch (mailErr) {
        console.error('SMTP send failed:', mailErr.message);
        if (!isDevMode()) {
          return res.status(500).json({
            success: false,
            message: 'Failed to send OTP email. Check SMTP settings.',
            details: mailErr.message,
          });
        }
      }
    }

    if (isDevMode()) {
      logOtpDev(email, otp);
      return res.status(200).json({
        success: true,
        message:
          'OTP generated (dev mode). Email was not sent — check logs/otp_log.txt or the server console for your code.',
        email,
        devMode: true,
      });
    }

    return res.status(500).json({
      success: false,
      message:
        'Email is not configured. Set GMAIL_USERNAME and GMAIL_APP_PASSWORD in .env.local (see .env.example).',
    });
  } catch (error) {
    console.error('OTP sending error:', error);
    return res.status(500).json({
      success: false,
      message: 'Failed to send OTP',
      details: error.message,
    });
  }
};
