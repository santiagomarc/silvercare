@props(['medication', 'time', 'log'])

@php
    $now = now();
    $scheduledTime = \Carbon\Carbon::parse(today()->format('Y-m-d') . ' ' . $time);
    $windowStart = $scheduledTime->copy();
    $windowEnd = $scheduledTime->copy()->addMinutes(60);
    
    $isWithinWindow = $now->between($windowStart, $windowEnd);
    $isPastWindow = $now->gt($windowEnd);
    $isBeforeWindow = $now->lt($windowStart);
    $isTaken = $log?->is_taken ?? false;
    $takenAt = $log?->taken_at;
    
    $canTake = $isWithinWindow || $isPastWindow;
    $canUndo = !$isPastWindow;
    
    $status = '';
    $icon = '';
    $bgClass = '';
    $iconBgClass = '';
    
    if ($isTaken) {
        $wasLate = $takenAt && $takenAt->gt($windowEnd);
        $status = $wasLate ? 'Taken Late' : 'Taken';
        $icon = '✓';
        $bgClass = $wasLate ? 'bg-orange-50 border-orange-300' : 'bg-green-50 border-green-300';
        $iconBgClass = $wasLate ? 'bg-orange-200' : 'bg-green-200';
        $canTake = false;
    } elseif ($isPastWindow) {
        $status = 'Missed';
        $icon = '!';
        $bgClass = 'bg-red-50 border-red-300';
        $iconBgClass = 'bg-red-200';
        $canTake = true;
        $canUndo = false;
    } elseif ($isWithinWindow) {
        $status = 'Take Now';
        $icon = '💊';
        $bgClass = 'bg-blue-50 border-blue-300 ring-2 ring-blue-400 ring-offset-2';
        $iconBgClass = 'bg-blue-200 animate-pulse';
        $canTake = true;
        $canUndo = false;
    } else {
        $status = 'Upcoming';
        $icon = '⏳';
        $bgClass = 'bg-gray-50 border-gray-200 opacity-75';
        $iconBgClass = 'bg-gray-200';
        $canTake = false;
        $canUndo = false;
    }
@endphp

<div class="flex items-center justify-between p-3 rounded-xl border {{ $bgClass }} transition-all duration-300">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-full flex items-center justify-center text-lg {{ $iconBgClass }}">
            {{ $icon }}
        </div>
        <div>
            <p class="font-bold text-gray-900">{{ \Carbon\Carbon::parse($time)->format('g:i A') }}</p>
            <p class="text-xs font-medium text-gray-600">{{ $status }}</p>
        </div>
    </div>
    
    <div class="flex gap-2">
        @if($isTaken && $canUndo)
            <form action="{{ route('elderly.medications.undo', $medication) }}" method="POST">
                @csrf
                <input type="hidden" name="time" value="{{ $time }}">
                <button type="submit" class="px-3 py-1.5 text-sm font-bold text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Undo
                </button>
            </form>
        @endif
        
        @if(!$isTaken)
            <form action="{{ route('elderly.medications.take', $medication) }}" method="POST">
                @csrf
                <input type="hidden" name="time" value="{{ $time }}">
                <button type="submit" 
                    @disabled(!$canTake)
                    class="px-4 py-1.5 text-sm font-bold text-white rounded-lg transition-colors {{ $canTake ? 'bg-blue-600 hover:bg-blue-700 shadow-md' : 'bg-gray-400 cursor-not-allowed' }}">
                    Take
                </button>
            </form>
        @endif
    </div>
</div>