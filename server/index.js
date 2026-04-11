import express from 'express';
import path from 'path';
import { fileURLToPath } from 'url';
import { createProxyMiddleware } from 'http-proxy-middleware';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const app = express();
const PORT = process.env.PORT || 4007;

// 1. Proxy Archie
app.use('/archie', createProxyMiddleware({
  target: 'https://archie-chatbot.fly.dev',
  changeOrigin: true,
}));

// 2. Proxy Genealogy Visualiser
app.use('/genealogy-viz', createProxyMiddleware({
  target: 'https://genealogy-viz.fly.dev',
  changeOrigin: true,
  pathRewrite: { '^/genealogy-viz': '/' },
}));

// 3. Local Gallery
app.use(express.static(path.join(__dirname, '../public')));

app.get('*', (req, res) => {
  res.sendFile(path.join(__dirname, '../public/index.html'));
});

app.listen(PORT, () => {
  console.log('Gateway running on port ' + PORT);
});
