// ==================== SERVICE WORKER (MODO PASSIVO - NÃO APAGA DADOS) ====================
const CACHE_NAME = 'auto-care-v18';

// Arquivos essenciais para funcionar offline (somente HTML/CSS/JS estrutural)
const urlsToCache = [
  '/auto-care/',
  '/auto-care/index.html',
  '/auto-care/style.css',
  '/auto-care/db.js',
  '/auto-care/app.js',
  '/auto-care/manifest.json'
];

self.addEventListener('install', event => {
  console.log('🔄 Service Worker instalado.');
  // Pré-cache dos arquivos essenciais (não mexe no IndexedDB)
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(urlsToCache))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', event => {
  console.log('✅ Service Worker ativado.');
  // Limpa caches antigos de versões anteriores DO SERVICE WORKER (não mexe no IndexedDB)
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cache => {
          if (cache !== CACHE_NAME) {
            console.log('🗑️ Removendo cache antigo:', cache);
            return caches.delete(cache);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', event => {
  // IGNORA completamente requisições ao IndexedDB ou APIs internas do navegador
  const url = event.request.url;
  if (url.includes('indexeddb') || url.includes('chrome-extension') || url.includes('bfcache')) {
    return;
  }

  // Para requisições de navegação (HTML), usa "Network First" para garantir a versão mais recente
  if (event.request.mode === 'navigate') {
    event.respondWith(
      fetch(event.request)
        .then(response => {
          // Se conseguir buscar online, atualiza o cache silenciosamente
          const responseClone = response.clone();
          caches.open(CACHE_NAME).then(cache => cache.put(event.request, responseClone));
          return response;
        })
        .catch(() => {
          // Se estiver offline, tenta servir do cache
          return caches.match(event.request);
        })
    );
    return;
  }

  // Para JS, CSS e imagens: Cache First (prioriza velocidade, atualiza em segundo plano)
  event.respondWith(
    caches.match(event.request)
      .then(cachedResponse => {
        if (cachedResponse) {
          // Atualiza o cache em segundo plano sem travar a resposta
          fetch(event.request).then(response => {
            caches.open(CACHE_NAME).then(cache => cache.put(event.request, response));
          });
          return cachedResponse;
        }
        // Se não tiver em cache, busca da rede
        return fetch(event.request);
      })
  );
});
