/**
 * Alpine.data('googleFitSync') — Google Fit sync button handler.
 *
 * Accepts an optional vitalType string so individual vitals screens
 * can scope the sync to just their relevant metric type.
 *
 * C12 FIX: Replaced window.location.reload() with targeted event dispatches
 * matching the SPA-like pattern used by vital-recorder, checklist-tracker, etc.
 * The page only reloads if steps data was synced (no reactive event system yet).
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
                    if (items) setTimeout(() => toast?.info(`Synced: ${items}`), 800);

                    // C12 FIX: Fire vital-recorded for each synced vital type so the
                    // garden-wellness and hero-action progress indicators update reactively
                    // without requiring a full page refresh.
                    Object.keys(data.synced).forEach((type) => {
                        if (type !== 'steps') {
                            window.dispatchEvent(new CustomEvent('vital-recorded', {
                                detail: { type },
                            }));
                        }
                    });

                    // If the server returns updated aggregate progress counts, broadcast them.
                    if (data.vitals !== undefined) {
                        window.dispatchEvent(new CustomEvent('progress-updated', {
                            detail: { vitals: data.vitals, vitalTotal: data.vitalTotal },
                        }));
                    }
                }

                // Steps data has no reactive event system yet — a reload is the only
                // reliable way to refresh the step count widget. Only reload when steps
                // were actually part of this sync batch.
                if (data.synced?.steps) {
                    setTimeout(() => window.location.reload(), 1500);
                }

            } catch (err) {
                console.error('Google Fit sync failed:', err);
                toast?.error(err.message);
            } finally {
                this.syncing = false;
            }
        },
    };
}
