# NoteShare (JavaScript)

VIT note-sharing platform — **HTML, CSS, and JavaScript**.


About the project---
https://www.loom.com/share/a6f6789430d743668abb22ed33cc7f67

Website walkthro---
https://www.loom.com/share/7da3877838394332b7561d6a6d7e4829



## Run locally

```bash
npm install
npm run dev
```

Open http://localhost:3000/login.html

## OTP email

Add to `.env.local`:

```
GMAIL_USERNAME=your@gmail.com
GMAIL_APP_PASSWORD=your-app-password
FROM_EMAIL=your@gmail.com
FROM_NAME=NoteShare
```

Without SMTP, dev mode writes OTP to `logs/otp_log.txt` and the terminal.

## Deploy (Vercel)

1. Push to GitHub and import in Vercel
2. Set the same env vars in Project → Settings → Environment Variables
3. Enable **Email/Password** in Firebase Console (`noteshare-3`)

## Structure

- `*.html` — pages
- `css/`, `js/` — assets
- `api/` — serverless OTP & admin (Node.js)
- `server/dev.js` — local dev server

