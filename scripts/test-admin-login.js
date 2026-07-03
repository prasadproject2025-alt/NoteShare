// Simple test script for admin-login API
// Usage: node scripts/test-admin-login.js [username] [password] [port]

const http = require('http');

const username = process.argv[2] || process.env.ADMIN_USERNAME || 'admin';
const password = process.argv[3] || process.env.ADMIN_PASSWORD || 'admin123';
const port = process.argv[4] || process.env.PORT || 3000;

const data = JSON.stringify({ username, password });

const options = {
  hostname: 'localhost',
  port: port,
  path: '/api/admin-login',
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Content-Length': Buffer.byteLength(data),
  },
};

const req = http.request(options, (res) => {
  let body = '';
  res.on('data', (chunk) => body += chunk);
  res.on('end', () => {
    console.log('Status:', res.statusCode);
    try { console.log('Response:', JSON.parse(body)); }
    catch (e) { console.log('Response (raw):', body); }
  });
});

req.on('error', (e) => console.error('Request error:', e.message));
req.write(data);
req.end();
