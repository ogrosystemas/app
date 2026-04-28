// SAM ERP — Service Worker PWA v2
const APP_VERSION  = 'sam-v2';
const CACHE_STATIC = `${APP_VERSION}-static`;
const CACHE_PAGES  = `${APP_VERSION}-pages`;

const STATIC_ASSETS = [
  '/manifest.json',
  '/assets/icons/icon-192.png',
  '/assets/icons/icon-512.png',
  'https://unpkg.com/lucide@latest/dist/umd/lucide.min.js',
];

const PAGE_CACHE = [
  '/pages/dashboard.php',
  '/pages/sac.php',
  '/pages/anuncios.php',
  '/pages/financeiro.php',
  '/pages/estoque.php',
];

self.addEventListener('install', e => {
  e.waitUntil(
    Promise.all([
      caches.open(CACHE_STATIC).then(c => c.addAll(STATIC_ASSETS).catch(()=>{})),
      caches.open(CACHE_PAGES).then(c => c.addAll(PAGE_CACHE).catch(()=>{})),
    ]).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', e => {
  e.waitUntil(
    caches.keys()
      .then(keys => Promise.all(keys.filter(k => !k.startsWith(APP_VERSION)).map(k => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', e => {
  const req = e.request;
  const url = new URL(req.url);
  if (req.method !== 'GET') return;
  if (!url.protocol.startsWith('http')) return;
  if (url.pathname.startsWith('/api/') || url.pathname.includes('auth.php')) return;

  // CDN externos — cache first
  if (url.hostname !== self.location.hostname) {
    e.respondWith(
      caches.match(req).then(cached => cached || fetch(req).then(res => {
        if (res.ok) caches.open(CACHE_STATIC).then(c => c.put(req, res.clone()));
        return res;
      }).catch(() => cached || new Response('', {status:408})))
    );
    return;
  }

  // Páginas PHP — network first, cache fallback
  if (url.pathname.endsWith('.php') || url.pathname === '/') {
    e.respondWith(
      fetch(req).then(res => {
        if (res.ok) caches.open(CACHE_PAGES).then(c => c.put(req, res.clone()));
        return res;
      }).catch(() => caches.match(req).then(c => c || caches.match('/pages/dashboard.php')))
    );
    return;
  }

  // Outros assets — cache first
  e.respondWith(
    caches.match(req).then(cached => cached || fetch(req).then(res => {
      if (res.ok) caches.open(CACHE_STATIC).then(c => c.put(req, res.clone()));
      return res;
    }))
  );
});

// Push Notifications
self.addEventListener('push', e => {
  const data = e.data?.json() ?? {};
  e.waitUntil(self.registration.showNotification(data.title || '📦 SAM ERP', {
    body:    data.body || 'Nova notificação',
    icon:    '/assets/icons/icon-192.png',
    badge:   '/assets/icons/icon-72.png',
    vibrate: [200, 100, 200],
    tag:     data.tag || 'sam',
    data:    { url: data.url || '/pages/dashboard.php' },
  }));
});

self.addEventListener('notificationclick', e => {
  e.notification.close();
  const url = e.notification.data?.url || '/pages/dashboard.php';
  e.waitUntil(
    clients.matchAll({type:'window',includeUncontrolled:true}).then(wins => {
      const w = wins.find(w => w.url.includes(self.location.hostname));
      if (w) { w.focus(); w.navigate(url); } else clients.openWindow(url);
    })
  );
});
