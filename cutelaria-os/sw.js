const CACHE_NAME = 'cutelaria-os-v2';

const urlsToCache = [

  './',
  './index.html',

  './css/main.css',
  './css/layout.css',
  './css/components.css',
  './css/pages.css',
  './css/animations.css',

  './js/app.js',

  './manifest.json'

];

self.addEventListener(
  'install',
  (event) => {

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

    );

  }
);