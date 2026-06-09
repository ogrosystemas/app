// ============================================================
// sw.js — Service Worker para PWA offline
// ============================================================

const CACHE_NAME = 'mdo-pro-v1';

// Arquivos essenciais para funcionar offline
const PRECACHE = [
  '/',
  '/mao-de-obra-pro/index.html',
  '/mao-de-obra-pro/css/app.css',
  '/mao-de-obra-pro/js/app.js',
  '/mao-de-obra-pro/js/db.js',
  '/mao-de-obra-pro/js/calculadora.js',
  '/mao-de-obra-pro/js/router.js',
  '/mao-de-obra-pro/pages/setup.js',
  '/mao-de-obra-pro/pages/dashboard.js',
  '/mao-de-obra-pro/pages/clientes.js',
  '/mao-de-obra-pro/pages/catalogo.js',
  '/mao-de-obra-pro/pages/orcamento.js',
  '/mao-de-obra-pro/pages/visualizar.js',
  '/mao-de-obra-pro/pages/configuracoes.js',
  '/mao-de-obra-pro/manifest.json',
  '/mao-de-obra-pro/icons/icon-192.png',
  '/mao-de-obra-pro/icons/icon-512.png',
  // CDNs — serão cacheados na primeira visita
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js',
];

self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(PRECACHE))
  );
  self.skipWaiting();
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
    )
  );
  self.clients.claim();
});

// Estratégia: Cache First para assets, Network First para páginas
self.addEventListener('fetch', (e) => {
  const url = new URL(e.request.url);

  // Ignora requisições não-GET
  if (e.request.method !== 'GET') return;

  // CDNs e assets locais: Cache First
  if (
    url.hostname.includes('cdn.jsdelivr.net') ||
    url.hostname.includes('cdnjs.cloudflare.com') ||
    e.request.url.match(/\.(css|js|png|ico|woff2?)$/)
  ) {
    e.respondWith(
      caches.match(e.request).then(cached => {
        if (cached) return cached;
        return fetch(e.request).then(res => {
          const clone = res.clone();
          caches.open(CACHE_NAME).then(c => c.put(e.request, clone));
          return res;
        });
      })
    );
    return;
  }

  // Páginas: Network First, fallback para cache
  e.respondWith(
    fetch(e.request)
      .then(res => {
        const clone = res.clone();
        caches.open(CACHE_NAME).then(c => c.put(e.request, clone));
        return res;
      })
      .catch(() => caches.match(e.request).then(c => c || caches.match('/index.html')))
  );
});
