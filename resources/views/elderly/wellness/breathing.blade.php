<x-dashboard-layout sc>
    <x-slot:title>Breathing Exercise - SilverCare</x-slot:title>

    <div x-data="breathingApp()">
        <x-dashboard-nav
            title="Breathing Space"
            subtitle="Reduce anxiety with guided breathing"
            role="elderly"
            :unread-notifications="$unreadNotifications"
        />

        <main id="main-content" class="sc-app-main">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                {{-- Back Navigation --}}
                <div class="flex items-center justify-between gap-3">
                    <a href="{{ route('elderly.wellness.index') }}"
                       class="sc-btn sc-btn-ghost sc-btn-sm">
                        <x-lucide-arrow-left class="sc-i w-4 h-4" aria-hidden="true" />
                        <span>Back to Wellness</span>
                    </a>
                </div>

                {{-- Page Header --}}
                <div>
                    <h2 class="sc-h2">Breathe with Me</h2>
                    <p class="sc-lead mt-1">Follow the circle. Inhale as it grows, hold, then exhale as it shrinks.</p>
                </div>

                {{-- Interactive Area: Circle & Controls --}}
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center pt-2">

                    {{-- Breathing Circle --}}
                    <div class="lg:col-span-7 flex items-center justify-center min-h-[360px] py-4">
                        <div class="relative flex items-center justify-center" style="width: 320px; height: 320px;">
                            {{-- Outer subtle guide rings --}}
                            <div class="absolute inset-0 rounded-full border border-[var(--sc-line)] opacity-40 transition-transform ease-in-out duration-[4000ms]"
                                 :style="isRunning && (currentStep === 0 || currentStep === 1) ? 'transform: scale(1.15)' : 'transform: scale(1)'"></div>
                            <div class="absolute inset-4 rounded-full border border-[var(--sc-line-strong)] opacity-20 transition-transform ease-in-out duration-[4000ms]"
                                 :style="isRunning && (currentStep === 0 || currentStep === 1) ? 'transform: scale(1.08)' : 'transform: scale(1)'"></div>

                            {{-- Main animated breathing circle --}}
                            <div
                                class="relative sc-card rounded-full flex flex-col items-center justify-center shadow-xl border border-[var(--sc-line-strong)] transition-all ease-in-out select-none"
                                :style="circleStyle"
                            >
                                <span class="sc-eyebrow text-xs uppercase tracking-widest" x-text="text"></span>
                                <span class="sc-quote sc-num text-6xl font-bold leading-none mt-2 text-[var(--sc-ink)]" x-text="secondsLeft"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Controls panel --}}
                    <div class="lg:col-span-5 flex flex-col gap-5">

                        {{-- Step indicator --}}
                        <div class="sc-card p-5">
                            <p class="sc-eyebrow mb-3">Current Cycle</p>
                            <div class="flex justify-between gap-1.5">
                                <template x-for="(step, i) in ['Inhale', 'Hold', 'Exhale', 'Hold']" :key="i">
                                    <div class="flex-1 text-center">
                                        <div class="h-1.5 rounded-full mb-1.5 transition-all duration-300"
                                             :class="currentStep === i && isRunning ? 'bg-[var(--sc-brand)]' : 'bg-[var(--sc-line)]'"></div>
                                        <span class="text-[11px] font-semibold uppercase tracking-wider text-[var(--sc-muted)]"
                                              :class="currentStep === i && isRunning ? 'text-[var(--sc-ink)] font-bold' : ''"
                                              x-text="step"></span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Start / Pause + Reset --}}
                        <div class="flex gap-3">
                            <button
                                type="button"
                                @click="toggle()"
                                class="flex-1 sc-btn sc-btn-primary text-base flex items-center justify-center gap-2.5 py-3.5"
                            >
                                <x-lucide-play x-show="!isRunning" class="sc-i w-5 h-5 fill-current" aria-hidden="true" />
                                <x-lucide-pause x-show="isRunning" class="sc-i w-5 h-5 fill-current" aria-hidden="true" />
                                <span x-text="isRunning ? 'Pause' : 'Start'"></span>
                            </button>

                            <button
                                type="button"
                                @click="reset()"
                                class="sc-btn sc-btn-ghost text-base font-semibold px-5 py-3.5"
                            >
                                Reset
                            </button>
                        </div>

                        {{-- Cycle Speed --}}
                        <div class="sc-card p-5">
                            <p class="sc-eyebrow mb-3">Cycle Speed</p>
                            <div class="grid grid-cols-4 gap-2">
                                <template x-for="sec in [3, 4, 5, 6]" :key="sec">
                                    <button
                                        type="button"
                                        @click="setDuration(sec)"
                                        class="py-2.5 rounded-xl font-semibold text-sm transition-all border sc-num flex items-center justify-center"
                                        :class="stepDuration === sec
                                            ? 'bg-[var(--sc-surface-3)] text-[var(--sc-ink)] border-[var(--sc-line-strong)] shadow-sm font-bold'
                                            : 'bg-[var(--sc-surface)] text-[var(--sc-muted)] border-[var(--sc-line)] hover:text-[var(--sc-body)] hover:bg-[var(--sc-surface-2)]'"
                                        x-text="sec + 's'"
                                        :disabled="isRunning"
                                    ></button>
                                </template>
                            </div>
                        </div>

                        {{-- Tip card --}}
                        <div class="sc-card-quiet p-4 flex gap-3.5 items-start">
                            <div class="sc-plate sc-plate-sm mt-0.5">
                                <x-lucide-lightbulb class="sc-i w-4 h-4" aria-hidden="true" />
                            </div>
                            <div>
                                <p class="sc-eyebrow mb-1">Tip</p>
                                <p class="text-sm text-[var(--sc-body)] leading-relaxed" x-text="tip"></p>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </main>
    </div>

    @push('scripts')
    <script>
        function breathingApp() {
            return {
                isRunning: false,
                text: 'Ready',
                secondsLeft: 4,
                stepDuration: 4,
                currentStep: -1,
                timer: null,
                tips: [
                    'Breathe through your nose for a calmer effect.',
                    'Close your eyes and focus on the circle rhythm.',
                    'Try to relax your shoulders as you exhale.',
                    'Even 2 minutes of breathing can ease anxiety.',
                    'Let your belly rise first, then your chest.'
                ],
                tip: '',

                init() {
                    this.tip = this.tips[Math.floor(Math.random() * this.tips.length)];
                },

                get circleStyle() {
                    let scale = 1;
                    if (this.currentStep === 0) scale = 1.45;
                    if (this.currentStep === 1) scale = 1.45;
                    if (this.currentStep === 2) scale = 1.0;
                    if (this.currentStep === 3) scale = 1.0;
                    const duration = this.stepDuration * 1000;
                    return `width: 200px; height: 200px; transform: scale(${scale}); transition: transform ${duration}ms ease-in-out;`;
                },

                setDuration(sec) {
                    if (this.isRunning) return;
                    this.stepDuration = sec;
                    this.secondsLeft = sec;
                },

                toggle() {
                    this.isRunning ? this.pause() : this.start();
                },

                start() {
                    if (this.currentStep === -1) this.currentStep = 0;
                    this.isRunning = true;
                    this.processStep();
                    this.timer = setInterval(() => this.tick(), 1000);
                },

                pause() {
                    this.isRunning = false;
                    clearInterval(this.timer);
                },

                reset() {
                    this.pause();
                    this.currentStep = -1;
                    this.text = 'Ready';
                    this.secondsLeft = this.stepDuration;
                    this.tip = this.tips[Math.floor(Math.random() * this.tips.length)];
                },

                tick() {
                    if (!this.isRunning) return;
                    this.secondsLeft--;
                    if (this.secondsLeft <= 0) this.nextStep();
                },

                nextStep() {
                    this.currentStep = (this.currentStep + 1) % 4;
                    this.secondsLeft = this.stepDuration;
                    this.processStep();
                },

                processStep() {
                    const steps = ['Inhale', 'Hold', 'Exhale', 'Hold'];
                    this.text = steps[this.currentStep];
                }
            }
        }
    </script>
    @endpush
</x-dashboard-layout>