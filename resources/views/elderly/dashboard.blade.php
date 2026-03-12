{{-- ============================================================
     ELDERLY DASHBOARD — Progressive Disclosure Layout
     ============================================================
     Structured into 3 tabs: Today / Health / Activity
     Hero card shows the single most urgent action.
     All interactivity via extracted Alpine.data() components.
     ============================================================ --}}

<x-dashboard-layout>
    <x-slot:title>Dashboard - SilverCare</x-slot:title>
    <x-slot:bodyClass>min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(255,255,255,0.92),_rgba(240,249,255,0.88)_22%,_rgba(224,242,254,0.76)_48%,_rgba(254,242,242,0.82)_100%)]</x-slot:bodyClass>

    @push('styles')
    <style>
        /* Range slider styling (mood tracker) */
        input[type=range] {
            appearance: none;
            -webkit-appearance: none;
            background: transparent;
        }
        input[type=range]::-webkit-slider-thumb {
            -webkit-appearance: none;
            height: 36px;
            width: 36px;
            border-radius: 50%;
            background: #fff;
            border: 6px solid currentColor;
            cursor: pointer;
            margin-top: -14px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
            transition: transform 0.1s ease;
        }
        input[type=range]:active::-webkit-slider-thumb {
            transform: scale(1.2);
        }
        input[type=range]::-webkit-slider-runnable-track {
            width: 100%;
            height: 8px;
            background: #E5E7EB;
            border-radius: 999px;
        }
        input[type=range]:focus { outline: none; }
    </style>
    @endpush

    {{-- Navigation --}}
    <x-dashboard-nav
        title="Dashboard Overview"
        role="elderly"
        :unread-notifications="$unreadNotifications"
    />

    {{-- ══════════════════════════════════════════════════════════
         MAIN CONTENT — wrapped in dashboardTabs Alpine component
         ══════════════════════════════════════════════════════════ --}}
        <main id="main-content"
                    class="relative max-w-[1600px] mx-auto px-6 lg:px-12 py-5"
          x-data="dashboardTabs('today')">

                <div class="ambient-orb -left-24 top-20 h-80 w-80 bg-sky-300/40"></div>
                <div class="ambient-orb right-0 top-32 h-64 w-64 bg-rose-300/30"></div>
                <div class="ambient-orb bottom-10 left-1/3 h-56 w-56 bg-amber-300/25"></div>

        <x-flash-messages />

        {{-- ╔══════════════════╗
             ║  HERO ACTION     ║
             ╚══════════════════╝ --}}
        <x-elderly-hero-action
            :medications="$todayMedications"
            :medication-logs="$medicationLogs"
            :vitals-data="$vitalsData"
            :checklists="$todayChecklists"
            :daily-goals-progress="$dailyGoalsProgress"
        />

        {{-- ╔══════════════════╗
             ║  TAB BAR         ║
             ╚══════════════════╝ --}}
        <x-elderly-tab-bar />

        {{-- ═══════════════════════════════════════════════════════
             TAB PANEL: TODAY
             Tasks, medications, mood, and garden progress.
             ═══════════════════════════════════════════════════════ --}}
        <div x-show="isActive('today')"
             class="panel-shell panel-shell-today p-4 md:p-5"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             id="panel-today"
             role="tabpanel"
             aria-labelledby="tab-today">

            <div class="ambient-orb -right-6 -top-6 h-36 w-36 bg-amber-200/35"></div>
            <div class="ambient-orb -left-8 bottom-0 h-32 w-32 bg-sky-200/25"></div>

            <div class="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-6">

                {{-- LEFT COLUMN: Mood + Medications --}}
                <div class="lg:col-span-7 space-y-6">
                    <x-elderly-mood-tracker :initial-mood="$todayMood" />

                    <x-medication-list
                        :medications="$todayMedications"
                        :logs="$medicationLogs"
                    />
                </div>

                {{-- RIGHT COLUMN: Garden + Tasks --}}
                <div class="lg:col-span-5 space-y-5">
                    <x-elderly-garden
                        :completed-checklists="$completedChecklists"
                        :total-checklists="$totalChecklists"
                        :taken-medication-doses="$takenMedicationDoses"
                        :total-medication-doses="$totalMedicationDoses"
                        :completed-vitals="$completedVitals"
                        :total-required-vitals="$totalRequiredVitals"
                    />

                    <x-task-list
                        :checklists="$todayChecklists"
                        :completed-count="$completedChecklists"
                        :total-count="$totalChecklists"
                    />
                </div>

            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════
             TAB PANEL: HEALTH
             Vital cards, Google Fit steps.
             ═══════════════════════════════════════════════════════ --}}
        <div x-show="isActive('health')"
             class="panel-shell panel-shell-health p-4 md:p-5"
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             id="panel-health"
             role="tabpanel"
             aria-labelledby="tab-health">

            <div class="ambient-orb right-2 top-2 h-36 w-36 bg-sky-200/30"></div>
            <div class="ambient-orb left-10 bottom-0 h-28 w-28 bg-indigo-200/25"></div>

            {{-- Health Vitals Header --}}
            <div class="relative z-10 flex justify-between items-center mb-5 rounded-[1.5rem] border border-white/70 bg-white/55 px-5 py-4 backdrop-blur-md shadow-[0_18px_40px_-32px_rgba(15,23,42,0.32)]">
                <div>
                    <h3 class="font-extrabold text-xl text-gray-900">Health Vitals</h3>
                    <p class="text-sm text-gray-500 font-medium">Record and track your daily vitals</p>
                </div>
                <div class="flex items-center gap-2">
                    @if($googleFitConnected)
                        <span class="badge badge-success text-xs shadow-[0_14px_24px_-20px_rgba(34,197,94,0.8)]">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.545,10.239v3.821h5.445c-0.712,2.315-2.647,3.972-5.445,3.972c-3.332,0-6.033-2.701-6.033-6.032s2.701-6.032,6.033-6.032c1.498,0,2.866,0.549,3.921,1.453l2.814-2.814C17.503,2.988,15.139,2,12.545,2C7.021,2,2.543,6.477,2.543,12s4.478,10,10.002,10c8.396,0,10.249-7.85,9.426-11.748L12.545,10.239z"/></svg>
                            Google Fit Connected
                        </span>
                    @endif
                    <span class="badge text-xs border border-white/80 bg-white/75 text-slate-600 shadow-[0_16px_28px_-22px_rgba(15,23,42,0.35)]">
                        {{ $completedVitals }}/{{ $totalRequiredVitals }} recorded
                    </span>
                </div>
            </div>

            {{-- Vital Cards Grid --}}
            <div class="relative z-10 grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 mt-5">
                <x-vital-card type="blood_pressure" :metric-data="$vitalsData['blood_pressure'] ?? null" />
                <x-vital-card type="sugar_level" :metric-data="$vitalsData['sugar_level'] ?? null" />
                <x-vital-card type="temperature" :metric-data="$vitalsData['temperature'] ?? null" />
                <x-vital-card type="heart_rate" :metric-data="$vitalsData['heart_rate'] ?? null" />
            </div>

            {{-- Steps Progress --}}
            <div class="relative z-10">
                <x-elderly-steps-card
                    :steps-data="$stepsData"
                    :google-fit-connected="$googleFitConnected"
                />
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════
             TAB PANEL: ACTIVITY
             Quick-link action cards + upcoming events.
             ═══════════════════════════════════════════════════════ --}}
        <div x-show="isActive('activity')"
             class="panel-shell panel-shell-activity p-4 md:p-5"
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             id="panel-activity"
             role="tabpanel"
             aria-labelledby="tab-activity">

            <div class="ambient-orb right-0 top-0 h-40 w-40 bg-rose-200/30"></div>
            <div class="ambient-orb left-12 bottom-0 h-32 w-32 bg-indigo-200/25"></div>

            <div class="relative z-10 grid grid-cols-1 md:grid-cols-3 gap-4">

                {{-- 1. WELLNESS CENTER --}}
                     <a href="{{ route('elderly.wellness.index') }}"
                         class="card-gradient group bg-gradient-to-br from-rose-500 to-pink-600 p-6 min-h-[140px] flex flex-col justify-between text-white shadow-[0_30px_55px_-30px_rgba(225,29,72,0.7)]">
                    <div class="absolute top-0 right-0 -mt-6 -mr-6 w-24 h-24 rounded-full bg-white/20 blur-xl" aria-hidden="true"></div>
                    <div class="relative z-10">
                        <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm w-fit mb-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                        </div>
                        <h3 class="text-lg font-extrabold leading-tight">Wellness Center</h3>
                        <p class="text-pink-100 text-sm font-medium mt-0.5">Relax, stretch, play</p>
                    </div>
                    <div class="absolute bottom-4 right-4 h-8 w-8 rounded-full bg-white/20 flex items-center justify-center group-hover:bg-white group-hover:text-rose-600 transition-all" aria-hidden="true">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                    </div>
                </a>

                {{-- 2. MY SCHEDULE --}}
                     <a href="{{ route('calendar.index') }}"
                         class="card-gradient group bg-gradient-to-br from-orange-400 to-amber-500 p-6 min-h-[140px] flex flex-col justify-between text-white shadow-[0_30px_55px_-30px_rgba(249,115,22,0.66)]">
                    <div class="absolute top-0 right-0 -mt-6 -mr-6 w-24 h-24 rounded-full bg-white/20 blur-xl" aria-hidden="true"></div>
                    <div class="relative z-10">
                        <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm w-fit mb-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        <h3 class="text-lg font-extrabold leading-tight">My Schedule</h3>
                        <p class="text-orange-100 text-sm font-medium mt-0.5">Appointments & reminders</p>
                    </div>
                    <div class="absolute bottom-4 right-4 h-8 w-8 rounded-full bg-white/20 flex items-center justify-center group-hover:bg-white group-hover:text-orange-600 transition-all" aria-hidden="true">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                    </div>
                </a>

                {{-- 3. HEALTH ANALYTICS --}}
                     <a href="{{ route('elderly.vitals.analytics') }}"
                         class="card-gradient group bg-gradient-to-br from-indigo-500 to-purple-600 p-6 min-h-[140px] flex flex-col justify-between text-white shadow-[0_30px_55px_-30px_rgba(99,102,241,0.66)]">
                    <div class="absolute top-0 right-0 -mt-6 -mr-6 w-24 h-24 rounded-full bg-white/20 blur-xl" aria-hidden="true"></div>
                    <div class="relative z-10">
                        <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm w-fit mb-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-extrabold leading-tight">Health Analytics</h3>
                        <p class="text-purple-100 text-sm font-medium mt-0.5">View insights & trends</p>
                    </div>
                    <div class="absolute bottom-4 right-4 h-8 w-8 rounded-full bg-white/20 flex items-center justify-center group-hover:bg-white group-hover:text-purple-600 transition-all" aria-hidden="true">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                    </div>
                </a>
            </div>

            {{-- Upcoming Events --}}
            @if(!empty($upcomingEvents) && count($upcomingEvents) > 0)
                <div class="relative z-10 mt-6">
                    <h3 class="font-extrabold text-lg text-gray-900 mb-3">Upcoming Events</h3>
                    <div class="space-y-3">
                        @foreach($upcomingEvents as $event)
                            <div class="card-glass p-4 flex items-center gap-4">
                                <div class="w-12 h-12 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600 font-extrabold text-sm flex-shrink-0">
                                    {{ $event->start_time->format('M') }}<br>{{ $event->start_time->format('d') }}
                                </div>
                                <div class="min-w-0">
                                    <p class="font-bold text-gray-900 text-sm truncate">{{ $event->title }}</p>
                                    <p class="text-xs text-gray-500">{{ $event->start_time->format('g:i A') }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

    </main>

    {{-- ╔══════════════════════════════════════════════════════════╗
         ║  GLOBAL OVERLAYS & WIDGETS                              ║
         ╚══════════════════════════════════════════════════════════╝ --}}

    {{-- Toast notification container --}}
    <div x-data class="toast-container" aria-live="polite" aria-atomic="true">
        <template x-for="t in $store.toast.queue" :key="t.id">
            <div class="toast"
                 :class="'toast-' + t.type"
                 x-text="t.message"
                 x-show="t.visible"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 translate-y-4"
                 role="alert">
            </div>
        </template>
    </div>

    {{-- AI Assistant Chat Widget --}}
    <x-ai-chat-widget />

</x-dashboard-layout>
