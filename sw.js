const CACHE_NAME = 'einkaufs-v1.5.5';
const ASSETS = [
  './',
  './index.php',
  './favicon.png',
  './assets/css/style.css',
  './assets/js/app.js',
  './manifest.webmanifest',
  './assets/css/inter.css',
  './assets/css/fontawesome.min.css'
];

// Install Event
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(ASSETS))
  );
  self.skipWaiting();
});

// Activate Event
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys => {
      return Promise.all(
        keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key))
      );
    })
  );
});

// Fetch Event
self.addEventListener('fetch', event => {
  // Für API-Calls: Erst Netzwerk, dann Cache (Network First)
  if (event.request.url.includes('/api/')) {
    event.respondWith(
      fetch(event.request)
        .catch(() => caches.match(event.request))
    );
    return;
  }

  // Für statische Files: Erst Cache, dann Netzwerk (Cache First)
  event.respondWith(
    caches.match(event.request)
      .then(response => response || fetch(event.request))
  );
});

// Message Event for immediate update
self.addEventListener('message', event => {
  if (event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});
