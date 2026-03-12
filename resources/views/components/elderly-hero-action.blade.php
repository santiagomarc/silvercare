{{-- ============================================================
     ElderlyHeroAction — Time-aware "What's Next" priority card
     ============================================================ --}}

<div class="hero-action bg-gradient-to-br {{ $gradient }} mb-6"
     role="status"
     aria-label="Next action: {{ $headline }}">

    {{-- Decorative blurs --}}
    <div class="absolute top-0 right-0 -mt-10 -mr-8 h-36 w-36 rounded-full bg-white/20 blur-3xl"></div>
    <div class="absolute bottom-0 left-0 -mb-8 -ml-8 h-24 w-24 rounded-full bg-black/15 blur-2xl"></div>

    <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        {{-- Left: Icon + Text --}}
        <div class="flex items-start gap-4">
            <div class="text-4xl md:text-5xl flex-shrink-0" aria-hidden="true">{{ $icon }}</div>
            <div>
                <h2 class="text-xl md:text-2xl font-extrabold text-white leading-tight">
                    {{ $headline }}
                </h2>
                <p class="text-white/80 text-sm mt-1 max-w-md">{{ $subtext }}</p>
            </div>
        </div>

        {{-- Right: CTA Button --}}
        <button
            @click="{{ $ctaAction }}"
            class="flex-shrink-0 bg-white/20 hover:bg-white/30 backdrop-blur-sm
                   text-white font-bold text-sm px-6 py-3.5 rounded-xl
                   transition-all duration-200 hover:scale-105 active:scale-95
                   min-h-touch flex items-center gap-2 self-start md:self-center"
            aria-label="{{ $ctaLabel }}"
        >
            {{ $ctaLabel }}
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
    </div>

    {{-- Progress mini-bar --}}
    <div class="relative z-10 mt-5">
        <div class="flex justify-between items-center mb-1.5">
            <span class="text-white/70 text-xs font-bold">Daily Progress</span>
            <span class="text-white font-extrabold text-sm">{{ $overallProgress }}%</span>
        </div>
        <div class="progress-track bg-white/20">
            <div class="progress-fill bg-white" @style(['width: ' . $overallProgress . '%'])></div>
        </div>
    </div>
</div>
