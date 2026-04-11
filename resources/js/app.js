import './bootstrap';

import Alpine from 'alpinejs';

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
import { initOfflineQueue } from './utils/offline-queue.js';

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

window.Alpine = Alpine;
Alpine.start();
