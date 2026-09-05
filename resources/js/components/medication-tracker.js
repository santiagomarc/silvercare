/**
 * Alpine.data('medicationTracker') — Medication dose toggle with progress tracking.
 */
import Alpine from 'alpinejs';
import { createConfetti } from './confetti.js';
import { sendJsonRequest } from '../utils/offline-queue.js';

export default function medicationTracker(takenDoses = 0, totalDoses = 0) {
    return {
        taken: takenDoses,
        total: totalDoses,
        expanded: takenDoses < totalDoses,

        init() {
            window.addEventListener('ai-medication-logged', (event) => {
                this._applyAiMedicationLog(event.detail || {});
            });

            // C2: a queued dose only becomes a checkmark once the server has
            // actually confirmed it.
            window.addEventListener('offline-queue-item-synced', (event) => {
                this._resolvePending(event.detail || {}, 'synced');
            });
            window.addEventListener('offline-queue-item-failed', (event) => {
                this._resolvePending(event.detail || {}, 'failed');
            });
            window.addEventListener('offline-queue-item-conflict', (event) => {
                this._resolvePending(event.detail || {}, 'conflict');
            });

            this.$watch('taken', (value) => {
                if (this.total > 0 && value >= this.total) {
                    this.expanded = false;
                }
            });
        },

        get progress() {
            return this.total > 0 ? Math.round((this.taken / this.total) * 100) : 0;
        },

        /**
         * Toggle a single medication dose entry.
         * @param {HTMLElement} entry — the .medication-entry element
         */
        async toggleEntry(entry) {
            if (entry.dataset.processing === 'true') return;
            entry.dataset.processing = 'true';

            const medicationId = entry.dataset.medicationId;
            const time = entry.dataset.time;
            const isTaken = entry.dataset.taken === 'true';
            const canTake = entry.dataset.canTake === 'true';
            const canUndo = entry.dataset.canUndo === 'true';
            const toast = Alpine.store('toast');

            // Validation Check
            if (!canTake && !isTaken) {
                toast?.info('Too early! Wait until the scheduled time window.');
                entry.dataset.processing = 'false';
                return;
            }
            if (isTaken && !canUndo) {
                toast?.info('Cannot unmark — grace period has ended.');
                entry.dataset.processing = 'false';
                return;
            }

            // --- SWEETALERT2 CONFIRMATION FOR UNDOING MEDICATIONS ---
            if (isTaken) {
                const confirmed = await window.Swal.fire({
                    title: 'Unmark Medication?',
                    html: '<p class="text-lg text-slate-600 mt-2">Are you sure you want to unmark this dose? Only do this if you clicked it by mistake.</p>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48', // Tailwind rose-600
                    cancelButtonColor: '#64748b', // Tailwind slate-500
                    confirmButtonText: '<span class="text-lg font-bold px-4 py-2">Yes, unmark it</span>',
                    cancelButtonText: '<span class="text-lg font-bold px-4 py-2">Cancel</span>',
                    reverseButtons: true, // Puts the primary action on the right
                    customClass: {
                        popup: 'rounded-2xl border border-slate-200 shadow-2xl',
                        title: 'sc-dialog-title'
                    }
                });

                if (!confirmed.isConfirmed) {
                    entry.dataset.processing = 'false';
                    return; // Stop execution if they cancel
                }
            }
            // -----------------------------------------------------------

            entry.style.opacity = '0.7';
            const endpoint = isTaken
                ? `/my-medications/${medicationId}/undo`
                : `/my-medications/${medicationId}/take`;

            try {
                const result = await sendJsonRequest(endpoint, {
                    method: 'POST',
                    body: { time },
                });

                // C2: a queued action has NOT reached the server. Show it as
                // waiting to sync — never a checkmark, and never confetti. The
                // senior must not be told a dose was recorded when it wasn't.
                if (result.queued) {
                    entry.dataset.pendingSync = 'true';
                    entry.dataset.pendingIntent = isTaken ? 'undo' : 'take';
                    entry.dataset.idempotencyKey = result.idempotencyKey || '';

                    // The dose is locked while it waits: tapping again would
                    // queue a second, contradictory intent.
                    entry.dataset.canTake = 'false';
                    entry.dataset.canUndo = 'false';

                    this._updateEntryAppearance(entry, 'pending-sync');
                    toast?.info('Saved on your device. It will sync when you are back online.');
                    return;
                }

                if (!result.ok) {
                    throw new Error(result.data?.message || 'Failed to update');
                }

                const data = result.data || {};

                // Online Live Handling
                if (data.is_taken) {
                    entry.dataset.taken = 'true';
                    entry.dataset.canTake = 'false';
                    // Update canUndo dynamically based on whether they took it late
                    entry.dataset.canUndo = data.taken_late ? 'false' : 'true';
                    
                    this.taken++;
                    this._updateEntryAppearance(entry, data.taken_late ? 'taken-late' : 'taken');
                    createConfetti(entry);
                    toast?.success(data.message || 'Medication taken!');
                } else {
                    entry.dataset.taken = 'false';
                    entry.dataset.canTake = 'true'; // Re-enable taking
                    entry.dataset.canUndo = 'false';
                    
                    this.taken--;
                    this._updateEntryAppearance(entry, this._computeStatus(time));
                    toast?.info(data.message || 'Medication unmarked');
                }

                window.dispatchEvent(new CustomEvent('progress-updated', {
                    detail: { medications: this.taken, medicationTotal: this.total }
                }));

            } catch (err) {
                console.error('Medication toggle failed:', err);
                toast?.error(err.message);
            } finally {
                entry.dataset.processing = 'false';
                entry.style.opacity = '';
            }
        },

        /**
         * Settle a dose that was waiting to sync, once the server has ruled on it.
         *
         * synced   -> the intent was applied; show it for real
         * failed   -> permanently rejected; roll back to the real status
         * conflict -> server disagrees; flag for review rather than guessing
         */
        _resolvePending({ idempotencyKey }, outcome) {
            if (!idempotencyKey) return;

            const entry = document.querySelector(
                `.medication-entry[data-idempotency-key="${CSS.escape(idempotencyKey)}"]`
            );
            if (!entry) return;

            const intent = entry.dataset.pendingIntent;
            const time = entry.dataset.time;

            delete entry.dataset.pendingSync;
            delete entry.dataset.pendingIntent;
            delete entry.dataset.idempotencyKey;

            if (outcome === 'synced') {
                if (intent === 'take') {
                    entry.dataset.taken = 'true';
                    entry.dataset.canTake = 'false';
                    entry.dataset.canUndo = 'true';
                    this.taken = Math.min(this.total, this.taken + 1);
                    this._updateEntryAppearance(entry, 'taken');
                    createConfetti(entry);
                } else {
                    entry.dataset.taken = 'false';
                    entry.dataset.canTake = 'true';
                    entry.dataset.canUndo = 'false';
                    this.taken = Math.max(0, this.taken - 1);
                    this._updateEntryAppearance(entry, this._computeStatus(time));
                }
            } else if (outcome === 'conflict') {
                entry.dataset.canTake = 'false';
                entry.dataset.canUndo = 'false';
                this._updateEntryAppearance(entry, 'conflict');
            } else {
                // Rejected: nothing was applied, so restore the real state.
                entry.dataset.canTake = intent === 'take' ? 'true' : 'false';
                entry.dataset.canUndo = 'false';
                this._updateEntryAppearance(entry, this._computeStatus(time));
            }

            window.dispatchEvent(new CustomEvent('progress-updated', {
                detail: { medications: this.taken, medicationTotal: this.total }
            }));
        },

        _computeStatus(timeStr) {
            // C9 FIX: Note that this uses browser-local time. If the server is in
            // a different timezone (e.g. Asia/Manila), the frontend window boundaries
            // might drift from the server's window boundaries. A permanent fix would
            // involve passing the server timezone to the frontend via a <meta> tag.
            const now = new Date();
            const [h, m] = timeStr.split(':').map(Number);
            const sched = new Date();
            sched.setHours(h, m, 0, 0);
            const windowEnd = new Date(sched.getTime() + 60 * 60 * 1000);
            const windowStart = new Date(sched.getTime() - 60 * 60 * 1000);

            if (now > windowEnd) return 'missed';
            if (now >= windowStart && now <= windowEnd) return 'active';
            return 'upcoming';
        },

        _updateEntryAppearance(entry, status) {
            entry.classList.remove(
                'dose-taken', 'dose-taken-late', 'dose-missed', 'dose-active',
                'dose-upcoming', 'dose-pending-sync', 'dose-conflict', 'opacity-75'
            );
            entry.classList.add(`dose-${status}`);

            const iconDiv = entry.querySelector('[data-icon]');
            const statusSpan = entry.querySelector('[data-status-label]');
            const title = entry.querySelector('[data-med-name]');

            // Distinct icon and wording, not just a colour — dose status must be
            // readable without relying on colour alone (WCAG 1.4.1). `icon` is a
            // sprite id from partials/sc-icons.blade.php and `css` a mark tone,
            // so this matches what MedicationPresenter renders server-side.
            const statusMap = {
                'taken':        { icon: 'check', text: 'Taken',           css: 'sc-mark-ok'    },
                'taken-late':   { icon: 'check', text: 'Taken late',      css: 'sc-mark-warn'  },
                'missed':       { icon: 'alert', text: 'Missed',          css: 'sc-mark-alert' },
                'active':       { icon: 'pill',  text: 'Take now',        css: 'sc-mark-alert' },
                'upcoming':     { icon: 'clock', text: 'Upcoming',        css: ''              },
                'pending-sync': { icon: 'undo',  text: 'Waiting to sync', css: 'sc-mark-warn'  },
                'conflict':     { icon: 'alert', text: 'Needs review',    css: 'sc-mark-alert' },
            };

            const s = statusMap[status] || statusMap.upcoming;
            if (iconDiv) {
                iconDiv.innerHTML = `<svg class="sc-i w-6 h-6" aria-hidden="true" focusable="false"><use href="#i-${s.icon}"/></svg>`;
            }
            if (statusSpan) {
                // A dot carries the hue and the word carries the meaning. The
                // filled badge this replaces failed contrast in dark mode
                // (amber-on-amber at 3.32:1) and put a coloured bubble on every
                // dose row, which is the look the redesign exists to remove.
                statusSpan.className = `sc-mark ${s.css}`.trim();
                statusSpan.replaceChildren(
                    document.createElement('i'),
                    document.createTextNode(s.text),
                );
            }
            if (title) {
                if (status === 'taken' || status === 'taken-late') {
                    title.classList.add('line-through', 'sc-task-text-done');
                } else {
                    title.classList.remove('line-through', 'sc-task-text-done');
                }
            }
        },

        _applyAiMedicationLog(action) {
            const medicationId = String(action.medication_id || '');
            const scheduledTime = action.scheduled_time;

            if (!medicationId || !scheduledTime) return;

            const selector = `.medication-entry[data-medication-id="${CSS.escape(medicationId)}"][data-time="${CSS.escape(scheduledTime)}"]`;
            const entry = document.querySelector(selector);

            if (!entry || entry.dataset.taken === 'true') return;

            entry.dataset.taken = 'true';
            entry.dataset.canTake = 'false';
            entry.dataset.canUndo = action.taken_late ? 'false' : 'true';

            this.taken = Math.min(this.total, this.taken + 1);

            this._updateEntryAppearance(entry, action.taken_late ? 'taken-late' : 'taken');
            createConfetti(entry);

            if (action.source !== 'user') {
                const toast = Alpine.store('toast');
                toast?.success('Medication logged by Silvia');
            }

            window.dispatchEvent(new CustomEvent('progress-updated', {
                detail: { medications: this.taken, medicationTotal: this.total }
            }));
        },
    };
}