import nodemailer from 'nodemailer';

const transporter = nodemailer.createTransport({
  host: process.env.SMTP_HOST || 'smtp.gmail.com',
  port: process.env.SMTP_PORT ? parseInt(process.env.SMTP_PORT) : 587,
  secure: false,
  auth: {
    user: process.env.GMAIL_USERNAME,
    pass: process.env.GMAIL_APP_PASSWORD ? process.env.GMAIL_APP_PASSWORD.replace(/\s+/g, '') : undefined,
  },
});

export async function sendMail({ to, subject, html, text }) {
  const mailOptions = {
    from: `${process.env.FROM_NAME || 'NoteShare'} <${process.env.FROM_EMAIL || process.env.GMAIL_USERNAME}>`,
    to,
    subject,
    html,
    text,
  };

  return transporter.sendMail(mailOptions);
}

export async function sendOtpEmail({ to, name, otp }) {
  const html = `
    <div style="font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto;">
      <h2 style="color: #28a745;">NoteShare Email Verification</h2>
      <p>Hi ${name},</p>
      <p>Your OTP for email verification is:</p>
      <div style="background-color: #f0f0f0; padding: 20px; border-radius: 8px; text-align: center;">
        <h1 style="color: #28a745; letter-spacing: 5px; margin: 0;">${otp}</h1>
      </div>
      <p style="margin-top: 20px;">This OTP will expire in 10 minutes.</p>
      <p>If you didn't request this, please ignore this email.</p>
      <p>Best regards,<br><strong>NoteShare Team</strong></p>
    </div>
  `;

  return sendMail({ to, subject: 'NoteShare - Email Verification OTP', html });
}
