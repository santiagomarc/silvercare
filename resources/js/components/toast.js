/**
 * Global toast notification store.
 *
 * Usage in Blade:
 *   Alpine.$store.toast.show('Message', 'success')
 *
 * The <div id="toast-container"> in the layout auto-renders toasts.
 */
export default {
    items: [],
    _id: 0,

    show(message, type = 'info', duration = 3000) {
        const id = ++this._id;
        this.items.push({ id, message, type, visible: false });

        // Trigger enter animation on next tick
        setTimeout(() => {
            const item = this.items.find(t => t.id === id);
            if (item) item.visible = true;
        }, 10);

        // Auto-dismiss
        setTimeout(() => this.dismiss(id), duration);
    },

    dismiss(id) {
        const item = this.items.find(t => t.id === id);
        if (item) item.visible = false;
        setTimeout(() => {
            this.items = this.items.filter(t => t.id !== id);
        }, 300);
    },

    success(msg) { this.show(msg, 'success'); },
    error(msg)   { this.show(msg, 'error', 4000); },
    info(msg)    { this.show(msg, 'info'); },
};
