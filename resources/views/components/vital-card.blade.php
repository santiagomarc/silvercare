<a href="{{ $route }}" class="vital-card bg-white rounded-[24px] p-6 shadow-md border border-gray-100 hover:shadow-lg transition-all h-48 flex flex-col justify-between group cursor-pointer" data-type="{{ $type }}">
    <div class="flex justify-between items-start">
        <div class="w-12 h-12 {{ $bg }} rounded-2xl flex items-center justify-center {{ $color }} group-hover:scale-110 transition-transform">
            {!! $icon !!}
        </div>
        <div class="flex items-center gap-1.5">
            @if($data['recorded'] ?? false)
                @if(($data['source'] ?? 'manual') === 'google_fit')
                    <span class="text-[10px] font-bold text-blue-600 bg-blue-50 px-2 py-1 rounded-full">Google Fit</span>
                @endif
                @if($status)
                    <span class="text-[10px] font-bold {{ $status['bg'] }} {{ $status['text'] }} px-2 py-1 rounded-full">{{ $status['label'] }}</span>
                @endif
            @endif
            <svg class="w-4 h-4 text-gray-300 group-hover:{{ $color }} transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
        </div>
    </div>
    <div>
        <h4 class="font-[800] text-gray-500 text-sm uppercase tracking-wide mb-1">{{ $title }}</h4>
        @if($data['recorded'] ?? false)
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-[900] text-gray-900 group-hover:hidden">
                    {{ $type === 'blood_pressure' ? $data['value_text'] : ($type === 'temperature' ? number_format($data['value'], 1) : intval($data['value'])) }}
                </span>
                <span class="text-base font-[700] text-gray-400 group-hover:hidden">{{ $unit }}</span>
            </div>
            <p class="text-sm font-[700] text-gray-400 mt-1 group-hover:hidden">{{ $data['measured_at']?->format('g:i A') }}</p>
            <div class="hidden group-hover:flex w-full py-3 mt-1 rounded-xl border-2 border-dashed {{ $border }} {{ $color }} font-bold text-sm items-center justify-center gap-2 transition-colors">
                <span>+</span> Measure Now
            </div>
        @else
            <div class="w-full py-3 mt-1 rounded-xl border-2 border-dashed border-gray-300 text-gray-400 font-bold text-sm group-hover:{{ $border }} group-hover:{{ $color }} transition-colors flex items-center justify-center gap-2">
                <span>+</span> Measure
            </div>
        @endif
    </div>
</a>