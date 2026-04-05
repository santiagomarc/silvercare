/**
 * Alpine.data('dashboardTabs') — Tab switching for the elderly dashboard.
 *
 * Handles progressive disclosure with Today / Health / Activity tabs.
 */
export default function dashboardTabs(initialTab = 'today') {
    return {
        activeTab: initialTab,
        isSwitching: false,

        init() {
            const queryTab = new URLSearchParams(window.location.search).get('tab');
            if (queryTab && ['today', 'health', 'activity'].includes(queryTab)) {
                this.activeTab = queryTab;
            }

            window.addEventListener('switch-dashboard-tab', (event) => {
                const tab = event.detail?.tab;
                if (tab && ['today', 'health', 'activity'].includes(tab)) {
                    this.switchTab(tab);
                }
            });
        },

        switchTab(tab) {
            if (this.activeTab === tab) return;
            
            // Trigger "squish" animation
            this.isSwitching = true;
            setTimeout(() => {
                this.activeTab = tab;
            }, 100); // Small delay to let the droplet squish before moving

            setTimeout(() => {
                this.isSwitching = false;
            }, 300); // Release the squish as it slides

            const url = new URL(window.location.href);
            url.searchParams.set('tab', tab);
            window.history.replaceState({}, '', url);
        },

        isActive(tab) {
            return this.activeTab === tab;
        },
    };
}
