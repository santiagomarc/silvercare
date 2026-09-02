const STATIC_CACHE = 'silvercare-static-v2';
const RUNTIME_CACHE = 'silvercare-runtime-v2';
const OFFLINE_URL = '/offline.html';

const STATIC_ASSETS = [
    OFFLINE_URL,
    '/manifest.webmanifest',
    '/assets/icons/silvercare.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => cache.addAll(STATIC_ASSETS))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(
            keys
                .filter((key) => ![STATIC_CACHE, RUNTIME_CACHE].includes(key))
                .map((key) => caches.delete(key))
        ))
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);
    const isNavigation = request.mode === 'navigate';
    const isStaticAsset =
        request.destination === 'style' ||
        request.destination === 'script' ||
        request.destination === 'image' ||
        request.destination === 'font';

    if (isNavigation) {
        event.respondWith(networkFirstNavigation(request));
        return;
    }

    if (isStaticAsset || url.pathname.startsWith('/build/')) {
        event.respondWith(staleWhileRevalidate(request));
    }
});

self.addEventListener('message', (event) => {
    if (event.data && event.data.action === 'CLEAR_USER_DATA') {
        event.waitUntil(
            caches.delete(RUNTIME_CACHE)
        );
    }
});

// ── Browser push (H8) ────────────────────────────────────────────────
// Clinical alerts reach a caregiver whose phone is locked and whose email
// is unread. Only critical and emergency alerts are pushed; the server
// decides that, so anything arriving here is worth showing.

self.addEventListener('push', (event) => {
    let data = {};

    try {
        data = event.data ? event.data.json() : {};
    } catch {
        // A payload we cannot parse is still a signal something happened —
        // show a generic prompt rather than swallowing it.
        data = {};
    }

    const isEmergency = data.severity === 'emergency';
    const title = data.title || 'SilverCare alert';
    const body = data.body || 'Open SilverCare to review this alert.';

    event.waitUntil(
        self.registration.showNotification(title, {
            body,
            icon: '/assets/icons/silvercare.png',
            badge: '/assets/icons/silvercare.png',
            // Alerts for the same patient replace each other rather than
            // stacking, but a new alert always re-notifies.
            tag: data.alert_id ? `silvercare-alert-${data.alert_id}` : 'silvercare-alert',
            renotify: true,
            // Emergencies stay on screen until the caregiver acts on them.
            requireInteraction: isEmergency,
            vibrate: isEmergency ? [200, 100, 200, 100, 200] : [200, 100, 200],
            data: {
                url: data.url || '/caregiver/dashboard',
                alertId: data.alert_id || null,
            },
            actions: [
                { action: 'open', title: 'Review alert' },
            ],
        })
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const target = (event.notification.data && event.notification.data.url) || '/caregiver/dashboard';

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            // Focus an already-open SilverCare tab instead of piling up new ones.
            for (const client of clientList) {
                if ('focus' in client) {
                    client.navigate?.(target);
                    return client.focus();
                }
            }

            return self.clients.openWindow ? self.clients.openWindow(target) : undefined;
        })
    );
});

async function networkFirstNavigation(request) {
    try {
        return await fetch(request);
    } catch {
        const offline = await caches.match(OFFLINE_URL);
        return offline || new Response('Offline', { status: 503, statusText: 'Offline' });
    }
}

async function staleWhileRevalidate(request) {
    const runtime = await caches.open(RUNTIME_CACHE);
    const cached = await runtime.match(request);

    const fetchPromise = fetch(request)
        .then((response) => {
            if (response && response.ok) {
                runtime.put(request, response.clone());
            }
            return response;
        })
        .catch(() => null);

    return cached || fetchPromise || new Response('', { status: 504, statusText: 'Gateway Timeout' });
}
