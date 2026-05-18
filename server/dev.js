const http = require('http');
const fs = require('fs');
const path = require('path');
const { URL } = require('url');

const ROOT = path.join(__dirname, '..');
const PORT = process.env.PORT || 3000;

const MIME = {
  '.html': 'text/html; charset=utf-8',
  '.css': 'text/css; charset=utf-8',
  '.js': 'application/javascript; charset=utf-8',
  '.json': 'application/json',
  '.png': 'image/png',
  '.jpg': 'image/jpeg',
  '.jpeg': 'image/jpeg',
  '.gif': 'image/gif',
  '.svg': 'image/svg+xml',
  '.ico': 'image/x-icon',
  '.woff2': 'font/woff2',
};

function loadEnv() {
  for (const file of ['.env.local', '.env']) {
    const p = path.join(ROOT, file);
    if (!fs.existsSync(p)) continue;
    fs.readFileSync(p, 'utf8')
      .split('\n')
      .forEach((line) => {
        const m = line.match(/^\s*([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)$/);
        if (!m || process.env[m[1]]) return;
        process.env[m[1]] = m[2].replace(/^["']|["']$/g, '');
      });
  }
}

loadEnv();
process.env.NODE_ENV = process.env.NODE_ENV || 'development';
process.env.OTP_DEV_MODE = process.env.OTP_DEV_MODE || 'true';

async function handleApi(req, res, pathname) {
  const apiFile = path.join(ROOT, 'api', path.basename(pathname) + '.js');
  if (!fs.existsSync(apiFile)) {
    res.writeHead(404, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify({ success: false, message: 'API not found' }));
    return;
  }

  let body = '';
  for await (const chunk of req) body += chunk;

  const handler = require(apiFile);
  const mockReq = {
    method: req.method,
    body: body ? JSON.parse(body) : {},
    headers: req.headers,
  };
  const mockRes = {
    statusCode: 200,
    headers: {},
    status(code) {
      this.statusCode = code;
      return this;
    },
    setHeader(k, v) {
      this.headers[k] = v;
    },
    json(data) {
      if (!this.headers['Content-Type']) {
        this.setHeader('Content-Type', 'application/json');
      }
      const payload = JSON.stringify(data);
      res.writeHead(this.statusCode, this.headers);
      res.end(payload);
    },
    end(data) {
      res.writeHead(this.statusCode, this.headers);
      res.end(data);
    },
  };

  try {
    await handler(mockReq, mockRes);
  } catch (err) {
    console.error('API error:', pathname, err);
    res.writeHead(500, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify({ success: false, message: err.message }));
  }
}

function serveStatic(req, res, pathname) {
  let filePath = pathname === '/' ? '/login.html' : pathname;
  filePath = path.join(ROOT, filePath.replace(/\//g, path.sep));

  if (!filePath.startsWith(ROOT)) {
    res.writeHead(403);
    res.end('Forbidden');
    return;
  }

  if (fs.existsSync(filePath) && fs.statSync(filePath).isDirectory()) {
    const index = path.join(filePath, 'index.html');
    if (fs.existsSync(index)) filePath = index;
  }

  if (!fs.existsSync(filePath) || fs.statSync(filePath).isDirectory()) {
    res.writeHead(404, { 'Content-Type': 'text/plain' });
    res.end('Not found');
    return;
  }

  const ext = path.extname(filePath).toLowerCase();
  res.writeHead(200, { 'Content-Type': MIME[ext] || 'application/octet-stream' });
  fs.createReadStream(filePath).pipe(res);
}

const server = http.createServer(async (req, res) => {
  const url = new URL(req.url, `http://localhost:${PORT}`);
  const pathname = decodeURIComponent(url.pathname);

  if (pathname.startsWith('/api/')) {
    await handleApi(req, res, pathname);
    return;
  }

  serveStatic(req, res, pathname);
});

server.listen(PORT, () => {
  console.log(`NoteShare running at http://localhost:${PORT}`);
  console.log(`  Login:  http://localhost:${PORT}/login.html`);
  console.log(`  Home:   http://localhost:${PORT}/index.html`);
});
