{{-- ============================================================
     ElderlyHeroAction — Time-aware "What's Next" priority card
     With inline "I Took It" / "Skip" for medication actions
     and real-time progress via Alpine event listeners.
     ============================================================ --}}

<div class="hero-action bg-gradient-to-br {{ $gradient }} mb-6"
     role="status"
     aria-label="Next action: {{ $headline }}"
     x-data="heroAction({
         progress: {{ $overallProgress }},
         actionType: '{{ $actionType }}',
         medicationId: {{ $medicationId ?? 'null' }},
         scheduledTime: '{{ $scheduledTime ?? '' }}'
     })">

    {{-- Decorative blurs --}}
    <div class="absolute top-0 right-0 -mt-10 -mr-8 h-36 w-36 rounded-full bg-white/20 blur-3xl"></div>
    <div class="absolute bottom-0 left-0 -mb-8 -ml-8 h-24 w-24 rounded-full bg-black/15 blur-2xl"></div>

    <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        {{-- Left: Icon + Text --}}
        <div class="flex items-start gap-4">
            <div class="text-4xl md:text-5xl flex-shrink-0" aria-hidden="true">{{ $icon }}</div>
            <div>
                <h2 class="text-xl md:text-2xl font-extrabold text-white leading-tight"
                    x-text="heroHeadline">
                    {{ $headline }}
                </h2>
                <p class="text-white/80 text-sm mt-1 max-w-md" x-text="heroSubtext">{{ $subtext }}</p>
            </div>
        </div>

        {{-- Right: Action Buttons --}}
        <div class="flex items-center gap-2 self-start md:self-center flex-shrink-0">
            @if($actionType === 'medication' && $medicationId)
                {{-- ✓ I Took It --}}
                <button
                    @click="takeMedication()"
                    :disabled="marking"
                    x-show="!taken"
                    class="bg-white text-green-700 font-bold text-sm px-5 py-3 rounded-xl
                           transition-all duration-200 hover:scale-105 active:scale-95
                           min-h-touch flex items-center gap-2 shadow-lg
                           disabled:opacity-60 disabled:cursor-not-allowed"
                    aria-label="Mark medication as taken"
                >
                    <span x-show="!marking">✓ I Took It</span>
                    <span x-show="marking" class="flex items-center gap-1.5">
                        <svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Logging...
                    </span>
                </button>

                {{-- Taken confirmation --}}
                <div x-show="taken" x-cloak
                     class="bg-white/30 backdrop-blur-sm text-white font-bold text-sm px-5 py-3 rounded-xl flex items-center gap-2">
                    ✅ Taken!
                </div>

                {{-- Skip / Later --}}
                <button
                    @click="skipMedication()"
                    x-show="!taken"
                    class="bg-white/20 hover:bg-white/30 backdrop-blur-sm
                           text-white font-bold text-sm px-4 py-3 rounded-xl
                           transition-all duration-200 hover:scale-105 active:scale-95
                           min-h-touch flex items-center gap-1"
                    aria-label="Skip or take later"
                >
                    Later
                </button>
            @else
                {{-- Default CTA for non-medication actions --}}
                <button
                    @click="{{ $ctaAction }}"
                    class="flex-shrink-0 bg-white/20 hover:bg-white/30 backdrop-blur-sm
                           text-white font-bold text-sm px-6 py-3.5 rounded-xl
                           transition-all duration-200 hover:scale-105 active:scale-95
                           min-h-touch flex items-center gap-2"
                    aria-label="{{ $ctaLabel }}"
                >
                    {{ $ctaLabel }}
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            @endif
        </div>
    </div>

    {{-- Progress mini-bar (real-time) --}}
    <div class="relative z-10 mt-5">
        <div class="flex justify-between items-center mb-1.5">
            <span class="text-white/70 text-xs font-bold">Daily Progress</span>
            <span class="text-white font-extrabold text-sm" x-text="currentProgress + '%'">{{ $overallProgress }}%</span>
        </div>
        <div class="progress-track bg-white/20">
            <div class="progress-fill bg-white transition-all duration-700 ease-out"
                 :style="'width:' + currentProgress + '%'"></div>
        </div>
    </div>
</div>
