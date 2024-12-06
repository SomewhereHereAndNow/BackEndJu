const express = require('express');
const { createProxyMiddleware } = require('http-proxy-middleware');

const app = express();
const port = 3000;

// Proxy requests to the PHP backend
app.use('/', createProxyMiddleware({
  target: 'http://localhost:3001', // Your PHP server URL (e.g., Apache or PHP built-in server)
  changeOrigin: true,
  pathRewrite: {
    '^/': '/', // Rewrite the path if needed
  },
}));

app.listen(port, () => {
  console.log(`Node server is running on http://localhost:${port}`);
});
