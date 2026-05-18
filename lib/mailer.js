const nodemailer = require('nodemailer');

function getSmtpConfig() {
  const user =
    process.env.GMAIL_USERNAME ||
    process.env.SMTP_USERNAME ||
    process.env.SMTP_USER ||
    '';
  const pass = (
    process.env.GMAIL_APP_PASSWORD ||
    process.env.SMTP_PASSWORD ||
    process.env.SMTP_PASS ||
    ''
  ).replace(/\s+/g, '');

  return {
    user: user.trim(),
    pass,
    host: process.env.SMTP_HOST || 'smtp.gmail.com',
    port: process.env.SMTP_PORT ? parseInt(process.env.SMTP_PORT, 10) : 587,
    fromEmail: (process.env.FROM_EMAIL || process.env.SMTP_FROM_EMAIL || user).trim(),
    fromName: process.env.FROM_NAME || process.env.SMTP_FROM_NAME || 'NoteShare',
  };
}

function isSmtpConfigured() {
  const { user, pass } = getSmtpConfig();
  if (!user || !pass) return false;
  if (user.includes('your-email') || pass.includes('your-app')) return false;
  return true;
}

function createTransporter() {
  const cfg = getSmtpConfig();
  return nodemailer.createTransport({
    host: cfg.host,
    port: cfg.port,
    secure: false,
    auth: { user: cfg.user, pass: cfg.pass },
  });
}

async function sendOtpEmail({ to, name, otp }) {
  const cfg = getSmtpConfig();
  const displayName = name || to.split('@')[0];
  const html =
    '<div style="font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto;">' +
    '<h2 style="color: #28a745;">NoteShare Email Verification</h2>' +
    `<p>Hi ${displayName},</p>` +
    '<p>Your OTP for email verification is:</p>' +
    '<div style="background-color: #f0f0f0; padding: 20px; border-radius: 8px; text-align: center;">' +
    `<h1 style="color: #28a745; letter-spacing: 5px; margin: 0;">${otp}</h1>` +
    '</div>' +
    '<p style="margin-top: 20px;">This OTP will expire in 10 minutes.</p>' +
    '<p>Best regards,<br><strong>NoteShare Team</strong></p>' +
    '</div>';

  const transporter = createTransporter();
  await transporter.sendMail({
    from: `${cfg.fromName} <${cfg.fromEmail}>`,
    to,
    subject: 'NoteShare - Email Verification OTP',
    html,
  });
}

module.exports = { getSmtpConfig, isSmtpConfigured, sendOtpEmail };
