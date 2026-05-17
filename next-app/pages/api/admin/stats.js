import fs from 'fs/promises';
import path from 'path';

function safeReadJson(filePath) {
  return fs.readFile(filePath, 'utf8')
    .then((t) => JSON.parse(t))
    .catch(() => null);
}

export default async function handler(req, res) {
  if (req.method !== 'GET') return res.status(405).end();

  const logsDir = path.join(process.cwd(), 'logs');
  const usersPath = path.join(logsDir, 'users.json');
  const otpPath = path.join(logsDir, 'otp_data.json');

  const users = (await safeReadJson(usersPath)) || {};
  const otpData = (await safeReadJson(otpPath)) || {};

  const usersCount = Object.keys(users).length;
  const recentUsers = Object.entries(users)
    .map(([uid, u]) => ({ uid, email: u.email, name: u.name, createdAt: u.createdAt }))
    .sort((a, b) => (a.createdAt || '').localeCompare(b.createdAt || ''))
    .reverse()
    .slice(0, 10);

  const pendingOtps = Object.keys(otpData).length;

  // Placeholder counts for notes/rentals/shared — keep zero if not available.
  const notesCount = 0;
  const sharedCount = 0;
  const rentalCount = 0;

  res.status(200).json({ usersCount, notesCount, sharedCount, rentalCount, recentUsers, pendingOtps });
}
