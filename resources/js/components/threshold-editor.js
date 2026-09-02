/**
 * Alpine.data('thresholdEditor') — per-patient alert threshold form (M2).
 *
 * The thresholds table and the resolution order (patient override, then
 * config default) shipped in sprint 2 and worked. What was missing was any way
 * for a caregiver to actually set one: the route returned raw JSON.
 */
export default function thresholdEditor({
    metricType,
    thresholds = {},
    defaults = {},
    isCustom = false,
    endpoint,
} = {}) {
    return {
        metricType,
        thresholds: { ...thresholds },
        defaults: { ...defaults },
        isCustom,
        endpoint,
        busy: false,
        message: '',
        ok: false,

        async post(body) {
            const response = await fetch(this.endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                credentials: 'same-origin',
                cache: 'no-store',
                body: JSON.stringify(body),
            });

            if (response.status === 419) {
                throw new Error('Your session timed out. Please refresh the page and try again.');
            }

            const data = await response.json().catch(() => null);

            if (!response.ok || !data?.success) {
                throw new Error(data?.message || 'Could not save these thresholds.');
            }

            return data;
        },

        async save() {
            if (this.busy) return;

            // A blank field would be sent as null and silently disable that
            // bound, so refuse rather than quietly weakening an alert rule.
            const blank = Object.entries(this.thresholds)
                .filter(([, v]) => v === '' || v === null || Number.isNaN(v))
                .map(([k]) => k);

            if (blank.length > 0) {
                this.ok = false;
                this.message = 'Every threshold needs a value. Leave the default in place if you are unsure.';
                return;
            }

            this.busy = true;
            this.message = '';

            try {
                await this.post({ metric_type: this.metricType, thresholds: this.thresholds });
                this.isCustom = true;
                this.ok = true;
                this.message = 'Saved. Future readings use these values.';
            } catch (error) {
                this.ok = false;
                this.message = error.message;
            } finally {
                this.busy = false;
            }
        },

        async resetToDefault() {
            if (this.busy) return;

            this.busy = true;
            this.message = '';

            try {
                const data = await this.post({
                    metric_type: this.metricType,
                    thresholds: this.defaults,
                    reset_to_default: true,
                });

                this.thresholds = { ...(data.thresholds ?? this.defaults) };
                this.isCustom = false;
                this.ok = true;
                this.message = 'Reset to the clinical default.';
            } catch (error) {
                this.ok = false;
                this.message = error.message;
            } finally {
                this.busy = false;
            }
        },
    };
}
