// ============================================================
// sw.js — Service Worker PWA offline
// Usa paths RELATIVOS — funciona em qualquer subdiretório
// ============================================================

const CACHE_NAME = 'mdo-pro-v2';

// Paths relativos ao sw.js (que fica na raiz do app)
const PRECACHE_RELATIVE = [
  './',
  './index.html',
  './css/app.css',
  './js/app.js',
  './js/db.js',
  './js/calculadora.js',
  './js/router.js',
  './pages/setup.js',
  './pages/dashboard.js',
  './pages/clientes.js',
  './pages/catalogo.js',
  './pages/orcamento.js',
  './pages/visualizar.js',
  './pages/configuracoes.js',
  './manifest.json',
  './icons/icon-192.png',
  './icons/icon-512.png',
];

const PRECACHE_CDN = [
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js',
];

// Resolve paths relativos baseado na localização do SW
const SW_BASE = self.location.href.replace('/sw.js', '');

const PRECACHE = [
  ...PRECACHE_RELATIVE.map(p => SW_BASE + '/' + p.replace('./', '')),
  ...PRECACHE_CDN,
];

self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        // Cacheia CDN primeiro (erros aqui não travam o install)
        cache.addAll(PRECACHE_CDN).catch(() => {});
        // Cacheia arquivos locais (crítico)
        return cache.addAll(
          PRECACHE_RELATIVE.map(p => SW_BASE + '/' + p.replace('./', ''))
        );
      })
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

self.addEventListener('fetch', (e) => {
  if (e.request.method !== 'GET') return;

  const url = e.request.url;

  // CDN: Cache First
  if (url.includes('cdn.jsdelivr.net') || url.includes('cdnjs.cloudflare.com')) {
    e.respondWith(
      caches.match(e.request).then(cached => {
        if (cached) return cached;
        return fetch(e.request).then(res => {
          caches.open(CACHE_NAME).then(c => c.put(e.request, res.clone()));
          return res;
        }).catch(() => cached);
      })
    );
    return;
  }

  // Assets locais: Cache First
  if (url.match(/\.(css|js|png|ico|woff2?|json)$/)) {
    e.respondWith(
      caches.match(e.request).then(cached => {
        if (cached) return cached;
        return fetch(e.request).then(res => {
          caches.open(CACHE_NAME).then(c => c.put(e.request, res.clone()));
          return res;
        });
      })
    );
    return;
  }

  // HTML/navegação: Network First, fallback index.html
  e.respondWith(
    fetch(e.request)
      .then(res => {
        caches.open(CACHE_NAME).then(c => c.put(e.request, res.clone()));
        return res;
      })
      .catch(() =>
        caches.match(e.request)
          .then(c => c || caches.match(SW_BASE + '/index.html'))
      )
  );
});
