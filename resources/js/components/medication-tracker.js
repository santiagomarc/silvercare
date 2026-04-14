/**
 * Alpine.data('medicationTracker') — Medication dose toggle with progress tracking.
 *
 * Props:
 *   takenDoses: int  — initial count of taken doses
 *   totalDoses: int  — total number of doses for today
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
            // C1 CLIENT FIX: Processing guard prevents a second click from
            // firing while the first request is still in-flight.
            if (entry.dataset.processing === 'true') return;
            entry.dataset.processing = 'true';

            const medicationId = entry.dataset.medicationId;
            const time = entry.dataset.time;
            const isTaken = entry.dataset.taken === 'true';
            const canTake = entry.dataset.canTake === 'true';
            const canUndo = entry.dataset.canUndo === 'true';
            const toast = Alpine.store('toast');

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

            entry.style.opacity = '0.7';
            const endpoint = isTaken
                ? `/my-medications/${medicationId}/undo`
                : `/my-medications/${medicationId}/take`;

            try {
                const result = await sendJsonRequest(endpoint, {
                    method: 'POST',
                    body: { time },
                });

                if (result.queued) {
                    if (!isTaken) {
                        entry.dataset.taken = 'true';
                        this.taken++;
                        this._updateEntryAppearance(entry, 'taken');
                        createConfetti(entry);
                    } else {
                        entry.dataset.taken = 'false';
                        this.taken--;
                        this._updateEntryAppearance(entry, this._computeStatus(time));
                    }

                    toast?.info('Saved offline. Changes will sync automatically.');
                    window.dispatchEvent(new CustomEvent('progress-updated', {
                        detail: { medications: this.taken, medicationTotal: this.total }
                    }));
                    return;
                }

                if (!result.ok) {
                    throw new Error(result.data?.message || 'Failed to update');
                }

                const data = result.data || {};

                if (data.is_taken) {
                    entry.dataset.taken = 'true';
                    entry.dataset.canTake = 'false';
                    entry.dataset.canUndo = data.taken_late ? 'false' : 'true';
                    this.taken++;
                    this._updateEntryAppearance(entry, data.taken_late ? 'taken-late' : 'taken');
                    createConfetti(entry);
                    toast?.success(data.message || 'Medication taken!');
                } else {
                    entry.dataset.taken = 'false';
                    entry.dataset.canTake = 'true';
                    entry.dataset.canUndo = 'true';
                    this.taken--;
                    this._updateEntryAppearance(entry, this._computeStatus(time));
                    toast?.info(data.message || 'Medication unmarked');
                }

                // Notify other components
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
         * Compute current status for a dose based on scheduled time.
         */
        _computeStatus(timeStr) {
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

        /**
         * Update DOM classes on a medication entry based on status.
         */
        _updateEntryAppearance(entry, status) {
            // Remove all status classes
            entry.classList.remove(
                'dose-taken', 'dose-taken-late', 'dose-missed', 'dose-active', 'dose-upcoming', 'opacity-75'
            );
            // Add correct one
            entry.classList.add(`dose-${status}`);

            const iconDiv = entry.querySelector('[data-icon]');
            const statusSpan = entry.querySelector('[data-status-label]');
            const title = entry.querySelector('[data-med-name]');

            const statusMap = {
                'taken':      { icon: '✓', text: 'Taken',      css: 'text-green-700'  },
                'taken-late': { icon: '✓', text: 'Taken Late', css: 'text-orange-600' },
                'missed':     { icon: '!', text: 'Missed',     css: 'text-red-600'    },
                'active':     { icon: '●', text: 'Take Now',   css: 'text-amber-600'  },
                'upcoming':   { icon: '○', text: 'Upcoming',   css: 'text-gray-400'   },
            };

            const s = statusMap[status] || statusMap.upcoming;
            if (iconDiv) iconDiv.textContent = s.icon;
            if (statusSpan) {
                statusSpan.className = `badge text-xs font-bold ${s.css}`;
                statusSpan.textContent = s.text;
            }
            if (title) {
                if (status === 'taken' || status === 'taken-late') {
                    title.classList.add('line-through', 'opacity-75');
                } else {
                    title.classList.remove('line-through', 'opacity-75');
                }
            }
        },

        _applyAiMedicationLog(action) {
            const medicationId = String(action.medication_id || '');
            const scheduledTime = action.scheduled_time;

            if (!medicationId || !scheduledTime) {
                return;
            }

            const selector = `.medication-entry[data-medication-id="${CSS.escape(medicationId)}"][data-time="${CSS.escape(scheduledTime)}"]`;
            const entry = document.querySelector(selector);

            if (!entry) {
                return;
            }

            if (entry.dataset.taken === 'true') {
                return;
            }

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
