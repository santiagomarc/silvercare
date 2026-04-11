/**
 * Alpine.data('checklistPageItem') — Single-item AJAX checklist toggle
 * for the dedicated /my-checklists page (H6 FIX).
 *
 * Previously, the page used a <form method="POST"> causing a full page reload
 * on every toggle. This component sends AJAX requests matching the dashboard
 * checklistTracker pattern — no page reload required.
 *
 * Props:
 *   checklistId: int     — the checklist item's database ID
 *   isCompleted: boolean — initial completion state from server render
 */
import Alpine from 'alpinejs';
import { sendJsonRequest } from '../utils/offline-queue.js';

export default function checklistPageItem(checklistId, isCompleted = false) {
    return {
        completed:  Boolean(isCompleted),
        processing: false,

        async toggle() {
            // Processing guard — prevents double-click race conditions
            if (this.processing) return;
            this.processing = true;

            const toast = Alpine.store('toast');

            // Optimistic UI update
            const prev = this.completed;
            this.completed = !prev;

            try {
                const result = await sendJsonRequest(`/my-checklists/${checklistId}/toggle`, {
                    method: 'POST',
                    body: {},
                });

                if (result.queued) {
                    toast?.info('Saved offline. Changes will sync automatically.');
                    return;
                }

                if (!result.ok) throw new Error(result.data?.message || 'Failed to update');

                const data = result.data || {};
                // Reconcile with server response (server is authoritative)
                this.completed = Boolean(data.is_completed);

                if (data.is_completed) {
                    toast?.success('Task completed! ✅');
                } else {
                    toast?.info('Task marked incomplete');
                }

            } catch (err) {
                // Rollback optimistic update on failure
                this.completed = prev;
                console.error('Checklist page toggle failed:', err);
                Alpine.store('toast')?.error('Failed to update task. Please try again.');
            } finally {
                this.processing = false;
            }
        },
    };
}
