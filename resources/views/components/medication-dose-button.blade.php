@props(['medication', 'time', 'log'])

@php
    $dose = \App\Presenters\MedicationPresenter::getDoseStatus($time, $log);
    $status = $dose['status'];
    $icon = $dose['icon'];
    $bgClass = $dose['bg'];
    $iconBgClass = $dose['iconBg'];
    $canTake = $dose['canTake'];
    $canUndo = $dose['canUndo'];
    $isTaken = $dose['isTaken'];
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
                <button type="submit" class="px-4 py-2.5 text-sm font-bold text-gray-600 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors min-h-touch min-w-touch">
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
                    class="px-5 py-2.5 text-sm font-bold text-white rounded-xl transition-colors min-h-touch {{ $canTake ? 'bg-blue-600 hover:bg-blue-700 shadow-md' : 'bg-gray-400 cursor-not-allowed' }}">
                    Take
                </button>
            </form>
        @endif
    </div>
</div>