{{-- ============================================================
     ElderlyTabBar — Accessible tab switcher for progressive disclosure.
     Uses Alpine 'dashboardTabs' component for state.
     ============================================================ --}}

@props(['activeTab' => 'today'])

<div
    class="tab-bar mb-6 relative isolate"
    role="tablist"
    aria-label="Dashboard sections"
    x-data="{
        pillLeft: 0,
        pillWidth: 0,
        pillHeight: 0,
        pillTop: 0,
        updatePill() {
            const activeEl = this.$el.querySelector('[aria-selected=\'true\']');
            if (activeEl) {
                this.pillLeft = activeEl.offsetLeft;
                this.pillWidth = activeEl.offsetWidth;
                this.pillHeight = activeEl.offsetHeight;
                this.pillTop = activeEl.offsetTop;
            }
        }
    }"
    x-init="
        $nextTick(() => updatePill());
        $watch('activeTab', () => { $nextTick(() => updatePill()) });
    "
    @resize.window="updatePill()"
>
    <!-- Sliding Pill Indicator -->
    <div
        class="absolute rounded-[2rem] bg-indigo-600 transition-all duration-300 z-0 pointer-events-none"
        style="transition-timing-function: cubic-bezier(0.25, 1, 0.5, 1);"
        :style="`left: ${pillLeft}px; top: ${pillTop}px; width: ${pillWidth}px; height: ${pillHeight}px;`"
        :class="[
            pillWidth === 0 ? 'opacity-0' : 'opacity-100 shadow-[0_12px_24px_-8px_rgba(79,70,229,0.45),_0_4px_8px_-4px_rgba(79,70,229,0.3)]',
            isSwitching ? 'scale-y-[0.88] scale-x-[1.03] bg-gradient-to-r from-indigo-600 to-indigo-500' : 'scale-100'
        ]"
    ></div>

    <button
        role="tab"
        :aria-selected="isActive('today') ? 'true' : 'false'"
        :tabindex="isActive('today') ? 0 : -1"
        @click="switchTab('today')"
        class="tab-btn"
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
        id="tab-activity"
        aria-controls="panel-activity"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
        </svg>
        <span>Activity</span>
    </button>
</div>
