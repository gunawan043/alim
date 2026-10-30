const CACHE = 'alim-shell-v3';
const OFFLINE_URL = '/offline';

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE).then((cache) => cache.addAll([OFFLINE_URL])));
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') return;

    if (event.request.mode === 'navigate') {
        event.respondWith(fetch(event.request).catch(() => caches.match(OFFLINE_URL)));
        return;
    }

    const url = new URL(event.request.url);
    if (url.origin !== self.location.origin) return;

    if (/\.(css|js|png|jpg|jpeg|svg|webp|woff2?)$/i.test(url.pathname)) {
        event.respondWith(
            caches.match(event.request).then((cached) => cached || fetch(event.request).then((response) => {
                const copy = response.clone();
                caches.open(CACHE).then((cache) => cache.put(event.request, copy));
                return response;
            }))
        );
    }
});

self.addEventListener('push', (event) => {
    let payload = {};

    try {
        payload = event.data ? event.data.json() : {};
    } catch (error) {
        payload = { title: 'ALIM', body: event.data ? event.data.text() : '' };
    }

    const title = payload.title || 'ALIM';
    const options = {
        body: payload.body || '',
        icon: payload.icon || '/build/images/alim-sm-light.png',
        badge: payload.badge || '/build/images/alim-sm-light.png',
        tag: payload.tag || 'alim-notification',
        data: payload,
        renotify: true,
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const payload = event.notification.data || {};
    const targetUrl = payload.url || '/';

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                if ('focus' in client) {
                    if ('navigate' in client) {
                        client.navigate(targetUrl).catch(() => {});
                    }
                    return client.focus();
                }
            }

            if (self.clients.openWindow) {
                return self.clients.openWindow(targetUrl);
            }
        })
    );
});
