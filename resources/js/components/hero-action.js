/**
 * Alpine.data('heroAction') — Unified hero/queue card interactions.
 */
import Alpine from 'alpinejs';
import { createConfetti } from './confetti.js';
import { sendJsonRequest } from '../utils/offline-queue.js';

export default function heroAction({ progress = 0, steps = [], initialTotal = null } = {}) {
    const normalized = Array.isArray(steps) ? steps : [];
    const total = Number.isInteger(initialTotal) ? initialTotal : normalized.length;

    return {
        currentProgress: progress,
        steps: normalized,
        initialTotal: total,
        busy: false,

        init() {
            window.addEventListener('progress-updated', (e) => {
                this._recalculateProgress(e.detail);
            });

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
            return this.steps.length > 0 ? this.steps[0] : {
                id: 'done',
                type: 'done',
                title: 'All caught up! Great job! 🎉',
                subtitle: "You've completed all your tasks, medications, and vitals for today.",
                tag: 'Done',
                icon: '🎉',
                gradient: 'from-emerald-600 to-green-700',
            };
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

        get currentTitle() {
            return this.current.title || '';
        },

        get currentSubtitle() {
            return this.current.subtitle || '';
        },

        get currentTag() {
            return this.current.tag || 'Action';
        },

        get currentIcon() {
            return this.current.icon || '✅';
        },

        get currentGradient() {
            return this.current.gradient || 'from-emerald-600 to-green-700';
        },

        get currentTypeLabel() {
            const type = this.current.type || 'done';
            if (type === 'medication') return 'Medication';
            if (type === 'task') return 'Task';
            if (type === 'vital') return 'Vital';
            if (type === 'mood') return 'Mood';
            return 'Done';
        },

        get isMedication() {
            return this.current.type === 'medication';
        },

        get isTask() {
            return this.current.type === 'task';
        },

        get isVital() {
            return this.current.type === 'vital';
        },

        get isMood() {
            return this.current.type === 'mood';
        },

        get isDone() {
            return this.current.type === 'done';
        },

        get currentRoute() {
            return this.current.route || '';
        },

        get canDefer() {
            return this.steps.length > 1 && !this.busy && this.current.type !== 'done';
        },

        laterCurrent() {
            if (this.steps.length <= 1 || this.current.type === 'done') return;
            const first = this.steps.shift();
            this.steps.push(first);
        },

        markDoneById(stepId) {
            const idx = this.steps.findIndex((s) => s.id === stepId);
            if (idx === -1) return;
            this.steps.splice(idx, 1);
            createConfetti(this.$el);
        },

        async completeMedication() {
            if (!this.isMedication || this.busy) return;
            this.busy = true;

            try {
                const result = await sendJsonRequest(`/my-medications/${this.current.medication_id}/take`, {
                    method: 'POST',
                    body: { time: this.current.time },
                });

                if (result.queued) {
                    window.dispatchEvent(new CustomEvent('ai-medication-logged', {
                        detail: {
                            medication_id: this.current.medication_id,
                            scheduled_time: this.current.time,
                            taken_late: false,
                        },
                    }));
                    Alpine.store('toast')?.info('Saved offline. Medication will sync automatically.');
                    return;
                }

                const data = result.data || {};
                if (!result.ok || !data.is_taken) {
                    Alpine.store('toast')?.error(data.message || 'Could not mark medication as taken.');
                    return;
                }

                window.dispatchEvent(new CustomEvent('ai-medication-logged', {
                    detail: {
                        medication_id: this.current.medication_id,
                        scheduled_time: this.current.time,
                        taken_late: data.taken_late || false,
                    },
                }));

                Alpine.store('toast')?.success(data.message || 'Medication marked as taken.');
            } catch (err) {
                Alpine.store('toast')?.error('Failed to update medication.');
            } finally {
                this.busy = false;
            }
        },

        async completeTask() {
            if (!this.isTask || this.busy) return;
            this.busy = true;

            try {
                const result = await sendJsonRequest(`/my-checklists/${this.current.task_id}/toggle`, {
                    method: 'POST',
                    body: {},
                });

                if (result.queued) {
                    window.dispatchEvent(new CustomEvent('action-queue-task-completed', {
                        detail: { taskId: this.current.task_id },
                    }));
                    Alpine.store('toast')?.info('Saved offline. Task completion will sync automatically.');
                    return;
                }

                const data = result.data || {};
                if (!result.ok || !data.is_completed) {
                    Alpine.store('toast')?.error(data.message || 'Could not complete task.');
                    return;
                }

                window.dispatchEvent(new CustomEvent('action-queue-task-completed', {
                    detail: { taskId: this.current.task_id },
                }));

                Alpine.store('toast')?.success(data.message || 'Task completed!');
            } catch (error) {
                Alpine.store('toast')?.error('Failed to update task.');
            } finally {
                this.busy = false;
            }
        },

        openMood() {
            const mood = document.getElementById('today-mood-tracker');
            if (mood) {
                mood.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            Alpine.store('toast')?.info('Use the mood slider below, then save to complete this step.');
        },

        _recalculateProgress(detail) {
            const medTaken = detail.medications ?? 0;
            const medTotal = detail.medicationTotal ?? 1;
            const tasksDone = detail.checklists ?? null;
            const tasksTotal = detail.checklistTotal ?? null;

            if (tasksDone !== null && tasksTotal !== null && tasksTotal > 0) {
                const medPct = medTotal > 0 ? (medTaken / medTotal) * 100 : 100;
                const taskPct = (tasksDone / tasksTotal) * 100;
                this.currentProgress = Math.round((medPct + taskPct) / 2);
            } else {
                this.currentProgress = medTotal > 0 ? Math.round((medTaken / medTotal) * 100) : this.currentProgress;
            }
        },
    };
}
