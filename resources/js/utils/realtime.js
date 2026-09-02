/**
 * Realtime alert delivery over Reverb (C3).
 *
 * Four ShouldBroadcastNow events have existed since sprint 5 and reached
 * nobody: `resources/js/bootstrap.js` had `//import './echo';` commented out,
 * so `window.Echo` was never constructed, and the intended subscriber lived in
 * `public/js/caregiver-realtime.js`, which no template loaded. The server-side
 * tests passed because `Event::assertDispatched()` proves the event was raised,
 * not that a browser received it.
 *
 * This module ships inside the Vite bundle so it cannot become orphaned again,
 * and it is a no-op when Echo is unavailable (Reverb not running, no profile on
 * the page) rather than throwing.
 */

function toast(message, type = 'info') {
    if (typeof window.scToast === 'function') {
        window.scToast(message, type);
        return;
    }

    window.Alpine?.store('toast')?.show(message, type);
}

function severityType(severity) {
    if (severity === 'emergency' || severity === 'critical') return 'error';
    if (severity === 'warning') return 'warning';
    return 'info';
}

/**
 * Caregiver channel: alerts, dose confirmations, check-ins, status changes.
 */
function subscribeCaregiver(profileId) {
    const channel = window.Echo.private(`caregiver.${profileId}`);

    channel.listen('.critical.alert.fired', (data) => {
        toast(`${data.title ?? 'New alert'} — ${data.patient_name ?? 'your patient'}`, severityType(data.severity));

        // Ask the page to refresh its alert list rather than hand-building a
        // card here: the Blade template owns that markup, and duplicating it in
        // JS is how the two drift apart.
        window.dispatchEvent(new CustomEvent('realtime-alert-fired', { detail: data }));
    });

    channel.listen('.dose.confirmed', (data) => {
        window.dispatchEvent(new CustomEvent('realtime-dose-confirmed', { detail: data }));
    });

    channel.listen('.checkin.received', (data) => {
        const needsHelp = data.status === 'need_help';
        toast(
            needsHelp
                ? `${data.patient_name ?? 'Your patient'} checked in and asked for help.`
                : `${data.patient_name ?? 'Your patient'} checked in — doing okay.`,
            needsHelp ? 'warning' : 'success'
        );
        window.dispatchEvent(new CustomEvent('realtime-checkin-received', { detail: data }));
    });

    channel.listen('.alert.status.updated', (data) => {
        window.dispatchEvent(new CustomEvent('realtime-alert-status', { detail: data }));

        if (data.state === 'resolved') {
            document.getElementById(`alert-card-${data.alert_id}`)?.remove();
        }
    });

    return channel;
}

/**
 * Elderly channel: the senior's own dose state, so a dose confirmed on another
 * device (or by the AI assistant) updates this screen without a reload.
 */
function subscribeElderly(profileId) {
    const channel = window.Echo.private(`elderly.${profileId}`);

    channel.listen('.dose.confirmed', (data) => {
        window.dispatchEvent(new CustomEvent('ai-medication-logged', {
            detail: {
                medication_id: data.medication_id,
                scheduled_time: data.scheduled_time,
                taken_late: data.taken_late,
                source: 'realtime',
            },
        }));
    });

    return channel;
}

export function initRealtime() {
    if (typeof window === 'undefined' || !window.Echo) {
        return null;
    }

    const meta = document.querySelector('meta[name="sc-profile"]');
    if (!meta) {
        return null;
    }

    const profileId = Number.parseInt(meta.getAttribute('data-profile-id') ?? '', 10);
    const userType = meta.getAttribute('data-user-type');

    if (!Number.isInteger(profileId) || profileId <= 0) {
        return null;
    }

    try {
        return userType === 'caregiver'
            ? subscribeCaregiver(profileId)
            : subscribeElderly(profileId);
    } catch (error) {
        // Reverb being down must never break the page. In-app and email
        // delivery still reach the caregiver.
        console.warn('Realtime subscription unavailable:', error);
        return null;
    }
}
