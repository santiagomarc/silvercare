/**
 * Alpine.data('actionQueue') — One-at-a-time queue controller for Today tab.
 */
import Alpine from 'alpinejs';
import { createConfetti } from './confetti.js';

export default function actionQueue(initialSteps = [], initialTotal = null) {
    const normalized = Array.isArray(initialSteps) ? initialSteps : [];
    const total = Number.isInteger(initialTotal) ? initialTotal : normalized.length;

    return {
        steps: normalized,
        initialTotal: total,
        busy: false,

        init() {
            window.addEventListener('ai-medication-logged', (e) => {
                const d = e.detail || {};
                const id = `med-${d.medication_id}-${String(d.scheduled_time || '').replace(':', '')}`;
                this.markDoneById(id);
            });

            window.addEventListener('action-queue-task-completed', (e) => {
                const taskId = e.detail?.taskId;
                if (taskId) {
                    this.markDoneById(`task-${taskId}`);
                }
            });

            window.addEventListener('mood-logged', () => {
                this.markDoneById('mood-today');
            });
        },

        get current() {
            return this.steps.length > 0 ? this.steps[0] : null;
        },

        get currentStepNumber() {
            if (this.initialTotal === 0) return 0;
            const done = this.initialTotal - this.steps.length;
            return Math.min(done + 1, this.initialTotal);
        },

        get completionProgress() {
            if (this.initialTotal === 0) return 100;
            const done = this.initialTotal - this.steps.length;
            return Math.round((done / this.initialTotal) * 100);
        },

        get nextPreview() {
            return this.steps.slice(1, 4);
        },

        laterCurrent() {
            if (this.steps.length <= 1) return;
            const first = this.steps.shift();
            this.steps.push(first);
        },

        markDoneById(stepId) {
            const idx = this.steps.findIndex((s) => s.id === stepId);
            if (idx === -1) return;
            this.steps.splice(idx, 1);
            createConfetti(this.$el);
        },

        async completeMedication(step) {
            if (this.busy) return;
            this.busy = true;

            try {
                const resp = await fetch(`/my-medications/${step.medication_id}/take`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ time: step.time }),
                });

                const data = await resp.json();
                if (!resp.ok || !data.is_taken) {
                    Alpine.store('toast')?.error(data.message || 'Could not mark medication as taken.');
                    return;
                }

                window.dispatchEvent(new CustomEvent('ai-medication-logged', {
                    detail: {
                        medication_id: step.medication_id,
                        scheduled_time: step.time,
                        taken_late: data.taken_late || false,
                    },
                }));

                Alpine.store('toast')?.success(data.message || 'Medication marked as taken.');
            } catch (error) {
                Alpine.store('toast')?.error('Failed to update medication.');
            } finally {
                this.busy = false;
            }
        },

        async completeTask(step) {
            if (this.busy) return;
            this.busy = true;

            try {
                const resp = await fetch(`/my-checklists/${step.task_id}/toggle`, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const data = await resp.json();
                if (!resp.ok || !data.is_completed) {
                    Alpine.store('toast')?.error(data.message || 'Could not complete task.');
                    return;
                }

                window.dispatchEvent(new CustomEvent('action-queue-task-completed', {
                    detail: { taskId: step.task_id },
                }));

                Alpine.store('toast')?.success(data.message || 'Task completed!');
            } catch (error) {
                Alpine.store('toast')?.error('Failed to update task.');
            } finally {
                this.busy = false;
            }
        },

        openMood() {
            window.dispatchEvent(new CustomEvent('action-queue-open-details'));
            Alpine.store('toast')?.info('Use the mood slider below, then save to complete this step.');
        },
    };
}
