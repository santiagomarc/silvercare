{{-- ============================================================
     ElderlyGarden — Compact Garden of Wellness progress display.
     Uses Alpine.data('gardenWellness').
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
    class="surface-mint relative overflow-hidden p-5"
    role="region"
    aria-label="Garden of Wellness progress"
>
    {{-- Decorative sun --}}
    <div class="absolute right-1 top-0 h-16 w-16 rounded-full bg-yellow-300/55 blur-xl opacity-30 animate-pulse-gentle" aria-hidden="true"></div>
    <div class="ambient-orb -left-6 bottom-0 h-24 w-24 bg-emerald-200/35 blur-2xl" aria-hidden="true"></div>

    <div class="flex items-center justify-between mb-3 relative z-10">
        <h3 class="font-extrabold text-base text-emerald-900">🌱 Your Garden</h3>
        <div class="flex items-center gap-2">
            <span class="badge badge-success text-xs" x-text="overallProgress + '%'"></span>
            <span class="badge text-xs" :class="isWilting ? 'badge-danger' : 'badge-info'" x-text="streakLabel"></span>
        </div>
    </div>

    {{-- Plant SVG — single element with template switching --}}
    <div class="flex flex-col items-center relative z-10">
        <div class="w-20 h-20 flex items-center justify-center" aria-hidden="true">
            {{-- Stage -1: Wilting --}}
            <template x-if="stage === -1">
                <svg viewBox="0 0 100 100" class="w-16 h-16 opacity-90">
                    <path d="M30 80 L35 100 L65 100 L70 80 Z" fill="#A16207" stroke="#713F12" stroke-width="2"/>
                    <path d="M50 80 Q48 62 54 48" stroke="#84CC16" stroke-width="4" fill="none"/>
                    <path d="M54 58 Q72 62 68 74" stroke="#A3A3A3" stroke-width="3" fill="none"/>
                    <path d="M52 60 Q34 62 36 74" stroke="#A3A3A3" stroke-width="3" fill="none"/>
                    <circle cx="55" cy="43" r="8" fill="#FDE68A" stroke="#D97706" stroke-width="2"/>
                </svg>
            </template>
            {{-- Stage 0: Seed --}}
            <template x-if="stage === 0">
                <svg viewBox="0 0 100 100" class="w-16 h-16 opacity-80">
                    <path d="M30 80 L35 100 L65 100 L70 80 Z" fill="#9CA3AF" stroke="#6B7280" stroke-width="2"/>
                    <path d="M50 80 Q60 60 55 50" stroke="#9CA3AF" stroke-width="3" fill="none"/>
                    <path d="M55 50 Q40 55 45 65" stroke="#9CA3AF" stroke-width="2" fill="none"/>
                    <path d="M55 50 Q65 55 60 65" stroke="#9CA3AF" stroke-width="2" fill="none"/>
                </svg>
            </template>
            {{-- Stage 1: Seedling --}}
            <template x-if="stage === 1">
                <svg viewBox="0 0 100 100" class="w-16 h-16 drop-shadow-md">
                    <path d="M30 80 L35 100 L65 100 L70 80 Z" fill="#D97706" stroke="#92400E" stroke-width="2"/>
                    <path d="M50 80 Q50 70 50 65" stroke="#10B981" stroke-width="4" fill="none"/>
                    <path d="M50 65 Q40 60 40 50 M50 65 Q60 60 60 50" stroke="#10B981" stroke-width="3" fill="none" stroke-linecap="round"/>
                </svg>
            </template>
            {{-- Stage 2: Growing --}}
            <template x-if="stage === 2">
                <svg viewBox="0 0 100 100" class="w-16 h-16 drop-shadow-md">
                    <path d="M30 80 L35 100 L65 100 L70 80 Z" fill="#D97706" stroke="#92400E" stroke-width="2"/>
                    <path d="M50 80 Q55 60 50 45" stroke="#10B981" stroke-width="4" fill="none"/>
                    <path d="M50 65 Q30 55 40 45 M50 65 Q70 55 60 45" stroke="#10B981" stroke-width="3" fill="none"/>
                </svg>
            </template>
            {{-- Stage 3: Budding --}}
            <template x-if="stage === 3">
                <svg viewBox="0 0 100 100" class="w-16 h-16 drop-shadow-md">
                    <path d="M30 80 L35 100 L65 100 L70 80 Z" fill="#D97706" stroke="#92400E" stroke-width="2"/>
                    <path d="M50 80 Q55 60 50 45" stroke="#10B981" stroke-width="4" fill="none"/>
                    <path d="M50 65 Q30 55 40 45 M50 65 Q70 55 60 45" stroke="#10B981" stroke-width="3" fill="none"/>
                    <circle cx="50" cy="40" r="8" fill="#FBCFE8" stroke="#DB2777" stroke-width="2"/>
                </svg>
            </template>
            {{-- Stage 4: Blooming --}}
            <template x-if="stage === 4">
                <svg viewBox="0 0 100 100" class="w-16 h-16 drop-shadow-lg transition-transform duration-700 hover:scale-110">
                    <path d="M25 80 L30 100 L70 100 L75 80 Z" fill="#D97706" stroke="#92400E" stroke-width="2"/>
                    <path d="M50 80 Q50 60 50 40" stroke="#10B981" stroke-width="4" fill="none"/>
                    <path d="M50 60 Q30 50 40 40 M50 60 Q70 50 60 40" stroke="#10B981" stroke-width="3" fill="none"/>
                    <circle cx="50" cy="30" r="15" fill="#F472B6" stroke="#DB2777" stroke-width="2"/>
                    <path d="M50 30 L50 10 M50 30 L70 30 M50 30 L50 50 M50 30 L30 30" stroke="#DB2777" stroke-width="2"/>
                    <circle cx="50" cy="30" r="5" fill="#FCD34D"/>
                </svg>
            </template>
        </div>
    </div>

    {{-- Message --}}
    <p class="text-center font-bold text-sm text-emerald-800 mt-2 leading-tight" x-text="message" role="status"></p>

    <p class="text-center text-xs font-semibold mt-1" :class="isWilting ? 'text-red-700' : 'text-emerald-700'" x-text="streakDetail"></p>

    {{-- Water bar --}}
    <div class="progress-track mt-3 bg-white/50 border border-emerald-100">
        <div class="progress-fill bg-blue-400" :style="`width: ${overallProgress}%`"></div>
    </div>

    {{-- Metrics row --}}
    <div class="grid grid-cols-3 gap-2 text-center mt-3">
        <div class="surface-accent rounded-xl px-2 py-2">
            <div class="text-xs text-gray-500 font-bold">Tasks</div>
            <div class="font-extrabold text-sm text-emerald-800">
                <span x-text="checklists.done"></span>/<span x-text="checklists.total"></span>
            </div>
        </div>
        <div class="surface-accent rounded-xl px-2 py-2">
            <div class="text-xs text-gray-500 font-bold">Meds</div>
            <div class="font-extrabold text-sm text-emerald-800">
                <span x-text="meds.done"></span>/<span x-text="meds.total"></span>
            </div>
        </div>
        <div class="surface-accent rounded-xl px-2 py-2">
            <div class="text-xs text-gray-500 font-bold">Vitals</div>
            <div class="font-extrabold text-sm text-emerald-800">
                <span x-text="vitals.done"></span>/<span x-text="vitals.total"></span>
            </div>
        </div>
    </div>
</div>
