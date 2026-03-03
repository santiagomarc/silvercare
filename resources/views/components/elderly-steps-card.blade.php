{{-- ============================================================
     ElderlyStepsCard — Google Fit steps progress display.
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
    class="bg-gradient-to-r from-emerald-500 to-teal-500 rounded-card p-6 shadow-lg text-white relative overflow-hidden"
    role="region"
    aria-label="Steps progress"
>
    {{-- Decorative circles --}}
    <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-16 -mt-16" aria-hidden="true"></div>
    <div class="absolute bottom-0 left-0 w-20 h-20 bg-white/5 rounded-full -ml-10 -mb-10" aria-hidden="true"></div>

    <div class="relative z-10">
        <div class="flex justify-between items-start mb-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-2xl" aria-hidden="true">👟</span>
                    <h3 class="font-extrabold text-lg">Today's Steps</h3>
                </div>
                <p class="text-white/70 text-sm">
                    @if($googleFitConnected)
                        <span class="inline-flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.545,10.239v3.821h5.445c-0.712,2.315-2.647,3.972-5.445,3.972c-3.332,0-6.033-2.701-6.033-6.032s2.701-6.032,6.033-6.032c1.498,0,2.866,0.549,3.921,1.453l2.814-2.814C17.503,2.988,15.139,2,12.545,2C7.021,2,2.543,6.477,2.543,12s4.478,10,10.002,10c8.396,0,10.249-7.85,9.426-11.748L12.545,10.239z"/></svg>
                            Synced from Google Fit
                        </span>
                    @else
                        Connect Google Fit to track steps
                    @endif
                </p>
            </div>

            <div class="text-right">
                @if($stepsData)
                    <div class="text-3xl font-black" aria-label="{{ number_format($steps) }} steps">{{ number_format($steps) }}</div>
                    <div class="text-white/70 text-xs">/ {{ number_format($goal) }} goal</div>
                @else
                    <div class="text-3xl font-black">--</div>
                    <div class="text-white/70 text-xs">No data yet</div>
                @endif
            </div>
        </div>

        {{-- Progress Bar --}}
        <div class="progress-track bg-white/20">
            <div class="progress-fill bg-white" style="width: {{ $progress }}%"></div>
        </div>

        <div class="flex justify-between items-center mt-3 text-sm">
            <span class="text-white/80">{{ $progress }}% of daily goal</span>

            @if($goalReached)
                <span class="badge bg-white/20 text-white font-bold text-xs" role="status">
                    🎉 Goal Reached!
                </span>
            @elseif($stepsData)
                <span class="text-white/60 text-xs">
                    {{ number_format($goal - $steps) }} steps to go
                </span>
            @endif
        </div>

        {{-- Sync / Connect button --}}
        <div class="mt-4">
            @if($googleFitConnected)
                <button
                    @click="sync()"
                    :disabled="syncing"
                    class="text-sm font-bold text-white bg-white/20 hover:bg-white/30 px-4 py-2
                           rounded-xl transition-colors flex items-center gap-2 min-h-touch"
                    aria-label="Sync Google Fit data"
                >
                    <svg class="w-4 h-4" :class="syncing && 'animate-spin'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span x-text="syncing ? 'Syncing...' : 'Sync Google Fit'"></span>
                </button>
            @else
                <a
                    href="{{ route('elderly.googlefit.connect') }}"
                    class="text-sm font-bold text-white bg-white/20 hover:bg-white/30 px-4 py-2
                           rounded-xl transition-colors inline-flex items-center gap-2 min-h-touch"
                    aria-label="Connect Google Fit"
                >
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12.545,10.239v3.821h5.445c-0.712,2.315-2.647,3.972-5.445,3.972c-3.332,0-6.033-2.701-6.033-6.032s2.701-6.032,6.033-6.032c1.498,0,2.866,0.549,3.921,1.453l2.814-2.814C17.503,2.988,15.139,2,12.545,2C7.021,2,2.543,6.477,2.543,12s4.478,10,10.002,10c8.396,0,10.249-7.85,9.426-11.748L12.545,10.239z"/></svg>
                    Connect Google Fit
                </a>
            @endif
        </div>
    </div>
</div>
