@php
    // M2 — caregiver-facing threshold editor. Previously this route returned
    // raw JSON, so there was no way to configure per-patient thresholds through
    // the interface at all.
    $metricLabels = [
        'blood_pressure' => ['name' => 'Blood Pressure', 'unit' => 'mmHg', 'icon' => '🩺'],
        'sugar_level'    => ['name' => 'Blood Sugar',    'unit' => 'mg/dL', 'icon' => '🩸'],
        'temperature'    => ['name' => 'Temperature',    'unit' => '°C',   'icon' => '🌡️'],
        'heart_rate'     => ['name' => 'Heart Rate',     'unit' => 'BPM',  'icon' => '💓'],
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

<x-dashboard-layout>
    <x-slot:title>Alert Thresholds - SilverCare</x-slot:title>

    <x-dashboard-nav title="Alert Thresholds" role="caregiver" />

    <main class="max-w-4xl mx-auto px-6 lg:px-12 py-6">
        <div class="mb-6">
            <a href="{{ route('caregiver.dashboard') }}"
               class="text-sm font-bold text-blue-700 dark:text-blue-400 hover:underline">← Back to dashboard</a>

            <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white mt-3">
                Alert thresholds for {{ $patient->user?->name ?? 'your patient' }}
            </h1>
            <p class="text-sm font-medium text-slate-600 dark:text-slate-300 mt-1 max-w-2xl">
                SilverCare raises an alert when a reading crosses one of these values. The defaults are
                general clinical guidance — adjust them only on the advice of your patient's doctor,
                whose targets may differ from the general population.
            </p>
        </div>

        <div class="space-y-5">
            @foreach($metrics as $metricType => $config)
                @php $meta = $metricLabels[$metricType] ?? ['name' => $metricType, 'unit' => '', 'icon' => '•']; @endphp

                <section
                    x-data="thresholdEditor({
                        metricType: @js($metricType),
                        thresholds: @js($config['thresholds']),
                        defaults: @js($config['default_thresholds']),
                        isCustom: {{ $config['is_custom'] ? 'true' : 'false' }},
                        endpoint: @js(route('caregiver.patients.thresholds.update', $patient)),
                    })"
                    class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/60 p-5 shadow-sm"
                >
                    <div class="flex items-center justify-between gap-3 mb-4 pb-3 border-b border-slate-100 dark:border-slate-700">
                        <div class="flex items-center gap-3">
                            <span class="text-xl" aria-hidden="true">{{ $meta['icon'] }}</span>
                            <div>
                                <h2 class="text-base font-extrabold text-slate-900 dark:text-white">{{ $meta['name'] }}</h2>
                                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">Measured in {{ $meta['unit'] }}</p>
                            </div>
                        </div>

                        <span class="text-xs font-black px-2.5 py-1 rounded-md"
                              x-bind:class="isCustom
                                ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-200'
                                : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'"
                              x-text="isCustom ? 'Custom' : 'Clinical default'"></span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach($config['thresholds'] as $field => $value)
                            <div>
                                <label for="{{ $metricType }}_{{ $field }}"
                                       class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                    {{ $fieldLabels[$field] ?? \Illuminate\Support\Str::headline($field) }}
                                </label>
                                <input
                                    id="{{ $metricType }}_{{ $field }}"
                                    type="number"
                                    step="0.1"
                                    inputmode="decimal"
                                    x-model.number="thresholds[@js($field)]"
                                    class="w-full min-h-[44px] rounded-xl border-2 border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white px-3 py-2 text-sm font-semibold focus:border-blue-500 focus:ring-blue-500"
                                >
                                <p class="text-[11px] font-medium text-slate-400 mt-1">
                                    Default: {{ $config['default_thresholds'][$field] ?? '—' }}
                                </p>
                            </div>
                        @endforeach
                    </div>

                    <p x-show="message" x-cloak x-text="message"
                       class="mt-4 text-sm font-bold rounded-xl px-4 py-2.5"
                       x-bind:class="ok ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200'"></p>

                    <div class="flex flex-wrap items-center gap-3 mt-5">
                        <button type="button" x-on:click="save()" x-bind:disabled="busy"
                                class="min-h-[44px] px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-extrabold shadow disabled:opacity-60">
                            <span x-show="!busy">Save thresholds</span>
                            <span x-show="busy">Saving…</span>
                        </button>

                        <button type="button" x-on:click="resetToDefault()" x-bind:disabled="busy || !isCustom"
                                class="min-h-[44px] px-5 py-2.5 rounded-xl border-2 border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 text-sm font-bold hover:bg-slate-50 dark:hover:bg-slate-700 disabled:opacity-40">
                            Reset to clinical default
                        </button>
                    </div>
                </section>
            @endforeach
        </div>
    </main>
</x-dashboard-layout>
