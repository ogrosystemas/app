const CACHE_NAME =
  'cutelaria-os-v10';

const urlsToCache = [

  './',
  './index.html',

  './manifest.json',

  './css/main.css',
  './css/layout.css',
  './css/components.css',
  './css/pages.css',
  './css/animations.css',

  './js/app.js'

];

self.addEventListener(
  'install',
  (event) => {

    self.skipWaiting();

    event.waitUntil(

      caches.open(CACHE_NAME)
        .then((cache) => {

          return cache.addAll(
            urlsToCache
          );

        })

    );

  }
);

self.addEventListener(
  'activate',
  (event) => {

    event.waitUntil(

      caches.keys()
        .then((keys) => {

          return Promise.all(

            keys.map((key) => {

              if (
                key !== CACHE_NAME
              ) {

                return caches.delete(key);

              }

            })

          );

        })
        .then(() => {

          return self.clients.claim();

        })

    );

  }
);

self.addEventListener(
  'fetch',
  (event) => {

    event.respondWith(

      caches.match(event.request)
        .then((response) => {

          return (
            response
            ||
            fetch(event.request)
          );

        })

    );

  }
);