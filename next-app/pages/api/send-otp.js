import nodemailer from 'nodemailer';
import fs from 'fs';
import path from 'path';

function generateOTP() {
  return Math.floor(100000 + Math.random() * 900000).toString();
}

const transporter = nodemailer.createTransport({
  host: process.env.SMTP_HOST || 'smtp.gmail.com',
  port: process.env.SMTP_PORT ? parseInt(process.env.SMTP_PORT) : 587,
  secure: false,
  auth: {
    user: process.env.GMAIL_USERNAME,
    pass: process.env.GMAIL_APP_PASSWORD,
  },
});

export default async function handler(req, res) {
  if (req.method !== 'POST') return res.status(405).json({ error: 'Method not allowed' });

  try {
    const { email, name } = req.body;
    if (!email || !name) return res.status(400).json({ error: 'Email and name are required' });

    // Generate OTP and store in a local JSON file
    const otp = generateOTP();
    const expirationTime = Date.now() + 10 * 60 * 1000; // 10 minutes
    const key = email.replace(/[.@]/g, '_');
    const logsDir = path.join(process.cwd(), 'logs');
    const otpFile = path.join(logsDir, 'otp_data.json');

    if (!fs.existsSync(logsDir)) fs.mkdirSync(logsDir, { recursive: true });
    let otpData = {};
    if (fs.existsSync(otpFile)) {
      try { otpData = JSON.parse(fs.readFileSync(otpFile, 'utf8') || '{}'); } catch (e) { otpData = {}; }
    }

    // Clean expired
    const now = Date.now();
    for (const k of Object.keys(otpData)) {
      if (otpData[k].expiresAt < now) delete otpData[k];
    }

    otpData[key] = { otp, email, name, createdAt: Date.now(), expiresAt: expirationTime };
    fs.writeFileSync(otpFile, JSON.stringify(otpData, null, 2), 'utf8');

    // Send email via reusable mailer
    const { sendOtpEmail } = await import('../../lib/mailer');
    await sendOtpEmail({ to: email, name, otp });

    return res.status(200).json({ success: true, message: 'OTP sent to your email successfully', email });
  } catch (error) {
    console.error('OTP sending error:', error);
    return res.status(500).json({ error: 'Failed to send OTP', details: error.message });
  }
}
