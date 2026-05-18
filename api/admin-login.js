const crypto = require('crypto');

module.exports = async function handler(req, res) {
  if (req.method !== 'POST') {
    return res.status(405).json({ success: false, message: 'Method not allowed' });
  }

  const { username, password } = req.body || {};
  const adminUser = process.env.ADMIN_USERNAME || 'admin';
  const adminPass = process.env.ADMIN_PASSWORD || 'admin123';

  if (username === adminUser && password === adminPass) {
    const token = crypto.randomBytes(24).toString('hex');
    return res.status(200).json({
      success: true,
      token,
      username,
    });
  }

  return res.status(401).json({ success: false, message: 'Invalid credentials' });
};
