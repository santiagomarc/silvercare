@props([
    'completedChecklists' => 0,
    'totalChecklists' => 0,
    'takenMedicationDoses' => 0,
    'totalMedicationDoses' => 0,
    'completedVitals' => 0,
    'totalRequiredVitals' => 0,
    'streakDays' => 0,
    'isWilting' => false,
    'missedCount' => 0,
])

<div
    x-data="gardenWellness(
        { done: {{ $completedChecklists }}, total: {{ $totalChecklists }} },
        { done: {{ $takenMedicationDoses }}, total: {{ $totalMedicationDoses }} },
        { done: {{ $completedVitals }}, total: {{ $totalRequiredVitals }} },
        { streakDays: {{ $streakDays }}, isWilting: {{ $isWilting ? 'true' : 'false' }}, missedCount: {{ $missedCount }} }
    )"
    class="fixed left-4 bottom-24 z-40 hidden lg:block"
>
    <button
        type="button"
        @click="window.dispatchEvent(new CustomEvent('switch-dashboard-tab', { detail: { tab: 'today' } }))"
        class="rounded-2xl border px-4 py-3 shadow-lg backdrop-blur-xl transition-all hover:-translate-y-0.5"
        :class="isWilting ? 'border-red-200 bg-red-50/85 text-red-700' : 'border-emerald-200 bg-white/85 text-emerald-800'"
        aria-label="Open Today tab and view Garden progress"
        title="Garden of Wellness"
    >
        <div class="flex items-center gap-3">
            <div class="text-2xl" x-text="stageIcon"></div>
            <div class="text-left leading-tight">
                <p class="text-xs font-black uppercase tracking-wide">Garden</p>
                <p class="text-sm font-extrabold" x-text="overallProgress + '% complete'"></p>
                <p class="text-[11px] font-semibold" x-text="streakLabel"></p>
            </div>
        </div>
    </button>
</div>
