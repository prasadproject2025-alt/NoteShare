const fs = require('fs');
const path = require('path');

function loadEnv(rootDir) {
  const root = rootDir || path.join(__dirname, '..');
  for (const file of ['.env.local', '.env']) {
    const p = path.join(root, file);
    if (!fs.existsSync(p)) continue;
    fs.readFileSync(p, 'utf8')
      .replace(/^\uFEFF/, '')
      .split(/\r?\n/)
      .forEach((line) => {
        const t = line.trim();
        if (!t || t.startsWith('#')) return;
        const i = t.indexOf('=');
        if (i < 0) return;
        const key = t.slice(0, i).trim();
        let val = t.slice(i + 1).trim();
        if (
          (val.startsWith('"') && val.endsWith('"')) ||
          (val.startsWith("'") && val.endsWith("'"))
        ) {
          val = val.slice(1, -1);
        }
        if (process.env[key] === undefined) process.env[key] = val;
      });
  }
}

module.exports = { loadEnv };
