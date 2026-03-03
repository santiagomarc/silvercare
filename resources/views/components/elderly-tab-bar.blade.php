{{-- ============================================================
     ElderlyTabBar — Accessible tab switcher for progressive disclosure.
     Uses Alpine 'dashboardTabs' component for state.
     ============================================================ --}}

@props(['activeTab' => 'today'])

<div class="tab-bar mb-6" role="tablist" aria-label="Dashboard sections">
    <button
        role="tab"
        :aria-selected="isActive('today') ? 'true' : 'false'"
        :tabindex="isActive('today') ? 0 : -1"
        @click="switchTab('today')"
        class="tab-btn"
        :class="isActive('today') && 'bg-white text-navy shadow-sm'"
        id="tab-today"
        aria-controls="panel-today"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        <span>Today</span>
    </button>

    <button
        role="tab"
        :aria-selected="isActive('health') ? 'true' : 'false'"
        :tabindex="isActive('health') ? 0 : -1"
        @click="switchTab('health')"
        class="tab-btn"
        :class="isActive('health') && 'bg-white text-navy shadow-sm'"
        id="tab-health"
        aria-controls="panel-health"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
        </svg>
        <span>Health</span>
    </button>

    <button
        role="tab"
        :aria-selected="isActive('activity') ? 'true' : 'false'"
        :tabindex="isActive('activity') ? 0 : -1"
        @click="switchTab('activity')"
        class="tab-btn"
        :class="isActive('activity') && 'bg-white text-navy shadow-sm'"
        id="tab-activity"
        aria-controls="panel-activity"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
        </svg>
        <span>Activity</span>
    </button>
</div>
