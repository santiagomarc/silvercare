<x-dashboard-layout sc>
    <x-slot:title>Morning Stretch - SilverCare</x-slot:title>

    <div id="main-content" x-data="stretchGuide()" class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
        <x-dashboard-nav
            title="Body Movement"
            subtitle="Exercises for mobility and balance"
            role="elderly"
            :unread-notifications="$unreadNotifications"
        />

        {{-- Top bar: routine tabs LEFT, back button RIGHT --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="inline-flex p-1 rounded-xl bg-[var(--sc-surface-quiet)] border border-[var(--sc-border-subtle)] self-start max-w-full overflow-x-auto">
                <button
                    type="button"
                    @click="setLevel(0)"
                    :class="level === 0 ? 'bg-[var(--sc-surface)] text-[var(--sc-ink)] shadow-xs border border-[var(--sc-border)] font-semibold' : 'text-[var(--sc-ink-muted)] hover:text-[var(--sc-ink)] font-medium'"
                    class="px-3.5 py-1.5 sm:px-5 sm:py-2 rounded-lg text-xs sm:text-sm font-semibold transition-colors whitespace-nowrap"
                >
                    {{ __('Seated') }}
                </button>
                <button
                    type="button"
                    @click="setLevel(1)"
                    :class="level === 1 ? 'bg-[var(--sc-surface)] text-[var(--sc-ink)] shadow-xs border border-[var(--sc-border)] font-semibold' : 'text-[var(--sc-ink-muted)] hover:text-[var(--sc-ink)] font-medium'"
                    class="px-3.5 py-1.5 sm:px-5 sm:py-2 rounded-lg text-xs sm:text-sm font-semibold transition-colors whitespace-nowrap"
                >
                    {{ __('Standing') }}
                </button>
                <button
                    type="button"
                    @click="setLevel(2)"
                    :class="level === 2 ? 'bg-[var(--sc-surface)] text-[var(--sc-ink)] shadow-xs border border-[var(--sc-border)] font-semibold' : 'text-[var(--sc-ink-muted)] hover:text-[var(--sc-ink)] font-medium'"
                    class="px-3.5 py-1.5 sm:px-5 sm:py-2 rounded-lg text-xs sm:text-sm font-semibold transition-colors whitespace-nowrap"
                >
                    {{ __('Balance') }}
                </button>
            </div>

            <a href="{{ route('elderly.wellness.index') }}" class="sc-btn sc-btn-ghost inline-flex items-center gap-1.5 text-sm self-start sm:self-auto">
                <x-lucide-arrow-left class="sc-i w-4 h-4" aria-hidden="true" />
                <span>{{ __('Back to Wellness') }}</span>
            </a>
        </div>

        {{-- Main 2-column layout --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            {{-- LEFT: Exercise info card --}}
            <div class="lg:col-span-5 sc-card p-5 sm:p-7 flex flex-col items-center text-center gap-6">
                {{-- Icon Plate --}}
                <div class="sc-plate w-20 h-20 rounded-2xl flex items-center justify-center text-[var(--sc-ink)] flex-shrink-0">
                    <div x-html="current.icon" class="[&_svg]:sc-i [&_svg]:w-10 [&_svg]:h-10 text-[var(--sc-ink)]"></div>
                </div>

                {{-- Title & meta --}}
                <div>
                    <h2 class="text-2xl sm:text-3xl font-bold text-[var(--sc-ink)] tracking-tight" x-text="current.title"></h2>
                    <p class="text-sm font-semibold text-[var(--sc-ink-muted)] mt-1.5 flex items-center justify-center gap-2">
                        <span x-text="current.duration" class="sc-num"></span>
                        <span aria-hidden="true">&bull;</span>
                        <span x-text="current.difficulty"></span>
                    </p>
                </div>

                {{-- Benefits --}}
                <div class="sc-card-quiet rounded-2xl p-4 sm:p-5 w-full text-left">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-[var(--sc-ink-muted)] mb-3">
                        {{ __('Benefits') }}
                    </h3>
                    <ul class="space-y-2.5">
                        <template x-for="b in current.benefits" :key="b">
                            <li class="flex items-start gap-2.5 text-sm font-medium text-[var(--sc-ink)]">
                                <x-lucide-check class="sc-i w-4 h-4 text-[var(--sc-emerald)] flex-shrink-0 mt-0.5" aria-hidden="true" />
                                <span x-text="b"></span>
                            </li>
                        </template>
                    </ul>
                </div>

                {{-- Caution --}}
                <div class="w-full rounded-xl p-3.5 bg-[var(--sc-surface-quiet)] border border-[var(--sc-border-subtle)] flex items-start gap-3 text-left">
                    <x-lucide-triangle-alert class="sc-i w-4 h-4 text-[var(--sc-warn)] flex-shrink-0 mt-0.5" aria-hidden="true" />
                    <p class="text-xs font-semibold text-[var(--sc-ink)] leading-relaxed" x-text="current.caution"></p>
                </div>
            </div>

            {{-- RIGHT: Checklist + nav --}}
            <div class="lg:col-span-7 flex flex-col gap-6">

                {{-- Header + progress --}}
                <div class="sc-card p-4 sm:p-6 lg:p-7">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-xl sm:text-2xl font-bold text-[var(--sc-ink)]">
                            {{ __('Steps Checklist') }}
                        </h3>
                        <span class="text-xs font-bold text-[var(--sc-ink-muted)] uppercase tracking-wider sc-num">
                            <span x-text="current.steps.filter(s => s.completed).length"></span> / <span x-text="current.steps.length"></span> {{ __('done') }}
                        </span>
                    </div>

                    {{-- Progress bar --}}
                    <div class="sc-progress mb-6" role="progressbar" :aria-valuenow="current.steps.filter(s => s.completed).length" :aria-valuemax="current.steps.length">
                        <div class="sc-progress-fill" :style="`width: ${(current.steps.filter(s => s.completed).length / current.steps.length) * 100}%`"></div>
                    </div>

                    {{-- Checklist steps --}}
                    <div class="space-y-3">
                        <template x-for="(step, idx) in current.steps" :key="idx">
                            <div
                                @click="toggleStep(idx)"
                                class="sc-card sc-lift flex items-center gap-3.5 sm:gap-4 p-3.5 sm:p-4 cursor-pointer select-none transition-all"
                                :class="step.completed ? 'opacity-80' : ''"
                            >
                                {{-- Checkbox --}}
                                <button
                                    type="button"
                                    class="sc-check-btn"
                                    :class="step.completed ? 'sc-check-on' : 'sc-check-off'"
                                    :aria-label="step.completed ? 'Mark incomplete: ' + step.text : 'Mark complete: ' + step.text"
                                >
                                    <svg class="sc-i w-3.5 h-3.5 transition-all duration-200"
                                         :class="step.completed ? 'opacity-100 scale-100' : 'opacity-0 scale-0'"
                                         aria-hidden="true" focusable="false" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </button>

                                {{-- Step label + text --}}
                                <div class="flex items-baseline gap-2.5 sm:gap-3 flex-1 min-w-0">
                                    <span class="text-xs font-bold text-[var(--sc-ink-muted)] uppercase tracking-wider flex-shrink-0 sc-num" x-text="'Step ' + (idx + 1)"></span>
                                    <p class="text-sm sm:text-base font-semibold transition-colors leading-snug break-words"
                                       :class="step.completed ? 'line-through sc-task-text-done' : 'text-[var(--sc-ink)]'"
                                       x-text="step.text"></p>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Bottom navigation controls --}}
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mt-6 pt-6 border-t border-[var(--sc-border)]">
                        <div class="flex items-center justify-between sm:justify-start gap-4">
                            <button
                                type="button"
                                @click="prev()"
                                :disabled="currentIndex === 0"
                                class="sc-btn sc-btn-secondary inline-flex items-center gap-2 disabled:opacity-40"
                            >
                                <x-lucide-arrow-left class="sc-i w-4 h-4" aria-hidden="true" />
                                <span>{{ __('Previous') }}</span>
                            </button>

                            <div class="text-xs font-bold text-[var(--sc-ink-muted)] uppercase tracking-wider sc-num">
                                {{ __('Exercise') }} <span x-text="currentIndex + 1"></span> {{ __('of') }} <span x-text="exercises[level].length"></span>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="next()"
                            :disabled="!allStepsCompleted"
                            class="sc-btn sc-btn-primary inline-flex items-center justify-center gap-2 disabled:opacity-40 w-full sm:w-auto"
                        >
                            <span x-text="currentIndex === exercises[level].length - 1 ? '{{ __('Finish Session') }}' : '{{ __('Next Exercise') }}'"></span>
                            <x-lucide-arrow-right class="sc-i w-4 h-4" aria-hidden="true" />
                        </button>
                    </div>
                </div>

            </div>
        </div>

        {{-- Complete Modal --}}
        <template x-if="showComplete">
            <div
                class="sc-scrim flex items-center justify-center p-4"
                x-cloak
                x-show="showComplete"
                x-transition.opacity.duration.200ms
                @click.self="showComplete = false"
            >
                <div
                    class="sc-dialog text-center max-w-md w-full"
                    x-show="showComplete"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                >
                    <div class="sc-plate sc-plate-ok w-16 h-16 rounded-2xl mx-auto mb-5 flex items-center justify-center">
                        <x-lucide-party-popper class="sc-i w-8 h-8 text-[var(--sc-emerald)]" aria-hidden="true" />
                    </div>
                    <h3 class="text-2xl sm:text-3xl font-bold text-[var(--sc-ink)] tracking-tight mb-2">
                        {{ __('Session Complete!') }}
                    </h3>
                    <p class="text-sm sm:text-base text-[var(--sc-ink-muted)] mb-6 leading-relaxed">
                        {{ __("You've completed the") }} <span class="font-semibold text-[var(--sc-ink)]" x-text="levelNames[level]"></span> {{ __("routine. Excellent work keeping your body moving.") }}
                    </p>

                    <div class="flex flex-col gap-3">
                        <button
                            type="button"
                            @click="showComplete = false; currentIndex = 0; resetChecklist();"
                            class="sc-btn sc-btn-primary w-full py-3.5 text-base justify-center inline-flex items-center gap-2"
                        >
                            <x-lucide-rotate-ccw class="sc-i w-4 h-4" aria-hidden="true" />
                            <span>{{ __('Try Another Routine') }}</span>
                        </button>

                        <a
                            href="{{ route('elderly.wellness.index') }}"
                            class="sc-btn sc-btn-secondary w-full py-3.5 text-base justify-center"
                        >
                            {{ __('Back to Wellness') }}
                        </a>
                    </div>
                </div>
            </div>
        </template>
    </div>

    @push('scripts')
    <script>
        function stretchGuide() {
            return {
                level: 0,
                levelNames: ['Seated', 'Standing', 'Balance'],
                currentIndex: 0,
                showComplete: false,
                exercises: [
                    [
                        {
                            title: 'Neck Rolls', duration: '2 mins', difficulty: 'Easy', caution: 'Stop if you feel dizzy.',
                            icon: `<svg class="sc-i w-10 h-10" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`,
                            benefits: ['Relieves neck tension', 'Improves flexibility'],
                            steps: [{text:'Sit straight with feet flat.', completed:false}, {text:'Tilt head to right shoulder.', completed:false}, {text:'Hold for 5 seconds.', completed:false}, {text:'Repeat on left side.', completed:false}]
                        },
                        {
                            title: 'Ankle Circles', duration: '2 mins', difficulty: 'Easy', caution: 'Keep movements smooth.',
                            icon: `<svg class="sc-i w-10 h-10" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>`,
                            benefits: ['Improves circulation', 'Reduces stiffness'],
                            steps: [{text:'Lift right foot slightly.', completed:false}, {text:'Rotate ankle clockwise 5 times.', completed:false}, {text:'Rotate counter-clockwise 5 times.', completed:false}, {text:'Switch to left foot.', completed:false}]
                        }
                    ],
                    [
                        {
                            title: 'Marching in Place', duration: '3 mins', difficulty: 'Medium', caution: 'Use a chair for support if needed.',
                            icon: `<svg class="sc-i w-10 h-10" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>`,
                            benefits: ['Boosts heart rate', 'Strengthens legs'],
                            steps: [{text:'Stand tall near a chair.', completed:false}, {text:'Lift knees alternately.', completed:false}, {text:'Swing arms gently.', completed:false}, {text:'Continue for 30 steps.', completed:false}]
                        }
                    ],
                    [
                        {
                            title: 'Single Leg Stand', duration: '2 mins', difficulty: 'Hard', caution: 'Hold onto a sturdy chair.',
                            icon: `<svg class="sc-i w-10 h-10" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`,
                            benefits: ['Improves balance', 'Prevents falls'],
                            steps: [{text:'Stand behind a chair.', completed:false}, {text:'Lift right foot off ground.', completed:false}, {text:'Hold for 10 seconds.', completed:false}, {text:'Switch legs.', completed:false}]
                        }
                    ]
                ],

                get current() { return this.exercises[this.level][this.currentIndex]; },
                get allStepsCompleted() { return this.current.steps.every(s => s.completed); },

                setLevel(lvl) {
                    this.level = lvl;
                    this.currentIndex = 0;
                    this.resetChecklist();
                },

                toggleStep(idx) {
                    this.current.steps[idx].completed = !this.current.steps[idx].completed;
                },

                resetChecklist() {
                    this.exercises[this.level].forEach(ex => {
                        ex.steps.forEach(s => s.completed = false);
                    });
                },

                next() {
                    if (this.currentIndex < this.exercises[this.level].length - 1) {
                        this.currentIndex++;
                    } else {
                        this.showComplete = true;
                    }
                },

                prev() {
                    if (this.currentIndex > 0) this.currentIndex--;
                }
            }
        }
    </script>
    @endpush
</x-dashboard-layout>