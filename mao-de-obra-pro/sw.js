// ============================================================
// sw.js — Service Worker para PWA offline
// BASE: /mao-de-obra-pro
// ============================================================

const CACHE_NAME = 'mdo-pro-v1';
const BASE = '/mao-de-obra-pro';

const PRECACHE = [
  BASE + '/',
  BASE + '/index.html',
  BASE + '/css/app.css',
  BASE + '/js/app.js',
  BASE + '/js/db.js',
  BASE + '/js/calculadora.js',
  BASE + '/js/router.js',
  BASE + '/pages/setup.js',
  BASE + '/pages/dashboard.js',
  BASE + '/pages/clientes.js',
  BASE + '/pages/catalogo.js',
  BASE + '/pages/orcamento.js',
  BASE + '/pages/visualizar.js',
  BASE + '/pages/configuracoes.js',
  BASE + '/manifest.json',
  BASE + '/icons/icon-192.png',
  BASE + '/icons/icon-512.png',
  // CDNs — cacheados na primeira visita
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

self.addEventListener('fetch', (e) => {
  const url = new URL(e.request.url);
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
      .catch(() => caches.match(e.request)
        .then(c => c || caches.match(BASE + '/index.html'))
      )
  );
});
