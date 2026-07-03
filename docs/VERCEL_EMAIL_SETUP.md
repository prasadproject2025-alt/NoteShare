# Vercel Email (OTP) Setup Guide - CRITICAL FOR PRODUCTION

## ⚠️ IMPORTANT: Vercel Blocks Port 25

**Vercel blocks Port 25 to prevent spam.** You MUST use one of these secure ports:
- **Port 587** (TLS/STARTTLS) - **RECOMMENDED** ✅
- **Port 465** (SSL) - Alternative

If you get "Connection refused" or "OTP not sending" errors, this is likely the cause.

---

## Step 1: Get Gmail App Password

### Generate App-Specific Password (Required for Gmail)

1. Go to [https://myaccount.google.com/apppasswords](https://myaccount.google.com/apppasswords)
2. Select "Mail" and "Windows Computer"
3. Google will generate a **16-character app-specific password**
4. **Copy this password** (NOT your regular Gmail password)

### Gmail Requirements
- ✅ 2-Factor Authentication must be ENABLED
- ✅ Must use App Password (not regular password)
- ✅ Account must be a regular Gmail account (not G Suite/Workspace)

---

## Step 2: Set Environment Variables in Vercel

Go to your Vercel project dashboard:

1. **Click:** "Settings" → "Environment Variables"
2. **Add these variables:**

```
GMAIL_USERNAME = prasad.project786@gmail.com
GMAIL_APP_PASSWORD = xxxx xxxx xxxx xxxx    (16-char password from Step 1)
FROM_EMAIL = prasad.project2025@gmail.com
FROM_NAME = NoteShare
SMTP_HOST = smtp.gmail.com
SMTP_PORT = 587
```

⚠️ **Make sure SMTP_PORT is set to 587 (not 25, not 465)**

3. **Click "Save"** after each variable
4. **Redeploy** your project after adding variables

---

## Step 3: Test Your Email Configuration

### Local Testing (Before Deploying)

Create a test file `test-email-vercel.js`:

```javascript
// test-email-vercel.js
require('dotenv').config({ path: '.env.local' });

const nodemailer = require('nodemailer');

async function testEmail() {
  console.log('Testing email configuration for Vercel...\n');
  
  const user = process.env.GMAIL_USERNAME;
  const pass = process.env.GMAIL_APP_PASSWORD;
  const port = parseInt(process.env.SMTP_PORT || '587');
  
  console.log('Configuration:');
  console.log(`  Email: ${user}`);
  console.log(`  Port: ${port}`);
  console.log(`  Host: smtp.gmail.com`);
  console.log(`  TLS/SSL: ${port === 465 ? 'SSL' : 'TLS'}\n`);
  
  if (!user || !pass) {
    console.error('❌ ERROR: GMAIL_USERNAME or GMAIL_APP_PASSWORD not set!');
    process.exit(1);
  }

  const transporter = nodemailer.createTransport({
    host: 'smtp.gmail.com',
    port: port,
    secure: port === 465, // true for 465, false for 587
    requireTLS: port === 587,
    auth: {
      user: user,
      pass: pass.replace(/\s+/g, ''),
    },
    tls: {
      rejectUnauthorized: true,
    },
  });

  try {
    console.log('Connecting to Gmail SMTP...');
    await transporter.verify();
    console.log('✅ Connection successful!\n');

    console.log('Sending test email...');
    const result = await transporter.sendMail({
      from: `NoteShare <${user}>`,
      to: 'prasad.project786@gmail.com',
      subject: 'NoteShare Email Test - Vercel Setup',
      html: '<h2>✅ Email Configuration is Working!</h2><p>Your OTP system is ready for Vercel deployment.</p>',
    });

    console.log('✅ Email sent successfully!');
    console.log(`   Message ID: ${result.messageId}`);
    console.log('\n✅ Your email setup is configured correctly for Vercel.\n');
  } catch (error) {
    console.error('❌ Email test failed:', error.message);
    console.error('\nCommon issues:');
    console.error('  1. Wrong app password (must be 16 chars, from Google Account)');
    console.error('  2. 2FA not enabled on Gmail account');
    console.error('  3. Port 587 blocked (shouldn\'t happen with Vercel)');
    console.error('  4. Gmail account credentials are incorrect');
    process.exit(1);
  }
}

testEmail();
```

Run it locally:
```bash
node test-email-vercel.js
```

Expected output:
```
Configuration:
  Email: prasad.project786@gmail.com
  Port: 587
  Host: smtp.gmail.com
  TLS/SSL: TLS

Connecting to Gmail SMTP...
✅ Connection successful!

Sending test email...
✅ Email sent successfully!
   Message ID: <xxx@gmail.com>

✅ Your email setup is configured correctly for Vercel.
```

---

## Step 4: Deploy and Test on Vercel

After deploying to Vercel:

1. Go to your live Vercel URL
2. Go to the account creation page: `/create-account.html`
3. Enter your email and click "Send OTP"
4. Check:
   - ✅ Email received in Gmail inbox
   - ✅ OTP shows in email within 30 seconds
   - ✅ No "ECONNREFUSED" errors in Vercel logs

### If Email Doesn't Arrive

1. **Check Vercel Logs:**
   - Go to Vercel Dashboard → Deployments → Latest → Logs
   - Look for errors in Function logs

2. **Check Gmail Security:**
   - Go to [Gmail Activity](https://myaccount.google.com/security-checkup)
   - Look for "Suspicious activity blocked"
   - If shown, click "Review activity" and mark as "This is me"

3. **Verify Environment Variables:**
   - In Vercel Dashboard → Settings → Environment Variables
   - Confirm all 6 variables are set (not showing as "undefined")

---

## Port Configuration Reference

### Gmail + Vercel Setup (RECOMMENDED)

```
SMTP_HOST = smtp.gmail.com
SMTP_PORT = 587              ← Use this for Vercel
Secure = false               
RequireTLS = true            ← Required for TLS
```

### Alternative: Port 465 (SSL)

```
SMTP_HOST = smtp.gmail.com
SMTP_PORT = 465
Secure = true
RequireTLS = false
```

**Why Port 587 is Recommended:**
- ✅ More widely supported across hosting platforms
- ✅ Starts unencrypted then upgrades via STARTTLS
- ✅ Better compatibility with Vercel
- ✅ Lower risk of connection issues

---

## Troubleshooting

### ❌ "Error: connect ECONNREFUSED 127.0.0.1:25"
**Problem:** Trying to use Port 25 (blocked by Vercel)
**Solution:** Change `SMTP_PORT` to `587` in Vercel Environment Variables

### ❌ "Error: Invalid login or app password"
**Problem:** Using regular Gmail password instead of app-specific password
**Solution:** 
1. Go to [App Passwords](https://myaccount.google.com/apppasswords)
2. Generate new 16-character password
3. Copy the exact password (including spaces)
4. Update `GMAIL_APP_PASSWORD` in Vercel

### ❌ "OTP generates but doesn't send (no error in logs)"
**Problem:** SMTP_PORT environment variable not set
**Solution:**
1. Go to Vercel Settings → Environment Variables
2. Add: `SMTP_PORT = 587`
3. Redeploy

### ❌ "Suspicious activity blocked" by Gmail
**Problem:** Google blocked the connection for security
**Solution:**
1. Go to [Gmail Security Checkup](https://myaccount.google.com/security-checkup)
2. Review "Suspicious activity"
3. Click "Review activity" and select "This is me"
4. Retry OTP sending

### ❌ "2-Step Verification is off"
**Problem:** Gmail requires 2FA to use app passwords
**Solution:**
1. Go to [Google Account Security](https://myaccount.google.com/security)
2. Enable "2-Step Verification"
3. Then generate app-specific password again

---

## Quick Checklist

- [ ] 2-Factor Authentication enabled on Gmail
- [ ] App-specific password generated (16 characters)
- [ ] `GMAIL_USERNAME` set in Vercel env vars
- [ ] `GMAIL_APP_PASSWORD` set with correct 16-char password
- [ ] `SMTP_PORT` set to `587` in Vercel env vars
- [ ] `FROM_EMAIL` set in Vercel env vars
- [ ] `FROM_NAME` set in Vercel env vars
- [ ] Local test email works before deploying
- [ ] Project redeployed after adding env vars
- [ ] Can create account and receive OTP

---

## How It Works on Vercel

```
User submits email on create-account.html
        ↓
POST /api/send-otp.js (Vercel Serverless Function)
        ↓
Nodemailer connects to smtp.gmail.com:587 (TLS)
        ↓
Gmail authenticates with your Gmail account
        ↓
Email sent to user
        ↓
User receives OTP in Gmail inbox
```

---

## Local vs Production

### Local Development
- Can use `OTP_DEV_MODE=true` to log OTP to files
- Email optional but recommended for testing

### Vercel Production  
- Email **required** for account creation to work
- No OTP logging to files (not available on serverless)
- Must have environment variables configured

---

**Still having issues?** Check the Vercel function logs:
1. Go to Vercel Dashboard
2. Select your project
3. Click "Deployments" → Latest deployment
4. Click "Logs" at the bottom
5. Look for `/api/send-otp` function logs

The error message there will tell you exactly what's wrong! 🔍
