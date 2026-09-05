@php
    // M2 — caregiver-facing threshold editor.
    $metricLabels = [
        'blood_pressure' => ['name' => 'Blood Pressure', 'unit' => 'mmHg'],
        'sugar_level'    => ['name' => 'Blood Sugar',    'unit' => 'mg/dL'],
        'temperature'    => ['name' => 'Temperature',    'unit' => '°C'],
        'heart_rate'     => ['name' => 'Heart Rate',     'unit' => 'BPM'],
    ];

    $fieldLabels = [
        'critical_systolic_high'  => 'Critical systolic — high',
        'critical_systolic_low'   => 'Critical systolic — low',
        'critical_diastolic_high' => 'Critical diastolic — high',
        'critical_diastolic_low'  => 'Critical diastolic — low',
        'warning_systolic_high'   => 'Warning systolic — high',
        'warning_systolic_low'    => 'Warning systolic — low',
        'warning_diastolic_high'  => 'Warning diastolic — high',
        'warning_diastolic_low'   => 'Warning diastolic — low',
        'critical_high'           => 'Critical — high',
        'critical_low'            => 'Critical — low',
        'warning_high'            => 'Warning — high',
        'warning_low'             => 'Warning — low',
    ];
@endphp

<x-dashboard-layout sc>
    <x-slot:title>Alert Thresholds - SilverCare</x-slot:title>

    <x-dashboard-nav
        title="Alert Thresholds"
        subtitle="Per-patient clinical guidance and alert boundaries"
        role="caregiver"
        :show-back="true"
        :back-url="route('caregiver.dashboard')"
        back-label="Back to Dashboard"
    />

    <main id="main-content" class="sc-app-main">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-12 space-y-6">

            <div class="mb-6">
                <div class="mb-3">
                    <a href="{{ route('caregiver.dashboard') }}"
                       class="sc-btn sc-btn-ghost sc-btn-sm">
                        <x-lucide-arrow-left class="sc-i w-4 h-4" aria-hidden="true" />
                        <span>Back to Dashboard</span>
                    </a>
                </div>

                <h2 class="sc-h3">
                    Alert thresholds for {{ $patient->user?->name ?? 'your patient' }}
                </h2>
                <p class="text-sm mt-1 max-w-2xl" style="color:var(--sc-body)">
                    SilverCare raises an alert when a reading crosses one of these values. The defaults are
                    general clinical guidance — adjust them only on the advice of your patient's doctor,
                    whose targets may differ from the general population.
                </p>
            </div>

            <div class="space-y-6">
                @foreach($metrics as $metricType => $config)
                    @php $meta = $metricLabels[$metricType] ?? ['name' => \Illuminate\Support\Str::headline($metricType), 'unit' => '']; @endphp

                    <section
                        x-data="thresholdEditor({
                            metricType: @js($metricType),
                            thresholds: @js($config['thresholds']),
                            defaults: @js($config['default_thresholds']),
                            isCustom: {{ $config['is_custom'] ? 'true' : 'false' }},
                            endpoint: @js(route('caregiver.patients.thresholds.update', $patient)),
                        })"
                        class="sc-card p-5 sm:p-6 md:p-8"
                        aria-labelledby="{{ $metricType }}-heading"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-6 pb-4" style="border-bottom: 1px solid var(--sc-line)">
                            <div class="flex items-center gap-3">
                                <div class="sc-plate sc-plate-sm">
                                    @if($metricType === 'blood_pressure')
                                        <x-lucide-activity class="sc-i w-5 h-5" aria-hidden="true" />
                                    @elseif($metricType === 'sugar_level')
                                        <x-lucide-droplet class="sc-i w-5 h-5" aria-hidden="true" />
                                    @elseif($metricType === 'temperature')
                                        <x-lucide-thermometer class="sc-i w-5 h-5" aria-hidden="true" />
                                    @elseif($metricType === 'heart_rate')
                                        <x-lucide-heart-pulse class="sc-i w-5 h-5" aria-hidden="true" />
                                    @else
                                        <x-lucide-activity class="sc-i w-5 h-5" aria-hidden="true" />
                                    @endif
                                </div>
                                <div>
                                    <h3 id="{{ $metricType }}-heading" class="sc-h3 text-base">{{ $meta['name'] }}</h3>
                                    <p class="text-xs sc-num" style="color:var(--sc-muted)">Measured in {{ $meta['unit'] }}</p>
                                </div>
                            </div>

                            <span class="sc-badge"
                                  x-bind:class="isCustom ? 'sc-badge-brand' : ''"
                                  x-text="isCustom ? 'Custom' : 'Clinical default'"></span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            @foreach($config['thresholds'] as $field => $value)
                                <div class="sc-field !mb-0">
                                    <label for="{{ $metricType }}_{{ $field }}"
                                           class="sc-label text-sm font-semibold">
                                        {{ $fieldLabels[$field] ?? \Illuminate\Support\Str::headline($field) }}
                                    </label>
                                    <input
                                        id="{{ $metricType }}_{{ $field }}"
                                        type="number"
                                        step="0.1"
                                        inputmode="decimal"
                                        x-model.number="thresholds[@js($field)]"
                                        class="sc-input sc-num"
                                    >
                                    <p class="sc-help sc-num">
                                        Default: {{ $config['default_thresholds'][$field] ?? '—' }}
                                    </p>
                                </div>
                            @endforeach
                        </div>

                        <p x-show="message" x-cloak x-text="message"
                           role="status"
                           class="mt-4 sc-flash sc-num"
                           x-bind:class="ok ? 'sc-flash-ok' : 'sc-flash-alert'"></p>

                        <div class="flex flex-wrap items-center gap-3 mt-6 pt-4" style="border-top: 1px solid var(--sc-line)">
                            <button type="button" x-on:click="save()" x-bind:disabled="busy"
                                    class="sc-btn sc-btn-primary sc-btn-sm w-full sm:w-auto">
                                <span x-show="!busy">Save thresholds</span>
                                <span x-show="busy" x-cloak>Saving…</span>
                            </button>

                            <button type="button" x-on:click="resetToDefault()" x-bind:disabled="busy || !isCustom"
                                    class="sc-btn sc-btn-ghost sc-btn-sm w-full sm:w-auto">
                                Reset to clinical default
                            </button>
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
    </main>
</x-dashboard-layout>
