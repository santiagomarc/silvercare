{{-- ============================================================
     HEALTH ANALYTICS — every vital over a week, a month, a quarter.

     The charts read their palette from the --sc-chart-* tokens at draw
     time and again whenever the theme changes, so a chart follows dark
     mode and high contrast instead of freezing one set of colours.
     Series order is fixed across the app (FRONTEND_DESIGN_SYSTEM §9b):
     blood pressure is series 1, heart rate 2, sugar 3, temperature 4.
     Out-of-range points are drawn as triangles as well as tinted, and
     every chart has its numbers in text beside it.

     Every route, id, data hook and JavaScript function is unchanged.
     ============================================================ --}}

<x-dashboard-layout>
    <x-slot:title>Health Analytics - SilverCare</x-slot:title>
    <x-slot:bodyClass>sc-page min-h-screen</x-slot:bodyClass>

    {{-- Chart.js ships in the app bundle (window.Chart); the CDN copy is gone. --}}

    <x-dashboard-nav
        title="Health Analytics"
        subtitle="Your vitals insights & trends"
        role="elderly"
        :unread-notifications="$unreadNotifications ?? 0"
    />

@php
    // Calculate health score based on latest readings and normal ranges
    $healthScore = 0;
    $healthFactors = [];
    $totalFactors = 0;

    foreach($analyticsData as $type => $data) {
        if (($data['7days']['count'] ?? 0) > 0) {
            $totalFactors++;
            $score = 0;
            $status = 'unknown';

            if ($type === 'blood_pressure') {
                $sys = $data['7days']['systolic_avg'] ?? 120;
                $dia = $data['7days']['diastolic_avg'] ?? 80;
                if ($sys < 120 && $dia < 80) { $score = 100; $status = 'Optimal'; }
                elseif ($sys < 130 && $dia < 85) { $score = 85; $status = 'Normal'; }
                elseif ($sys < 140 && $dia < 90) { $score = 70; $status = 'Elevated'; }
                else { $score = 50; $status = 'High'; }
            } elseif ($type === 'heart_rate') {
                $hr = $data['7days']['avg'] ?? 72;
                if ($hr >= 60 && $hr <= 100) { $score = 100; $status = 'Optimal'; }
                elseif ($hr >= 50 && $hr <= 110) { $score = 80; $status = 'Normal'; }
                else { $score = 60; $status = 'Attention'; }
            } elseif ($type === 'temperature') {
                $temp = $data['7days']['avg'] ?? 36.5;
                if ($temp >= 36.1 && $temp <= 37.2) { $score = 100; $status = 'Normal'; }
                elseif ($temp >= 35.5 && $temp <= 37.8) { $score = 75; $status = 'Mild'; }
                else { $score = 50; $status = 'Attention'; }
            } elseif ($type === 'sugar_level') {
                $sugar = $data['7days']['avg'] ?? 100;
                if ($sugar >= 70 && $sugar <= 100) { $score = 100; $status = 'Optimal'; }
                elseif ($sugar >= 60 && $sugar <= 125) { $score = 80; $status = 'Normal'; }
                else { $score = 60; $status = 'Attention'; }
            }

            $healthScore += $score;
            $healthFactors[$type] = ['score' => $score, 'status' => $status];
        }
    }

    $healthScore = $totalFactors > 0 ? round($healthScore / $totalFactors) : 0;
    $healthLabel = $healthScore >= 90 ? 'Excellent' : ($healthScore >= 75 ? 'Good' : ($healthScore >= 60 ? 'Fair' : 'Needs Attention'));
    $healthTone  = $healthScore >= 75 ? 'ok' : ($healthScore >= 60 ? 'warn' : 'alert');

    // One icon per vital, everywhere — the same map App\View\Components\VitalCard uses.
    $vitalIconMap = [
        'blood_pressure' => 'heart-pulse',
        'sugar_level'    => 'droplet',
        'temperature'    => 'thermometer',
        'heart_rate'     => 'activity',
    ];
    // The detail drawer is built in JavaScript, which cannot render a Blade
    // component, so the four glyphs are rendered once here and handed over.
    $vitalIconSvg = collect($vitalIconMap)
        ->map(fn ($icon) => svg('lucide-' . $icon, 'sc-i w-5 h-5', ['aria-hidden' => 'true'])->toHtml())
        ->all();

    $factorTone = fn ($status) => match ($status) {
        'Optimal', 'Normal' => 'ok',
        'Elevated', 'Mild'  => 'warn',
        default             => 'alert',
    };
