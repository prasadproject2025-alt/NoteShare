const crypto = require('crypto');

module.exports = async function handler(req, res) {
  if (req.method !== 'POST') {
    return res.status(405).json({ success: false, message: 'Method not allowed' });
  }

  const { username, password } = req.body || {};
  const cleanUser = (username || '').toLowerCase().trim();
  const rawPass = (password || '').trim();

  const adminUser = (process.env.ADMIN_USERNAME || 'admin').toLowerCase().trim();
  const adminPass = process.env.ADMIN_PASSWORD || 'admin123';

  const isDefaultAdmin = (cleanUser === adminUser && rawPass === adminPass);
  const isSuperAdmin = (
    (cleanUser === 'privateprasad@vitstudent.ac.in' || cleanUser === 'privateprasad') &&
    (rawPass === '#Prasad0428' || rawPass === adminPass)
  );

  if (isDefaultAdmin || isSuperAdmin) {
    const token = crypto.randomBytes(24).toString('hex');
    const resolvedUser = isSuperAdmin ? 'privateprasad@vitstudent.ac.in' : (username || adminUser);
    return res.status(200).json({
      success: true,
      token,
      username: resolvedUser,
      email: isSuperAdmin ? 'privateprasad@vitstudent.ac.in' : `${cleanUser}@vitstudent.ac.in`,
      role: 'admin'
    });
  }

  return res.status(401).json({ success: false, message: 'Invalid admin username or password' });
};

