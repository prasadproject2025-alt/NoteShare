const fs = require('fs');
const path = require('path');

function loadEnv() {
  const root = path.join(__dirname, '..');
  for (const file of ['.env.local', '.env']) {
    const p = path.join(root, file);
    if (!fs.existsSync(p)) continue;
    console.log('Loading', file);
    fs.readFileSync(p, 'utf8')
      .split(/\r?\n/)
      .forEach((line) => {
        const t = line.trim();
        if (!t || t.startsWith('#')) return;
        const i = t.indexOf('=');
        if (i < 0) return;
        const k = t.slice(0, i).trim();
        let v = t.slice(i + 1).trim();
        if (
          (v.startsWith('"') && v.endsWith('"')) ||
          (v.startsWith("'") && v.endsWith("'"))
        ) {
          v = v.slice(1, -1);
        }
        if (!process.env[k]) process.env[k] = v;
      });
  }
}

loadEnv();
const { isSmtpConfigured, getSmtpConfig, sendOtpEmail } = require('../lib/mailer');

const cfg = getSmtpConfig();
console.log('SMTP configured:', isSmtpConfigured());
console.log('User:', cfg.user ? `${cfg.user.slice(0, 3)}***@${cfg.user.split('@')[1]}` : 'MISSING');
console.log('Pass length:', cfg.pass ? cfg.pass.length : 0);
console.log('From:', cfg.fromEmail);

if (!isSmtpConfigured()) {
  console.log('\nFAIL: Set GMAIL_USERNAME and GMAIL_APP_PASSWORD in .env or .env.local');
  process.exit(1);
}

const testTo = process.argv[2] || cfg.user;
console.log('\nSending test OTP to:', testTo);

sendOtpEmail({ to: testTo, name: 'Test', otp: '123456' })
  .then(() => {
    console.log('SUCCESS: Email sent!');
    process.exit(0);
  })
  .catch((err) => {
    console.error('FAIL:', err.message);
    if (err.response) console.error('Response:', err.response);
    process.exit(1);
  });
