/**
 * Offline request queue (C2 / S3).
 *
 * When a request cannot reach the server it is stored in IndexedDB and replayed
 * on reconnect. Two things about that are safety-critical:
 *
 *  1. A queued action has NOT happened yet. This module used to return
 *     `{ ok: true, queued: true }`, so a senior tapping "Take" with no signal
 *     got a definitive green checkmark for a dose the server never received.
 *     It now returns `ok: false, pending: true` — the caller must render a
 *     "waiting to sync" state, never a confirmation.
 *
 *  2. Replaying a write twice must not double-apply it. Every queued request
 *     carries a stable idempotency key generated once at queue time, so a
 *     replay after a flaky connection is recognised by the server as the same
 *     intent rather than a second dose.
 *
 * Failures are reported with machine-readable codes so the UI can explain what
 * to do instead of showing a generic toast.
 */

const DB_NAME = 'silvercare_offline';
const STORE_NAME = 'request_queue';
const DB_VERSION = 1;
const MAX_QUEUE_ITEMS = 300;

/** Machine-readable outcomes for a queued request. */
export const SyncStatus = {
    PENDING: 'pending_sync',
    CONFIRMED: 'confirmed',
    REJECTED: 'rejected',
    CONFLICT: 'conflict',
};

export const SyncErrorCode = {
    SESSION_EXPIRED: 'SESSION_EXPIRED',
    DOSE_ALREADY_TAKEN: 'DOSE_ALREADY_TAKEN',
    DOSE_WINDOW_EXPIRED: 'DOSE_WINDOW_EXPIRED',
    MEDICATION_EXPIRED: 'MEDICATION_EXPIRED',
    CONFLICT: 'CONFLICT',
    REJECTED: 'REJECTED',
};

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

/**
 * Stable per-intent key. Generated once when the request is created and reused
 * on every replay, so the server can recognise a retry.
 */
function newIdempotencyKey() {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
        return crypto.randomUUID();
    }

    return `sc-${Date.now()}-${Math.random().toString(36).slice(2, 11)}`;
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

export async function getQueuedRequests() {
    return withStore('readonly', (store) => {
        const index = store.index('createdAt');
        const req = index.getAll();
        return new Promise((resolve, reject) => {
            req.onsuccess = () => resolve(req.result || []);
            req.onerror = () => reject(req.error);
        });
    });
}

export async function pendingSyncCount() {
    try {
        return (await getQueuedRequests()).length;
    } catch {
        return 0;
    }
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

function emit(name, detail) {
    window.dispatchEvent(new CustomEvent(name, { detail }));
}

/**
 * Send a JSON request, queueing it for later if the network is unavailable.
 *
 * Returns one of:
 *   { ok: true,  queued: false, status: 'confirmed', data }   server accepted
 *   { ok: false, queued: false, status: 'rejected' | 'conflict', errorCode, data }
 *   { ok: false, queued: true,  status: 'pending_sync', pending: true, idempotencyKey }
 *
 * `ok` is never true for a queued request. The action has not happened yet.
 */
export async function sendJsonRequest(url, { method = 'POST', body = {} } = {}) {
    const idempotencyKey = newIdempotencyKey();

    const payload = {
        url,
        method,
        idempotencyKey,
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': currentCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            'X-Idempotency-Key': idempotencyKey,
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
            return {
                ok: false,
                queued: false,
                status: response.status === 409 ? SyncStatus.CONFLICT : SyncStatus.REJECTED,
                errorCode: data?.error_code ?? classifyStatus(response.status),
                response,
                data,
            };
        }

        return {
            ok: true,
            queued: false,
            status: SyncStatus.CONFIRMED,
            response,
            data,
        };
    } catch (error) {
        // Network unreachable — hold the intent and tell the caller plainly
        // that nothing has been confirmed.
        await queueRequest(payload);

        emit('offline-queue-enqueued', { url, method, idempotencyKey });

        return {
            ok: false,
            queued: true,
            pending: true,
            status: SyncStatus.PENDING,
            idempotencyKey,
            response: null,
            data: null,
            error,
        };
    }
}

