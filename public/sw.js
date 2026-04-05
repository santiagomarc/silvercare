const STATIC_CACHE = 'silvercare-static-v1';
const RUNTIME_CACHE = 'silvercare-runtime-v1';
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

async function networkFirstNavigation(request) {
    const runtime = await caches.open(RUNTIME_CACHE);

    try {
        const response = await fetch(request);
        runtime.put(request, response.clone());
        return response;
    } catch {
        const cached = await runtime.match(request);
        if (cached) return cached;

        const offline = await caches.match(OFFLINE_URL);
        return offline || new Response('Offline', { status: 503, statusText: 'Offline' });
    }
}

async function staleWhileRevalidate(request) {
    const runtime = await caches.open(RUNTIME_CACHE);
    const cached = await runtime.match(request);

    const fetchPromise = fetch(request)
        .then((response) => {
            runtime.put(request, response.clone());
            return response;
        })
        .catch(() => null);

    return cached || fetchPromise || new Response('', { status: 504, statusText: 'Gateway Timeout' });
}
