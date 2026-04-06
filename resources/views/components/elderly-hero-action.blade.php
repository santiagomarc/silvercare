{{-- ============================================================
     ElderlyHeroAction — Unified hero + queue action surface.
     Shows current priority action, step meta, and coming-up preview.
     ============================================================ --}}

<div class="hero-action bg-gradient-to-br {{ $gradient }} mb-6"
     role="status"
     aria-label="Next action: {{ $headline }}"
     x-data="heroAction({
         progress: {{ $overallProgress }},
         steps: @js($steps),
         initialTotal: {{ $initialTotal }}
     })"
     :class="'bg-gradient-to-br ' + currentGradient">

    {{-- Decorative blurs --}}
    <div class="absolute top-0 right-0 -mt-10 -mr-8 h-36 w-36 rounded-full bg-white/20 blur-3xl"></div>
    <div class="absolute bottom-0 left-0 -mb-8 -ml-8 h-24 w-24 rounded-full bg-black/15 blur-2xl"></div>

    <div class="relative z-10 flex items-start justify-between gap-3 mb-4">
        <div>
            <h3 class="text-white/90 text-sm font-black uppercase tracking-wider">Today's Priority Action</h3>
            <p class="text-white/70 text-xs font-semibold">Single-card queue with context and coming-up actions.</p>
        </div>
        <template x-if="initialTotal > 0">
            <span class="rounded-full bg-white/20 backdrop-blur-sm px-3 py-1 text-xs font-bold text-white">
                Step <span x-text="currentStepNumber"></span> of <span x-text="initialTotal"></span>
            </span>
        </template>
    </div>

    <div class="relative z-10 flex flex-col md:flex-row md:items-start md:justify-between gap-4">
        {{-- Left: Icon + Text + badges --}}
        <div class="flex items-start gap-4 min-w-0">
            <div class="text-4xl md:text-5xl flex-shrink-0" aria-hidden="true" x-text="currentIcon">{{ $icon }}</div>
            <div>
                <div class="flex items-center gap-2 mb-1 flex-wrap">
                    <span class="rounded-full bg-white/20 px-2.5 py-1 text-[11px] font-black uppercase tracking-wide text-white" x-text="currentTag">{{ $tag }}</span>
                    <span class="rounded-full bg-white/15 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-white/90" x-text="currentTypeLabel">{{ $actionType }}</span>
                </div>

                <h2 class="text-xl md:text-2xl font-extrabold text-white leading-tight"
                    x-text="currentTitle">
                    {{ $headline }}
                </h2>
                <p class="text-white/80 text-sm mt-1 max-w-xl" x-text="currentSubtitle">{{ $subtext }}</p>
            </div>
        </div>

        {{-- Right: Action Buttons --}}
        <div class="flex items-center gap-2 self-start md:self-center flex-shrink-0">
            <button
                x-show="isMedication && !busy"
                @click="completeMedication()"
                class="bg-white text-green-700 font-bold text-sm px-5 py-3 rounded-xl transition-all duration-200 hover:scale-105 active:scale-95 min-h-touch flex items-center gap-2 shadow-lg"
                aria-label="Mark medication as taken"
            >✓ I Took It</button>

            <button
                x-show="isTask && !busy"
                @click="completeTask()"
                class="bg-white text-[#000080] font-bold text-sm px-5 py-3 rounded-xl transition-all duration-200 hover:scale-105 active:scale-95 min-h-touch flex items-center gap-2 shadow-lg"
                aria-label="Mark task as complete"
            >✓ Mark Complete</button>

            <a
                x-show="isVital && currentRoute"
                :href="currentRoute"
                class="bg-white text-teal-700 font-bold text-sm px-5 py-3 rounded-xl transition-all duration-200 hover:scale-105 active:scale-95 min-h-touch flex items-center gap-2 shadow-lg"
            >Record Now</a>

            <button
                x-show="isMood"
                @click="openMood()"
                class="bg-white text-purple-700 font-bold text-sm px-5 py-3 rounded-xl transition-all duration-200 hover:scale-105 active:scale-95 min-h-touch flex items-center gap-2 shadow-lg"
            >Open Mood Tracker</button>

            <a
                x-show="isDone"
                href="{{ route('elderly.wellness.index') }}"
                class="bg-white text-emerald-700 font-bold text-sm px-5 py-3 rounded-xl transition-all duration-200 hover:scale-105 active:scale-95 min-h-touch flex items-center gap-2 shadow-lg"
            >Wellness Center</a>

            <button
                x-show="canDefer"
                @click="laterCurrent()"
                :disabled="busy"
                class="bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white font-bold text-sm px-4 py-3 rounded-xl transition-all duration-200 hover:scale-105 active:scale-95 min-h-touch"
            >Later</button>

            <button
                x-show="busy"
                disabled
                class="bg-white/30 backdrop-blur-sm text-white font-bold text-sm px-4 py-3 rounded-xl min-h-touch"
            >Saving...</button>
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

    <div class="relative z-10 mt-4" x-show="nextPreview.length > 0">
        <p class="text-xs font-bold uppercase tracking-wider text-white/70 mb-2">Coming Up</p>
        <div class="space-y-2">
            <template x-for="(item, i) in nextPreview" :key="item.id">
                <div class="rounded-xl border border-white/25 bg-white/10 px-3 py-2 flex items-center justify-between">
                    <p class="text-sm font-semibold text-white truncate">
                        <span class="text-white/70 mr-1">Step <span x-text="currentStepNumber + i + 1"></span>:</span>
                        <span x-text="item.title"></span>
                    </p>
                    <span class="text-[10px] font-bold text-white/70 uppercase tracking-wide" x-text="item.tag"></span>
                </div>
            </template>
        </div>
    </div>
</div>
