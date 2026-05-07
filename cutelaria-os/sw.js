const CACHE_NAME =
  'cutelaria-os-v1';

const urlsToCache = [

  './',

  './index.html',

  './manifest.json',

  './css/main.css',
  './css/layout.css',
  './css/components.css',
  './css/pages.css',
  './css/animations.css',

  './js/app.js',

  './assets/icons/icon-192.png',
  './assets/icons/icon-512.png'
];

// INSTALL

self.addEventListener('install', (event) => {

  event.waitUntil(

    caches.open(CACHE_NAME)
      .then((cache) => {

        return cache.addAll(
          urlsToCache
        );

      })

  );

});

// FETCH

self.addEventListener('fetch', (event) => {

  event.respondWith(

    caches.match(event.request)

      .then((response) => {

        return response || fetch(event.request);

      })

  );

});

// ACTIVATE

self.addEventListener('activate', (event) => {

  event.waitUntil(

    caches.keys()

      .then((cacheNames) => {

        return Promise.all(

          cacheNames.map((cache) => {

            if (cache !== CACHE_NAME) {

              return caches.delete(cache);

            }

          })

        );

      })

  );

});