@endphp

<main id="main-content" class="sc-app-main">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-10 space-y-6">

        {{-- Toolbar: period selector + Export, and the way back --}}
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-3 flex-wrap">
                {{-- Period selection is a tablist: one of three, always one. --}}
                <div class="sc-tablist" role="tablist" aria-label="Time period">
                    <button type="button" role="tab" onclick="changePeriod('7days')"  class="period-btn sc-tab" aria-selected="true"  data-period="7days">Week</button>
                    <button type="button" role="tab" onclick="changePeriod('30days')" class="period-btn sc-tab" aria-selected="false" data-period="30days">Month</button>
                    <button type="button" role="tab" onclick="changePeriod('90days')" class="period-btn sc-tab" aria-selected="false" data-period="90days">3 Months</button>
                </div>

                <a href="{{ route('elderly.vitals.export') }}" class="sc-btn sc-btn-ghost sc-btn-sm">
                    <x-lucide-download class="sc-i w-4 h-4" aria-hidden="true" />
                    <span>Export</span>
                </a>
            </div>

            <a href="{{ route('dashboard') }}" class="sc-btn sc-btn-ghost sc-btn-sm">
                <x-lucide-arrow-left class="sc-i w-4 h-4" aria-hidden="true" />
                <span>Back to Dashboard</span>
            </a>
        </div>

        {{-- Health score + quick stats --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            <section class="sc-card p-6" aria-labelledby="score-title">
                <div class="flex items-center gap-3 mb-4">
                    <span class="sc-plate sc-plate-sm sc-plate-{{ $healthTone }}">
                        <x-lucide-heart class="sc-i w-5 h-5" aria-hidden="true" />
                    </span>
                    <h2 id="score-title" class="sc-h3">Health score</h2>
                </div>

                <div class="flex items-center gap-6">
                    {{-- Ring: the track is a hairline, the arc is the score's tone.
                         The number sits in the middle, so the ring is decoration. --}}
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
                            <span class="sr-only">Score {{ $healthScore }} out of 100. </span>Based on <span class="sc-num">{{ $totalFactors }}</span> tracked vitals
                        </p>

                        @if($totalFactors > 0)
                        <ul class="flex flex-wrap gap-x-4 gap-y-1.5">
                            @foreach($healthFactors as $type => $factor)
                            <li class="sc-mark sc-mark-{{ $factorTone($factor['status']) }}"><i></i>
                                <x-dynamic-component :component="'lucide-' . ($vitalIconMap[$type] ?? 'stethoscope')" class="sc-i w-4 h-4" aria-hidden="true" />
                                <span class="sr-only">{{ $analyticsData[$type]['config']['name'] ?? $type }}: </span>{{ $factor['status'] }}
                            </li>
                            @endforeach
                        </ul>
                        @endif
                    </div>
                </div>
            </section>

            @php
                $consistencyScore = $totalReadings > 0 ? min(100, round(($readingsThisWeek / 28) * 100)) : 0;
                $quickStats = [
                    ['icon' => 'chart-column', 'label' => 'Total readings', 'value' => $totalReadings],
                    ['icon' => 'trending-up',  'label' => 'This week',      'value' => $readingsThisWeek],
                    ['icon' => 'circle-check', 'label' => 'Consistency',    'value' => $consistencyScore . '%'],
                    ['icon' => 'clock',        'label' => 'Vitals tracked', 'value' => $totalFactors . '/4'],
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

        {{-- AI vitals trend analyzer --}}
        @if($totalFactors > 0)
        <section x-data="vitalsAiAnalyzer()" class="sc-card p-6" aria-labelledby="ai-title">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="sc-plate sc-plate-sm">
                        <x-lucide-sparkles class="sc-i w-5 h-5" aria-hidden="true" />
                    </span>
                    <div>
                        <h2 id="ai-title" class="sc-h3">AI vitals trend analyzer</h2>
                        <p class="text-sm mt-0.5" style="color: var(--sc-muted)">Reads your last week of readings and explains them in plain words</p>
                    </div>
                </div>
                <button type="button" @click="analyze()" :disabled="loading" class="sc-btn sc-btn-primary flex-shrink-0">
                    <template x-if="loading">
                        <x-lucide-loader-circle class="sc-i w-5 h-5 animate-spin" aria-hidden="true" />
                    </template>
                    <span x-text="loading ? 'Analyzing...' : (analysis ? 'Re-analyze' : 'Analyze my trends')">Analyze my trends</span>
                </button>
            </div>

            <div x-show="analysis" x-cloak x-transition class="sc-card-quiet p-5 mt-5" aria-live="polite">
                <div class="max-w-none space-y-2" style="color: var(--sc-body)" x-html="renderMarkdown(analysis)"></div>
                <p class="sc-mark mt-4">
                    <x-lucide-stethoscope class="sc-i w-4 h-4" aria-hidden="true" />
                    <span>This is AI-generated insight, not medical advice.</span>
                </p>
            </div>

            <p x-show="error" x-cloak x-transition x-text="error" role="alert" class="sc-error mt-4"></p>
        </section>

        <script>
        function vitalsAiAnalyzer() {
            return {
                loading: false,
                analysis: null,
                error: null,

                async analyze() {
                    this.loading = true;
                    this.error = null;
                    try {
                        const res = await fetch('{{ route("elderly.ai-assistant.chat") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                message: 'Analyze my health vitals trends from the past week. Focus on what looks good, any concerning patterns, and practical tips to improve. Be specific with my numbers.',
                            }),
                        });
                        const data = await res.json();
                        if (data.response) {
                            this.analysis = data.response;
                        } else {
                            this.error = data.error || 'Unable to generate analysis right now.';
                        }
                    } catch (e) {
                        this.error = 'Network error — please try again.';
                    } finally {
                        this.loading = false;
                    }
                },

                renderMarkdown(text) {
                    if (!text) return '';
                    return text
                        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                        .replace(/\*(.*?)\*/g, '<em>$1</em>')
                        .replace(/^### (.*$)/gm, '<h4 class="font-semibold mt-3 mb-1">$1</h4>')
                        .replace(/^## (.*$)/gm, '<h3 class="sc-h3 mt-3 mb-1">$1</h3>')
                        .replace(/^- (.*$)/gm, '<li class="ml-5">$1</li>')
                        .replace(/(<li.*<\/li>)/gs, '<ul class="list-disc space-y-1">$1</ul>')
                        .replace(/\n{2,}/g, '<br><br>')
                        .replace(/\n/g, '<br>');
                },
            };
        }
        </script>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            {{-- Personalised insights --}}
            @if($totalFactors > 0)
            <section class="sc-card p-6" aria-labelledby="insights-title">
                <div class="flex items-center gap-3 mb-5">
                    <span class="sc-plate sc-plate-sm">
                        <x-lucide-lightbulb class="sc-i w-5 h-5" aria-hidden="true" />
                    </span>
                    <div>
                        <h2 id="insights-title" class="sc-h3">Personalised insights</h2>
                        <p class="text-sm mt-0.5" style="color: var(--sc-muted)">Based on your vitals</p>
                    </div>
                </div>
                <ul class="space-y-3">
                    @foreach($analyticsData as $type => $data)
                        @if(($data['7days']['count'] ?? 0) > 0)
                            @php
                                $insight = '';
                                $insightType = 'info';

                                if ($type === 'blood_pressure') {
                                    $sys = $data['7days']['systolic_avg'] ?? 120;
                                    if ($sys < 120) { $insight = 'Your blood pressure is in the optimal range. Keep up the good work!'; $insightType = 'success'; }
                                    elseif ($sys < 130) { $insight = 'Blood pressure is normal. Consider reducing salt intake for even better results.'; $insightType = 'info'; }
                                    else { $insight = 'Blood pressure is elevated. Regular exercise and stress management can help.'; $insightType = 'warning'; }
                                } elseif ($type === 'heart_rate') {
                                    $hr = $data['7days']['avg'] ?? 72;
                                    if ($hr >= 60 && $hr <= 80) { $insight = 'Your resting heart rate indicates good cardiovascular health!'; $insightType = 'success'; }
                                    elseif ($hr < 60) { $insight = 'Low heart rate detected. This may be normal if you\'re athletic.'; $insightType = 'info'; }
                                    else { $insight = 'Slightly elevated heart rate. Try relaxation techniques.'; $insightType = 'warning'; }
                                } elseif ($type === 'temperature') {
                                    $temp = $data['7days']['avg'] ?? 36.5;
                                    if ($temp >= 36.1 && $temp <= 37.2) { $insight = 'Body temperature is perfectly normal.'; $insightType = 'success'; }
                                    else { $insight = 'Temperature variations detected. Monitor for any symptoms.'; $insightType = 'warning'; }
                                } elseif ($type === 'sugar_level') {
                                    $sugar = $data['7days']['avg'] ?? 100;
                                    if ($sugar >= 70 && $sugar <= 100) { $insight = 'Blood sugar levels are in the healthy range!'; $insightType = 'success'; }
                                    elseif ($sugar < 70) { $insight = 'Blood sugar may be low. Ensure regular, balanced meals.'; $insightType = 'warning'; }
                                    else { $insight = 'Blood sugar is slightly elevated. Consider dietary adjustments.'; $insightType = 'warning'; }
                                }

                                $insightTone = ['success' => 'ok', 'warning' => 'warn', 'info' => ''][$insightType];
                                $insightWord = ['success' => 'Good', 'warning' => 'Watch', 'info' => 'Note'][$insightType];
                            @endphp
                            <li class="sc-card-quiet p-4 flex items-start gap-3">
                                <span class="sc-plate sc-plate-sm flex-shrink-0 {{ $insightTone ? 'sc-plate-' . $insightTone : '' }}">
                                    <x-dynamic-component :component="'lucide-' . ($vitalIconMap[$type] ?? 'stethoscope')" class="sc-i w-5 h-5" aria-hidden="true" />
                                </span>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h3 class="font-semibold" style="color: var(--sc-ink)">{{ $data['config']['name'] }}</h3>
                                        <span class="sc-mark {{ $insightTone ? 'sc-mark-' . $insightTone : '' }}"><i></i>{{ $insightWord }}</span>
                                    </div>
                                    <p class="text-sm mt-0.5" style="color: var(--sc-body)">{{ $insight }}</p>
                                </div>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </section>
            @endif

            {{-- Body metrics --}}
            <section class="sc-card p-6" aria-labelledby="bmi-title">
                <div class="flex items-center gap-3 mb-5">
                    <span class="sc-plate sc-plate-sm">
                        <x-lucide-scale class="sc-i w-5 h-5" aria-hidden="true" />
                    </span>
                    <div>
                        <h2 id="bmi-title" class="sc-h3">Body metrics</h2>
                        <p class="text-sm mt-0.5" style="color: var(--sc-muted)">Weight, height &amp; BMI</p>
                    </div>
                </div>

                @if($bmiData['bmi'])
                    @php
                        $bmiTone = match ($bmiData['category']) {
                            'Normal' => 'ok',
                            'Obese'  => 'alert',
                            default  => 'warn',
                        };
                    @endphp
                    <div class="text-center mb-6">
                        <div class="sc-card-quiet inline-flex flex-col items-center justify-center w-28 h-28 rounded-full mb-3">
                            <span class="sc-stat-value sc-num" style="margin-top:0">{{ $bmiData['bmi'] }}</span>
                            <span class="sc-stat-label">BMI</span>
                        </div>
                        <p><span class="sc-mark sc-mark-{{ $bmiTone }} text-base"><i></i>{{ $bmiData['category'] }}</span></p>
                    </div>

                    <dl class="grid grid-cols-2 gap-3 mb-4">
                        <div class="sc-stat text-center">
                            <x-lucide-ruler class="sc-i w-5 h-5 mx-auto mb-1" style="color: var(--sc-muted)" aria-hidden="true" />
                            <dt class="sc-stat-label">Height</dt>
                            <dd class="sc-num font-bold text-xl" style="color: var(--sc-ink)">{{ $bmiData['height'] }} <span class="sc-stat-unit">cm</span></dd>
                        </div>
                        <div class="sc-stat text-center">
                            <x-lucide-scale class="sc-i w-5 h-5 mx-auto mb-1" style="color: var(--sc-muted)" aria-hidden="true" />
                            <dt class="sc-stat-label">Weight</dt>
                            <dd class="sc-num font-bold text-xl" style="color: var(--sc-ink)">{{ $bmiData['weight'] }} <span class="sc-stat-unit">kg</span></dd>
                        </div>
                    </dl>

                    {{-- The scale: four bands, each named, each toned. --}}
                    <div class="sc-card-quiet p-3">
                        <p class="sc-stat-label text-center mb-2">BMI categories</p>
                        <ul class="grid grid-cols-4 gap-1 text-center text-sm sc-num" style="color: var(--sc-body)">
                            <li><span class="block h-2 rounded-full mb-1" style="background: var(--sc-warn)"></span>Under 18.5</li>
                            <li><span class="block h-2 rounded-full mb-1" style="background: var(--sc-ok)"></span>18.5–24.9</li>
                            <li><span class="block h-2 rounded-full mb-1" style="background: var(--sc-warn)"></span>25–29.9</li>
                            <li><span class="block h-2 rounded-full mb-1" style="background: var(--sc-alert)"></span>30+</li>
                        </ul>
                    </div>
                @else
                    <div class="sc-empty py-8">
                        <x-lucide-scale class="sc-i w-8 h-8" style="color: var(--sc-muted)" aria-hidden="true" />
                        <p class="font-semibold" style="color: var(--sc-ink)">No body metrics yet</p>
                        <p class="text-sm">Add your weight and height to your profile to see your BMI.</p>
                        <a href="{{ route('profile.edit') }}" class="sc-btn sc-btn-ghost sc-btn-sm">Update profile</a>
                    </div>
                @endif
            </section>
        </div>

        {{-- Steps --}}
        <section class="sc-card p-6" aria-labelledby="steps-title">
            <div class="flex flex-col md:flex-row md:items-center gap-6">
                <div class="flex items-center gap-3 flex-shrink-0">
                    <span class="sc-plate sc-plate-sm">
                        <x-lucide-footprints class="sc-i w-5 h-5" aria-hidden="true" />
                    </span>
                    <div>
                        <h2 id="steps-title" class="sc-h3">Daily steps</h2>
                        <p class="text-sm mt-0.5" style="color: var(--sc-muted)">Track your activity</p>
                    </div>
                </div>

                @if($stepsData['today'])
                    @php
                        $stepsPercent = min(100, ($stepsData['today']['value'] / $stepsData['today']['goal']) * 100);
                        $strokeOffset = 264 - (264 * $stepsPercent / 100);
                    @endphp
                    <div class="flex-shrink-0">
                        <div class="relative w-20 h-20" role="img" aria-label="{{ round($stepsPercent) }} percent of today's step goal">
                            <svg class="w-full h-full -rotate-90" viewBox="0 0 100 100" aria-hidden="true">
                                <circle cx="50" cy="50" r="42" stroke="var(--sc-line)" stroke-width="8" fill="none"/>
                                <circle cx="50" cy="50" r="42" stroke="var(--sc-ok)" stroke-width="8" fill="none"
                                    stroke-dasharray="264"
                                    stroke-dashoffset="{{ $strokeOffset }}"
                                    stroke-linecap="round"/>
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="sc-num font-bold" style="color: var(--sc-ink)">{{ round($stepsPercent) }}%</span>
                            </div>
                        </div>
                    </div>

                    <dl class="flex-1 grid grid-cols-3 gap-3">
                        <div class="sc-stat text-center">
                            <dt class="sc-stat-label">Today</dt>
                            <dd class="sc-num font-bold text-xl" style="color: var(--sc-ink)">{{ number_format($stepsData['today']['value']) }}</dd>
                        </div>
                        <div class="sc-stat text-center">
                            <dt class="sc-stat-label">Weekly</dt>
                            <dd class="sc-num font-bold text-xl" style="color: var(--sc-ink)">{{ number_format($stepsData['weeklyTotal']) }}</dd>
                        </div>
                        <div class="sc-stat text-center">
                            <dt class="sc-stat-label">Daily avg</dt>
                            <dd class="sc-num font-bold text-xl" style="color: var(--sc-ink)">{{ number_format($stepsData['weeklyAvg']) }}</dd>
                        </div>
                    </dl>

                    @if($stepsData['today']['source'] === 'google_fit')
                    <div class="flex-shrink-0">
                        <span class="sc-badge sc-badge-brand">
                            <x-lucide-link class="sc-i w-4 h-4" aria-hidden="true" />
                            Google Fit
                        </span>
                    </div>
                    @endif
                @else
                    <div class="flex-1 sc-empty py-6">
                        <p class="font-semibold" style="color: var(--sc-ink)">No steps data available</p>
                        <p class="text-sm">Connect Google Fit to track steps</p>
                    </div>
                @endif
            </div>
        </section>

        {{-- Per-vital analytics --}}
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

                    <button type="button" onclick="openDetailModal('{{ $type }}')" class="sc-btn sc-btn-ghost sc-btn-sm flex-shrink-0">
                        <x-lucide-maximize-2 class="sc-i w-4 h-4" aria-hidden="true" />
                        Details<span class="sr-only"> for {{ $data['config']['name'] }}</span>
                    </button>
                </div>

                <div class="p-5">
                    @foreach(['7days', '30days', '90days'] as $period)
                    @php
                        $p = $data[$period] ?? [];
                        $periodWords = ['7days' => 'last 7 days', '30days' => 'last 30 days', '90days' => 'last 90 days'][$period];
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

                            <dl class="grid grid-cols-3 gap-3 mb-4">
                                @if($type === 'blood_pressure')
                                    <div class="sc-card-quiet p-3 text-center">
                                        <dt class="sc-stat-label">Systolic avg</dt>
                                        <dd class="sc-num font-bold text-xl" style="color: var(--sc-ink)">{{ $p['systolic_avg'] ?? '-' }}</dd>
                                    </div>
                                    <div class="sc-card-quiet p-3 text-center">
                                        <dt class="sc-stat-label">Diastolic avg</dt>
                                        <dd class="sc-num font-bold text-xl" style="color: var(--sc-ink)">{{ $p['diastolic_avg'] ?? '-' }}</dd>
                                    </div>
                                    <div class="sc-card-quiet p-3 text-center">
                                        <dt class="sc-stat-label">Readings</dt>
                                        <dd class="sc-num font-bold text-xl" style="color: var(--sc-ink)">{{ $p['count'] }}</dd>
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
                                    <div class="sc-card-quiet p-3 text-center">
                                        <dt class="sc-stat-label">Average</dt>
                                        <dd class="sc-num font-bold text-xl" style="color: var(--sc-ink)">{{ $p['avg'] ?? '-' }}</dd>
                                    </div>
                                    <div class="sc-card-quiet p-3 text-center">
                                        <dt class="sc-stat-label">Min / Max</dt>
                                        <dd class="sc-num font-bold text-lg" style="color: var(--sc-ink)">{{ $p['min'] ?? '-' }}<span style="color: var(--sc-muted)">/</span>{{ $p['max'] ?? '-' }}</dd>
                                    </div>
                                    <div class="sc-card-quiet p-3 text-center">
                                        <dt class="sc-stat-label">Trend</dt>
                                        {{-- An arrow and a word: the direction is never colour alone. --}}
                                        <dd class="sc-mark {{ $trendTone ? 'sc-mark-' . $trendTone : '' }} justify-center text-base mt-1">
                                            <x-dynamic-component :component="'lucide-' . $trendIcon" class="sc-i w-5 h-5" aria-hidden="true" />
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
                                <p class="text-sm">Start recording to see analytics</p>
                                <a href="{{ route('elderly.vitals.' . $type) }}" class="sc-btn sc-btn-ghost sc-btn-sm">
                                    <x-lucide-plus class="sc-i w-4 h-4" aria-hidden="true" />
                                    Add reading
                                </a>
                            </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </section>
            @endforeach
        </div>
    </div>
</main>

{{-- Detail drawer. The scrim is the backdrop; the panel slides in from the
     right with a transform, which is one of the two things we animate. --}}
<div id="detailModal" class="fixed inset-0 z-[60] hidden">
    <div class="sc-scrim" onclick="closeDetailModal()"></div>
    <div class="absolute right-0 top-0 bottom-0 w-full max-w-xl z-[70] overflow-y-auto translate-x-full transition-transform duration-300 ease-out"
         style="background: var(--sc-surface); border-left: 1px solid var(--sc-line); box-shadow: var(--sc-sh-lg)"
         id="detailDrawer"
         role="dialog"
         aria-modal="true"
         aria-labelledby="detailModalTitle">
        <div id="detailModalContent"></div>
    </div>
</div>

@push('scripts')
<script>
    const charts = {};
    const analyticsData = @json($analyticsData);
    const vitalIconSvg = @json($vitalIconSvg);
    let currentPeriod = '7days';

    /* ── Theme ─────────────────────────────────────────────────────
       Read the palette from CSS so the chart follows light, dark and
       high contrast. Series order is fixed across the app. */
    const SERIES_INDEX = { blood_pressure: 1, heart_rate: 2, sugar_level: 3, temperature: 4 };

    function chartTheme(type) {
        const css = getComputedStyle(document.documentElement);
        const v = (name) => css.getPropertyValue(name).trim();
        const hc = document.documentElement.classList.contains('high-contrast');
        return {
            line:  v(`--sc-chart-${SERIES_INDEX[type] || 5}`),
            alert: v('--sc-chart-3'),
            grid:  v('--sc-chart-grid'),
            axis:  v('--sc-chart-axis'),
            band:  v('--sc-chart-band'),
            surface: v('--sc-surface'),
            ink:   v('--sc-ink'),
            highContrast: hc,
        };
    }

    /* ── Range rules ───────────────────────────────────────────────
       The same thresholds config/vitals.php uses, evaluated in order:
       the first rule that matches wins, the default is "Normal". */
    function scalarStatus(value, config) {
        const rules = Array.isArray(config.status_thresholds) ? config.status_thresholds : [];
        for (const r of rules) {
            if (r.default) continue;
            if ('min' in r && value >= r.min) return r;
            if ('max' in r && value <= r.max) return r;
        }
        return rules.find(r => r.default) || { label: 'Normal', tone: 'green' };
    }
    function isOutOfRange(type, value, config, part) {
        if (type === 'blood_pressure') {
            const t = config.status_thresholds || {};
            const lo = t.low?.[part], hi = t.elevated?.[part];
            return (lo !== undefined && value < lo) || (hi !== undefined && value >= hi);
        }
        return scalarStatus(value, config).tone !== 'green';
    }
    // The healthy range behind a scalar series: above the highest "low"
    // rule, below the lowest "high" rule.
    function normalBand(config) {
        const rules = Array.isArray(config.status_thresholds) ? config.status_thresholds : [];
        const mins = rules.filter(r => 'min' in r).map(r => r.min);
        const maxs = rules.filter(r => 'max' in r).map(r => r.max);
        if (!mins.length || !maxs.length) return null;
        return { from: Math.max(...maxs), to: Math.min(...mins) };
    }

    // Paints the healthy range as a soft band behind the line.
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
        Object.keys(analyticsData).forEach(type => {
            const data = analyticsData[type];
            ['7days', '30days', '90days'].forEach(period => {
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
    }

    function createChart(ctx, type, periodData, config) {
        // Oldest to newest, left to right.
        const metrics = [...(periodData.metrics || [])].sort((a, b) => new Date(a.measured_at) - new Date(b.measured_at));
        const labels = metrics.map(m => {
            const d = new Date(m.measured_at);
            return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        });
        const theme = chartTheme(type);

        // Out-of-range points get a triangle and a larger radius as well as
        // the alert hue, so the shape carries the meaning on its own.
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
            // Same hue, different dash: the two lines are one vital.
            datasets = [
                { label: 'Systolic',  data: systolic,  borderWidth: 2, fill: false, tension: 0.35, _flags: sysFlags, ...pointStyles(sysFlags) },
                { label: 'Diastolic', data: diastolic, borderWidth: 2, fill: false, tension: 0.35, borderDash: [6, 4], _flags: diaFlags, ...pointStyles(diaFlags) },
            ];
        } else {
            const values = metrics.map(m => parseFloat(m.value));
            const flags = values.map(v => isOutOfRange(type, v, config));
            datasets = [{ label: config.name, data: values, borderWidth: 2, fill: false, tension: 0.35, _flags: flags, ...pointStyles(flags) }];
        }

        const chart = new Chart(ctx, {
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
                                return `${item.dataset.label}: ${item.formattedValue} ${config.unit}${flag ? ' — out of range' : ''}`;
                            },
                        },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        title: { display: true, text: config.unit, font: { size: 12 } },
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

    function applyChartTheme(chart) {
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
        y.title.color = theme.axis;
        x.ticks.color = theme.axis;
        chart.options.plugins.legend.labels.color = theme.ink;
        chart.options.plugins.healthyBand.color = theme.highContrast ? null : theme.band;
        chart.options.plugins.tooltip.backgroundColor = theme.ink;
        chart.options.plugins.tooltip.titleColor = theme.surface;
        chart.options.plugins.tooltip.bodyColor = theme.surface;
        chart.update('none');
    }

    // Re-read the palette when the Display menu changes the theme.
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
        // A canvas that was hidden at draw time has no size; give it one now.
        Object.entries(charts).forEach(([id, chart]) => { if (id.endsWith(`-${period}`)) chart.resize(); });
    }

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    function openDetailModal(type) {
        const data = analyticsData[type];
        const periodData = data[currentPeriod];
        const modal = document.getElementById('detailModal');
        const content = document.getElementById('detailModalContent');
        const icon = vitalIconSvg[type] || '';

        let historyHtml = '';
        if (periodData && periodData.metrics && periodData.metrics.length > 0) {
            const rows = [...periodData.metrics].sort((a, b) => new Date(b.measured_at) - new Date(a.measured_at));
            historyHtml = rows.map(m => {
                const value = type === 'blood_pressure' ? m.value_text : `${parseFloat(m.value).toFixed(type === 'temperature' ? 1 : 0)} ${data.config.unit}`;
                const date = new Date(m.measured_at);
                const dateStr = date.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
                const timeStr = date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
                const source = m.source === 'google_fit' ? '<span class="sc-badge sc-badge-brand">Google Fit</span>' : '<span class="sc-badge">Manual</span>';
                return `
                    <li class="flex items-center justify-between gap-3 py-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="sc-plate sc-plate-sm flex-shrink-0">${icon}</span>
                            <div class="min-w-0">
                                <p class="sc-num font-bold text-lg" style="color: var(--sc-ink)">${escapeHtml(value)}</p>
                                <p class="text-sm sc-num" style="color: var(--sc-muted)">${escapeHtml(dateStr)} · ${escapeHtml(timeStr)}</p>
                            </div>
                        </div>
                        ${source}
                    </li>
                `;
            }).join('');
        } else {
            historyHtml = '<li class="sc-empty py-8"><p class="font-semibold" style="color: var(--sc-ink)">No readings found</p><p class="text-sm">for this period</p></li>';
        }

        const cell = (label, value, sub) => `
            <div class="sc-card-quiet p-4 text-center">
                <dt class="sc-stat-label">${label}</dt>
                <dd class="sc-num font-bold text-2xl" style="color: var(--sc-ink)">${escapeHtml(value ?? '-')}</dd>
                ${sub ? `<dd class="text-sm sc-num mt-1" style="color: var(--sc-muted)">${escapeHtml(sub)}</dd>` : ''}
            </div>`;

        const statsHtml = type === 'blood_pressure' ? `
            <dl class="grid grid-cols-2 gap-3">
                ${cell('Systolic avg', periodData?.systolic_avg || '-', `${periodData?.systolic_min || '-'} – ${periodData?.systolic_max || '-'}`)}
                ${cell('Diastolic avg', periodData?.diastolic_avg || '-', `${periodData?.diastolic_min || '-'} – ${periodData?.diastolic_max || '-'}`)}
            </dl>
        ` : `
            <dl class="grid grid-cols-3 gap-3">
                ${cell('Average', periodData?.avg || '-')}
                ${cell('Minimum', periodData?.min || '-')}
                ${cell('Maximum', periodData?.max || '-')}
            </dl>
        `;

        const periodLabel = currentPeriod === '7days' ? 'Last 7 days' : (currentPeriod === '30days' ? 'Last 30 days' : 'Last 90 days');

        content.innerHTML = `
            <div class="sticky top-0 z-10 p-5 flex items-center justify-between gap-3" style="background: var(--sc-surface); border-bottom: 1px solid var(--sc-line)">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="sc-plate flex-shrink-0">${icon}</span>
                    <div class="min-w-0">
                        <h2 id="detailModalTitle" class="sc-dialog-title">${escapeHtml(data.config.name)}</h2>
                        <p class="text-sm" style="color: var(--sc-muted)">${periodLabel} · <span class="sc-num">${periodData?.count || 0}</span> readings</p>
                    </div>
                </div>
                <button type="button" onclick="closeDetailModal()" class="sc-icon-btn flex-shrink-0">
                    <svg class="sc-i w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    <span class="sr-only">Close</span>
                </button>
            </div>

            <div class="p-5 space-y-6">
                <section aria-labelledby="detail-stats-title">
                    <h3 id="detail-stats-title" class="sc-eyebrow mb-3">Statistics</h3>
                    ${statsHtml}
                </section>

                <a href="${window.location.origin}/my-vitals/${type}" class="sc-btn sc-btn-primary w-full">
                    <svg class="sc-i w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Add new reading
                </a>

                <section aria-labelledby="detail-history-title">
                    <h3 id="detail-history-title" class="sc-eyebrow mb-3">All readings</h3>
                    <ul class="sc-divide max-h-[400px] overflow-y-auto">
                        ${historyHtml}
                    </ul>
                </section>
            </div>
        `;

        modal.classList.remove('hidden');
        setTimeout(() => {
            document.getElementById('detailDrawer').classList.remove('translate-x-full');
            content.querySelector('button')?.focus();
        }, 10);
    }

    function closeDetailModal() {
        const modal = document.getElementById('detailModal');
        const drawer = document.getElementById('detailDrawer');
        drawer.classList.add('translate-x-full');
        setTimeout(() => { modal.classList.add('hidden'); }, 300);
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeDetailModal();
    });

    document.addEventListener('DOMContentLoaded', () => {
        initCharts();
    });
</script>
@endpush

<x-ai-chat-widget />

</x-dashboard-layout>
