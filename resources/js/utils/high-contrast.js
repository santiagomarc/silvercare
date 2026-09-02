/**
 * High contrast mode (M7).
 *
 * The `html.high-contrast` ruleset shipped in an earlier sprint with no way to
 * turn it on — no toggle, no persistence, nothing that ever applied the class.
 * It was unreachable CSS advertised in a commit message as an accessibility
 * feature.
 *
 * This applies it, remembers the choice per device, and respects the operating
 * system's own `prefers-contrast: more` setting as the default so a senior who
 * has already asked for higher contrast system-wide gets it without hunting for
 * a switch.
 */

const STORAGE_KEY = 'silvercare-high-contrast';

function systemPrefersMoreContrast() {
    return window.matchMedia?.('(prefers-contrast: more)').matches ?? false;
}

function storedPreference() {
    try {
        return localStorage.getItem(STORAGE_KEY);
    } catch {
        // Private browsing or blocked site data — fall back to the OS setting.
        return null;
    }
}

function persist(enabled) {
    try {
        localStorage.setItem(STORAGE_KEY, enabled ? 'on' : 'off');
    } catch {
        // Not being able to remember the choice must not stop it applying now.
    }
}

export function isHighContrastEnabled() {
    return document.documentElement.classList.contains('high-contrast');
}

export function applyHighContrast(enabled) {
    document.documentElement.classList.toggle('high-contrast', enabled);
    persist(enabled);
    return enabled;
}

/**
 * Resolve the initial state: an explicit choice always wins over the OS hint.
 */
export function initHighContrast() {
    if (typeof document === 'undefined') return;

    const stored = storedPreference();
    const enabled = stored === null ? systemPrefersMoreContrast() : stored === 'on';

    document.documentElement.classList.toggle('high-contrast', enabled);

    // Follow the OS setting only while the user has expressed no preference.
    window.matchMedia?.('(prefers-contrast: more)').addEventListener?.('change', (event) => {
        if (storedPreference() === null) {
            document.documentElement.classList.toggle('high-contrast', event.matches);
        }
    });
}

/**
 * Alpine component for the accessibility toggle.
 */
export default function highContrastToggle() {
    return {
        enabled: false,

        init() {
            this.enabled = isHighContrastEnabled();
        },

        toggle() {
            this.enabled = applyHighContrast(!this.enabled);

            window.Alpine?.store('toast')?.show(
                this.enabled ? 'High contrast turned on.' : 'High contrast turned off.',
                'success'
            );
        },
    };
}
