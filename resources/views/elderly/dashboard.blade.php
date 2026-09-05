{{-- ============================================================
     ELDERLY DASHBOARD — Progressive Disclosure Layout
     ============================================================
     Structured into 3 tabs: Today / Health / Activity
     Hero card shows the single most urgent action.
     All interactivity via extracted Alpine.data() components.
     ============================================================ --}}

<x-dashboard-layout sc>
    <x-slot:title>Dashboard - SilverCare</x-slot:title>
    <x-slot:bodyClass>sc-page min-h-screen</x-slot:bodyClass>

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

    {{-- The greeting IS the page heading. It used to sit under a second,
         generic "Dashboard Overview" title in the app bar, so the screen
         opened with two competing headings and the top one said nothing.
         The app bar owns the only <h1>; everything below starts at <h2>. --}}
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

    <x-dashboard-nav
        :title="$greeting . ', ' . $firstName . '.'"
        :subtitle="$dashboardNow->format('l, j F Y')"
        role="elderly"
        :unread-notifications="$unreadNotifications"
    />

    {{-- ══════════════════════════════════════════════════════════
         MAIN CONTENT — wrapped in dashboardTabs Alpine component

         `sc-ambient` replaces the six coloured blur orbs that used to
         float behind this page. It is the landing page's ground: a 72px
         hairline grid under a radial mask, plus two very low-opacity
         glows. Texture without colour — which is the whole point, since
         the orbs were what made the screen read as candy.
         ══════════════════════════════════════════════════════════ --}}
    <main id="main-content"
          class="sc-ambient sc-stack relative max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-10 pt-5 pb-12"
          x-data="dashboardTabs('today')">

        <x-flash-messages />

        {{-- ╔══════════════════════════════════╗
             ║  DAILY WELLNESS CHECK-IN CARD    ║
             ╚══════════════════════════════════╝ --}}
        {{-- Daily check-in.
             Previously this posted and then called window.location.reload().
             The reload did not repaint reliably, so a senior tapped the button
             and nothing appeared to happen. It is now a reactive Alpine
             component that updates in place — no reload, no lost scroll
             position, and failures are actually shown instead of swallowed. --}}
        <div
            x-data="dailyCheckin({
                checkedIn: {{ ($hasCheckedInToday ?? false) ? 'true' : 'false' }},
                checkedInAt: @js($todayCheckin?->checked_in_at?->format('g:i A')),
                status: @js($todayCheckin?->status),
                endpoint: @js(route('elderly.checkin')),
            })"
            id="daily-checkin-banner"
            class="sc-card-quiet p-4 sm:p-5"
        >
            <div class="flex flex-col sm:flex-row sm:flex-wrap items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    {{-- The plate is the only tinted thing here, and it is icon-sized.
                         This used to be a 48px filled square holding an emoji, inside
                         a full-width tinted band — three colour events for one fact. --}}
                    <span class="sc-plate sc-plate-sm"
                          x-bind:class="checkedIn ? (needsHelp ? 'sc-plate-alert' : 'sc-plate-ok') : ''">
                        <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-check"/></svg>
                    </span>
                    <div>
                        {{-- Server-rendered text inside x-text: the correct wording
                             is in the HTML for screen readers and for the moment
                             before Alpine boots, then Alpine keeps it in sync. --}}
                        <h2 class="sc-h3" x-text="heading">@if($hasCheckedInToday ?? false)Checked in for today@else Daily wellness check-in @endif</h2>
                        <p class="text-sm mt-1" style="color: var(--sc-muted)" x-text="subheading">@if($hasCheckedInToday ?? false)You checked in at {{ $todayCheckin?->checked_in_at?->format('g:i A') ?? 'today' }}. Your caregiver knows you're safe.@else Let your caregiver know you are doing well today with a single tap. @endif</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <div x-show="!checkedIn" class="flex flex-wrap items-center gap-3 w-full sm:w-auto"
                         @if($hasCheckedInToday ?? false) style="display:none" @endif>
                        <button
                            type="button"
                            x-on:click="submit('ok')"
                            x-bind:disabled="busy"
                            class="sc-btn sc-btn-primary flex-1 sm:flex-none"
                        >
                            <span x-show="!busy">I'm doing OK</span>
                            <span x-show="busy">Sending…</span>
                        </button>
                        <button
                            type="button"
                            x-on:click="submit('need_help')"
                            x-bind:disabled="busy"
                            class="sc-btn sc-btn-ghost flex-1 sm:flex-none"
                        >
                            Need help?
                        </button>
                    </div>

                    {{-- Status after check-in: a dot and a word, not a filled badge. --}}
                    <span x-show="checkedIn"
                          @if(!($hasCheckedInToday ?? false)) style="display:none" @endif
                          class="sc-mark"
                          x-bind:class="needsHelp ? 'sc-mark-warn' : 'sc-mark-ok'"><i></i><span
                          x-text="needsHelp ? 'Caregiver notified' : 'All good'">@if(($todayCheckin?->status ?? null) === 'need_help')Caregiver notified@else All good @endif</span></span>
                </div>
            </div>

            <p x-show="error" x-cloak x-text="error" role="alert" class="sc-error mt-3"></p>
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
            <div class="sc-card-quiet px-5 py-3.5"
                 x-data="{ dismissed: false }"
                 x-show="!dismissed"
                 x-transition>
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <span class="sc-plate sc-plate-sm sc-plate-warn">
                            <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-alert"/></svg>
                        </span>
                        <div>
                            <p class="font-semibold" style="color: var(--sc-ink)">Complete your health profile</p>
                            <p class="text-sm mt-1 flex flex-wrap items-center gap-x-3 gap-y-1" style="color: var(--sc-muted)">
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
                           class="sc-btn sc-btn-primary sc-btn-sm">
                            Complete now
                        </a>
                        <button @click="dismissed = true"
                                class="sc-icon-btn"
                                aria-label="Dismiss">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
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

        {{-- The floating "High contrast" switch that used to sit here is gone.
             The app bar's Display menu already carries the same control, beside
             text size where a reader looks for it, and this copy sat in the
             bottom-right corner where the chat widget covered it. Two switches
             for one setting, one of them unreachable. --}}

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


                <div class="relative z-10 space-y-5">
                    <section class="sc-card p-4 sm:p-5">
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


            {{-- Health Vitals Header --}}
            <div class="sc-card flex flex-wrap gap-4 justify-between items-center mb-5 px-5 py-4">
                <div>
                    <h2 class="sc-h3">Health vitals</h2>
                    <p class="text-sm mt-1" style="color: var(--sc-muted)">Record and track your daily vitals</p>
                </div>
                <div class="flex items-center gap-2">
                    @if($googleFitConnected)
                        <span class="sc-mark sc-mark-ok">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.545,10.239v3.821h5.445c-0.712,2.315-2.647,3.972-5.445,3.972c-3.332,0-6.033-2.701-6.033-6.032s2.701-6.032,6.033-6.032c1.498,0,2.866,0.549,3.921,1.453l2.814-2.814C17.503,2.988,15.139,2,12.545,2C7.021,2,2.543,6.477,2.543,12s4.478,10,10.002,10c8.396,0,10.249-7.85,9.426-11.748L12.545,10.239z"/></svg>
                            Google Fit Connected
                        </span>
                    @endif
                    <span class="sc-mark sc-num">
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


            @php
                /* Four destinations, one shape. These were four full-bleed
                   gradient cards — rose, orange, indigo, purple — each with a
                   blurred white orb behind it. That is eight colour events on
                   one row, for four links that do the same kind of thing.
                   They are links: they get one calm card each and an icon. */
                $activityLinks = [
                    ['route' => 'elderly.wellness.index',  'icon' => 'sprout',   'title' => 'Wellness centre',  'desc' => 'Relax, stretch, play'],
                    ['route' => 'calendar.index',          'icon' => 'calendar', 'title' => 'My schedule',      'desc' => 'Appointments and reminders'],
                    ['route' => 'elderly.messages.index',  'icon' => 'chat',     'title' => 'Care messages',    'desc' => 'Message your caregiver'],
                    ['route' => 'elderly.vitals.analytics','icon' => 'activity', 'title' => 'Health analytics', 'desc' => 'View insights and trends'],
                ];
            @endphp
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                @foreach ($activityLinks as $link)
                    <a href="{{ route($link['route']) }}"
                       class="sc-card sc-lift group p-5 flex flex-col gap-3 min-h-[9rem] no-underline">
                        <span class="sc-plate sc-plate-sm">
                            <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-{{ $link['icon'] }}"/></svg>
                        </span>
                        <span class="mt-auto">
                            <span class="sc-h3 block">{{ $link['title'] }}</span>
                            <span class="text-sm block mt-1" style="color: var(--sc-muted)">{{ $link['desc'] }}</span>
                        </span>
                    </a>
                @endforeach
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
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        </template>
    </div>

    {{-- Account Completion Success Modal --}}
    @if(session('account_created_and_completed'))
    <div x-data="{ show: true }" 
         x-init="setTimeout(() => show = false, 10000)"
         x-show="show"
         class="fixed inset-0 z-50 flex items-center justify-center pointer-events-none px-4"
         x-transition:enter="transition ease-out duration-700"
         x-transition:enter-start="opacity-0 translate-y-12"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-500"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-12">
        {{-- One card, one plate, no decorative blob. The old version pinned
             Montserrat by name and painted itself in raw #000080, so it kept
             the previous design alive in a modal almost nobody sees. --}}
        <div class="sc-card sc-card-pop p-8 max-w-sm w-full pointer-events-auto text-center"
             role="status">
            <span class="sc-plate sc-plate-ok mx-auto mb-4">
                <svg class="sc-i w-6 h-6" aria-hidden="true" focusable="false"><use href="#i-check"/></svg>
            </span>
            <h2 class="sc-h3">Account created</h2>
            <p class="mt-2" style="color: var(--sc-muted)">
                Your account has been created. You can now link your caregiver.
            </p>
        </div>
    </div>
    @endif

    {{-- AI Assistant Chat Widget --}}
    <x-ai-chat-widget />

    {{-- Floating Senior Voice Assistant Button --}}
    <div class="fixed bottom-6 left-6 z-40">
        <button
            type="button"
            id="senior-voice-mic-btn"
            onclick="window.SilverCareVoice.start()"
            title="Speak a vital reading (e.g. 'Blood pressure 120 over 80')"
            aria-label="Speak a vital reading"
            class="sc-icon-btn sc-icon-btn-float"
        >
            <svg class="sc-i w-6 h-6" aria-hidden="true" focusable="false"><use href="#i-mic"/></svg>
        </button>
    </div>

    @push('scripts')
    <script src="{{ asset('js/voice-vital-capture.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.SilverCareVoice && window.SilverCareVoice.isSupported()) {
                window.SilverCareVoice.init();
            }
        });
    </script>
    @endpush

</x-dashboard-layout>
