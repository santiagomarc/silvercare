/**
 * Alpine.data('dashboardTabs') — Tab switching for the elderly dashboard.
 *
 * Handles progressive disclosure with Today / Health / Activity tabs.
 */
export default function dashboardTabs(initialTab = 'today') {
    return {
        activeTab: initialTab,

        switchTab(tab) {
            this.activeTab = tab;
        },

        isActive(tab) {
            return this.activeTab === tab;
        },
    };
}