function classifyStatus(status) {
    if (status === 401 || status === 403) return SyncErrorCode.SESSION_EXPIRED;
    if (status === 409) return SyncErrorCode.CONFLICT;
    return SyncErrorCode.REJECTED;
}

function humanMessage(errorCode, fallback) {
    switch (errorCode) {
        case SyncErrorCode.SESSION_EXPIRED:
            return 'Your session expired. Please log in again to sync your saved actions.';
        case SyncErrorCode.DOSE_ALREADY_TAKEN:
            return 'That dose was already recorded, so your offline tap was not applied again.';
        case SyncErrorCode.DOSE_WINDOW_EXPIRED:
            return 'That dose was too far past its time to record. Please tell your caregiver.';
        case SyncErrorCode.MEDICATION_EXPIRED:
            return 'That prescription had already ended, so the dose was not recorded.';
        case SyncErrorCode.CONFLICT:
            return 'One of your saved actions conflicts with the server and needs review.';
        default:
            return fallback || 'One of your saved actions could not be applied.';
    }
}

let isFlushing = false;

export async function flushOfflineQueue() {
    if (isFlushing || !navigator.onLine) {
        return { synced: 0, rejected: 0, conflicts: 0, pending: await pendingSyncCount() };
    }

    isFlushing = true;

    try {
        const queued = await getQueuedRequests();
        let synced = 0;
        let rejected = 0;
        let conflicts = 0;

        for (const item of queued) {
            try {
                const response = await fetch(item.url, {
                    method: item.method,
                    headers: {
                        ...item.headers,
                        // CSRF rotates between sessions; the idempotency key does not.
                        'X-CSRF-TOKEN': currentCsrfToken(),
                    },
                    body: item.body,
                    credentials: 'same-origin',
                });

                if (response.ok) {
                    await deleteQueuedRequest(item.id);
                    synced++;
                    emit('offline-queue-item-synced', {
                        url: item.url,
                        method: item.method,
                        idempotencyKey: item.idempotencyKey,
                        data: await tryParseJson(response),
                    });
                    continue;
                }

                const data = await tryParseJson(response);
                const errorCode = data?.error_code ?? classifyStatus(response.status);

                // 409 is a genuine conflict: the server's state disagrees with
                // what the client intended. Surface it for review rather than
                // silently dropping the senior's action.
                if (response.status === 409) {
                    await deleteQueuedRequest(item.id);
                    conflicts++;
                    emit('offline-queue-item-conflict', {
                        url: item.url,
                        idempotencyKey: item.idempotencyKey,
                        errorCode,
                        message: humanMessage(errorCode, data?.message),
                    });
                    notify(humanMessage(errorCode, data?.message), 'warning');
                    continue;
                }

                // Other 4xx: permanently invalid, will never succeed. Drop it,
                // but always tell the user what happened to their action.
                if (response.status >= 400 && response.status < 500) {
                    await deleteQueuedRequest(item.id);
                    rejected++;
                    emit('offline-queue-item-failed', {
                        url: item.url,
                        status: response.status,
                        idempotencyKey: item.idempotencyKey,
                        errorCode,
                        message: humanMessage(errorCode, data?.message),
                    });
                    notify(humanMessage(errorCode, data?.message), 'error');
                    continue;
                }

                // 5xx — server trouble. Keep it queued and retry later.
                break;
            } catch {
                // Still offline. Keep everything queued.
                break;
            }
        }

        const pending = await pendingSyncCount();

        if (synced > 0 || rejected > 0 || conflicts > 0) {
            emit('offline-queue-flushed', { synced, rejected, conflicts, pending });
        }

        return { synced, rejected, conflicts, pending };
    } finally {
        isFlushing = false;
    }
}

function notify(message, type) {
    if (typeof window.scToast === 'function') {
        window.scToast(message, type);
        return;
    }

    window.Alpine?.store('toast')?.show(message, type);
}

export function initOfflineQueue() {
    if (typeof window === 'undefined' || typeof indexedDB === 'undefined') {
        return;
    }

    let onlineTimeout = null;
    window.addEventListener('online', () => {
        if (onlineTimeout) clearTimeout(onlineTimeout);
        onlineTimeout = setTimeout(() => {
            flushOfflineQueue().catch(() => {});
        }, 500);
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
