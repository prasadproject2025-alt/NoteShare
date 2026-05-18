const fs = require('fs');
const path = require('path');
const { loadEnv } = require('../lib/env');
const { isSmtpConfigured, sendOtpEmail } = require('../lib/mailer');

loadEnv();

function generateOTP() {
  return Math.floor(100000 + Math.random() * 900000).toString();
}

function logOtp(email, otp, emailSent) {
  const logsDir = path.join(process.cwd(), 'logs');
  if (!fs.existsSync(logsDir)) fs.mkdirSync(logsDir, { recursive: true });
  const line = `${new Date().toISOString()} - OTP for ${email}: ${otp} - Email sent: ${emailSent ? 'YES' : 'NO'}\n`;
  fs.appendFileSync(path.join(logsDir, 'otp_log.txt'), line, 'utf8');
  if (!emailSent) {
    console.log(`[NoteShare] OTP for ${email}: ${otp} (logged — email not sent)`);
  }
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

    if (!isSmtpConfigured()) {
      const allowDevLog = process.env.OTP_DEV_MODE === 'true';
      if (allowDevLog) {
        logOtp(email, otp, false);
        return res.status(200).json({
          success: true,
          devMode: true,
          message:
            'SMTP not configured. OTP saved to logs/otp_log.txt — add GMAIL_USERNAME and GMAIL_APP_PASSWORD to .env.local',
          email,
        });
      }
      return res.status(500).json({
        success: false,
        message:
          'Email not configured. Add GMAIL_USERNAME and GMAIL_APP_PASSWORD to .env.local (see .env.example), then restart the server.',
      });
    }

    try {
      await sendOtpEmail({ to: email, name: displayName, otp });
      logOtp(email, otp, true);
      return res.status(200).json({
        success: true,
        message: 'OTP sent to your email successfully! Check inbox and spam folder.',
        email,
      });
    } catch (mailErr) {
      console.error('SMTP send failed:', mailErr);
      logOtp(email, otp, false);
      return res.status(500).json({
        success: false,
        message: 'Failed to send OTP email.',
        details: mailErr.message,
        hint: 'Check Gmail App Password, enable 2FA, and ensure FROM_EMAIL matches GMAIL_USERNAME.',
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
