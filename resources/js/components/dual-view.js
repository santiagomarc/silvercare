/**
 * Two-perspective product preview (senior / caregiver).
 *
 * Implements the WAI-ARIA tabs pattern: one tab stop for the whole set,
 * arrow keys move between tabs, and focus follows selection.
 */
export default function dualView() {
    return {
        view: 'senior',
        taken: false,
        order: ['senior', 'caregiver'],

        select(next) {
            this.view = next;
            this.$refs['tab_' + next]?.focus();
        },

        move(delta) {
            const at = this.order.indexOf(this.view);
            const to = (at + delta + this.order.length) % this.order.length;
            this.select(this.order[to]);
        },
    };
}
