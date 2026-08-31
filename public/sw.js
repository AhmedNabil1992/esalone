const CACHE_NAME = 'ps-web-v1';
const ASSETS_TO_CACHE = [
    'https://cdnjs.cloudflare.com/ajax/libs/sql.js/1.8.0/sql-wasm.js',
    'https://cdnjs.cloudflare.com/ajax/libs/sql.js/1.8.0/sql-wasm.wasm'
];

// Install Event
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            return cache.addAll(ASSETS_TO_CACHE);
        }).then(() => self.skipWaiting())
    );
});

// Activate Event
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys => {
            return Promise.all(
                keys.map(key => {
                    if (key !== CACHE_NAME) {
                        return caches.delete(key);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch Event
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);

    // Skip non-GET requests
    if (event.request.method !== 'GET') {
        return;
    }

    // Skip Chrome Extensions and external analytics
    if (!url.protocol.startsWith('http')) {
        return;
    }

    // Page navigation: Network-First (with fallback to Cache)
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request)
                .then(networkResponse => {
                    if (networkResponse.status === 200) {
                        const responseClone = networkResponse.clone();
                        caches.open(CACHE_NAME).then(cache => {
                            cache.put(event.request, responseClone);
                        });
                    }
                    return networkResponse;
                })
                .catch(() => {
                    return caches.match(event.request).then(cachedResponse => {
                        if (cachedResponse) {
                            return cachedResponse;
                        }
                        // Fallback to primary cached panels
                        return caches.match('/admin/device-control') || caches.match('/admin/sales');
                    });
                })
        );
        return;
    }

    // Static assets: Cache-First
    event.respondWith(
        caches.match(event.request).then(cachedResponse => {
            if (cachedResponse) {
                return cachedResponse;
            }

            return fetch(event.request).then(networkResponse => {
                if (networkResponse.status === 200 && (
                    url.pathname.endsWith('.js') ||
                    url.pathname.endsWith('.css') ||
                    url.pathname.endsWith('.woff2') ||
                    url.host.includes('unpkg.com') ||
                    url.host.includes('jsdelivr.net')
                )) {
                    const responseClone = networkResponse.clone();
                    caches.open(CACHE_NAME).then(cache => {
                        cache.put(event.request, responseClone);
                    });
                }
                return networkResponse;
            }).catch(() => {
                return new Response('', { status: 408, statusText: 'Network timeout' });
            });
        })
    );
});

// Web Push Notifications
self.addEventListener('push', function (event) {
    let title = 'Notification';
    let options = {
        body: '',
        icon: '/favicon.ico',
        badge: '/favicon.ico',
        data: {},
        tag: 'default',
        renotify: true,
    };

    if (event.data) {
        try {
            const data = event.data.json();
            title = data.title || title;
            options.body = data.body || '';
            options.icon = data.icon || options.icon;
            options.badge = data.badge || options.badge;
            options.data = data.data || {};
            options.tag = data.tag || options.tag;
        } catch (e) {
            title = event.data.text() || title;
        }
    }

    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();

    const actionUrl = (event.notification.data && event.notification.data.action_url)
        ? event.notification.data.action_url
        : '/admin';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
            for (let i = 0; i < clientList.length; i++) {
                const client = clientList[i];
                if (new URL(client.url).origin === self.location.origin) {
                    return client.focus().then(function (focused) {
                        if (focused.navigate) {
                            return focused.navigate(actionUrl);
                        }
                    });
                }
            }
            return clients.openWindow(actionUrl);
        })
    );
});
