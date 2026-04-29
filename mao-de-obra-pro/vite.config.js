import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import { VitePWA } from 'vite-plugin-pwa'
import path from 'path'

export default defineConfig({
  plugins: [
    react(),
    VitePWA({ registerType: 'autoUpdate' })
  ],
  resolve: {
    alias: {
      // Isso aqui diz: toda vez que eu usar @, olhe para a pasta src
      '@': path.resolve(__dirname, './src'),
    },
  },
})