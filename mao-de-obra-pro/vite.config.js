import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
  plugins: [
    react(),
    VitePWA({
      strategies: 'injectManifest',   // <-- importante para usar seu sw.js
      srcDir: 'public',
      filename: 'sw.js',
      injectManifest: {
        swDest: 'dist/sw.js'
      },
      manifest: false, // se quiser gerar o manifest via plugin, mantenha true; caso contrário, false e forneça manualmente
      // Se quiser que o plugin ainda gere o manifest, remova manifest:false e configure as opções abaixo
      manifest: {
        name: 'Mão de Obra PRO',
        short_name: 'MDO Pro',
        description: 'Sistema de orçamentos para serviços',
        theme_color: '#2563eb',
        background_color: '#ffffff',
        display: 'standalone',
        start_url: '/',
        icons: [
          { src: '/icon-192.png', sizes: '192x192', type: 'image/png', purpose: 'any maskable' },
          { src: '/icon-512.png', sizes: '512x512', type: 'image/png', purpose: 'any maskable' }
        ]
      }
    })
  ]
});