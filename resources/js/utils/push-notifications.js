/**
 * Browser push notifications (H8).
 *
 * Clinical alerts reach a caregiver through three channels. In-app only works
 * while they are looking at the dashboard; email is often muted. Push is the
 * one that reaches a locked phone, which for a critical vital or an SOS is the
 * whole point.
 *
 * The VAPID public key comes from /api/push/config rather than a build-time
 * variable, so rotating keys does not require a rebuild and a deployment
 * without keys configured degrades to "push unavailable" instead of erroring.
 */

const CONFIG_URL = '/api/push/config';
const SUBSCRIBE_URL = '/api/push/subscribe';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

/**
 * VAPID keys are base64url; PushManager wants raw bytes.
 */
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = window.atob(base64);
    const output = new Uint8Array(raw.length);

    for (let i = 0; i < raw.length; i += 1) {
        output[i] = raw.charCodeAt(i);
    }

    return output;
}

export function isPushSupported() {
    return (
        'serviceWorker' in navigator &&
        'PushManager' in window &&
        'Notification' in window
    );
}

export function pushPermission() {
    return isPushSupported() ? Notification.permission : 'unsupported';
}

async function fetchPushConfig() {
    try {
        const response = await fetch(CONFIG_URL, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            return { enabled: false, public_key: null };
        }

        return await response.json();
    } catch {
        return { enabled: false, public_key: null };
    }
}

async function sendSubscriptionToServer(subscription) {
    const response = await fetch(SUBSCRIBE_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify(subscription.toJSON()),
    });

    if (!response.ok) {
        throw new Error(`Server rejected the subscription (${response.status})`);
    }

    return response.json();
}

/**
 * Ask for permission and subscribe this browser.
 *
 * Returns { ok, reason } so the caller can show a specific message rather than
 * a generic failure. Never throws for an expected outcome like a denied prompt.
 */
export async function enablePushNotifications() {
    if (!isPushSupported()) {
        return { ok: false, reason: 'unsupported', message: 'This browser cannot show notifications.' };
    }

    const config = await fetchPushConfig();

    if (!config.enabled || !config.public_key) {
        return {
            ok: false,
            reason: 'not_configured',
            message: 'Push notifications are not set up on this server yet.',
        };
    }

    // Must be called from a user gesture, or browsers reject the prompt.
    const permission = await Notification.requestPermission();

    if (permission !== 'granted') {
        return {
            ok: false,
            reason: permission,
            message:
                permission === 'denied'
                    ? 'Notifications are blocked. Allow them in your browser settings to receive urgent alerts.'
                    : 'Notification permission was not granted.',
        };
    }

    try {
        const registration = await navigator.serviceWorker.ready;

        // Reuse an existing subscription when there is one; re-subscribing
        // would invalidate the endpoint the server already has.
        const subscription =
            (await registration.pushManager.getSubscription()) ??
            (await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(config.public_key),
            }));

        await sendSubscriptionToServer(subscription);

        return { ok: true, message: 'Urgent alerts will now reach you on this device.' };
    } catch (error) {
        return {
            ok: false,
            reason: 'subscribe_failed',
            message: 'Could not enable notifications on this device.',
            error,
        };
    }
}

export async function disablePushNotifications() {
    if (!isPushSupported()) {
        return { ok: false, reason: 'unsupported' };
    }

    try {
        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.getSubscription();

        if (!subscription) {
            return { ok: true, message: 'Notifications were already off for this device.' };
        }

        // Tell the server first: if unsubscribing locally succeeded but the
        // server kept the row, it would push to a dead endpoint forever.
        await fetch(SUBSCRIBE_URL, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ endpoint: subscription.endpoint }),
        });

        await subscription.unsubscribe();

        return { ok: true, message: 'Notifications turned off for this device.' };
    } catch (error) {
        return { ok: false, reason: 'unsubscribe_failed', error };
    }
}

/**
 * Re-register an already-granted subscription on page load.
 *
 * Browsers rotate push endpoints periodically. Without this the server would
 * keep pushing to a stale endpoint and the caregiver would silently stop
 * receiving alerts — the exact class of failure this whole effort is about.
 */
export async function syncExistingSubscription() {
    if (!isPushSupported() || Notification.permission !== 'granted') {
        return;
    }

    try {
        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.getSubscription();

        if (subscription) {
            await sendSubscriptionToServer(subscription);
        }
    } catch {
        // Best effort — a failed re-sync must never break page load.
    }
}

/**
 * Alpine component for the caregiver notification toggle.
 */
export default function pushToggle() {
    return {
        supported: isPushSupported(),
        permission: pushPermission(),
        busy: false,
        message: '',

        get enabled() {
            return this.permission === 'granted';
        },

        get blocked() {
            return this.permission === 'denied';
        },

        // Exposed as getters rather than inline expressions so the templates
        // stay free of `&&`, which HTML attribute parsers mishandle.
        get canEnable() {
            return !this.enabled && !this.blocked;
        },

        get showTurnOff() {
            return !this.busy && this.enabled;
        },

        get showTurnOn() {
            return !this.busy && !this.enabled;
        },

        async toggle() {
            if (this.busy) return;

            this.busy = true;
            this.message = '';

            const result = this.enabled
                ? await disablePushNotifications()
                : await enablePushNotifications();

            this.permission = pushPermission();
            this.message = result.message ?? '';
            this.busy = false;

            if (window.Alpine?.store('toast')) {
                window.Alpine.store('toast').show(
                    this.message || (result.ok ? 'Updated.' : 'Could not update notifications.'),
                    result.ok ? 'success' : 'error'
                );
            }
        },
    };
}
