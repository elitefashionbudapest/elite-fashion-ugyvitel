const CACHE_NAME = 'elite-fashion-v1';
const OFFLINE_URL = '/offline.html';

// Statikus fájlok cache-elése telepítéskor
const PRECACHE_URLS = [
    './',
    './assets/css/app.css',
    './assets/js/app.js',
    './manifest.json'
];

// Telepítés
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            return cache.addAll(PRECACHE_URLS).catch(() => {});
        })
    );
    self.skipWaiting();
});

// Aktiválás — régi cache törlése
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
        )
    );
    self.clients.claim();
});

// Fetch — network first, cache fallback (API hívásokat nem cache-eljük)
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);

    // API hívások, POST kérések — nem cache-eljük
    if (event.request.method !== 'GET' ||
        url.pathname.includes('/api') ||
        url.pathname.includes('/chat/') ||
        url.pathname.includes('/tasks/') ||
        url.pathname.includes('/notifications/')) {
        return;
    }

    event.respondWith(
        fetch(event.request)
            .then(response => {
                // Sikeres válasz cache-elése (csak statikus fájlok)
                if (response.ok && (
                    url.pathname.endsWith('.css') ||
                    url.pathname.endsWith('.js') ||
                    url.pathname.endsWith('.png') ||
                    url.pathname.endsWith('.jpg') ||
                    url.pathname.endsWith('.woff2')
                )) {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
                }
                return response;
            })
            .catch(() => {
                // Offline — cache-ből szolgáljuk
                return caches.match(event.request);
            })
    );
});

// Push értesítés fogadása
self.addEventListener('push', event => {
    const data = event.data ? event.data.json() : {};
    const title = data.title || 'Elite Fashion';
    const options = {
        body: data.body || 'Új értesítés érkezett',
        icon: './assets/icons/icon-192.png',
        badge: './assets/icons/icon-72.png',
        vibrate: [200, 100, 200],
        tag: data.tag || 'default',
        data: { url: data.url || './' },
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

// Push értesítésre kattintás
self.addEventListener('notificationclick', event => {
    event.notification.close();
    const url = event.notification.data?.url || './';
    event.waitUntil(
        clients.matchAll({ type: 'window' }).then(windowClients => {
            // Ha már nyitva van, fókuszáljunk rá
            for (const client of windowClients) {
                if (client.url.includes(url) && 'focus' in client) {
                    return client.focus();
                }
            }
            // Ha nincs nyitva, nyissuk meg
            return clients.openWindow(url);
        })
    );
});
