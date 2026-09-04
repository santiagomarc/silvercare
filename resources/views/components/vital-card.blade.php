{{-- ============================================================
    VitalCard — the latest reading for one vital.
    The whole card opens that vital's page, where it can be recorded.

    Status is a tone from App\View\Components\VitalCard, never a colour:
    the label ("High", "Normal") carries the meaning and the tint only
    reinforces it, so the card reads the same in high contrast.
    ============================================================ --}}

@php
    $measuredAt = $metricData['measured_at'] ?? null;
    if ($measuredAt && ! $measuredAt instanceof \Carbon\CarbonInterface) {
        $measuredAt = \Carbon\Carbon::parse($measuredAt);
    }
    $hasRecordedValue = (bool) ($metricData['recorded'] ?? false);

    $fromGoogleFit = ($metricData['source'] ?? 'manual') === 'google_fit';
    $sourceLabel = $fromGoogleFit ? 'Google Fit' : 'Manual Entry';

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
   class="sc-card sc-lift block min-h-[14rem] p-6"
   aria-label="Open {{ $title }} details"
   data-type="{{ $type }}">

    <div class="flex h-full flex-col">
        {{-- Header: icon + status --}}
        <div class="flex justify-between items-start gap-3 mb-4">
            <span class="sc-plate">
                <x-dynamic-component :component="'lucide-' . $icon" class="sc-i w-6 h-6" aria-hidden="true" />
            </span>

            <div class="flex flex-wrap justify-end items-center gap-1.5" x-show="hasRecorded" x-cloak>
                <span class="sc-badge {{ $fromGoogleFit ? 'sc-badge-brand' : '' }}">{{ $sourceLabel }}</span>
                @if($status)
                    <span class="sc-badge sc-badge-{{ $status['tone'] }}">{{ $status['label'] }}</span>
                @endif
            </div>
        </div>

        {{-- Body: the reading --}}
        <div class="mt-auto pt-2">
            <h4 class="sc-stat-label" x-text="title"></h4>

            <div x-show="hasRecorded" x-cloak>
                <div class="flex items-baseline gap-2 flex-wrap">
                    <span class="sc-stat-value sc-num" x-text="value"></span>
                    <span class="font-semibold" style="color:var(--sc-muted)" x-text="unit"></span>
                </div>
                <div class="flex items-center gap-2 mt-3">
                    <span class="flex items-center gap-1.5 font-medium" style="color:var(--sc-muted)">
                        <x-lucide-clock class="sc-i w-4 h-4" aria-hidden="true" />
                        <span class="sc-num" x-text="measuredAt"></span>
                    </span>
                    <span class="sc-badge sc-badge-ok">Today</span>
                </div>
            </div>

            <div x-show="!hasRecorded" x-cloak>
                <p class="mt-2 flex items-center justify-center gap-2 rounded-2xl border border-dashed px-5 py-4 font-semibold"
                   style="border-color:var(--sc-line-strong); color:var(--sc-brand-text)">
                    <x-lucide-plus class="sc-i w-5 h-5" aria-hidden="true" />
                    Record Now
                </p>
            </div>
        </div>
    </div>
</a>
