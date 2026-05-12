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
    
    // Fallback values if none passed initially
    $initValue = '';
    if ($hasRecordedValue) {
        $initValue = $type === 'blood_pressure' 
            ? ($metricData['value_text'] ?? '') 
            : ($type === 'temperature' ? number_format($metricData['value'], 1) : intval($metricData['value']));
    }
    $initTime = $measuredAt ? $measuredAt->format('g:i A') : '';
@endphp

<a href="{{ $route }}"
   x-data="{
       hasRecorded: {{ $hasRecordedValue ? 'true' : 'false' }},
       value: '{{ addslashes($initValue) }}',
       measuredAt: '{{ addslashes($initTime) }}',
       unit: '{{ addslashes($unit) }}',
       title: '{{ addslashes($title) }}'
   }"
   @vital-recorded.window="
       if ($event.detail.type === '{{ $type }}' && $event.detail.metric) {
           hasRecorded = true;
           value = $event.detail.metric.display_value;
           
           let d = new Date($event.detail.metric.measured_at);
           measuredAt = d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit', hour12: true });
       }
   "
   class="vital-card card-glass relative block min-h-[14rem] overflow-hidden rounded-[2rem] p-6 transition-all hover:-translate-y-1 hover:shadow-[0_20px_34px_-26px_rgba(15,23,42,0.18)]"
   aria-label="Open {{ $title }} details"
   data-type="{{ $type }}">

     <div class="absolute inset-0 rounded-[2rem] bg-gradient-to-br {{ $surfaceTint }} opacity-95" aria-hidden="true"></div>
     <div class="absolute left-3 top-3 h-24 w-24 rounded-full {{ $bg }} opacity-30 blur-2xl" aria-hidden="true"></div>
    <div class="absolute inset-0 rounded-[2rem] ring-1 ring-inset ring-white/70" aria-hidden="true"></div>

    <div class="relative z-10 flex h-full flex-col">
        {{-- Header: Icon + Badges --}}
        <div class="flex justify-between items-start gap-4 mb-4">
            <div class="w-14 h-14 {{ $bg }} rounded-2xl flex items-center justify-center {{ $color }} group-hover:scale-110 transition-transform shadow-[inset_0_1px_0_rgba(255,255,255,0.7)] text-2xl flex-shrink-0">
                {!! $icon !!}
            </div>
            
            <div class="flex flex-wrap justify-end items-center gap-1.5" x-show="hasRecorded" x-cloak>
                <span class="badge px-2.5 py-1 text-[10px] sm:text-xs font-bold uppercase tracking-wider rounded-lg shadow-sm {{ $sourceClasses }}">{{ $sourceLabel }}</span>
                @if($status)
                    <span class="badge px-2.5 py-1 text-[10px] sm:text-xs font-bold uppercase tracking-wider rounded-lg shadow-sm {{ $status['bg'] }} {{ $status['text'] }}">{{ $status['label'] }}</span>
                @endif
            </div>
        </div>

        {{-- Body: Metrics --}}
        <div class="mt-auto pt-2">
            <h4 class="font-extrabold text-slate-500 text-sm md:text-base tracking-wide mb-1.5" x-text="title"></h4>

            <div x-show="hasRecorded" x-cloak>
                <div class="flex items-baseline gap-2 flex-wrap">
                    <span class="text-4xl md:text-[2.75rem] leading-none font-black text-slate-900 tracking-tight" x-text="value"></span>
                    <span class="text-base md:text-lg font-extrabold text-slate-400" x-text="unit"></span>
                </div>
                <div class="flex items-center gap-2 mt-3">
                    <div class="flex items-center gap-1 text-sm font-bold text-slate-500">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span x-text="measuredAt"></span>
                    </div>
                    <span class="text-xs font-bold text-emerald-700 bg-emerald-100/80 px-2 py-0.5 rounded-md">Today</span>
                </div>
            </div>
            
            <div x-show="!hasRecorded" x-cloak>
                <div class="mt-2 rounded-2xl border-2 border-dashed border-slate-300 bg-white/60 px-5 py-4 text-center text-sm md:text-base font-bold text-slate-500 transition-colors group-hover:border-slate-400 group-hover:bg-white/80 group-hover:text-slate-700 shadow-sm">
                    <span aria-hidden="true" class="text-lg leading-none align-middle mr-1">+</span> Record Now
                </div>
            </div>
        </div>
    </div>
</a>
