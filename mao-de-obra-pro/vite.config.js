import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import { VitePWA } from 'vite-plugin-pwa'

export default defineConfig({
  plugins: [
    react(),
    VitePWA({
      registerType: 'autoUpdate',
      includeAssets: ['favicon.ico', 'robots.txt', 'apple-touch-icon.png'],
      manifest: {
        name: 'Mão de Obra PRO',
        short_name: 'MDO Pro',
        description: 'Sistema de orçamentos para serviços',
        theme_color: '#ffffff',
        icons: [
          {
            src: 'icon-192.png',
            sizes: '192x192',
            type: 'image/png'
          },
          {
            src: 'icon-512.png',
            sizes: '512x512',
            type: 'image/png'
          }
        ]
      }
    })
  ],
  resolve: {
    extensions: ['.jsx', '.js', '.json'], // Ordem importante
    alias: {
      '@': '/src',
      '@components': '/src/components',
      '@modules': '/src/modules',
      '@hooks': '/src/hooks',
      '@database': '/src/database',
      '@core': '/src/core'
    }
  },
  build: {
    rollupOptions: {
      input: {
        main: './index.html'
      },
      output: {
        manualChunks: undefined
      }
    }
  }
})