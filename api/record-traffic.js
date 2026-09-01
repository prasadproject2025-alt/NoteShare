/**
 * Record Live API Traffic & Security Telemetry
 * POST /api/record-traffic
 * Body: { email, actionType, detail, isFailedLogin, path }
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

  const { email, actionType, detail, isFailedLogin, path: reqPath } = req.body || {};
  if (!email) {
    return res.status(400).json({ success: false, message: 'Email required' });
  }

  const cleanEmail = email.toLowerCase().trim();
  const users = loadUsers();

  if (!users[cleanEmail]) {
    users[cleanEmail] = {
      email: cleanEmail,
      name: cleanEmail.split('@')[0],
      coins: 10,
      created_at: new Date().toISOString(),
      status: 'active',
      requests_count: 0,
      failed_logins: 0,
      actions: []
    };
  }

  const user = users[cleanEmail];
  user.requests_count = (user.requests_count || 0) + 1;
  user.last_active = Date.now();

  if (isFailedLogin) {
    user.failed_logins = (user.failed_logins || 0) + 1;
  }

  if (!user.actions) user.actions = [];
  user.actions.unshift({
    timestamp: new Date().toLocaleTimeString(),
    date: new Date().toLocaleDateString(),
    type: actionType || 'Page Interaction',
    detail: detail || (reqPath ? `Visited ${reqPath}` : 'API Call'),
    time_ms: Date.now()
  });

  if (user.actions.length > 25) {
    user.actions = user.actions.slice(0, 25);
  }

  // Calculate automated threat level
  if (user.failed_logins >= 4 || user.requests_count > 400) {
    user.risk = 'suspicious';
  } else if (user.requests_count > 200) {
    user.risk = 'moderate';
  } else {
    user.risk = 'normal';
  }

  saveUsers(users);

  return res.status(200).json({
    success: true,
    requests_count: user.requests_count,
    failed_logins: user.failed_logins,
    risk: user.risk,
    last_active: user.last_active
  });
};
