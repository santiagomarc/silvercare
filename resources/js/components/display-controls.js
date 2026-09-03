/**
 * Display & accessibility menu (header).
 *
 * Text scale multiplies the 18px root set in app.css, so the whole rem-based
 * layout grows with the copy rather than only the copy. Dark mode reuses the
 * app-wide helper; high contrast writes the same storage key that
 * utils/high-contrast.js reads, so the two stay in step.
 */
const SCALE_KEY = 'silvercare-text-scale';

export const TEXT_SCALES = [
    { value: 1,    label: 'A', preview: '1rem',    name: 'Default', aria: 'Default text size' },
    { value: 1.12, label: 'A', preview: '1.25rem', name: 'Large',   aria: 'Large text size' },
    { value: 1.25, label: 'A', preview: '1.5rem',  name: 'Largest', aria: 'Largest text size' },
];

export default function displayControls() {
    return {
        open: false,
        dark: false,
        contrast: false,
        scale: 1,
        scales: TEXT_SCALES,

        init() {
            const root = document.documentElement;
            this.dark = root.classList.contains('dark');
            this.contrast = root.classList.contains('high-contrast');

            let stored = NaN;
            try { stored = parseFloat(localStorage.getItem(SCALE_KEY)); } catch (e) { /* blocked storage */ }
            this.scale = (stored >= 1 && stored <= 1.35) ? stored : 1;
        },

        setScale(value) {
            this.scale = value;
            document.documentElement.style.fontSize = (18 * value) + 'px';
            // Enlarged labels no longer fit the desktop bar; hand it the menu.
            document.documentElement.classList.toggle('sc-text-scaled', value > 1);
            try { localStorage.setItem(SCALE_KEY, String(value)); } catch (e) { /* blocked storage */ }
        },

        toggleDark() {
            this.dark = window.toggleSilverCareTheme
                ? window.toggleSilverCareTheme()
                : document.documentElement.classList.toggle('dark');
        },

        toggleContrast() {
            this.contrast = document.documentElement.classList.toggle('high-contrast');
            try {
                localStorage.setItem('silvercare-high-contrast', this.contrast ? 'on' : 'off');
            } catch (e) { /* blocked storage */ }
        },
    };
}
