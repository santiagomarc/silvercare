{{-- ============================================================
    VitalCard — Displays the latest vital measurement.
    Entire card routes to the full vital page for history + recording.
    ============================================================ --}}

@php
    $measuredAt = $metricData['measured_at'] ?? null;
    if ($measuredAt && ! $measuredAt instanceof \Carbon\CarbonInterface) {
        $measuredAt = \Carbon\Carbon::parse($measuredAt);
    }
    $hasRecordedValue = (bool) ($metricData['recorded'] ?? false);

    $surfaceTint = match ($type) {
        'blood_pressure' => 'from-rose-50/55 via-white/95 to-white/95',
        'sugar_level' => 'from-cyan-50/55 via-white/95 to-white/95',
        'temperature' => 'from-amber-50/55 via-white/95 to-white/95',
        'heart_rate' => 'from-pink-50/55 via-white/95 to-white/95',
        default => 'from-slate-50/55 via-white/95 to-white/95',
    };

    $sourceLabel = ($metricData['source'] ?? 'manual') === 'google_fit' ? 'Google Fit' : 'Manual Entry';
    $sourceClasses = ($metricData['source'] ?? 'manual') === 'google_fit'
        ? 'bg-blue-100 text-blue-700'
        : 'bg-slate-100 text-slate-600';
@endphp

<a href="{{ $route }}"
    class="vital-card card-glass relative block h-48 overflow-hidden rounded-[1.75rem] p-6 transition-all hover:-translate-y-1 hover:shadow-[0_20px_34px_-26px_rgba(15,23,42,0.18)]"
   aria-label="Open {{ $title }} details"
   data-type="{{ $type }}">

     <div class="absolute inset-0 rounded-[1.75rem] bg-gradient-to-br {{ $surfaceTint }} opacity-95" aria-hidden="true"></div>
     <div class="absolute left-3 top-3 h-20 w-20 rounded-full {{ $bg }} opacity-30 blur-2xl" aria-hidden="true"></div>
    <div class="absolute inset-0 rounded-[1.75rem] ring-1 ring-inset ring-white/70" aria-hidden="true"></div>

    <div class="relative z-10 flex h-full flex-col justify-between">
        <div class="flex justify-between items-start gap-3">
        <div class="w-12 h-12 {{ $bg }} rounded-2xl flex items-center justify-center {{ $color }} group-hover:scale-110 transition-transform shadow-[inset_0_1px_0_rgba(255,255,255,0.7)]">
            {!! $icon !!}
        </div>
        <div class="flex items-center gap-1.5">
            @if($hasRecordedValue)
                <span class="badge text-xs {{ $sourceClasses }}">{{ $sourceLabel }}</span>
                @if($status)
                    <span class="badge text-xs {{ $status['bg'] }} {{ $status['text'] }}">{{ $status['label'] }}</span>
                @endif
            @endif
        </div>

        </div>

        <div>
            <h4 class="font-extrabold text-gray-500 text-sm uppercase tracking-wide mb-1">{{ $title }}</h4>

            @if($hasRecordedValue)
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-black text-gray-900">
                        {{ $type === 'blood_pressure' ? $metricData['value_text'] : ($type === 'temperature' ? number_format($metricData['value'], 1) : intval($metricData['value'])) }}
                    </span>
                    <span class="text-base font-bold text-gray-400">{{ $unit }}</span>
                </div>
                <p class="mt-1 text-sm font-bold text-gray-500">
                    {{ $measuredAt?->format('g:i A') }}
                </p>
                <p class="mt-1 text-xs font-semibold text-green-700">Recorded today</p>
            @else
                <div class="mt-2 rounded-2xl border-2 border-dashed border-gray-300 bg-white/70 px-4 py-4 text-center text-sm font-bold text-gray-500 transition-colors group-hover:border-gray-400 group-hover:text-gray-700">
                    <span aria-hidden="true">+</span> Record Now
                </div>
            @endif
        </div>
    </div>
</a>
