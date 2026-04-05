/**
 * Alpine.data('heroAction') — Hero card with inline medication actions
 * and real-time progress tracking via custom events.
 */
import { createConfetti } from './confetti.js';

export default function heroAction({ progress = 0, actionType = 'done', medicationId = null, scheduledTime = '' } = {}) {
    return {
        currentProgress: progress,
        actionType,
        medicationId,
        scheduledTime,
        marking: false,
        taken: false,
        heroHeadline: null,
        heroSubtext: null,

        init() {
            // Set initial text from DOM (server-rendered)
            this.heroHeadline = this.$el.querySelector('[x-text="heroHeadline"]')?.textContent?.trim() || '';
            this.heroSubtext = this.$el.querySelector('[x-text="heroSubtext"]')?.textContent?.trim() || '';

            // Listen for real-time progress updates from medication-tracker / checklist-tracker
            window.addEventListener('progress-updated', (e) => {
                this._recalculateProgress(e.detail);
            });
        },

        async takeMedication() {
            if (!this.medicationId || !this.scheduledTime || this.marking) return;

            this.marking = true;

            try {
                const resp = await fetch(`/my-medications/${this.medicationId}/take`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ time: this.scheduledTime }),
                });

                if (!resp.ok) {
                    const err = await resp.json();
                    const toast = Alpine.store('toast');
                    toast?.error(err.message || 'Failed to log medication');
                    return;
                }

                const data = await resp.json();

                if (data.is_taken) {
                    this.taken = true;
                    this.heroHeadline = '✅ ' + this.heroHeadline
                        .replace('Time to take ', '')
                        .replace('Missed: ', '')
                        .replace('Overdue: ', '') + ' — Done!';
                    this.heroSubtext = 'Great job! Check below for your next action.';

                    // Trigger confetti on the hero card itself
                    createConfetti(this.$el);

                    // Notify the medication list + garden to update
                    window.dispatchEvent(new CustomEvent('ai-medication-logged', {
                        detail: {
                            medication_id: this.medicationId,
                            scheduled_time: this.scheduledTime,
                            taken_late: data.taken_late || false,
                        },
                    }));

                    const toast = Alpine.store('toast');
                    toast?.success(data.message || 'Medication taken!');
                }
            } catch (err) {
                console.error('Hero take medication failed:', err);
                const toast = Alpine.store('toast');
                toast?.error('Something went wrong. Try the medication list below.');
            } finally {
                this.marking = false;
            }
        },

        skipMedication() {
            // Dismiss the hero card for this action — reload page to show next priority
            this.heroHeadline = 'Skipped — checking next action...';
            this.heroSubtext = '';
            setTimeout(() => window.location.reload(), 600);
        },

        _recalculateProgress(detail) {
            // Approximate overall progress from the event data
            // This mirrors the server-side dailyGoalsProgress calculation
            const medTaken = detail.medications ?? 0;
            const medTotal = detail.medicationTotal ?? 1;
            const tasksDone = detail.checklists ?? null;
            const tasksTotal = detail.checklistTotal ?? null;

            // If we have both, calculate weighted average (same as server logic)
            if (tasksDone !== null && tasksTotal !== null && tasksTotal > 0) {
                const medPct = medTotal > 0 ? (medTaken / medTotal) * 100 : 100;
                const taskPct = (tasksDone / tasksTotal) * 100;
                this.currentProgress = Math.round((medPct + taskPct) / 2);
            } else {
                // Only med data available
                this.currentProgress = medTotal > 0 ? Math.round((medTaken / medTotal) * 100) : this.currentProgress;
            }
        },
    };
}
