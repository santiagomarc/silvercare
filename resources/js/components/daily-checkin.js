/**
 * Alpine.data('dailyCheckin') — the senior's one-tap wellness check-in.
 *
 * This used to POST and then call `window.location.reload()`. The reload did
 * not repaint reliably, so from the senior's side the button did nothing until
 * they refreshed by hand — and a failure (expired CSRF token, no connection)
 * was swallowed entirely, because the handler only looked at `data.success`
 * and had no catch.
 *
 * The banner now updates itself from the server's response, and says so when
 * something goes wrong.
 */
export default function dailyCheckin({
    checkedIn = false,
    checkedInAt = null,
    status = null,
    endpoint = '/checkin',
} = {}) {
    return {
        checkedIn,
        checkedInAt,
        status,
        endpoint,
        busy: false,
        error: '',

        get needsHelp() {
            return this.checkedIn && this.status === 'need_help';
        },

        get heading() {
            if (!this.checkedIn) return 'Daily Wellness Check-in';
            return this.needsHelp ? 'Help Is On The Way' : 'Checked In For Today!';
        },

        get subheading() {
            if (!this.checkedIn) {
                return 'Let your caregiver know you are doing well today with a single tap.';
            }

            const when = this.checkedInAt ? `at ${this.checkedInAt}` : 'today';

            return this.needsHelp
                ? `We let your caregiver know you need help ${when}.`
                : `You checked in ${when}. Your caregiver knows you're safe!`;
        },

        async submit(nextStatus) {
            if (this.busy) return;

            this.busy = true;
            this.error = '';

            try {
                const response = await fetch(this.endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN':
                            document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                    credentials: 'same-origin',
                    cache: 'no-store',
                    body: JSON.stringify({ status: nextStatus }),
                });

                if (response.status === 419) {
                    this.error = 'Your session timed out. Please refresh the page and try again.';
                    return;
                }

                const data = await response.json().catch(() => null);

                if (!response.ok || !data?.success) {
                    this.error = data?.message || 'We could not save your check-in. Please try again.';
                    return;
                }

                // Repaint from what the server actually recorded.
                this.checkedIn = true;
                this.status = data.checkin?.status ?? nextStatus;
                this.checkedInAt = data.checkin?.checked_in_at
                    ? new Date(data.checkin.checked_in_at).toLocaleTimeString([], {
                          hour: 'numeric',
                          minute: '2-digit',
                      })
                    : null;

                window.Alpine?.store('toast')?.show(data.message || 'Checked in!', 'success');
            } catch {
                this.error = 'You appear to be offline. Your check-in was not sent.';
            } finally {
                this.busy = false;
            }
        },
    };
}
