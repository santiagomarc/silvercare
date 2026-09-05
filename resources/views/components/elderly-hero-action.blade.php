{{-- ============================================================
     ElderlyHeroAction — the one thing to do next.

     Arthur's screen answers "what do I do now?", so this card holds a
     single action and a single loud button. Urgency is carried by the
     tone the queue assigns (brand / ok / alert) *and* by the tag word
     beside it — never by colour alone.

     The icon name arrives from Alpine at runtime, so it is drawn from
     the sprite with a bound <use href>; the package component would
     need the name at compile time.
     ============================================================ --}}

<div class="sc-card sc-card-crest sc-card-glow p-5 sm:p-6 mb-4"
     role="status"
     aria-label="Next action: {{ $headline }}"
     x-data="heroAction({
         progress: {{ $overallProgress }},
         steps: @js($steps),
         initialTotal: {{ $initialTotal }}
     })">

    <div class="flex items-start justify-between gap-3 mb-4">
        <p class="sc-eyebrow">Today's priority action</p>
        <template x-if="initialTotal > 0">
            <span class="sc-badge sc-num">
                Step&nbsp;<span x-text="currentStepNumber"></span> of <span x-text="initialTotal"></span>
            </span>
        </template>
    </div>

    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
        {{-- Left: what it is --}}
        {{-- The status icon lives INSIDE the chip.

             It used to be a 3.25rem tinted plate floating to the left of the
             text column, top-aligned against the chip rather than the heading,
             so it read as a loose square that belonged to nothing. Chip and
             icon are one fact — "this dose is overdue" — so they are one
             object, and the heading gets the full width of the column. --}}
        <div class="min-w-0">
            <span class="{{ $tone === 'alert' ? 'sc-chip sc-chip-alert' : 'sc-mark sc-mark-' . ($tone === 'ok' ? 'ok' : 'brand') }}"
                  :class="currentTone === 'alert' ? 'sc-chip sc-chip-alert' : 'sc-mark sc-mark-' + (currentTone === 'ok' ? 'ok' : 'brand')">
                <svg class="sc-i w-4 h-4" aria-hidden="true" focusable="false">
                    <use href="#i-{{ $icon }}" :href="'#i-' + currentIcon"/>
                </svg>
                <span x-text="currentTag || currentTypeLabel">{{ $tag ?: $actionType }}</span>
            </span>

            <h2 class="sc-h2 mt-3" x-text="currentTitle">{{ $headline }}</h2>
            <p class="mt-1.5 max-w-xl" style="color:var(--sc-body)" x-text="currentSubtitle">{{ $subtext }}</p>
        </div>

        {{-- Right: the one action --}}
        <div class="flex flex-wrap items-center gap-2 self-start md:self-center flex-shrink-0">
            <button
                type="button"
                x-show="isMedication && !busy"
                @click="completeMedication()"
                class="sc-btn sc-btn-primary sc-btn-sm"
                aria-label="Mark medication as taken"
            >
                <x-lucide-check class="sc-i w-5 h-5" aria-hidden="true" />
                I Took It
            </button>

            <button
                type="button"
                x-show="isTask && !busy"
                @click="completeTask()"
                class="sc-btn sc-btn-primary sc-btn-sm"
                aria-label="Mark task as complete"
            >
                <x-lucide-check class="sc-i w-5 h-5" aria-hidden="true" />
                Mark Complete
            </button>

            <a
                x-show="isVital && currentRoute"
                :href="currentRoute"
                class="sc-btn sc-btn-primary sc-btn-sm"
            >Record Now</a>

            <button
                type="button"
                x-show="isMood"
                @click="openMood()"
                class="sc-btn sc-btn-primary sc-btn-sm"
            >Open Mood Tracker</button>

            <a
                x-show="isDone"
                href="{{ route('elderly.wellness.index') }}"
                class="sc-btn sc-btn-primary sc-btn-sm"
            >Wellness Center</a>

            <button
                type="button"
                x-show="canDefer"
                @click="laterCurrent()"
                :disabled="busy"
                class="sc-btn sc-btn-ghost sc-btn-sm"
            >Later</button>

            <button
                type="button"
                x-show="busy"
                disabled
                class="sc-btn sc-btn-ghost sc-btn-sm"
            >Saving...</button>
        </div>
    </div>

    {{-- Progress. The number is always beside the bar. --}}
    <div class="mt-5">
        <div class="flex justify-between items-center mb-1.5">
            <span class="font-semibold" style="color:var(--sc-body)">Daily Progress</span>
            <span class="font-semibold sc-num" style="color:var(--sc-ink)" x-text="currentProgress + '%'">{{ $overallProgress }}%</span>
        </div>
        <div class="sc-progress"
             role="progressbar"
             aria-valuemin="0"
             aria-valuemax="100"
             aria-valuenow="{{ $overallProgress }}"
             :aria-valuenow="currentProgress"
             aria-label="Progress through today's actions">
            <div class="sc-progress-fill" :style="'width:' + currentProgress + '%'" @style(['width: ' . $overallProgress . '%'])></div>
        </div>
    </div>

    <div class="mt-5 hidden md:block" x-show="nextPreview.length > 0">
        <p class="sc-eyebrow mb-2">Coming up</p>
        <ul class="space-y-2">
            <template x-for="(item, i) in nextPreview" :key="item.id">
                <li class="sc-card-quiet px-4 py-2.5 flex items-center justify-between gap-3">
                    <p class="min-w-0 truncate" style="color:var(--sc-body)">
                        <span class="sc-num" style="color:var(--sc-muted)">Step <span x-text="currentStepNumber + i + 1"></span>:</span>
                        <span class="font-semibold" style="color:var(--sc-ink)" x-text="item.title"></span>
                    </p>
                    <span class="sc-mark flex-none" :class="item.tone ? 'sc-mark-' + item.tone : ''"><i></i><span x-text="item.tag"></span></span>
                </li>
            </template>
        </ul>
    </div>
</div>
