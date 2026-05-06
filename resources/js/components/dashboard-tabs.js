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

            // Sync with browser back/forward buttons.
            // The logoutInterceptor checks for the `tab` key on state to skip its
            // own handling, so we must always include `tab` here.
            window.addEventListener('popstate', (e) => {
                // Only handle states that belong to us (they carry a `tab` key).
                if (!e.state || !e.state.tab) return;

                const tab = e.state.tab;
                if (['today', 'health', 'activity'].includes(tab)) {
                    this.activeTab = tab;
                }
            });

            window.addEventListener('switch-dashboard-tab', (event) => {
                const tab = event.detail?.tab;
                if (tab && ['today', 'health', 'activity'].includes(tab)) {
                    this.switchTab(tab);
                }
            });
        },

        switchTab(tab) {
            if (this.activeTab === tab) return;

            this.isSwitching = true;
            setTimeout(() => { this.activeTab = tab; }, 100);
            setTimeout(() => { this.isSwitching = false; }, 300);

            const url = new URL(window.location.href);
            url.searchParams.set('tab', tab);
            // Always tag tab states so the logout interceptor can identify them.
            window.history.pushState({ tab }, '', url);
        },

        isActive(tab) {
            return this.activeTab === tab;
        },
    };
}