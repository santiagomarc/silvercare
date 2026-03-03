/**
 * Alpine.data('checklistTracker') — Checklist toggle with optimistic UI.
 *
 * Props:
 *   completedCount: int — initial count of completed items
 *   totalCount: int     — total number of checklist items
 */
import Alpine from 'alpinejs';
import { createConfetti } from './confetti.js';

export default function checklistTracker(completedCount = 0, totalCount = 0) {
    return {
        completed: completedCount,
        total: totalCount,

        get progress() {
            return this.total > 0 ? Math.round((this.completed / this.total) * 100) : 0;
        },

        async toggle(checklistId, el) {
            const item = el.closest('.checklist-item');
            if (!item) return;

            const btn = el;
            const isCompleted = item.dataset.completed === 'true';
            btn.disabled = true;

            try {
                const resp = await fetch(`/my-checklists/${checklistId}/toggle`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
                const data = await resp.json();

                if (data.is_completed) {
                    item.dataset.completed = 'true';
                    this.completed++;
                    createConfetti(btn);
                    Alpine.store('toast')?.success('Task completed!');
                } else {
                    item.dataset.completed = 'false';
                    this.completed--;
                    Alpine.store('toast')?.info('Task marked incomplete');
                }

                // Dispatch a custom event so other components (garden, hero) can react
                window.dispatchEvent(new CustomEvent('progress-updated', {
                    detail: { checklists: this.completed, checklistTotal: this.total }
                }));

            } catch (err) {
                console.error('Checklist toggle failed:', err);
                Alpine.store('toast')?.error('Failed to update task');
            } finally {
                btn.disabled = false;
            }
        },
    };
}
