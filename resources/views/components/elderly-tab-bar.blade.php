{{-- Senior dashboard tab switcher.

     State lives in the Alpine `dashboardTabs` component on the parent
     (activeTab / isActive / switchTab / isSwitching) — this file only draws
     it. Keep the ids and aria-controls: the panels point back at them. --}}

@props(['activeTab' => 'today'])

@php
    $tabs = [
        ['key' => 'today',    'label' => 'Today',    'icon' => 'clipboard-list'],
        ['key' => 'health',   'label' => 'Health',   'icon' => 'heart-pulse'],
        ['key' => 'activity', 'label' => 'Activity', 'icon' => 'trending-up'],
    ];
@endphp

<div
    class="sc-tabbar mb-6"
    role="tablist"
    aria-label="Dashboard sections"
    x-data="{
        pillLeft: 0, pillWidth: 0, pillHeight: 0, pillTop: 0,
        updatePill() {
            const activeEl = this.$el.querySelector('[aria-selected=\'true\']');
            if (!activeEl) return;
            this.pillLeft   = activeEl.offsetLeft;
            this.pillWidth  = activeEl.offsetWidth;
            this.pillHeight = activeEl.offsetHeight;
            this.pillTop    = activeEl.offsetTop;
        }
    }"
    x-init="
        $nextTick(() => updatePill());
        $watch('activeTab', () => { $nextTick(() => updatePill()) });
    "
    @resize.window.debounce.100ms="updatePill()"
>
    <div
        class="sc-tabbar-pill"
        aria-hidden="true"
        :style="`left:${pillLeft}px; top:${pillTop}px; width:${pillWidth}px; height:${pillHeight}px`"
        :class="pillWidth === 0 ? 'opacity-0' : 'opacity-100'"
    ></div>

    @foreach ($tabs as $tab)
        <button
            type="button"
            role="tab"
            class="sc-tabbar-btn"
            id="tab-{{ $tab['key'] }}"
            aria-controls="panel-{{ $tab['key'] }}"
            aria-selected="{{ $activeTab === $tab['key'] ? 'true' : 'false' }}"
            :aria-selected="isActive('{{ $tab['key'] }}') ? 'true' : 'false'"
            tabindex="{{ $activeTab === $tab['key'] ? '0' : '-1' }}"
            :tabindex="isActive('{{ $tab['key'] }}') ? 0 : -1"
            @click="switchTab('{{ $tab['key'] }}')"
        >
            <x-dynamic-component :component="'lucide-' . $tab['icon']" class="sc-i w-6 h-6" aria-hidden="true" />
            <span>{{ $tab['label'] }}</span>
        </button>
    @endforeach
</div>
