{{-- ============================================================
     ElderlyGarden — the Garden of Wellness, in one card.

     The plant's palette is tokens (`--sc-plant-*`), so it stays a plant
     in dark and high-contrast mode instead of going navy. Nothing here
     names a colour; each path carries a class.

     Uses Alpine.data('gardenWellness') — logic untouched.
     ============================================================ --}}

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
    class="sc-card p-5"
    role="region"
    aria-label="Garden of Wellness progress"
>
    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2 mb-3">
        <h3 class="sc-h3 inline-flex items-center gap-2">
            <x-lucide-sprout class="sc-i w-6 h-6" style="color:var(--sc-plant-leaf)" aria-hidden="true" />
            Your Garden
        </h3>
        <div class="flex items-center gap-2">
            <span class="sc-badge sc-badge-ok sc-num" x-text="overallProgress + '%'"></span>
            <span class="sc-badge sc-badge-brand" :class="isWilting ? 'sc-badge-alert' : 'sc-badge-brand'" x-text="streakLabel"></span>
        </div>
    </div>

    {{-- The plant --}}
    <div class="flex flex-col items-center">
        <div class="w-20 h-20 flex items-center justify-center" aria-hidden="true">
            {{-- Stage -1: Wilting --}}
            <template x-if="stage === -1">
                <svg viewBox="0 0 100 100" class="w-16 h-16">
                    <path class="sc-plant-soil" d="M30 80 L35 100 L65 100 L70 80 Z" stroke-width="2"/>
                    <path class="sc-plant-stem" d="M50 80 Q48 62 54 48" stroke-width="4"/>
                    <path class="sc-plant-dry" d="M54 58 Q72 62 68 74" stroke-width="3"/>
                    <path class="sc-plant-dry" d="M52 60 Q34 62 36 74" stroke-width="3"/>
                    <circle class="sc-plant-bud" cx="55" cy="43" r="8" stroke-width="2"/>
                </svg>
            </template>
            {{-- Stage 0: Seed --}}
            <template x-if="stage === 0">
                <svg viewBox="0 0 100 100" class="w-16 h-16">
                    <path class="sc-plant-seed" d="M30 80 L35 100 L65 100 L70 80 Z" stroke-width="2"/>
                    <path class="sc-plant-dry" d="M50 80 Q60 60 55 50" stroke-width="3"/>
                    <path class="sc-plant-dry" d="M55 50 Q40 55 45 65" stroke-width="2"/>
                    <path class="sc-plant-dry" d="M55 50 Q65 55 60 65" stroke-width="2"/>
                </svg>
            </template>
            {{-- Stage 1: Seedling --}}
            <template x-if="stage === 1">
                <svg viewBox="0 0 100 100" class="w-16 h-16">
                    <path class="sc-plant-soil" d="M30 80 L35 100 L65 100 L70 80 Z" stroke-width="2"/>
                    <path class="sc-plant-stem" d="M50 80 Q50 70 50 65" stroke-width="4"/>
                    <path class="sc-plant-stem" d="M50 65 Q40 60 40 50 M50 65 Q60 60 60 50" stroke-width="3"/>
                </svg>
            </template>
            {{-- Stage 2: Growing --}}
            <template x-if="stage === 2">
                <svg viewBox="0 0 100 100" class="w-16 h-16">
                    <path class="sc-plant-soil" d="M30 80 L35 100 L65 100 L70 80 Z" stroke-width="2"/>
                    <path class="sc-plant-stem" d="M50 80 Q55 60 50 45" stroke-width="4"/>
                    <path class="sc-plant-stem" d="M50 65 Q30 55 40 45 M50 65 Q70 55 60 45" stroke-width="3"/>
                </svg>
            </template>
            {{-- Stage 3: Budding --}}
            <template x-if="stage === 3">
                <svg viewBox="0 0 100 100" class="w-16 h-16">
                    <path class="sc-plant-soil" d="M30 80 L35 100 L65 100 L70 80 Z" stroke-width="2"/>
                    <path class="sc-plant-stem" d="M50 80 Q55 60 50 45" stroke-width="4"/>
                    <path class="sc-plant-stem" d="M50 65 Q30 55 40 45 M50 65 Q70 55 60 45" stroke-width="3"/>
                    <circle class="sc-plant-bloom" cx="50" cy="40" r="8" stroke-width="2"/>
                </svg>
            </template>
            {{-- Stage 4: Blooming --}}
            <template x-if="stage === 4">
                <svg viewBox="0 0 100 100" class="w-16 h-16">
                    <path class="sc-plant-soil" d="M25 80 L30 100 L70 100 L75 80 Z" stroke-width="2"/>
                    <path class="sc-plant-stem" d="M50 80 Q50 60 50 40" stroke-width="4"/>
                    <path class="sc-plant-stem" d="M50 60 Q30 50 40 40 M50 60 Q70 50 60 40" stroke-width="3"/>
                    <circle class="sc-plant-bloom" cx="50" cy="30" r="15" stroke-width="2"/>
                    <path class="sc-plant-bloom" d="M50 30 L50 10 M50 30 L70 30 M50 30 L50 50 M50 30 L30 30" stroke-width="2"/>
                    <circle class="sc-plant-sun" cx="50" cy="30" r="5"/>
                </svg>
            </template>
        </div>
    </div>

    {{-- Message --}}
    <p class="text-center font-semibold mt-2 leading-snug" style="color:var(--sc-ink)" x-text="message" role="status"></p>

    <p class="text-center font-medium mt-1"
       style="color:var(--sc-muted)"
       :style="{ color: isWilting ? 'var(--sc-alert)' : 'var(--sc-muted)' }"
       x-text="streakDetail"></p>

    {{-- Water bar --}}
    <div class="sc-progress mt-3"
         role="progressbar"
         aria-valuemin="0"
         aria-valuemax="100"
         :aria-valuenow="overallProgress"
         aria-label="Garden progress today">
        <div class="sc-progress-fill" :style="`width: ${overallProgress}%`"></div>
    </div>

    {{-- Metrics --}}
    <div class="grid grid-cols-3 gap-2 text-center mt-3">
        <div class="sc-card-quiet px-2 py-2">
            <div class="sc-stat-label">Tasks</div>
            <div class="font-semibold sc-num" style="color:var(--sc-ink)">
                <span x-text="checklists.done"></span>/<span x-text="checklists.total"></span>
            </div>
        </div>
        <div class="sc-card-quiet px-2 py-2">
            <div class="sc-stat-label">Meds</div>
            <div class="font-semibold sc-num" style="color:var(--sc-ink)">
                <span x-text="meds.done"></span>/<span x-text="meds.total"></span>
            </div>
        </div>
        <div class="sc-card-quiet px-2 py-2">
            <div class="sc-stat-label">Vitals</div>
            <div class="font-semibold sc-num" style="color:var(--sc-ink)">
                <span x-text="vitals.done"></span>/<span x-text="vitals.total"></span>
            </div>
        </div>
    </div>
</div>
