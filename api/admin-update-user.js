/**
 * Admin API: Update or register a user in data/users.json
 * POST /api/admin-update-user
 * Body: { email, name?, coins?, status?, blocked?, department?, year?, requests_count?, failed_logins?, role?, firebase_uid? }
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

  const { email, name, coins, status, blocked, department, year, requests_count, failed_logins, role, firebase_uid } = req.body || {};
  if (!email) {
    return res.status(400).json({ success: false, message: 'Email is required' });
  }

  const cleanEmail = email.toLowerCase().trim();
  const users = loadUsers();

  if (!users[cleanEmail]) {
    users[cleanEmail] = {
      email: cleanEmail,
      name: name || cleanEmail.split('@')[0],
      coins: typeof coins === 'number' ? coins : 10,
      created_at: new Date().toISOString(),
      status: status || 'active'
    };
  }

  if (name !== undefined) users[cleanEmail].name = name;
  if (typeof coins === 'number') users[cleanEmail].coins = coins;
  if (status !== undefined) users[cleanEmail].status = status;
  if (blocked !== undefined) users[cleanEmail].blocked = !!blocked;
  if (department !== undefined) users[cleanEmail].department = department;
  if (year !== undefined) users[cleanEmail].year = year;
  if (role !== undefined) users[cleanEmail].role = role;
  if (requests_count !== undefined) users[cleanEmail].requests_count = requests_count;
  if (failed_logins !== undefined) users[cleanEmail].failed_logins = failed_logins;
  if (firebase_uid) users[cleanEmail].firebase_uid = firebase_uid;

  saveUsers(users);

  return res.status(200).json({ success: true, user: users[cleanEmail] });
};

