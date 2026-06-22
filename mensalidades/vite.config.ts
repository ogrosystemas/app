import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";
import { VitePWA } from "vite-plugin-pwa";

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    react(),
    VitePWA({
      // 'autoUpdate': o Service Worker se atualiza sozinho em segundo plano,
      // sem precisar que o usuário desinstale/reinstale o app.
      registerType: "autoUpdate",

      // injectRegister: null porque registramos o SW manualmente em main.tsx
      // (dá mais controle sobre o momento exato do registro, útil para PWAs offline-first).
      injectRegister: null,

      includeAssets: ["icons/icon-192.png", "icons/icon-512.png", "icons/icon-maskable-512.png"],

      manifest: {
        id: "/",
        name: "Mutantes Moto Clube",
        short_name: "Mutantes MC",
        description:
          "App de conferência de mensalidades do Mutantes Moto Clube. Controle de pagamentos 100% offline.",
        theme_color: "#0a0a0a",
        background_color: "#0a0a0a",
        display: "standalone",
        orientation: "portrait",
        scope: "/",
        start_url: "/",
        lang: "pt-BR",
        icons: [
          {
            src: "icons/icon-192.png",
            sizes: "192x192",
            type: "image/png",
            purpose: "any",
          },
          {
            src: "icons/icon-512.png",
            sizes: "512x512",
            type: "image/png",
            purpose: "any",
          },
          {
            src: "icons/icon-maskable-512.png",
            sizes: "512x512",
            type: "image/png",
            purpose: "maskable",
          },
        ],
      },

      workbox: {
        // Garante que o app shell completo (JS/CSS/HTML) seja cacheado no install do SW.
        globPatterns: ["**/*.{js,css,html,ico,png,svg,webmanifest}"],

        // Sem chamadas de API externas neste app (tudo é IndexedDB local),
        // mas mantemos um fallback de navegação para garantir abertura offline em qualquer rota.
        navigateFallback: "/index.html",

        // Ativa o SW novo imediatamente, sem esperar todas as abas antigas fecharem.
        skipWaiting: true,
        clientsClaim: true,

        // Limpa caches de versões antigas do app automaticamente.
        cleanupOutdatedCaches: true,
      },

      devOptions: {
        // Habilita o SW também em modo `vite dev`, para testar comportamento offline
        // sem precisar gerar build de produção a cada teste.
        enabled: true,
        type: "module",
      },
    }),
  ],

  server: {
    host: true,
    port: 5173,
  },
});
