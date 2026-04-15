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
            height: 48px;
            width: 48px;
            border-radius: 50%;
            background: #fff;
            border: 6px solid currentColor;
            cursor: pointer;
            margin-top: -20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
            transition: transform 0.1s ease;
        }
        input[type=range]::-moz-range-thumb {
            height: 48px;
            width: 48px;
            border-radius: 50%;
            background: #fff;
            border: 6px solid currentColor;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
            transition: transform 0.1s ease;
        }
        input[type=range]:active::-webkit-slider-thumb {
            transform: scale(1.2);
        }
        input[type=range]:active::-moz-range-thumb {
            transform: scale(1.2);
        }
        input[type=range]::-webkit-slider-runnable-track {
            width: 100%;
            height: 8px;
            background: #E5E7EB;
            border-radius: 999px;
        }
        input[type=range]::-moz-range-track {
            width: 100%;
            height: 8px;
            background: #E5E7EB;
            border-radius: 999px;
        }
        input[type=range]:focus-visible {
            outline: 2px solid #3451d1;
            outline-offset: 4px;
            border-radius: 4px;
        }
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
             ║  GREETING BANNER ║
             ╚══════════════════╝ --}}
        @php
            $dashboardNow = now()->timezone(config('app.timezone', 'Asia/Manila'));
            $hour = $dashboardNow->hour;
            $greeting = 'Good evening';
            if ($hour < 12) {
                $greeting = 'Good morning';
            } elseif ($hour < 17) {
                $greeting = 'Good afternoon';
            }
            $firstName = explode(' ', Auth::user()->name)[0];
        @endphp
        <div class="mb-6 flex flex-col md:flex-row md:items-end justify-between gap-2 relative z-10">
            <div>
                <h2 class="text-2xl md:text-3xl font-black text-gray-900 tracking-tight">
                    {{ $greeting }}, <span class="text-[#000080]">{{ $firstName }}</span>
                    <x-lucide-hand class="inline-block w-8 h-8 text-amber-500 align-[-0.2em]" aria-hidden="true" />
                </h2>
            </div>
            <div class="hidden sm:block md:text-right">
                <p class="text-xs font-bold text-[#000080]/60 uppercase tracking-widest leading-none">{{ $dashboardNow->format('l') }}</p>
                <p class="text-lg font-extrabold text-[#000080] leading-none mt-1">{{ $dashboardNow->format('M j, Y') }}</p>
            </div>
        </div>

        {{-- ╔══════════════════════════════╗
             ║  ONBOARDING NUDGE BANNER     ║
             ╚══════════════════════════════╝ --}}
        @php
            $dashboardProfile = Auth::user()->profile;
            $completion = $profileCompletion ?? [
                'personal_complete' => false,
                'emergency_complete' => false,
                'medical_complete' => false,
                'is_complete' => false,
            ];
            $personalStepComplete = $completion['personal_complete'];
            $emergencyStepComplete = $completion['emergency_complete'];
            $medicalStepComplete = $completion['medical_complete'];
            $showProfileNudge = $dashboardProfile && !($completion['is_complete'] ?? false);
        @endphp
        @if($showProfileNudge)
            <div class="mb-5 rounded-2xl border border-amber-200 bg-gradient-to-r from-amber-50 to-orange-50/70 backdrop-blur-sm px-5 py-4 shadow-sm"
                 x-data="{ dismissed: false }"
                 x-show="!dismissed"
                 x-transition>
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center text-amber-600 flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-extrabold text-gray-900">Complete your health profile</p>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Complete your profile:
                                <span class="inline-flex items-center gap-1 font-bold {{ $personalStepComplete ? 'text-emerald-700' : 'text-gray-500' }}">
                                    @if($personalStepComplete)
                                        <x-lucide-check class="w-4 h-4" aria-hidden="true" />
                                    @else
                                        <x-lucide-square class="w-4 h-4" aria-hidden="true" />
                                    @endif
                                    Personal
                                </span>
                                <span class="inline-flex items-center gap-1 font-bold {{ $emergencyStepComplete ? 'text-emerald-700' : 'text-gray-500' }}">
                                    @if($emergencyStepComplete)
                                        <x-lucide-check class="w-4 h-4" aria-hidden="true" />
                                    @else
                                        <x-lucide-square class="w-4 h-4" aria-hidden="true" />
                                    @endif
                                    Emergency
                                </span>
                                <span class="inline-flex items-center gap-1 font-bold {{ $medicalStepComplete ? 'text-emerald-700' : 'text-gray-500' }}">
                                    @if($medicalStepComplete)
                                        <x-lucide-check class="w-4 h-4" aria-hidden="true" />
                                    @else
                                        <x-lucide-square class="w-4 h-4" aria-hidden="true" />
                                    @endif
                                    Medical
                                </span>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <a href="{{ route('profile.completion') }}"
                           class="rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold px-4 py-2 transition-colors shadow-sm">
                            Complete Now →
                        </a>
                        <button @click="dismissed = true"
                                class="text-gray-400 hover:text-gray-600 transition-colors p-1"
                                aria-label="Dismiss">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- ╔══════════════════╗
             ║  HERO ACTION     ║
             ╚══════════════════╝ --}}
        <x-elderly-hero-action
            :medications="$todayMedications"
            :medication-logs="$medicationLogs"
            :vitals-data="$vitalsData"
            :checklists="$todayChecklists"
            :mood-recorded="$moodRecordedToday"
            :daily-goals-progress="$dailyGoalsProgress"
        />

        {{-- ╔══════════════════╗
             ║  TAB BAR         ║
             ╚══════════════════╝ --}}
        <x-elderly-tab-bar />

        {{-- ═══════════════════════════════════════════════════════
             TAB PANELS WRAPPER (Grid-stacked for smooth cross-fade)
             ═══════════════════════════════════════════════════════ --}}
        <div class="grid relative items-start">

            {{-- TAB PANEL: TODAY --}}
            <div x-show="isActive('today')"
                 class="col-start-1 row-start-1 panel-shell panel-shell-today p-4 md:p-5"
                 x-transition:enter="transition duration-500 delay-100 ease-out"
                 x-transition:enter-start="opacity-0 translate-y-4 scale-[0.98]"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition duration-300 ease-in absolute w-full left-0 top-0"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 -translate-y-4 scale-[0.98]"
                 id="panel-today"
                 role="tabpanel"
                 aria-labelledby="tab-today">

                <div class="ambient-orb -right-6 -top-6 h-36 w-36 bg-amber-200/35"></div>
                <div class="ambient-orb -left-8 bottom-0 h-32 w-32 bg-sky-200/25"></div>

                <div class="relative z-10 space-y-5">
                    <section class="rounded-2xl border border-white/65 bg-white/55 backdrop-blur-sm p-4">
                        <div id="today-details" class="pt-1">
                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                                {{-- LEFT COLUMN: Mood + Medications --}}
                                <div class="lg:col-span-7 space-y-6">
                                    <div id="today-mood-tracker">
                                        <x-elderly-mood-tracker :initial-mood="$todayMood" />
                                    </div>

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
                                        :streak-days="$gardenStreakDays"
                                        :is-wilting="$gardenIsWilting"
                                        :missed-count="$gardenMissedCount"
                                    />

                                    <x-task-list
                                        :checklists="$todayChecklists"
                                        :completed-count="$completedChecklists"
                                        :total-count="$totalChecklists"
                                    />
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
        </div>

            {{-- TAB PANEL: HEALTH --}}
            <div x-show="isActive('health')"
                 class="col-start-1 row-start-1 panel-shell panel-shell-health p-4 md:p-5"
                 x-cloak
                 x-transition:enter="transition duration-500 delay-100 ease-out"
                 x-transition:enter-start="opacity-0 translate-y-4 scale-[0.98]"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition duration-300 ease-in absolute w-full left-0 top-0"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 -translate-y-4 scale-[0.98]"
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
                    <span class="badge text-base px-4 py-2 border border-white/80 bg-white/75 text-slate-700 shadow-[0_16px_28px_-22px_rgba(15,23,42,0.35)]">
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

            {{-- TAB PANEL: ACTIVITY --}}
            <div x-show="isActive('activity')"
                 class="col-start-1 row-start-1 panel-shell panel-shell-activity p-4 md:p-5"
                 x-cloak
                 x-transition:enter="transition duration-500 delay-100 ease-out"
                 x-transition:enter-start="opacity-0 translate-y-4 scale-[0.98]"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition duration-300 ease-in absolute w-full left-0 top-0"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 -translate-y-4 scale-[0.98]"
                 id="panel-activity"
                 role="tabpanel"
                 aria-labelledby="tab-activity">

                <div class="ambient-orb right-0 top-0 h-40 w-40 bg-rose-200/30"></div>
            <div class="ambient-orb left-12 bottom-0 h-32 w-32 bg-indigo-200/25"></div>

            <div class="relative z-10 grid grid-cols-1 md:grid-cols-4 gap-4">

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

                {{-- 3. CARE MESSAGES --}}
                     <a href="{{ route('elderly.messages.index') }}"
                         class="card-gradient group bg-gradient-to-br from-indigo-500 to-sky-600 p-6 min-h-[140px] flex flex-col justify-between text-white shadow-[0_30px_55px_-30px_rgba(79,70,229,0.66)]">
                    <div class="absolute top-0 right-0 -mt-6 -mr-6 w-24 h-24 rounded-full bg-white/20 blur-xl" aria-hidden="true"></div>
                    <div class="relative z-10">
                        <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm w-fit mb-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h8m-8 4h5m-7 6l-3-3H3a2 2 0 01-2-2V7a2 2 0 012-2h18a2 2 0 012 2v8a2 2 0 01-2 2h-8l-5 5"></path></svg>
                        </div>
                        <h3 class="text-lg font-extrabold leading-tight">Care Messages</h3>
                        <p class="text-indigo-100 text-sm font-medium mt-0.5">Message your caregiver</p>
                    </div>
                    <div class="absolute bottom-4 right-4 h-8 w-8 rounded-full bg-white/20 flex items-center justify-center group-hover:bg-white group-hover:text-indigo-600 transition-all" aria-hidden="true">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                    </div>
                </a>

                {{-- 4. HEALTH ANALYTICS --}}
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

        </div> {{-- END TAB PANELS WRAPPER --}}

    </main>

    {{-- ╔══════════════════════════════════════════════════════════╗
         ║  GLOBAL OVERLAYS & WIDGETS                              ║
         ╚══════════════════════════════════════════════════════════╝ --}}

    {{-- Toast notification container (H2 FIX: icons + color for WCAG 1.4.1) --}}
    <div x-data class="toast-container" aria-live="polite" aria-atomic="true">
        <template x-for="t in $store.toast.queue" :key="t.id">
            <div class="toast-card"
                 :class="'toast-border-' + t.type"
                 x-show="t.visible"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 translate-y-4"
                 role="alert"
                 :aria-label="t.type + ': ' + t.message">
                
                <div class="flex items-center gap-3">
                    <span x-html="t.iconHtml" aria-hidden="true" class="flex-shrink-0"></span>
                    <span x-text="t.message" class="text-white font-medium text-sm leading-snug"></span>
                </div>
                
                <button @click="$store.toast.dismiss(t.id)" class="absolute right-3 top-1/2 -translate-y-1/2 text-white/50 hover:text-white/90 p-1 focus:outline-none">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        </template>
    </div>

    {{-- AI Assistant Chat Widget --}}
    <x-ai-chat-widget />

</x-dashboard-layout>
