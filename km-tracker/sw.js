// sw.js — Mutantes KM Tracker (Versão Super Simplificada)
// Esta versão NÃO cacheia páginas HTML para evitar o modo offline

const CACHE_NAME = 'mutantes-km-v5';
const STATIC_ASSETS = [
  '/assets/css/main.css',
  '/assets/logo.png',
  '/manifest.json'
];

// Instalação
self.addEventListener('install', event => {
  console.log('[SW] Instalando...');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        return Promise.allSettled(
          STATIC_ASSETS.map(asset => 
            cache.add(asset).catch(err => console.warn(`[SW] Não foi possível cachear: ${asset}`))
          )
        );
      })
      .then(() => self.skipWaiting())
  );
});

// Ativação
self.addEventListener('activate', event => {
  console.log('[SW] Ativando...');
  event.waitUntil(
    caches.keys()
      .then(keys => Promise.all(keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key))))
      .then(() => self.clients.claim())
  );
});

// Interceptação de requisições - APENAS assets estáticos
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);
  const request = event.request;
  
  // Só cacheia arquivos estáticos específicos
  const isStaticAsset = /\.(css|png|jpg|jpeg|webp|gif|svg|ico)$/i.test(url.pathname);
  
  if (isStaticAsset && request.method === 'GET') {
    event.respondWith(
      caches.match(request)
        .then(cachedResponse => {
          if (cachedResponse) {
            return cachedResponse;
          }
          return fetch(request)
            .then(response => {
              if (response && response.status === 200) {
                const responseToCache = response.clone();
                caches.open(CACHE_NAME).then(cache => {
                  cache.put(request, responseToCache);
                });
              }
              return response;
            });
        })
    );
    return;
  }
  
  // Para todo o resto (HTML, PHP, API) - SEMPRE vai para a rede
  // Para google_auth.php (OAuth callback) - deixa passar sem interceptar
  if (url.pathname.includes('google_auth.php')) {
    return; // Não intercepta, deixa o browser lidar normalmente
  }

  event.respondWith(
    fetch(request).catch(err => {
      console.warn('[SW] Fetch falhou:', err);
      throw err;
    })
  );
});

// Mensagem para pular espera
self.addEventListener('message', event => {
  if (event.data === 'skipWaiting') {
    self.skipWaiting();
  }
});