@props(['medication', 'time', 'log'])

@php
    $dose = \App\Presenters\MedicationPresenter::getDoseStatus($time, $log);
    $status = $dose['status'];
    $icon = $dose['icon'];
    $doseClass = $dose['doseClass'];
    $canTake = $dose['canTake'];
    $canUndo = $dose['canUndo'];
    $isTaken = $dose['isTaken'];
@endphp

<div class="sc-dose {{ $doseClass }} flex items-center justify-between">
    <div class="flex items-center gap-3">
        <span class="sc-plate sc-plate-sm" aria-hidden="true">
            <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-{{ $icon }}"/></svg>
        </span>
        <div>
            <p class="font-semibold sc-num" style="color:var(--sc-ink)">{{ \Carbon\Carbon::parse($time)->format('g:i A') }}</p>
            <p style="color:var(--sc-muted)">{{ $status }}</p>
        </div>
    </div>
    
    <div class="flex gap-2">
        @if($isTaken && $canUndo)
            <form action="{{ route('elderly.medications.undo', $medication) }}" method="POST">
                @csrf
                <input type="hidden" name="time" value="{{ $time }}">
                <button type="submit" class="sc-btn sc-btn-ghost sc-btn-sm">
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
                    class="sc-btn sc-btn-primary sc-btn-sm">
                    Take
                </button>
            </form>
        @endif
    </div>
</div>