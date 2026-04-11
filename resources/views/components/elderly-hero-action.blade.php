{{-- ============================================================
     ElderlyHeroAction — Unified hero + queue action surface.
     Shows current priority action, step meta, and coming-up preview.
     ============================================================ --}}

<div class="hero-action mb-4"
     role="status"
     aria-label="Next action: {{ $headline }}"
     x-data="heroAction({
         progress: {{ $overallProgress }},
         steps: @js($steps),
         initialTotal: {{ $initialTotal }}
     })"
     style="background-image: {{ $gradientStyle }};"
     :style="'background-image:' + currentGradientStyle">

    {{-- Decorative glass lighting --}}
    <div class="absolute inset-0 bg-gradient-to-br from-white/18 via-transparent to-black/25"></div>
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_14%_18%,rgba(255,255,255,0.35)_0%,rgba(255,255,255,0)_42%)]"></div>
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_85%_74%,rgba(191,219,254,0.28)_0%,rgba(191,219,254,0)_40%)]"></div>
    <div class="absolute top-0 right-0 -mt-10 -mr-8 h-40 w-40 rounded-full bg-white/18 blur-3xl"></div>
    <div class="absolute bottom-0 left-0 -mb-10 -ml-8 h-28 w-28 rounded-full bg-black/20 blur-2xl"></div>

    <div class="relative z-10 flex items-start justify-between gap-3 mb-4">
        <div>
            <h3 class="text-white text-sm font-black uppercase tracking-wider">Today's Priority Action</h3>
        </div>
        <template x-if="initialTotal > 0">
            <span class="hero-glass-chip px-3 py-1 text-xs font-bold text-white shadow-sm">
                Step <span x-text="currentStepNumber"></span> of <span x-text="initialTotal"></span>
            </span>
        </template>
    </div>

    <div class="relative z-10 flex flex-col md:flex-row md:items-start md:justify-between gap-3">
        {{-- Left: Icon + Text + badge --}}
        <div class="flex items-start gap-4 min-w-0">
            <div class="text-3xl md:text-4xl flex-shrink-0" aria-hidden="true" x-text="currentIcon">{{ $icon }}</div>
            <div>
                <div class="flex items-center gap-2 mb-1 flex-wrap">
                    <span class="hero-glass-chip px-2.5 py-1 text-xs font-black uppercase tracking-wide text-white" x-text="currentTag || currentTypeLabel">{{ $tag ?: $actionType }}</span>
                </div>

                <h2 class="text-xl md:text-2xl font-extrabold text-white leading-tight"
                    x-text="currentTitle">
                    {{ $headline }}
                </h2>
                <p class="text-white/92 text-sm mt-0.5 max-w-xl" x-text="currentSubtitle">{{ $subtext }}</p>
            </div>
        </div>

        {{-- Right: Action Buttons --}}
        <div class="flex items-center gap-2 self-start md:self-center flex-shrink-0">
            <button
                x-show="isMedication && !busy"
                @click="completeMedication()"
                class="hero-action-cta text-green-700 flex items-center gap-2"
                aria-label="Mark medication as taken"
            >✓ I Took It</button>

            <button
                x-show="isTask && !busy"
                @click="completeTask()"
                class="hero-action-cta text-[#000080] flex items-center gap-2"
                aria-label="Mark task as complete"
            >✓ Mark Complete</button>

            <a
                x-show="isVital && currentRoute"
                :href="currentRoute"
                class="hero-action-cta text-teal-700 flex items-center gap-2"
            >Record Now</a>

            <button
                x-show="isMood"
                @click="openMood()"
                class="hero-action-cta text-purple-700 flex items-center gap-2"
            >Open Mood Tracker</button>

            <a
                x-show="isDone"
                href="{{ route('elderly.wellness.index') }}"
                class="hero-action-cta text-emerald-700 flex items-center gap-2"
            >Wellness Center</a>

            <button
                x-show="canDefer"
                @click="laterCurrent()"
                :disabled="busy"
                class="hero-action-ghost"
            >Later</button>

            <button
                x-show="busy"
                disabled
                class="hero-action-ghost opacity-85"
            >Saving...</button>
        </div>
    </div>

    {{-- Progress mini-bar (real-time) --}}
    <div class="relative z-10 mt-4">
        <div class="flex justify-between items-center mb-1.5">
            <span class="text-white/90 text-xs font-bold">Daily Progress</span>
            <span class="text-white font-extrabold text-sm" x-text="currentProgress + '%'">{{ $overallProgress }}%</span>
        </div>
        <div class="progress-track bg-white/30">
            <div class="progress-fill bg-gradient-to-r from-white via-sky-100 to-cyan-100 transition-all duration-700 ease-out"
                 :style="'width:' + currentProgress + '%'"></div>
        </div>
    </div>

    <div class="relative z-10 mt-3 hidden md:block" x-show="nextPreview.length > 0">
        <p class="text-xs font-bold uppercase tracking-wider text-white/90 mb-2">Coming Up</p>
        <div class="space-y-2">
            <template x-for="(item, i) in nextPreview" :key="item.id">
                <div class="hero-glass-row px-3 py-2 flex items-center justify-between">
                    <p class="text-sm font-semibold text-white truncate">
                        <span class="text-white/85 mr-1">Step <span x-text="currentStepNumber + i + 1"></span>:</span>
                        <span x-text="item.title"></span>
                    </p>
                    <span class="text-xs font-bold text-white/85 uppercase tracking-wide" x-text="item.tag"></span>
                </div>
            </template>
        </div>
    </div>
</div>
