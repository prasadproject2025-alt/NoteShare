/**
 * Admin API: Delete a user from data/users.json
 * POST /api/admin-delete-user
 * Body: { email }
 */
const fs = require('fs');
const path = require('path');

const USERS_FILE = path.join(__dirname, '..', 'data', 'users.json');

function loadUsers() {
  try {
    if (!fs.existsSync(USERS_FILE)) return {};
    return JSON.parse(fs.readFileSync(USERS_FILE, 'utf8'));
  } catch (e) {
    return {};
  }
}

function saveUsers(users) {
  try {
    const dir = path.dirname(USERS_FILE);
    if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });
    fs.writeFileSync(USERS_FILE, JSON.stringify(users, null, 2) + '\n', 'utf8');
  } catch (e) {
    console.error('Failed to save users.json:', e);
  }
}

module.exports = async function handler(req, res) {
  if (req.method !== 'POST') {
    return res.status(405).json({ success: false, message: 'Method not allowed' });
  }

  const { email } = req.body || {};
  if (!email) {
    return res.status(400).json({ success: false, message: 'Email is required' });
  }

  const cleanEmail = email.toLowerCase().trim();
  const users = loadUsers();

  if (users[cleanEmail]) {
    delete users[cleanEmail];
    saveUsers(users);
  }

  return res.status(200).json({ success: true, message: `User ${cleanEmail} deleted` });
};
