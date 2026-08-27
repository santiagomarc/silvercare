/**
 * SilverCare Offline Sync Queue Manager
 * Intercepts & buffers dose confirmations, vitals, and check-ins when offline.
 * Automatically flushes to /api/offline/sync when connection is restored.
 */

window.SilverCareOffline = (function () {
    const STORAGE_KEY = 'silvercare_offline_mutations_v1';
    let isOnline = navigator.onLine;

    function getQueue() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            return raw ? JSON.parse(raw) : [];
        } catch (e) {
            console.error('Failed to read offline queue:', e);
            return [];
        }
    }

    function saveQueue(queue) {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(queue));
            updateUiBadge(queue.length);
        } catch (e) {
            console.error('Failed to save offline queue:', e);
        }
    }

    function generateUuid() {
        return 'mut_' + Date.now() + '_' + Math.random().toString(36).substring(2, 9);
    }

    function enqueueMutation(actionType, payload) {
        const queue = getQueue();
        const mutation = {
            client_mutation_id: generateUuid(),
            action_type: actionType,
            payload: payload,
            queued_at: new Date().toISOString(),
        };

        queue.push(mutation);
        saveQueue(queue);
        showToast(`Saved offline (${queue.length} pending sync). Will auto-sync when online.`, 'info');
        return mutation;
    }

    function flushQueue() {
        const queue = getQueue();
        if (queue.length === 0) return Promise.resolve();

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!csrfToken) {
            console.warn('No CSRF token found for offline sync.');
            return Promise.resolve();
        }

        showToast(`Syncing ${queue.length} offline actions to server...`, 'info');

        return fetch('/api/offline/sync', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ mutations: queue })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Clear successfully processed items
                localStorage.removeItem(STORAGE_KEY);
                updateUiBadge(0);
                showToast(`✓ All ${data.applied_count} offline actions synced successfully!`, 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showToast('Offline sync encountered conflicts. Retrying shortly.', 'error');
            }
        })
        .catch(err => {
            console.warn('Failed to flush offline queue:', err);
        });
    }

    function updateUiBadge(count) {
        let badge = document.getElementById('offline-sync-badge');
        if (!badge && count > 0) {
            badge = document.createElement('div');
            badge.id = 'offline-sync-badge';
            badge.className = 'fixed top-4 right-4 z-50 bg-amber-500 text-white text-xs font-black px-3 py-1.5 rounded-full shadow-lg flex items-center gap-1.5';
            document.body.appendChild(badge);
        }

        if (badge) {
            if (count === 0) {
                badge.remove();
            } else {
                badge.innerHTML = `<span>⚡ ${count} Offline Actions Pending</span>`;
            }
        }
    }

    function showToast(message, type) {
        let toast = document.getElementById('offline-sync-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'offline-sync-toast';
            document.body.appendChild(toast);
        }

        toast.textContent = message;
        if (type === 'success') {
            toast.className = 'fixed bottom-4 left-4 z-50 px-4 py-2.5 rounded-2xl bg-emerald-600 text-white text-xs font-bold shadow-xl animate-fade-in';
        } else if (type === 'error') {
            toast.className = 'fixed bottom-4 left-4 z-50 px-4 py-2.5 rounded-2xl bg-rose-600 text-white text-xs font-bold shadow-xl animate-fade-in';
        } else {
            toast.className = 'fixed bottom-4 left-4 z-50 px-4 py-2.5 rounded-2xl bg-slate-900 text-white text-xs font-bold shadow-xl animate-fade-in';
        }

        setTimeout(() => {
            if (toast) toast.remove();
        }, 4000);
    }

    function init() {
        window.addEventListener('online', function () {
            isOnline = true;
            showToast('Connection restored! Syncing offline data...', 'info');
            flushQueue();
        });

        window.addEventListener('offline', function () {
            isOnline = false;
            showToast('You are currently offline. Changes will be saved locally.', 'info');
        });

        // Check if there are pending items on startup
        const queue = getQueue();
        if (queue.length > 0) {
            updateUiBadge(queue.length);
            if (navigator.onLine) {
                flushQueue();
            }
        }
    }

    return {
        init: init,
        enqueue: enqueueMutation,
        flush: flushQueue,
        getPendingCount: () => getQueue().length,
        isOnline: () => isOnline,
    };
})();

document.addEventListener('DOMContentLoaded', function () {
    window.SilverCareOffline.init();
});
