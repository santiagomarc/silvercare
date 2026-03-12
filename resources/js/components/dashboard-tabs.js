/**
 * Alpine.data('dashboardTabs') — Tab switching for the elderly dashboard.
 *
 * Handles progressive disclosure with Today / Health / Activity tabs.
 */
export default function dashboardTabs(initialTab = 'today') {
    return {
        activeTab: initialTab,

        init() {
            const queryTab = new URLSearchParams(window.location.search).get('tab');
            if (queryTab && ['today', 'health', 'activity'].includes(queryTab)) {
                this.activeTab = queryTab;
            }
        },

        switchTab(tab) {
            this.activeTab = tab;

            const url = new URL(window.location.href);
            url.searchParams.set('tab', tab);
            window.history.replaceState({}, '', url);
        },

        isActive(tab) {
            return this.activeTab === tab;
        },
    };
}
