/**
 * Admin API: Get all registered users from data/users.json
 * GET or POST /api/admin-get-users
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

module.exports = async function handler(req, res) {
  const users = loadUsers();
  return res.status(200).json({
    success: true,
    users: Object.values(users),
    count: Object.keys(users).length
  });
};
