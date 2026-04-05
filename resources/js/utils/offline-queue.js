const DB_NAME = 'silvercare_offline';
const STORE_NAME = 'request_queue';
const DB_VERSION = 1;
const MAX_QUEUE_ITEMS = 300;

let dbPromise = null;

function openDb() {
    if (dbPromise) return dbPromise;

    dbPromise = new Promise((resolve, reject) => {
        const req = indexedDB.open(DB_NAME, DB_VERSION);

        req.onupgradeneeded = () => {
            const db = req.result;
            if (!db.objectStoreNames.contains(STORE_NAME)) {
                const store = db.createObjectStore(STORE_NAME, {
                    keyPath: 'id',
                    autoIncrement: true,
                });
                store.createIndex('createdAt', 'createdAt', { unique: false });
            }
        };

        req.onsuccess = () => resolve(req.result);
        req.onerror = () => reject(req.error);
    });

    return dbPromise;
}

async function withStore(mode, callback) {
    const db = await openDb();

    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_NAME, mode);
        const store = tx.objectStore(STORE_NAME);

        let result;
        try {
            result = callback(store);
        } catch (error) {
            reject(error);
            return;
        }

        tx.oncomplete = () => resolve(result);
        tx.onerror = () => reject(tx.error);
    });
}

function currentCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

async function queueRequest(entry) {
    await withStore('readwrite', (store) => {
        store.add({
            ...entry,
            createdAt: Date.now(),
        });
    });

    const queued = await getQueuedRequests();
    if (queued.length > MAX_QUEUE_ITEMS) {
        const overflow = queued.length - MAX_QUEUE_ITEMS;
        const oldest = queued.slice(0, overflow);
        for (const item of oldest) {
            await deleteQueuedRequest(item.id);
        }
    }
}

async function getQueuedRequests() {
    return withStore('readonly', (store) => {
        const index = store.index('createdAt');
        const req = index.getAll();
        return new Promise((resolve, reject) => {
            req.onsuccess = () => resolve(req.result || []);
            req.onerror = () => reject(req.error);
        });
    });
}

async function deleteQueuedRequest(id) {
    return withStore('readwrite', (store) => {
        store.delete(id);
    });
}

async function tryParseJson(response) {
    const contentType = response.headers.get('content-type') || '';
    if (!contentType.includes('application/json')) {
        return null;
    }

    try {
        return await response.clone().json();
    } catch {
        return null;
    }
}

export async function sendJsonRequest(url, { method = 'POST', body = {} } = {}) {
    const payload = {
        url,
        method,
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': currentCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(body),
    };

    try {
        const response = await fetch(url, {
            method: payload.method,
            headers: payload.headers,
            body: payload.body,
            credentials: 'same-origin',
        });

        const data = await tryParseJson(response);

        if (!response.ok) {
            return { ok: false, queued: false, response, data };
        }

        return { ok: true, queued: false, response, data };
    } catch (error) {
        await queueRequest(payload);

        window.dispatchEvent(new CustomEvent('offline-queue-enqueued', {
            detail: { url, method },
        }));

        return {
            ok: true,
            queued: true,
            response: null,
            data: null,
            error,
        };
    }
}

export async function flushOfflineQueue() {
    if (!navigator.onLine) {
        return { synced: 0, pending: (await getQueuedRequests()).length };
    }

    const queued = await getQueuedRequests();
    let synced = 0;

    for (const item of queued) {
        try {
            const response = await fetch(item.url, {
                method: item.method,
                headers: {
                    ...item.headers,
                    'X-CSRF-TOKEN': currentCsrfToken(),
                },
                body: item.body,
                credentials: 'same-origin',
            });

            if (response.ok || (response.status >= 400 && response.status < 500)) {
                await deleteQueuedRequest(item.id);
                synced++;

                window.dispatchEvent(new CustomEvent('offline-queue-item-synced', {
                    detail: { url: item.url, method: item.method },
                }));
                continue;
            }

            break;
        } catch {
            break;
        }
    }

    const pending = (await getQueuedRequests()).length;

    if (synced > 0) {
        window.dispatchEvent(new CustomEvent('offline-queue-flushed', {
            detail: { synced, pending },
        }));
    }

    return { synced, pending };
}

export function initOfflineQueue() {
    if (typeof window === 'undefined' || typeof indexedDB === 'undefined') {
        return;
    }

    window.addEventListener('online', () => {
        flushOfflineQueue().catch(() => {});
    });

    window.addEventListener('load', () => {
        if (navigator.onLine) {
            flushOfflineQueue().catch(() => {});
        }
    });

    setInterval(() => {
        if (navigator.onLine) {
            flushOfflineQueue().catch(() => {});
        }
    }, 30000);
}
