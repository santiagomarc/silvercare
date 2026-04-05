{{-- ============================================================
     Action Queue — Phase 5A sequential one-action-at-a-time flow.
     Shows only the current step prominently and previews what comes next.
     ============================================================ --}}

<div
    x-data="actionQueue(@js($steps), {{ $initialTotal }})"
    class="surface-sky relative overflow-hidden p-6"
    role="region"
    aria-label="Today's action queue"
>
    <div class="ambient-orb -right-6 -top-4 h-28 w-28 bg-indigo-200/35" aria-hidden="true"></div>
    <div class="ambient-orb left-16 -bottom-8 h-24 w-24 bg-sky-200/30 blur-2xl" aria-hidden="true"></div>

    <div class="relative z-10 flex items-start justify-between gap-4 mb-4">
        <div>
            <h3 class="text-lg font-extrabold text-gray-900">Action Queue</h3>
            <p class="text-xs text-gray-500 font-medium">One step at a time to reduce cognitive load.</p>
        </div>
        <template x-if="initialTotal > 0">
            <span class="badge bg-blue-100 text-blue-700 border border-blue-200 text-xs">
                Step <span x-text="currentStepNumber"></span> of <span x-text="initialTotal"></span>
            </span>
        </template>
    </div>

    <div class="relative z-10 progress-track bg-blue-100/70 mb-4" x-show="initialTotal > 0">
        <div class="progress-fill bg-gradient-to-r from-blue-500 to-indigo-600" :style="'width:' + completionProgress + '%'"></div>
    </div>

    <template x-if="initialTotal === 0">
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50/80 p-5 text-center">
            <p class="text-2xl mb-1" aria-hidden="true">🎉</p>
            <p class="font-extrabold text-emerald-800">All done for now!</p>
            <p class="text-xs text-emerald-700 mt-1">No pending actions in your queue.</p>
        </div>
    </template>

    <template x-if="current">
        <div class="rounded-2xl border border-white/70 bg-white/85 p-5 shadow-[0_18px_35px_-28px_rgba(15,23,42,0.38)]">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-[11px] font-black tracking-wide uppercase text-[#000080]/60" x-text="current.tag"></p>
                    <h4 class="text-xl font-black text-gray-900 leading-tight" x-text="current.title"></h4>
                    <p class="text-sm text-gray-500 mt-1" x-text="current.subtitle"></p>
                </div>
                <span class="text-xs font-bold text-slate-500 bg-slate-100 rounded-full px-2 py-1" x-text="current.type"></span>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <button
                    x-show="current.type === 'medication'"
                    @click="completeMedication(current)"
                    :disabled="busy"
                    class="rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold px-4 py-2.5 transition-colors disabled:opacity-60"
                >
                    <span x-show="!busy">✓ I Took It</span>
                    <span x-show="busy">Saving...</span>
                </button>

                <button
                    x-show="current.type === 'task'"
                    @click="completeTask(current)"
                    :disabled="busy"
                    class="rounded-xl bg-[#000080] hover:bg-blue-900 text-white text-sm font-bold px-4 py-2.5 transition-colors disabled:opacity-60"
                >
                    <span x-show="!busy">✓ Mark Complete</span>
                    <span x-show="busy">Saving...</span>
                </button>

                <a
                    x-show="current.type === 'vital'"
                    :href="current.route"
                    class="rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-sm font-bold px-4 py-2.5 transition-colors inline-flex items-center gap-2"
                >
                    Record Now
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </a>

                <button
                    x-show="current.type === 'mood'"
                    @click="openMood()"
                    class="rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-sm font-bold px-4 py-2.5 transition-colors"
                >
                    Open Mood Slider
                </button>

                <button
                    x-show="steps.length > 1"
                    @click="laterCurrent()"
                    class="rounded-xl border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 text-sm font-bold px-4 py-2.5 transition-colors"
                >
                    Do This Later
                </button>
            </div>
        </div>
    </template>

    <div class="relative z-10 mt-4" x-show="nextPreview.length > 0">
        <p class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-2">Coming Up</p>
        <div class="space-y-2">
            <template x-for="(item, i) in nextPreview" :key="item.id">
                <div class="rounded-xl border border-gray-100 bg-white/70 px-3 py-2 flex items-center justify-between">
                    <p class="text-sm font-semibold text-gray-700 truncate">
                        <span class="text-gray-400 mr-1">Step <span x-text="currentStepNumber + i + 1"></span>:</span>
                        <span x-text="item.title"></span>
                    </p>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wide" x-text="item.tag"></span>
                </div>
            </template>
        </div>
    </div>
</div>
