/**
 * Global toast notification store.
 *
 * Usage in Blade:
 *   Alpine.$store.toast.show('Message', 'success')
 *
 * The <div id="toast-container"> in the layout auto-renders toasts.
 *
 * H1 FIX: Durations increased from 3s/4s to 5s/7s for senior accessibility.
 * H2 FIX: Icons added per type so status is not conveyed by color alone (WCAG 1.4.1).
 */
export default {
    queue: [],
    _id: 0,

    // Icons keyed by type — used in the Blade template via t.icon
    _icons: {
        success: '✅',
        error:   '❌',
        info:    'ℹ️',
        warning: '⚠️',
    },

    show(message, type = 'info', duration = 5000) {
        const id = ++this._id;
        const icon = this._icons[type] ?? 'ℹ️';
        this.queue.push({ id, message, type, icon, visible: false });

        // Trigger enter animation on next tick
        setTimeout(() => {
            const item = this.queue.find(t => t.id === id);
            if (item) item.visible = true;
        }, 10);

        // Auto-dismiss
        setTimeout(() => this.dismiss(id), duration);
    },

    dismiss(id) {
        const item = this.queue.find(t => t.id === id);
        if (item) item.visible = false;
        setTimeout(() => {
            this.queue = this.queue.filter(t => t.id !== id);
        }, 300);
    },

    // H1 FIX: success/info → 5s, error → 7s
    success(msg) { this.show(msg, 'success', 5000); },
    error(msg)   { this.show(msg, 'error',   7000); },
    info(msg)    { this.show(msg, 'info',    5000); },
    warning(msg) { this.show(msg, 'warning', 6000); },
};
