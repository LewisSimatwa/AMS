import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  server: {
    port: 5173, // Add this line
    host: true, // allow access from network
    allowedHosts: ['arachidic-awedly-bell.ngrok-free.dev'], // add your ngrok host here
    strictPort: true, // Change to true so it fails if port is taken
    proxy: {
      '/backend': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/backend/, '/backend/api'),
        configure: (proxy, options) => {
          proxy.on('proxyReq', (proxyReq, req, res) => {
            if (req.headers.authorization) {
              proxyReq.setHeader('Authorization', req.headers.authorization);
            }
            Object.keys(req.headers).forEach(key => {
              if (key.toLowerCase() !== 'host') {
                proxyReq.setHeader(key, req.headers[key]);
              }
            });
          });
        }
      }
    }
  }
})