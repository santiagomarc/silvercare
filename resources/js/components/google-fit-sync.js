/**
 * Alpine.data('googleFitSync') — Google Fit sync button handler.
 */
import Alpine from 'alpinejs';

export default function googleFitSync() {
    return {
        syncing: false,

        async sync() {
            if (this.syncing) return;
            this.syncing = true;
            const toast = Alpine.store('toast');

            try {
                const resp = await fetch('/google-fit/sync', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const data = await resp.json();
                if (!resp.ok) throw new Error(data.message || 'Sync failed');

                toast?.success('Google Fit synced!');
                if (data.synced) {
                    const items = Object.entries(data.synced)
                        .map(([k, v]) => `${k}: ${v}`).join(', ');
                    if (items) setTimeout(() => toast?.info(items), 1000);
                }
                setTimeout(() => window.location.reload(), 1500);

            } catch (err) {
                console.error('Google Fit sync failed:', err);
                toast?.error(err.message);
            } finally {
                this.syncing = false;
            }
        },
    };
}
