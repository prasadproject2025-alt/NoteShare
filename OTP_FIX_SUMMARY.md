# ✅ OTP Email Fix - Vercel Port 587 Setup

## 🔴 Problem Identified

Your OTP emails are not sending because **Vercel blocks Port 25** (standard SMTP).

**Solution:** Use **Port 587 (TLS)** which Vercel fully supports.

---

## 🛠️ What Was Fixed

### 1. Updated Nodemailer Configuration (lib/mailer.js)
- Added explicit SSL/TLS handling for ports 587 and 465
- Configured `requireTLS: true` for port 587 (TLS/STARTTLS)
- Added SSL certificate verification for security
- Code now auto-detects which secure protocol to use

### 2. Email Configuration Template
- Updated `.env.local` with email variables
- Created `VERCEL_EMAIL_SETUP.md` with complete guide
- Added email test script: `scripts/test-email-config.js`

### 3. Package.json Updated
- Added: `npm run test:email-config` command
- Added: `npm run test:vercel` alias

---

## 🚀 Quick Setup (3 Steps)

### Step 1: Generate Gmail App Password

Go to: **https://myaccount.google.com/apppasswords**

1. Sign in to Gmail
2. Select: **Mail** → **Windows Computer**
3. Google generates 16-character password
4. **Copy it** (include spaces)

**Example:** `abcd efgh ijkl mnop`

---

### Step 2: Update `.env.local` File

Open `.env.local` and replace the placeholder:

**Find this line:**
```
GMAIL_APP_PASSWORD=xxxx xxxx xxxx xxxx
```

**Replace with your actual 16-character password from Step 1:**
```
GMAIL_APP_PASSWORD=abcd efgh ijkl mnop
```

⚠️ **Important:** Keep the spaces in the password!

---

### Step 3: Test Locally

Run the email configuration test:

```bash
npm run test:email-config
```

Expected output when working:
```
✅ Port 587 is supported by Vercel
✅ SMTP Connection successful! ✓
✅ Test email sent successfully! ✓
✅ All tests passed! ✓ Ready for Vercel deployment.
```

---

## 📋 Environment Variables Configured

Your `.env.local` now has:

```env
GMAIL_USERNAME=prasad.project786@gmail.com
GMAIL_APP_PASSWORD=xxxx xxxx xxxx xxxx      ← Replace with your 16-char password
FROM_NAME=NoteShare
FROM_EMAIL=prasad.project2025@gmail.com
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587                               ← Vercel-compatible port
```

---

## 🔌 Why Port 587 Works on Vercel

| Port | Type | Vercel Support | Usage |
|------|------|---|---|
| 25 | SMTP (plain) | ❌ BLOCKED | Standard but unencrypted |
| 465 | SMTPS (SSL) | ✅ Supported | Secure, immediate TLS |
| 587 | SMTP + TLS | ✅ **RECOMMENDED** | Secure, STARTTLS upgrade |

Port 587 is recommended because it:
- ✅ Starts with plain connection then upgrades to encrypted (STARTTLS)
- ✅ More compatible with all hosting platforms
- ✅ Works reliably on Vercel's serverless functions
- ✅ Lower risk of connection issues

---

## 🧪 How to Verify It Works

### Local Test
```bash
npm run test:email-config
```

### Live Test on Vercel

After deploying:

1. Go to your Vercel app URL
2. Click "Sign Up"
3. Enter your email: `test@vitstudent.ac.in`
4. Click "Send OTP"
5. **Check your email inbox** - OTP should arrive in 10-30 seconds

---

## 📱 Before Deploying to Vercel

1. ✅ **Gmail 2FA**: Enable at https://myaccount.google.com/security
2. ✅ **App Password**: Generate new one if not already done
3. ✅ **Local Test**: Run `npm run test:email-config`
4. ✅ **Env Variables**: Added to `.env.local`
5. ✅ **Git Commit**: `git add . && git commit -m "Fix OTP Port 587"`
6. ✅ **Vercel Environment**: Set same variables in Vercel Dashboard

---

## ⚠️ Common Issues & Fixes

### "OTP not sending" or "Connection refused"
- ❌ Using Port 25 or wrong port
- ✅ Check `SMTP_PORT=587` in `.env.local`

### "Invalid login" error
- ❌ Using regular Gmail password
- ✅ Use 16-character app password from Google Account

### "Gmail blocked suspicious activity"
- ❌ Gmail security feature triggered
- ✅ Go to https://myaccount.google.com/security-checkup
- ✅ Click "Review activity" → "This is me"

### Test passes locally but fails on Vercel
- ❌ Environment variables not set in Vercel
- ✅ Go to Vercel Dashboard → Settings → Environment Variables
- ✅ Add all 6 variables listed above
- ✅ Redeploy project

---

## 📚 Complete Documentation

For more details, see:
- **[VERCEL_EMAIL_SETUP.md](VERCEL_EMAIL_SETUP.md)** - Full setup guide with troubleshooting
- **[.env.example](.env.example)** - Example environment variables
- **[scripts/test-email-config.js](scripts/test-email-config.js)** - Email configuration test script

---

## ✨ What's Next

1. Update `GMAIL_APP_PASSWORD` in `.env.local`
2. Run `npm run test:email-config`
3. Verify email test passes ✅
4. Deploy to Vercel when ready
5. Set same environment variables in Vercel Dashboard
6. Test account creation with OTP on live app

---

**Your OTP system is now configured for Vercel deployment!** 🚀

The email configuration uses **Port 587 (TLS)** which is fully supported by Vercel, unlike Port 25 which is blocked.
