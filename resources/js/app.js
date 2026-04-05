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
import actionQueue        from './components/action-queue.js';

// ── Register global store ────────────────────────────────────────
Alpine.store('toast', toastStore);

// ── Register Alpine.data() components ────────────────────────────
Alpine.data('moodTracker',       (initialMood)    => moodTracker(initialMood));
Alpine.data('checklistTracker',  (done, total)    => checklistTracker(done, total));
Alpine.data('medicationTracker', (taken, total)   => medicationTracker(taken, total));
Alpine.data('gardenWellness',    (c, m, v)        => gardenWellness(c, m, v));
Alpine.data('dashboardTabs',     (tab)            => dashboardTabs(tab));
Alpine.data('googleFitSync',     ()               => googleFitSync());
Alpine.data('heroAction',        (opts)           => heroAction(opts));
Alpine.data('actionQueue',       (steps, total)   => actionQueue(steps, total));

window.Alpine = Alpine;
Alpine.start();
