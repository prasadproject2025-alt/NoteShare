const fs = require('fs');
const path = require('path');
const os = require('os');

function getOtpStorageFile() {
  // In serverless environments like Vercel, process.cwd() is read-only.
  // os.tmpdir() is the standard writable temporary directory on Vercel/AWS Lambda.
  const tmpDir = os.tmpdir();
  const dir = path.join(tmpDir, 'noteshare');
  try {
    if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });
    return path.join(dir, 'otp_data.json');
  } catch {
    return path.join(tmpDir, 'noteshare_otp_data.json');
  }
}

function getOtpLogFile() {
  const tmpDir = os.tmpdir();
  const dir = path.join(tmpDir, 'noteshare');
  try {
    if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });
    return path.join(dir, 'otp_log.txt');
  } catch {
    return path.join(tmpDir, 'noteshare_otp_log.txt');
  }
}

function saveOtp(key, data) {
  try {
    const file = getOtpStorageFile();
    let otpData = {};
    if (fs.existsSync(file)) {
      try {
        otpData = JSON.parse(fs.readFileSync(file, 'utf8') || '{}');
      } catch {
        otpData = {};
      }
    }
    const now = Date.now();
    for (const k of Object.keys(otpData)) {
      if (otpData[k].expiresAt < now) delete otpData[k];
    }
    otpData[key] = data;
    fs.writeFileSync(file, JSON.stringify(otpData, null, 2), 'utf8');
  } catch (err) {
    console.warn('[NoteShare] Failed to write OTP to temp file:', err.message);
  }
}

function getOtp(key) {
  try {
    const file = getOtpStorageFile();
    if (!fs.existsSync(file)) return null;
    const otpData = JSON.parse(fs.readFileSync(file, 'utf8') || '{}');
    return otpData[key] || null;
  } catch (err) {
    console.warn('[NoteShare] Failed to read OTP temp file:', err.message);
    return null;
  }
}

function deleteOtp(key) {
  try {
    const file = getOtpStorageFile();
    if (!fs.existsSync(file)) return;
    const otpData = JSON.parse(fs.readFileSync(file, 'utf8') || '{}');
    delete otpData[key];
    fs.writeFileSync(file, JSON.stringify(otpData, null, 2), 'utf8');
  } catch (err) {
    console.warn('[NoteShare] Failed to delete OTP from temp file:', err.message);
  }
}

function logOtp(email, otp, emailSent) {
  try {
    const file = getOtpLogFile();
    const line = `${new Date().toISOString()} - OTP for ${email}: ${otp} - Email sent: ${emailSent ? 'YES' : 'NO'}\n`;
    fs.appendFileSync(file, line, 'utf8');
  } catch (err) {
    // Ignore log writing errors in read-only/serverless environments
  }
  if (!emailSent) {
    console.log(`[NoteShare] OTP for ${email}: ${otp} (logged — email not sent)`);
  }
}

module.exports = { saveOtp, getOtp, deleteOtp, logOtp };
