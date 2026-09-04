{{-- ============================================================
     ElderlyStepsCard — today's step count from Google Fit.

     The bar always has its number beside it: a bar on its own is
     imprecise to everyone and unreadable to a screen reader, so the
     percentage is in the ARIA and in the text.
     ============================================================ --}}

@props([
    'stepsData' => null,
    'googleFitConnected' => false,
])

@php
    $steps = $stepsData['value'] ?? 0;
    $goal  = $stepsData['goal'] ?? 6000;
    $progress = $stepsData ? min(100, round(($steps / $goal) * 100)) : 0;
    $goalReached = $stepsData && $steps >= $goal;
@endphp

<div
    x-data="googleFitSync()"
    class="sc-card p-6"
    role="region"
    aria-label="Steps progress"
>
    <div class="flex flex-wrap justify-between items-start gap-4 mb-4">
        <div class="min-w-0">
            <div class="flex items-center gap-2 mb-1">
                <x-lucide-footprints class="sc-i w-7 h-7" style="color:var(--sc-brand-text)" aria-hidden="true" />
                <h3 class="sc-h3">Today's Steps</h3>
            </div>
            <p style="color:var(--sc-muted)">
                @if($googleFitConnected)
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4 flex-none" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12.545,10.239v3.821h5.445c-0.712,2.315-2.647,3.972-5.445,3.972c-3.332,0-6.033-2.701-6.033-6.032s2.701-6.032,6.033-6.032c1.498,0,2.866,0.549,3.921,1.453l2.814-2.814C17.503,2.988,15.139,2,12.545,2C7.021,2,2.543,6.477,2.543,12s4.478,10,10.002,10c8.396,0,10.249-7.85,9.426-11.748L12.545,10.239z"/></svg>
                        Synced from Google Fit
                    </span>
                @else
                    Connect Google Fit to track steps
                @endif
            </p>
        </div>

        <div class="text-right flex-none">
            @if($stepsData)
                <div class="sc-stat-value sc-num" aria-label="{{ number_format($steps) }} steps">{{ number_format($steps) }}</div>
                <div class="sc-num" style="color:var(--sc-muted)">/ {{ number_format($goal) }} goal</div>
            @else
                <div class="sc-stat-value">--</div>
                <div style="color:var(--sc-muted)">No data yet</div>
            @endif
        </div>
    </div>

    {{-- Progress --}}
    <div class="sc-progress"
         role="progressbar"
         aria-valuenow="{{ $progress }}"
         aria-valuemin="0"
         aria-valuemax="100"
         aria-label="Progress towards today's step goal">
        <div class="sc-progress-fill {{ $goalReached ? 'sc-progress-fill-ok' : '' }}" @style(['width: ' . $progress . '%'])></div>
    </div>

    <div class="flex justify-between items-center gap-3 mt-3">
        <span class="sc-num" style="color:var(--sc-body)">{{ $progress }}% of daily goal</span>

        @if($goalReached)
            <span class="sc-badge sc-badge-ok" role="status">
                <x-lucide-circle-check class="sc-i w-4 h-4" aria-hidden="true" />
                Goal reached
            </span>
        @elseif($stepsData)
            <span class="sc-num" style="color:var(--sc-muted)">
                {{ number_format($goal - $steps) }} steps to go
            </span>
        @endif
    </div>

    {{-- Sync / Connect --}}
    <div class="mt-5">
        @if($googleFitConnected)
            <button
                type="button"
                @click="sync()"
                :disabled="syncing"
                class="sc-btn sc-btn-ghost sc-btn-sm"
                aria-label="Sync Google Fit data"
            >
                <x-lucide-refresh-cw class="sc-i w-5 h-5" ::class="syncing && 'animate-spin'" aria-hidden="true" />
                <span x-text="syncing ? 'Syncing...' : 'Sync Google Fit'">Sync Google Fit</span>
            </button>
        @else
            <a
                href="{{ route('elderly.googlefit.connect') }}"
                class="sc-btn sc-btn-ghost sc-btn-sm"
                aria-label="Connect Google Fit"
            >
                <svg class="w-5 h-5 flex-none" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12.545,10.239v3.821h5.445c-0.712,2.315-2.647,3.972-5.445,3.972c-3.332,0-6.033-2.701-6.033-6.032s2.701-6.032,6.033-6.032c1.498,0,2.866,0.549,3.921,1.453l2.814-2.814C17.503,2.988,15.139,2,12.545,2C7.021,2,2.543,6.477,2.543,12s4.478,10,10.002,10c8.396,0,10.249-7.85,9.426-11.748L12.545,10.239z"/></svg>
                Connect Google Fit
            </a>
        @endif
    </div>
</div>
