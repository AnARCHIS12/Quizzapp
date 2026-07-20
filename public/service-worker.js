const CACHE_NAME = 'quizzapp-cache-v1';
const ASSETS_TO_CACHE = [
  '/',
  '/manifest.json',
  'https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap',
  'https://cdn.tailwindcss.com',
  'https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js'
];

// Install Service Worker and cache core shell resources
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        return cache.addAll(ASSETS_TO_CACHE);
      })
      .then(() => self.skipWaiting())
  );
});

// Activate and clean old caches
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cache => {
          if (cache !== CACHE_NAME) {
            return caches.delete(cache);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch events: Network first, fallback to Cache, then Offline placeholder
self.addEventListener('fetch', event => {
  // Only cache GET requests
  if (event.request.method !== 'GET') return;

  event.respondWith(
    fetch(event.request)
      .then(response => {
        // Clone the response and put it in cache if valid
        if (response && response.status === 200 && response.type === 'basic') {
          const responseToCache = response.clone();
          caches.open(CACHE_NAME).then(cache => {
            cache.put(event.request, responseToCache);
          });
        }
        return response;
      })
      .catch(() => {
        // Network failed, lookup cache
        return caches.match(event.request)
          .then(cachedResponse => {
            if (cachedResponse) {
              return cachedResponse;
            }
            
            // If it is a page navigation request, we can respond with a cached offline message
            if (event.request.headers.get('accept').includes('text/html')) {
              return new Response(
                '<h1>Quizzapp - Mode Hors-ligne</h1><p>Désolé, vous êtes actuellement déconnecté d\'Internet. Certaines pages dynamiques ne sont pas disponibles sans réseau.</p><a href="/">Retour à l\'accueil</a>',
                {
                  headers: { 'Content-Type': 'text/html; charset=utf-8' }
                }
              );
            }
          });
      })
  );
});
