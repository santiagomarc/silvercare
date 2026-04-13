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
    toastId: 0,

    // Icons keyed by type — returns raw HTML string for SVG
    icons: {
        success: `<svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`,
        error:   `<svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`,
        info:    `<svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`,
        warning: `<svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>`,
    },

    show(message, type = 'info', duration = 5000) {
        const id = ++this.toastId;
        const iconHtml = this.icons[type] ?? this.icons['info'];
        this.queue.push({ id, message, type, iconHtml, visible: false });

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
