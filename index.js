// Root entrypoint for Vercel deployment
module.exports = (req, res) => {
  res.writeHead(302, { Location: '/login.html' });
  res.end();
};
