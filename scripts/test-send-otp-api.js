const fs = require('fs');
const path = require('path');

const ROOT = path.join(__dirname, '..');

function loadEnv() {
  for (const file of ['.env.local', '.env']) {
    const p = path.join(ROOT, file);
    if (!fs.existsSync(p)) continue;
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
// OLD dev server defaults - simulate
// process.env.OTP_DEV_MODE = 'true';

const handler = require('../api/send-otp');
const res = {
  statusCode: 200,
  headers: {},
  status(c) { this.statusCode = c; return this; },
  setHeader(k, v) { this.headers[k] = v; },
  json(d) { console.log('Status:', this.statusCode); console.log(JSON.stringify(d, null, 2)); },
};
handler(
  {
    method: 'POST',
    body: { email: process.argv[2] || 'durgaprasad.s2022a@vitstudent.ac.in', name: 'Test' },
  },
  res
);
