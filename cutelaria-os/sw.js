const CACHE_NAME = 'cutelaria-os-202606121350';

const STATIC = [
  './',
  './index.html',
  './manifest.json',
  './css/main.css',
  './js/app.js'
];

// INSTALL
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC))
  );
});

// ACTIVATE — limpa caches antigos
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(
        keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k))
      ))
      .then(() => self.clients.claim())
  );
});

// FETCH — só intercepta requisições same-origin
// Requisições cross-origin (CDNs, fontes, APIs) passam direto para a rede
self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);

  // Ignora tudo que não seja da mesma origem
  if (url.origin !== self.location.origin) {
    return;
  }

  // Cache-first para same-origin
  event.respondWith(
    caches.match(event.request).then((cached) => cached || fetch(event.request))
  );
});

// MENSAGEM — ativa novo SW quando usuário confirma atualização
self.addEventListener('message', (event) => {
  if (event.data === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});
