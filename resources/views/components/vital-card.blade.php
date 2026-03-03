{{-- ============================================================
     VitalCard — Displays a single vital measurement.
     Clicking dispatches 'open-vital-modal' for recording.
     ============================================================ --}}

<div class="vital-card card p-6 h-48 flex flex-col justify-between group cursor-pointer transition-all hover:shadow-lg"
     @click="$dispatch('open-vital-modal', { type: '{{ $type }}' })"
     role="button"
     tabindex="0"
     @keydown.enter="$dispatch('open-vital-modal', { type: '{{ $type }}' })"
     aria-label="{{ ($data['recorded'] ?? false) ? 'Re-record ' . $title : 'Record ' . $title }}"
     data-type="{{ $type }}">

    <div class="flex justify-between items-start">
        <div class="w-12 h-12 {{ $bg }} rounded-2xl flex items-center justify-center {{ $color }} group-hover:scale-110 transition-transform">
            {!! $icon !!}
        </div>
        <div class="flex items-center gap-1.5">
            @if($data['recorded'] ?? false)
                @if(($data['source'] ?? 'manual') === 'google_fit')
                    <span class="badge badge-info text-xs">Google Fit</span>
                @endif
                @if($status)
                    <span class="badge text-xs {{ $status['bg'] }} {{ $status['text'] }}">{{ $status['label'] }}</span>
                @endif
            @endif
            <a href="{{ $route }}" @click.stop
               class="w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center text-gray-400 hover:{{ $color }} hover:bg-gray-200 transition-colors"
               aria-label="View {{ $title }} history"
               title="View history">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </a>
        </div>
    </div>

    <div>
        <h4 class="font-extrabold text-gray-500 text-sm uppercase tracking-wide mb-1">{{ $title }}</h4>

        @if($data['recorded'] ?? false)
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-black text-gray-900">
                    {{ $type === 'blood_pressure' ? $data['value_text'] : ($type === 'temperature' ? number_format($data['value'], 1) : intval($data['value'])) }}
                </span>
                <span class="text-base font-bold text-gray-400">{{ $unit }}</span>
            </div>
            <p class="text-sm font-bold text-gray-400 mt-1">
                {{ $data['measured_at']?->format('g:i A') }}
                <span class="text-xs ml-1 {{ $color }} opacity-0 group-hover:opacity-100 transition-opacity">· tap to re-record</span>
            </p>
        @else
            <div class="w-full py-3 mt-1 rounded-xl border-2 border-dashed border-gray-300 text-gray-400 font-bold text-sm group-hover:{{ $border }} group-hover:{{ $color }} transition-colors flex items-center justify-center gap-2">
                <span aria-hidden="true">+</span> Record
            </div>
        @endif
    </div>
</div>
