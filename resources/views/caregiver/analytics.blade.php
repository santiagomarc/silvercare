{{-- ============================================================
     CAREGIVER HEALTH ANALYTICS — vitals, adherence & task trends.

     Follows SilverCare design system rules:
     - The charts read their palette from --sc-chart-* tokens at draw
       time and whenever the theme changes (dark/high-contrast modes).
     - Fixed series order: BP is series 1, HR 2, sugar 3, temp 4.
     - Out-of-range points are drawn as triangles and tinted.
     - Every chart has numbers in text beside it for accessibility.
     - Semantic colour is restricted to icon scale (sc-mark, sc-plate).
     ============================================================ --}}

<x-dashboard-layout sc>
    <x-slot:title>Health Analytics - SilverCare</x-slot:title>
    <x-slot:bodyClass>sc-page min-h-screen</x-slot:bodyClass>

    @php
        $patientName = $elderlyUser->name ?? $elderly?->username ?? null;
        $navSubtitle = $patientName ? "Analytics & health trends for {$patientName}" : "Health insights & trends";

        $healthTone = $healthScore >= 75 ? 'ok' : ($healthScore >= 60 ? 'warn' : 'alert');

        $vitalIconMap = [
            'blood_pressure' => 'heart-pulse',
            'sugar_level'    => 'droplet',
            'temperature'    => 'thermometer',
            'heart_rate'     => 'activity',
        ];
    @endphp

    <x-dashboard-nav
        title="Health analytics"
        :subtitle="$navSubtitle"
        role="caregiver"
        :show-back="true"
    />

    <main id="main-content" class="sc-ambient sc-stack relative max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-10 pt-5 pb-12">

        <x-flash-messages />

        @if(!$elderly)
            <section class="sc-empty" aria-labelledby="no-elder-title">
                <span class="sc-plate">
                    <x-lucide-user-x class="sc-i w-6 h-6" aria-hidden="true" />
                </span>
                <h2 id="no-elder-title" class="sc-h3">No elder assigned</h2>
                <p>Analytics will be available once an elder is linked to your caregiver account.</p>
                <a href="{{ route('caregiver.dashboard') }}" class="sc-btn sc-btn-primary">
                    Go to dashboard
                </a>
            </section>
        @else

        {{-- Top Toolbar: Patient switcher, period selector, export action --}}
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            @if(($elderlyPatients ?? collect())->count() > 1)
                <div class="sc-card-quiet px-4 py-3 flex-grow lg:flex-grow-0">
                    <form method="GET" action="{{ route('caregiver.analytics') }}" class="flex flex-col sm:flex-row sm:items-center gap-3">
                        <label for="elderly" class="sc-label mb-0">Viewing patient</label>
                        <select
                            id="elderly"
                            name="elderly"
                            onchange="this.form.submit()"
                            class="sc-select sm:max-w-xs"
                        >
                            @foreach(($elderlyPatients ?? collect()) as $patient)
                                <option value="{{ $patient->id }}" @selected(($selectedElderlyId ?? null) === $patient->id)>
                                    {{ $patient->user?->name ?? ('Patient #' . $patient->id) }}
                                </option>
                            @endforeach
                        </select>
                        <span class="sc-mark sc-mark-brand sm:ml-auto"><i></i><span class="sc-num">{{ ($elderlyPatients ?? collect())->count() }}</span>&nbsp;linked patients</span>
                    </form>
                </div>
            @else
                <div></div>
            @endif

            <div class="flex items-center gap-3 flex-wrap">
                <div class="sc-tablist" role="tablist" aria-label="Time period">
                    <button type="button" role="tab" onclick="changePeriod('7days')"  class="period-btn sc-tab" aria-selected="true"  data-period="7days">Week</button>
                    <button type="button" role="tab" onclick="changePeriod('30days')" class="period-btn sc-tab" aria-selected="false" data-period="30days">Month</button>
                    <button type="button" role="tab" onclick="changePeriod('365days')" class="period-btn sc-tab" aria-selected="false" data-period="365days">Year</button>
                </div>

                <a href="{{ route('caregiver.analytics.export', ['elderly' => $selectedElderlyId]) }}" class="sc-btn sc-btn-ghost sc-btn-sm">
                    <x-lucide-download class="sc-i w-4 h-4" aria-hidden="true" />
                    <span>Export report</span>
                </a>

                <a href="{{ route('caregiver.dashboard') }}" class="sc-btn sc-btn-ghost sc-btn-sm">
                    <x-lucide-arrow-left class="sc-i w-4 h-4" aria-hidden="true" />
                    <span>Back to dashboard</span>
                </a>
            </div>
        </div>

        {{-- Health Score + Quick Stats Row --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            {{-- Health Score Card --}}
            <section class="sc-card p-6" aria-labelledby="score-title">
                <div class="flex items-center gap-3 mb-4">
                    <span class="sc-plate sc-plate-sm sc-plate-{{ $healthTone }}">
                        <x-lucide-heart class="sc-i w-5 h-5" aria-hidden="true" />
                    </span>
                    <h2 id="score-title" class="sc-h3">Health score</h2>
                </div>

                <div class="flex items-center gap-6">
                    <div class="relative w-28 h-28 flex-shrink-0" aria-hidden="true">
                        <svg class="w-full h-full -rotate-90" viewBox="0 0 100 100">
                            <circle cx="50" cy="50" r="42" stroke="var(--sc-line)" stroke-width="8" fill="none"/>
                            <circle cx="50" cy="50" r="42" stroke="var(--sc-{{ $healthTone }})" stroke-width="8" fill="none"
                                stroke-dasharray="264"
                                stroke-dashoffset="{{ 264 - (264 * $healthScore / 100) }}"
                                stroke-linecap="round"/>
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="sc-stat-value sc-num" style="margin-top:0">{{ $healthScore }}</span>
                        </div>
                    </div>

                    <div class="flex-grow min-w-0">
                        <p class="sc-h3">{{ $healthLabel }}</p>
                        <p class="text-sm mt-1 mb-3" style="color: var(--sc-muted)">
                            <span class="sr-only">Score {{ $healthScore }} out of 100. </span>Based on <span class="sc-num">{{ $totalFactors ?? 0 }}</span> tracked vitals
                        </p>

                        @if(isset($healthFactors) && count($healthFactors) > 0)
                        <ul class="flex flex-wrap gap-x-4 gap-y-1.5">
                            @foreach($healthFactors as $type => $factor)
                            @php
                                $fTone = match($factor['status'] ?? '') {
                                    'Optimal', 'Normal', 'Good' => 'ok',
                                    'Elevated', 'Mild', 'Fair' => 'warn',
                                    default => 'alert',
                                };
                            @endphp
                            <li class="sc-mark sc-mark-{{ $fTone }}"><i></i>
                                <x-dynamic-component :component="'lucide-' . ($vitalIconMap[$type] ?? 'stethoscope')" class="sc-i w-4 h-4" aria-hidden="true" />
                                <span class="sr-only">{{ $analyticsData[$type]['config']['name'] ?? $type }}: </span>{{ $factor['status'] }}
                            </li>
                            @endforeach
                        </ul>
                        @endif
                    </div>
                </div>
            </section>

            {{-- Quick Stats Grid --}}
            @php
                $quickStats = [
                    ['icon' => 'chart-column', 'label' => 'Total readings', 'value' => $totalReadings],
                    ['icon' => 'trending-up',  'label' => 'This week',      'value' => $readingsThisWeek],
                    ['icon' => 'pill',         'label' => 'Med adherence',  'value' => isset($medicationSummary['adherenceRate']) ? $medicationSummary['adherenceRate'] . '%' : '—'],
                    ['icon' => 'circle-check', 'label' => 'Task completion', 'value' => isset($taskSummary['completionRate']) ? $taskSummary['completionRate'] . '%' : '—'],
                ];
            @endphp
            <div class="lg:col-span-2 grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($quickStats as $stat)
                    <div class="sc-stat">
                        <span class="sc-plate sc-plate-sm mb-3">
                            <x-dynamic-component :component="'lucide-' . $stat['icon']" class="sc-i w-5 h-5" aria-hidden="true" />
                        </span>
                        <p class="sc-stat-label">{{ $stat['label'] }}</p>
                        <p class="sc-stat-value sc-num">{{ $stat['value'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Per-Vital Analytics Cards (with Line Charts) --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            @foreach($analyticsData as $type => $data)
            <section class="sc-card overflow-hidden" id="card-{{ $type }}" aria-labelledby="card-title-{{ $type }}">
                <div class="p-5 flex items-center justify-between gap-3 flex-wrap" style="border-bottom: 1px solid var(--sc-line)">
                    <div class="flex items-center gap-3">
                        <span class="sc-plate sc-plate-sm flex-shrink-0">
                            <x-dynamic-component :component="'lucide-' . ($vitalIconMap[$type] ?? 'stethoscope')" class="sc-i w-5 h-5" aria-hidden="true" />
                        </span>
                        <div>
                            <h2 id="card-title-{{ $type }}" class="sc-h3 whitespace-nowrap">{{ $data['config']['name'] }}</h2>
                            <p class="text-sm" style="color: var(--sc-muted)">{{ $data['config']['unit'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="p-5">
                    @foreach(['7days', '30days', '365days'] as $period)
                    @php
                        $p = $data[$period] ?? [];
                        $periodWords = ['7days' => 'last 7 days', '30days' => 'last 30 days', '365days' => 'last year'][$period];
                        $chartSummary = ($p['count'] ?? 0) > 0
                            ? ($type === 'blood_pressure'
                                ? 'Line chart of blood pressure over the ' . $periodWords . ': ' . $p['count'] . ' readings, systolic average ' . ($p['systolic_avg'] ?? '-') . ', diastolic average ' . ($p['diastolic_avg'] ?? '-') . ' ' . $data['config']['unit'] . '.'
                                : 'Line chart of ' . strtolower($data['config']['name']) . ' over the ' . $periodWords . ': ' . $p['count'] . ' readings, average ' . ($p['avg'] ?? '-') . ', lowest ' . ($p['min'] ?? '-') . ', highest ' . ($p['max'] ?? '-') . ' ' . $data['config']['unit'] . '.')
                            : '';
                    @endphp
                    <div class="period-data {{ $period !== '7days' ? 'hidden' : '' }}" data-period="{{ $period }}" data-type="{{ $type }}">
                        @if(($p['count'] ?? 0) > 0)
                            <div class="sc-chart sc-chart-sm mb-4">
                                <canvas id="chart-{{ $type }}-{{ $period }}" role="img" aria-label="{{ $chartSummary }}"></canvas>
                            </div>

                            <dl class="grid grid-cols-3 gap-2 sm:gap-3 mb-4">
                                @if($type === 'blood_pressure')
                                    <div class="sc-card-quiet p-2 sm:p-3 text-center">
                                        <dt class="sc-stat-label text-[11px] sm:text-xs">Systolic avg</dt>
                                        <dd class="sc-num font-bold text-base sm:text-xl" style="color: var(--sc-ink)">{{ $p['systolic_avg'] ?? '-' }}</dd>
                                    </div>
                                    <div class="sc-card-quiet p-2 sm:p-3 text-center">
                                        <dt class="sc-stat-label text-[11px] sm:text-xs">Diastolic avg</dt>
                                        <dd class="sc-num font-bold text-base sm:text-xl" style="color: var(--sc-ink)">{{ $p['diastolic_avg'] ?? '-' }}</dd>
                                    </div>
                                    <div class="sc-card-quiet p-2 sm:p-3 text-center">
                                        <dt class="sc-stat-label text-[11px] sm:text-xs">Readings</dt>
                                        <dd class="sc-num font-bold text-base sm:text-xl" style="color: var(--sc-ink)">{{ $p['count'] }}</dd>
                                    </div>
                                @else
                                    @php
                                        $trend = $p['trend'] ?? 'stable';
                                        [$trendIcon, $trendWord, $trendTone] = match ($trend) {
                                            'increasing' => ['trending-up',   'Rising',  'warn'],
                                            'decreasing' => ['trending-down', 'Falling', 'ok'],
                                            default      => ['minus',         'Stable',  ''],
                                        };
                                    @endphp
                                    <div class="sc-card-quiet p-2 sm:p-3 text-center">
                                        <dt class="sc-stat-label text-[11px] sm:text-xs">Average</dt>
                                        <dd class="sc-num font-bold text-base sm:text-xl" style="color: var(--sc-ink)">{{ $p['avg'] ?? '-' }}</dd>
                                    </div>
                                    <div class="sc-card-quiet p-2 sm:p-3 text-center">
                                        <dt class="sc-stat-label text-[11px] sm:text-xs">Min / Max</dt>
                                        <dd class="sc-num font-bold text-xs sm:text-base whitespace-nowrap" style="color: var(--sc-ink)">{{ $p['min'] ?? '-' }}<span style="color: var(--sc-muted)">/</span>{{ $p['max'] ?? '-' }}</dd>
                                    </div>
                                    <div class="sc-card-quiet p-2 sm:p-3 text-center">
                                        <dt class="sc-stat-label text-[11px] sm:text-xs">Trend</dt>
                                        <dd class="sc-mark {{ $trendTone ? 'sc-mark-' . $trendTone : '' }} justify-center text-xs sm:text-base mt-1">
                                            <x-dynamic-component :component="'lucide-' . $trendIcon" class="sc-i w-4 h-4 sm:w-5 sm:h-5" aria-hidden="true" />
                                            {{ $trendWord }}
                                        </dd>
                                    </div>
                                @endif
                            </dl>

                            @if($p['metrics']->count() > 0)
                            @php $latestMetric = $p['metrics']->sortByDesc('measured_at')->first(); @endphp
                            <div class="sc-card-quiet flex items-center justify-between gap-3 py-3 px-4">
                                <div>
                                    <p class="sc-stat-label">Latest reading</p>
                                    <p class="sc-num font-bold text-lg" style="color: var(--sc-ink)">
                                        @if($type === 'blood_pressure')
                                            {{ $latestMetric->value_text }}
                                        @else
                                            {{ number_format($latestMetric->value, $type === 'temperature' ? 1 : 0) }} {{ $data['config']['unit'] }}
                                        @endif
                                    </p>
                                </div>
                                <p class="text-sm whitespace-nowrap" style="color: var(--sc-muted)">{{ $latestMetric->measured_at->diffForHumans() }}</p>
                            </div>
                            @endif
                        @else
                            <div class="sc-empty py-8">
                                <x-dynamic-component :component="'lucide-' . ($vitalIconMap[$type] ?? 'stethoscope')" class="sc-i w-8 h-8" style="color: var(--sc-muted)" aria-hidden="true" />
                                <p class="font-semibold" style="color: var(--sc-ink)">No data yet</p>
                                <p class="text-sm">No readings recorded for this period</p>
                            </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </section>
            @endforeach
        </div>

        {{-- Medication & Task Summary Row --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            {{-- Medication Summary Card --}}
            <section class="sc-card p-6" aria-labelledby="med-summary-title">
                <div class="flex items-center gap-3 mb-5">
                    <span class="sc-plate sc-plate-sm">
                        <x-lucide-pill class="sc-i w-5 h-5" aria-hidden="true" />
                    </span>
                    <div>
                        <h2 id="med-summary-title" class="sc-h3">Medication summary</h2>
                        <p class="text-sm mt-0.5" style="color: var(--sc-muted)">Last 7 days</p>
                    </div>
                </div>

                @if(($medicationSummary['totalMedications'] ?? 0) > 0)
                    @php
                        $medRate = $medicationSummary['adherenceRate'] ?? 0;
                        $medTone = $medRate >= 80 ? 'ok' : ($medRate >= 50 ? 'warn' : 'alert');
                    @endphp
                    <dl class="grid grid-cols-3 gap-2 sm:gap-3 mb-5">
                        <div class="sc-card-quiet p-2 sm:p-3 text-center">
                            <dt class="sc-stat-label text-[11px] sm:text-xs">Doses taken</dt>
                            <dd class="sc-num font-bold text-base sm:text-xl" style="color: var(--sc-ink)">{{ $medicationSummary['totalTaken'] ?? 0 }}</dd>
                        </div>
                        <div class="sc-card-quiet p-2 sm:p-3 text-center">
                            <dt class="sc-stat-label text-[11px] sm:text-xs">Scheduled</dt>
                            <dd class="sc-num font-bold text-base sm:text-xl" style="color: var(--sc-ink)">{{ $medicationSummary['totalScheduled'] ?? 0 }}</dd>
                        </div>
                        <div class="sc-card-quiet p-2 sm:p-3 text-center">
                            <dt class="sc-stat-label text-[11px] sm:text-xs">Adherence</dt>
                            <dd class="sc-mark sc-mark-{{ $medTone }} justify-center text-sm sm:text-lg font-bold mt-1">
                                <i></i>{{ $medRate }}%
                            </dd>
                        </div>
                    </dl>

                    <div class="sc-chart sc-chart-sm mb-5">
                        <canvas id="medicationAdherenceChart" role="img" aria-label="Doughnut chart of medication adherence: {{ $medRate }}% doses taken."></canvas>
                    </div>

                    @if(($medicationSummary['lowStockCount'] ?? 0) > 0)
                        <div class="sc-card-quiet p-3 my-3 flex items-center gap-2 text-sm" style="border-left: 3px solid var(--sc-alert)">
                            <x-lucide-triangle-alert class="sc-i w-4 h-4 flex-shrink-0" style="color: var(--sc-alert)" aria-hidden="true" />
                            <span class="font-medium" style="color: var(--sc-ink)">{{ $medicationSummary['lowStockCount'] }} medication(s) running low on stock</span>
                        </div>
                    @endif

                    @if(!empty($medicationSummary['medications']))
                    <h3 class="sc-eyebrow mt-4 mb-2">Medications breakdown</h3>
                    <ul class="sc-divide max-h-48 overflow-y-auto">
                        @foreach($medicationSummary['medications'] as $med)
                            @php
                                $mAdh = $med['adherence'] ?? 0;
                                $mTone = $mAdh >= 80 ? 'ok' : ($mAdh >= 50 ? 'warn' : 'alert');
                            @endphp
                            <li class="flex items-center justify-between gap-3 py-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="sc-plate sc-plate-sm flex-shrink-0">
                                        <x-lucide-pill class="sc-i w-4 h-4" aria-hidden="true" />
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-semibold truncate" style="color: var(--sc-ink)">{{ $med['name'] }}</p>
                                        <p class="text-xs sc-num" style="color: var(--sc-muted)">{{ $med['taken'] }}/{{ $med['scheduled'] }} doses</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    @if($med['lowStock'])
                                        <span class="sc-badge sc-badge-alert">Low</span>
                                    @endif
                                    <span class="sc-mark sc-mark-{{ $mTone }}"><i></i><span class="sc-num">{{ $mAdh }}%</span></span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    @endif
                @else
                    <div class="sc-empty py-8">
                        <x-lucide-pill class="sc-i w-8 h-8" style="color: var(--sc-muted)" aria-hidden="true" />
                        <p class="font-semibold" style="color: var(--sc-ink)">No active medications</p>
                        <p class="text-sm">No medication schedules recorded for this elder</p>
                    </div>
                @endif

                <a href="{{ route('caregiver.medications.index', ['elderly' => $selectedElderlyId]) }}" class="sc-btn sc-btn-ghost w-full mt-4 justify-center">
                    <span>Manage medications</span>
                    <x-lucide-arrow-right class="sc-i w-4 h-4" aria-hidden="true" />
                </a>
            </section>

            {{-- Task Summary Card --}}
            <section class="sc-card p-6" aria-labelledby="task-summary-title">
                <div class="flex items-center gap-3 mb-5">
                    <span class="sc-plate sc-plate-sm">
                        <x-lucide-circle-check class="sc-i w-5 h-5" aria-hidden="true" />
                    </span>
                    <div>
                        <h2 id="task-summary-title" class="sc-h3">Task summary</h2>
                        <p class="text-sm mt-0.5" style="color: var(--sc-muted)">Last 7 days</p>
                    </div>
                </div>

                @if(($taskSummary['total'] ?? 0) > 0)
                    @php
                        $taskRate = $taskSummary['completionRate'] ?? 0;
                        $taskTone = $taskRate >= 80 ? 'ok' : ($taskRate >= 50 ? 'warn' : 'alert');
                    @endphp
                    <dl class="grid grid-cols-3 gap-2 sm:gap-3 mb-5">
                        <div class="sc-card-quiet p-2 sm:p-3 text-center">
                            <dt class="sc-stat-label text-[11px] sm:text-xs">Completed</dt>
                            <dd class="sc-num font-bold text-base sm:text-xl" style="color: var(--sc-ink)">{{ $taskSummary['completed'] ?? 0 }}</dd>
                        </div>
                        <div class="sc-card-quiet p-2 sm:p-3 text-center">
                            <dt class="sc-stat-label text-[11px] sm:text-xs">Total tasks</dt>
                            <dd class="sc-num font-bold text-base sm:text-xl" style="color: var(--sc-ink)">{{ $taskSummary['total'] ?? 0 }}</dd>
                        </div>
                        <div class="sc-card-quiet p-2 sm:p-3 text-center">
                            <dt class="sc-stat-label text-[11px] sm:text-xs">Rate</dt>
                            <dd class="sc-mark sc-mark-{{ $taskTone }} justify-center text-sm sm:text-lg font-bold mt-1">
                                <i></i>{{ $taskRate }}%
                            </dd>
                        </div>
                    </dl>

                    @if(($taskSummary['overdue'] ?? 0) > 0)
                        <div class="sc-card-quiet p-3 my-3 flex items-center gap-2 text-sm" style="border-left: 3px solid var(--sc-alert)">
                            <x-lucide-clock-alert class="sc-i w-4 h-4 flex-shrink-0" style="color: var(--sc-alert)" aria-hidden="true" />
                            <span class="font-medium" style="color: var(--sc-ink)">{{ $taskSummary['overdue'] }} overdue task(s)</span>
                        </div>
                    @endif

                    @if(($taskSummary['dueToday'] ?? 0) > 0)
                        <div class="sc-card-quiet p-3 my-3 flex items-center gap-2 text-sm" style="border-left: 3px solid var(--sc-warn)">
                            <x-lucide-calendar class="sc-i w-4 h-4 flex-shrink-0" style="color: var(--sc-warn)" aria-hidden="true" />
                            <span class="font-medium" style="color: var(--sc-ink)">{{ $taskSummary['dueToday'] }} task(s) due today</span>
                        </div>
                    @endif

                    @if(!empty($taskSummary['byCategory']) && count($taskSummary['byCategory']) > 0)
                        <h3 class="sc-eyebrow mt-4 mb-2">By category</h3>
                        <div class="space-y-3">
                            @foreach($taskSummary['byCategory'] as $cat)
                                <div>
                                    <div class="flex items-center justify-between text-sm mb-1">
                                        <span class="font-medium" style="color: var(--sc-body)">{{ $cat['category'] }}</span>
                                        <span class="sc-num font-bold" style="color: var(--sc-ink)">{{ $cat['rate'] }}%</span>
                                    </div>
                                    <div class="w-full h-2 rounded-full overflow-hidden" style="background: var(--sc-line)" aria-hidden="true">
                                        <div class="h-full rounded-full transition-all duration-300" style="width: {{ $cat['rate'] }}%; background: var(--sc-ok)"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @else
                    <div class="sc-empty py-8">
                        <x-lucide-clipboard-check class="sc-i w-8 h-8" style="color: var(--sc-muted)" aria-hidden="true" />
                        <p class="font-semibold" style="color: var(--sc-ink)">No tasks this week</p>
                        <p class="text-sm">No checklist items recorded for this period</p>
                    </div>
                @endif

                <a href="{{ route('caregiver.checklists.index', ['elderly' => $selectedElderlyId]) }}" class="sc-btn sc-btn-ghost w-full mt-4 justify-center">
                    <span>Manage checklists</span>
                    <x-lucide-arrow-right class="sc-i w-4 h-4" aria-hidden="true" />
                </a>
            </section>
        </div>

        @endif
    </main>

    @push('scripts')
    <script>
        const charts = {};
        const analyticsData = @json($analyticsData ?? []);
        const medicationSummary = @json($medicationSummary ?? []);
        let currentPeriod = '7days';

        /* ── Theme ─────────────────────────────────────────────────────
           Read palette from CSS tokens for theme and contrast modes. */
        const SERIES_INDEX = { blood_pressure: 1, heart_rate: 2, sugar_level: 3, temperature: 4 };

        function chartTheme(type) {
            const css = getComputedStyle(document.documentElement);
            const v = (name) => css.getPropertyValue(name).trim();
            const hc = document.documentElement.classList.contains('high-contrast');
            return {
                line:         v(`--sc-chart-${SERIES_INDEX[type] || 5}`),
                alert:        v('--sc-chart-3'),
                ok:           v('--sc-ok') || '#2e7d32',
                grid:         v('--sc-chart-grid'),
                axis:         v('--sc-chart-axis'),
                band:         v('--sc-chart-band'),
                surface:      v('--sc-surface'),
                ink:          v('--sc-ink'),
                cardQuiet:    v('--sc-line') || '#e5e7eb',
                highContrast: hc,
            };
        }

        /* ── Range rules ───────────────────────────────────────────────
           Evaluates config thresholds from backend config. */
        function scalarStatus(value, config) {
            const rules = Array.isArray(config?.status_thresholds) ? config.status_thresholds : [];
            for (const r of rules) {
                if (r.default) continue;
                if ('min' in r && value >= r.min) return r;
                if ('max' in r && value <= r.max) return r;
            }
            return rules.find(r => r.default) || { label: 'Normal', tone: 'green' };
        }

        function isOutOfRange(type, value, config, part) {
            if (type === 'blood_pressure') {
                const t = config?.status_thresholds || {};
                const lo = t.low?.[part], hi = t.elevated?.[part];
                return (lo !== undefined && value < lo) || (hi !== undefined && value >= hi);
            }
            return scalarStatus(value, config).tone !== 'green';
        }

        function normalBand(config) {
            const rules = Array.isArray(config?.status_thresholds) ? config.status_thresholds : [];
            const mins = rules.filter(r => 'min' in r).map(r => r.min);
            const maxs = rules.filter(r => 'max' in r).map(r => r.max);
            if (!mins.length || !maxs.length) return null;
            return { from: Math.max(...maxs), to: Math.min(...mins) };
        }

        const healthyBandPlugin = {
            id: 'healthyBand',
            beforeDatasetsDraw(chart, _args, opts) {
                if (!opts || !opts.band || !opts.color) return;
                const { ctx, chartArea, scales: { y } } = chart;
                if (!chartArea || !y) return;
                const top = Math.max(chartArea.top, Math.min(y.getPixelForValue(opts.band.to), chartArea.bottom));
                const bottom = Math.min(chartArea.bottom, Math.max(y.getPixelForValue(opts.band.from), chartArea.top));
                if (bottom <= top) return;
                ctx.save();
                ctx.fillStyle = opts.color;
                ctx.fillRect(chartArea.left, top, chartArea.right - chartArea.left, bottom - top);
                ctx.restore();
            },
        };

        function initCharts() {
            if (typeof window.Chart === 'undefined') {
                console.warn('Chart.js not yet loaded on window.');
                return;
            }

            Object.keys(analyticsData).forEach(type => {
                const data = analyticsData[type];
                ['7days', '30days', '365days'].forEach(period => {
                    const periodData = data[period];
                    if (periodData && periodData.count > 0) {
                        const canvasId = `chart-${type}-${period}`;
                        const ctx = document.getElementById(canvasId);
                        if (ctx && !charts[canvasId]) {
                            charts[canvasId] = createChart(ctx, type, periodData, data.config);
                        }
                    }
                });
            });

            const medCtx = document.getElementById('medicationAdherenceChart');
            if (medCtx && (medicationSummary.totalMedications || 0) > 0 && !charts['medicationAdherence']) {
                charts['medicationAdherence'] = createMedicationAdherenceChart(medCtx);
            }
        }

        function createChart(ctx, type, periodData, config) {
            const metrics = [...(periodData.metrics || [])].sort((a, b) => new Date(a.measured_at) - new Date(b.measured_at));
            const labels = metrics.map(m => {
                const d = new Date(m.measured_at);
                return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            });
            const theme = chartTheme(type);

            const pointStyles = (flags) => ({
                pointStyle: flags.map(f => f ? 'triangle' : 'circle'),
                pointRadius: flags.map(f => f ? 6 : 3),
                pointHoverRadius: flags.map(f => f ? 8 : 5),
            });

            let datasets = [];

            if (type === 'blood_pressure') {
                const systolic = [], diastolic = [];
                metrics.forEach(m => {
                    if (m.value_text) {
                        const parts = m.value_text.split('/');
                        systolic.push(parseInt(parts[0]));
                        diastolic.push(parseInt(parts[1]));
                    }
                });
                const sysFlags = systolic.map(v => isOutOfRange(type, v, config, 'systolic'));
                const diaFlags = diastolic.map(v => isOutOfRange(type, v, config, 'diastolic'));
                datasets = [
                    { label: 'Systolic',  data: systolic,  borderWidth: 2, fill: false, tension: 0.35, _flags: sysFlags, ...pointStyles(sysFlags) },
                    { label: 'Diastolic', data: diastolic, borderWidth: 2, fill: false, tension: 0.35, borderDash: [6, 4], _flags: diaFlags, ...pointStyles(diaFlags) },
                ];
            } else {
                const values = metrics.map(m => parseFloat(m.value));
                const flags = values.map(v => isOutOfRange(type, v, config));
                datasets = [{ label: config?.name || type, data: values, borderWidth: 2, fill: false, tension: 0.35, _flags: flags, ...pointStyles(flags) }];
            }

            const chart = new window.Chart(ctx, {
                type: 'line',
                data: { labels, datasets },
                plugins: [healthyBandPlugin],
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        healthyBand: { band: type === 'blood_pressure' ? null : normalBand(config) },
                        legend: { display: type === 'blood_pressure', position: 'top', labels: { usePointStyle: false, boxWidth: 28, boxHeight: 2, padding: 12, font: { size: 13 } } },
                        tooltip: {
                            padding: 10,
                            cornerRadius: 8,
                            callbacks: {
                                label(item) {
                                    const flag = item.dataset._flags?.[item.dataIndex];
                                    return `${item.dataset.label}: ${item.formattedValue} ${config?.unit || ''}${flag ? ' — out of range' : ''}`;
                                },
                            },
                        },
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            title: { display: !!config?.unit, text: config?.unit || '', font: { size: 12 } },
                            ticks: { font: { size: 12 } },
                        },
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 12 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 6 },
                        },
                    },
                },
            });

            chart._scType = type;
            chart._scConfig = config;
            applyChartTheme(chart);
            return chart;
        }

        function createMedicationAdherenceChart(ctx) {
            const adherenceRate = medicationSummary.adherenceRate || 0;
            const theme = chartTheme('sugar_level');

            const chart = new window.Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Doses Taken', 'Doses Missed'],
                    datasets: [{
                        data: [adherenceRate, Math.max(0, 100 - adherenceRate)],
                        borderWidth: 2,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '72%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 12,
                                font: { size: 12 }
                            }
                        }
                    }
                }
            });

            chart._scType = 'medication_doughnut';
            applyChartTheme(chart);
            return chart;
        }

        function applyChartTheme(chart) {
            if (chart._scType === 'medication_doughnut') {
                const theme = chartTheme('sugar_level');
                const ds = chart.data.datasets[0];
                if (ds) {
                    ds.backgroundColor = [theme.ok, theme.cardQuiet];
                    ds.borderColor = [theme.surface, theme.surface];
                }
                if (chart.options.plugins.legend) {
                    chart.options.plugins.legend.labels.color = theme.ink;
                }
                chart.update('none');
                return;
            }

            const theme = chartTheme(chart._scType);
            chart.data.datasets.forEach(ds => {
                ds.borderColor = theme.line;
                ds.backgroundColor = theme.surface;
                ds.pointBackgroundColor = ds._flags.map(f => f ? theme.alert : theme.surface);
                ds.pointBorderColor = ds._flags.map(f => f ? theme.alert : theme.line);
                ds.pointBorderWidth = 2;
            });
            const y = chart.options.scales.y, x = chart.options.scales.x;
            y.grid.color = theme.grid;
            y.ticks.color = theme.axis;
            if (y.title) y.title.color = theme.axis;
            x.ticks.color = theme.axis;
            if (chart.options.plugins.legend) {
                chart.options.plugins.legend.labels.color = theme.ink;
            }
            if (chart.options.plugins.healthyBand) {
                chart.options.plugins.healthyBand.color = theme.highContrast ? null : theme.band;
            }
            if (chart.options.plugins.tooltip) {
                chart.options.plugins.tooltip.backgroundColor = theme.ink;
                chart.options.plugins.tooltip.titleColor = theme.surface;
                chart.options.plugins.tooltip.bodyColor = theme.surface;
            }
            chart.update('none');
        }

        // Re-read palette when Display menu or system theme changes.
        new MutationObserver(() => {
            Object.values(charts).forEach(applyChartTheme);
        }).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

        function changePeriod(period) {
            currentPeriod = period;
            document.querySelectorAll('.period-btn').forEach(btn => {
                btn.setAttribute('aria-selected', btn.dataset.period === period ? 'true' : 'false');
            });
            document.querySelectorAll('.period-data').forEach(el => {
                el.classList.toggle('hidden', el.dataset.period !== period);
            });
            Object.entries(charts).forEach(([id, chart]) => {
                if (id.endsWith(`-${period}`)) chart.resize();
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            initCharts();
        });
    </script>
    @endpush

    {{-- Caregiver AI Health Analyst Widget --}}
    <x-ai-chat-widget role="caregiver" />

</x-dashboard-layout>
