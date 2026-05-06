/**
 * Alpine.data('googleFitSync') — Google Fit sync button handler.
 *
 * Accepts an optional vitalType string so individual vitals screens
 * can scope the sync to just their relevant metric type.
 */
import Alpine from 'alpinejs';

export default function googleFitSync(vitalType = null) {
    return {
        syncing: false,

        async sync() {
            if (this.syncing) return;
            this.syncing = true;
            const toast = Alpine.store('toast');

            try {
                // Build URL with optional vital_type param to avoid syncing all 4
                // vital types when we only need one (e.g. on the heart-rate page).
                const url = new URL('/google-fit/sync', window.location.origin);
                if (vitalType) url.searchParams.set('vital_type', vitalType);

                const resp = await fetch(url.toString(), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const data = await resp.json();

                // 401 = refresh token stale → user must reconnect
                if (resp.status === 401) {
                    toast?.error('Google Fit session expired. Please reconnect.');
                    return;
                }

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
