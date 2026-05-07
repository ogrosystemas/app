const CACHE_NAME = 'cutelaria-custo-v1';
const STATIC_ASSETS = [
    '/cutelaria-custo/',
    '/cutelaria-custo/index.html',
    '/cutelaria-custo/css/main.css',
    '/cutelaria-custo/css/layout.css',
    '/cutelaria-custo/css/components.css',
    '/cutelaria-custo/css/pages.css',
    '/cutelaria-custo/css/animations.css',
    '/cutelaria-custo/js/utils/constants.js',
    '/cutelaria-custo/js/utils/helpers.js',
    '/cutelaria-custo/js/utils/formatters.js',
    '/cutelaria-custo/js/utils/validators.js',
    '/cutelaria-custo/js/utils/calculations.js',
    '/cutelaria-custo/js/services/db.js',
    '/cutelaria-custo/js/services/storage.js',
    '/cutelaria-custo/js/services/export-import.js',
    '/cutelaria-custo/js/services/cache.js',
    '/cutelaria-custo/js/modules/toast.js',
    '/cutelaria-custo/js/modules/modal.js',
    '/cutelaria-custo/js/modules/navbar.js',
    '/cutelaria-custo/js/modules/ui.js',
    '/cutelaria-custo/js/modules/router.js',
    '/cutelaria-custo/js/pages/dashboard.js',
    '/cutelaria-custo/js/pages/materiais.js',
    '/cutelaria-custo/js/pages/insumos.js',
    '/cutelaria-custo/js/pages/equipamentos.js',
    '/cutelaria-custo/js/pages/faca.js',
    '/cutelaria-custo/js/pages/historico.js',
    '/cutelaria-custo/js/pages/configuracoes.js',
    '/cutelaria-custo/js/modules/app.js',
    '/cutelaria-custo/manifest.json'
];

const CDN_ASSETS = [
    'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap',
    'https://cdn.jsdelivr.net/npm/dexie@3.2.4/dist/dexie.min.js',
    'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(STATIC_ASSETS))
            .then(() => caches.open(CACHE_NAME + '-cdn'))
            .then((cdnCache) => cdnCache.addAll(CDN_ASSETS))
            .catch(() => caches.open(CACHE_NAME))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((names) =>
            Promise.all(
                names
                    .filter((n) => !n.startsWith(CACHE_NAME))
                    .map((n) => caches.delete(n))
            )
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // CDN assets
    if (CDN_ASSETS.includes(request.url)) {
        event.respondWith(
            caches.match(request).then((cached) =>
                cached || fetch(request).then((response) => {
                    const clone = response.clone();
                    caches.open(CACHE_NAME + '-cdn').then((c) => c.put(request, clone));
                    return response;
                })
            )
        );
        return;
    }

    // Static assets - cache first
    event.respondWith(
        caches.match(request).then((cached) => {
            if (cached) return cached;
            return fetch(request).then((response) => {
                if (!response || response.status !== 200 || response.type !== 'basic') {
                    return response;
                }
                const clone = response.clone();
                caches.open(CACHE_NAME).then((c) => c.put(request, clone));
                return response;
            }).catch(() => {
                // Offline fallback
                if (request.mode === 'navigate') {
                    return caches.match('/cutelaria-custo/index.html');
                }
                return new Response('Offline', { status: 503 });
            });
        })
    );
});
