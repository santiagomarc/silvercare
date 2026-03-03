import './bootstrap';

import Alpine from 'alpinejs';

// ── Alpine Components (extracted from inline scripts) ────────────
import toastStore         from './components/toast.js';
import moodTracker        from './components/mood-tracker.js';
import checklistTracker   from './components/checklist-tracker.js';
import medicationTracker  from './components/medication-tracker.js';
import gardenWellness     from './components/garden-wellness.js';
import vitalRecorder      from './components/vital-recorder.js';
import dashboardTabs      from './components/dashboard-tabs.js';
import googleFitSync      from './components/google-fit-sync.js';

// ── Register global store ────────────────────────────────────────
Alpine.store('toast', toastStore);

// ── Register Alpine.data() components ────────────────────────────
Alpine.data('moodTracker',       (initialMood)    => moodTracker(initialMood));
Alpine.data('checklistTracker',  (done, total)    => checklistTracker(done, total));
Alpine.data('medicationTracker', (taken, total)   => medicationTracker(taken, total));
Alpine.data('gardenWellness',    (c, m, v)        => gardenWellness(c, m, v));
Alpine.data('vitalRecorder',     ()               => vitalRecorder());
Alpine.data('dashboardTabs',     (tab)            => dashboardTabs(tab));
Alpine.data('googleFitSync',     ()               => googleFitSync());

window.Alpine = Alpine;
Alpine.start();
