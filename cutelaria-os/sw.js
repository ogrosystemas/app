const CACHE_NAME = 'cutelaria-os-v15';

const STATIC = [
  './',
  './index.html',
  './manifest.json',
  './css/main.css',
  './js/app.js'
];

// INSTALL — pré-cacheia arquivos estáticos e se ativa imediatamente
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC))
  );
  // NÃO chama skipWaiting aqui — espera o usuário confirmar a atualização
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

// FETCH — cache-first para arquivos estáticos, network-first para o resto
self.addEventListener('fetch', (event) => {
  event.respondWith(
    caches.match(event.request).then((cached) => cached || fetch(event.request))
  );
});

// MENSAGEM — quando o app pede para ativar o novo SW
self.addEventListener('message', (event) => {
  if (event.data === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});
