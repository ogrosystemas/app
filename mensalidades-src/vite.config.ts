import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";
import { VitePWA } from "vite-plugin-pwa";

/**
 * BASE_PATH: o app é hospedado dentro do repositório "app" do GitHub Pages,
 * na subpasta /mensalidades (ex: app.ogrosystemas.com.br/mensalidades/).
 * Todo o build (JS, CSS, ícones, manifest, service worker) precisa referenciar
 * este prefixo, ou os assets retornam 404 fora de localhost.
 */
const BASE_PATH = "/mensalidades/";

// https://vite.dev/config/
export default defineConfig({
  base: BASE_PATH,

  plugins: [
    react(),
    VitePWA({
      // 'prompt': o Service Worker novo fica esperando, sem assumir o controle sozinho.
      // A UI (ver UpdateBanner.tsx) decide o momento exato de ativar a versão nova —
      // seja por clique do usuário em "Atualizar", seja automaticamente após um pequeno
      // tempo de espera, conforme decisão de produto (evita ficar dias numa versão velha
      // só porque ninguém clicou, mas ainda dá controle imediato a quem quiser agir).
      registerType: "prompt",

      // injectRegister: null porque o registro do Service Worker é feito explicitamente
      // via hook useRegisterSW (virtual:pwa-register/react), dentro de UpdateBanner.tsx —
      // isso dá controle total sobre o ciclo de vida (quando avisar, quando aplicar a
      // atualização) em vez de deixar o plugin injetar um script de registro genérico.
      injectRegister: null,

      includeAssets: [
        "icons/icon-192.png",
        "icons/icon-512.png",
        "icons/icon-maskable-512.png",
        "icons/apple-touch-icon-180.png",
        "icons/apple-touch-icon-167.png",
        "icons/apple-touch-icon-152.png",
        "icons/apple-touch-icon-120.png",
        "icons/favicon-32.png",
        "icons/favicon-16.png",
      ],

      manifest: {
        // id/scope/start_url usam o BASE_PATH: isso isola este PWA dos outros
        // PWAs hospedados em outras subpastas do mesmo domínio (app.ogrosystemas.com.br).
        id: BASE_PATH,
        name: "Mutantes Moto Clube",
        short_name: "Mutantes MC",
        description:
          "App de conferência de mensalidades do Mutantes Moto Clube. Controle de pagamentos 100% offline.",
        theme_color: "#0a0a0a",
        background_color: "#0a0a0a",
        display: "standalone",
        orientation: "portrait",
        scope: BASE_PATH,
        start_url: BASE_PATH,
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
        // mas mantemos um fallback de navegação para garantir abertura offline em qualquer rota
        // dentro do escopo /mensalidades/ — sem isso, refresh ou deep-link cai em 404 do GitHub Pages.
        navigateFallback: `${BASE_PATH}index.html`,

        // Restringe o fallback de navegação ao escopo deste PWA, para nunca interceptar
        // navegação destinada aos outros PWAs hospedados em outras subpastas do domínio.
        navigateFallbackAllowlist: [new RegExp(`^${BASE_PATH}`)],

        // skipWaiting/clientsClaim NÃO são definidos aqui de propósito: com registerType
        // "prompt", é a chamada updateServiceWorker(true) feita pela UI (UpdateBanner.tsx)
        // que manda o novo SW assumir o controle e recarrega a página — nunca o workbox
        // sozinho em segundo plano.

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

  build: {
    rollupOptions: {
      output: {
        // Separa o SDK do Firebase (grande, muda raramente entre deploys) do código
        // da própria aplicação (pequeno, muda a cada deploy) em chunks distintos.
        // Isso permite que o navegador cacheie o chunk do Firebase por mais tempo —
        // um novo deploy que só altera lógica de negócio não invalida esse cache,
        // já que o conteúdo (e portanto o hash) do chunk do Firebase não mudou.
        manualChunks: {
          firebase: ["firebase/app", "firebase/auth", "firebase/firestore"],
        },
      },
    },
  },
});
