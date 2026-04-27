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
                        title: 'text-2xl font-extrabold text-slate-800'
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

                // Offline Queue Handling
                if (result.queued) {
                    if (!isTaken) {
                        entry.dataset.taken = 'true';
                        entry.dataset.canTake = 'false';
                        entry.dataset.canUndo = 'true'; // Optimistically allow undo
                        
                        this.taken++;
                        this._updateEntryAppearance(entry, 'taken');
                        createConfetti(entry);
                    } else {
                        entry.dataset.taken = 'false';
                        entry.dataset.canTake = 'true'; // Re-enable taking
                        entry.dataset.canUndo = 'false';
                        
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

        _updateEntryAppearance(entry, status) {
            entry.classList.remove(
                'dose-taken', 'dose-taken-late', 'dose-missed', 'dose-active', 'dose-upcoming', 'opacity-75'
            );
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