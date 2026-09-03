import './bootstrap';

import Alpine from 'alpinejs';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

import TomSelect from 'tom-select';
import 'tom-select/dist/css/tom-select.default.css';
import Chart from 'chart.js/auto';
import './profile-photo-cropper.js';


// ── Alpine Components (extracted from inline scripts) ────────────
import toastStore         from './components/toast.js';
import moodTracker        from './components/mood-tracker.js';
import checklistTracker   from './components/checklist-tracker.js';
import medicationTracker  from './components/medication-tracker.js';
import gardenWellness     from './components/garden-wellness.js';
import dashboardTabs      from './components/dashboard-tabs.js';
import googleFitSync      from './components/google-fit-sync.js';
import heroAction         from './components/hero-action.js';
import checklistPageItem  from './components/checklist-page-item.js';
import dailyCheckin       from './components/daily-checkin.js';
import thresholdEditor    from './components/threshold-editor.js';
import { initOfflineQueue } from './utils/offline-queue.js';
import { installDialogHelpers } from './utils/dialogs.js';
import pushToggle, { syncExistingSubscription } from './utils/push-notifications.js';
import { initRealtime } from './utils/realtime.js';
import highContrastToggle, { initHighContrast } from './utils/high-contrast.js';
import displayControls    from './components/display-controls.js';
import dualView           from './components/dual-view.js';
import { initScrollReveal } from './utils/scroll-reveal.js';

// ── Theme bootstrap (5H: Dark Mode Toggle) ──────────────────────
const preferredDark = window.matchMedia?.('(prefers-color-scheme: dark)').matches;
const savedTheme = localStorage.getItem('silvercare-theme');
const useDark = savedTheme === 'dark' || (!savedTheme && preferredDark);

if (useDark) {
	document.documentElement.classList.add('dark');
}

window.applySilverCareTheme = function applySilverCareTheme(theme) {
	const normalized = theme === 'dark' ? 'dark' : 'light';
	localStorage.setItem('silvercare-theme', normalized);
	document.documentElement.classList.toggle('dark', normalized === 'dark');
	return normalized === 'dark';
};

window.toggleSilverCareTheme = function toggleSilverCareTheme() {
	const willUseDark = !document.documentElement.classList.contains('dark');
	return window.applySilverCareTheme(willUseDark ? 'dark' : 'light');
};

// ── PWA bootstrap (5E: Offline support) ─────────────────────────
if ('serviceWorker' in navigator) {
	window.addEventListener('load', () => {
		navigator.serviceWorker.register('/sw.js').catch((error) => {
			console.warn('Service worker registration failed:', error);
		});
	});
}

initOfflineQueue();
installDialogHelpers();

// Browser endpoints rotate; re-register an already-granted subscription so a
// caregiver never silently stops receiving urgent alerts (H8).
syncExistingSubscription();

// C3: subscribe to the Reverb channel for this profile. Four broadcast events
// have existed since sprint 5 and reached no browser until now.
initRealtime();

// M7: apply the stored (or OS-preferred) contrast setting before first paint.
initHighContrast();

// ── Register global store ────────────────────────────────────────
Alpine.store('toast', toastStore);

// ── Register Alpine.data() components ────────────────────────────
Alpine.data('moodTracker',        (initialMood)         => moodTracker(initialMood));
Alpine.data('checklistTracker',   (done, total)         => checklistTracker(done, total));
Alpine.data('checklistPageItem',  (id, isCompleted)     => checklistPageItem(id, isCompleted));
Alpine.data('medicationTracker',  (taken, total)        => medicationTracker(taken, total));
// H7 FIX: gardenWellness now takes 4 args (checklists, meds, vitals, meta)
Alpine.data('gardenWellness',     (c, m, v, meta)       => gardenWellness(c, m, v, meta));
Alpine.data('dashboardTabs',      (tab)                 => dashboardTabs(tab));
Alpine.data('googleFitSync',      ()                    => googleFitSync());
Alpine.data('heroAction',         (opts)                => heroAction(opts));
Alpine.data('pushToggle',         ()                    => pushToggle());
Alpine.data('highContrastToggle', ()                    => highContrastToggle());
Alpine.data('dailyCheckin',       (opts)                => dailyCheckin(opts));
Alpine.data('thresholdEditor',    (opts)                => thresholdEditor(opts));
Alpine.data('displayControls',    ()                    => displayControls());
Alpine.data('dualView',           ()                    => dualView());

window.Alpine = Alpine;

// Wrap flatpickr assignment in try-catch to fail silently if not available
try {
	if (flatpickr) {
		window.flatpickr = flatpickr;
	}
} catch (error) {
	console.warn('flatpickr is not available on this page:', error);
}

window.TomSelect = TomSelect;
window.Chart = Chart;

// ── Patient Modal Store ──────────────────────────────────────────
Alpine.store('patientModal', {
    removeOpen: false,
    restoreOpen: false,
    removeAction: '',
    restoreAction: '',

    openRemove(action) {
        this.removeAction = action;
        this.removeOpen = true;
    },

    closeRemove() {
        this.removeOpen = false;
        this.removeAction = '';
    },

    openRestore(action) {
        this.restoreAction = action;
        this.restoreOpen = true;
    },

    closeRestore() {
        this.restoreOpen = false;
        this.restoreAction = '';
    },
});

Alpine.start();

// Reveal-on-scroll for any page using the shared `.sc-reveal` class.
initScrollReveal();
