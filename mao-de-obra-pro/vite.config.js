import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import { VitePWA } from 'vite-plugin-pwa'

export default defineConfig({
  plugins: [
    react(),
    VitePWA({
      registerType: 'autoUpdate',
      workbox: {
        globPatterns: ['**/*.{js,css,html,ico,png,svg,jsx}']
      }
    })
  ],
  resolve: {
    // Isso força o Vite a procurar arquivos .jsx e .js se ele se perder
    extensions: ['.mjs', '.js', '.mts', '.ts', '.jsx', '.tsx', '.json']
  },
  build: {
    // Garante que o build não falhe por avisos bobos de importação
    chunkSizeWarningLimit: 1600,
  }
})