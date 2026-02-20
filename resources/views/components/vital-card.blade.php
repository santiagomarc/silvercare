@props(['title', 'icon', 'color', 'bg', 'border', 'unit', 'value' => null, 'lastChecked' => null])

<div class="bg-white rounded-[24px] p-6 shadow-sm border border-gray-100 {{ $border }} transition-all hover:shadow-md h-48 flex flex-col justify-between group">
    <div class="flex justify-between items-start">
        <div class="w-12 h-12 {{ $bg }} rounded-2xl flex items-center justify-center {{ $color }} group-hover:scale-110 transition-transform">
            <!-- Replaced SVGs with a simple placeholder to save space in chat, use actual SVGs here -->
            <span class="text-xl">●</span> 
        </div>
        @if($value)
            <span class="bg-green-50 text-green-700 px-2 py-1 rounded-lg text-[10px] font-[800] uppercase tracking-wide">Recorded</span>
        @endif
    </div>

    <div>
        <h4 class="font-[700] text-gray-500 text-sm uppercase tracking-wide mb-1">{{ $title }}</h4>
        
        @if($value)
            <div class="flex items-baseline gap-1">
                <span class="text-3xl font-[900] text-gray-900">{{ $value }}</span>
                <span class="text-xs font-bold text-gray-400">{{ $unit }}</span>
            </div>
            <p class="text-[10px] font-bold text-gray-400 mt-2">Last: {{ $lastChecked }}</p>
        @else
            <button class="w-full py-3 mt-1 rounded-xl border-2 border-dashed border-gray-300 text-gray-400 font-bold text-sm hover:border-[#000080] hover:text-[#000080] transition-colors flex items-center justify-center gap-2">
                <span>+</span> Measure
            </button>
        @endif
    </div>
</div>