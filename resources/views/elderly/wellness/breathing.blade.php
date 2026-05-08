<x-dashboard-layout>
    <x-slot:title>Breathing Exercise - SilverCare</x-slot:title>
    <x-slot:bodyClass>h-screen overflow-hidden bg-[#E0F7FA]</x-slot:bodyClass>
 
    <div x-data="breathingApp()" class="h-full flex flex-col">
        <x-dashboard-nav
            title="Breathing Space"
            subtitle="Reduce anxiety with guided breathing"
            role="elderly"
            :unread-notifications="$unreadNotifications"
        />
 
        <main id="main-content" class="flex-1 overflow-hidden max-w-5xl w-full mx-auto px-6 flex flex-col py-4">
 
            {{-- Top bar: title left, back right --}}
            <div class="flex justify-between items-start mb-4 flex-shrink-0">
                <div>
                    <h1 class="text-3xl font-[900] text-teal-900 tracking-tight leading-tight">Breathe with Me</h1>
                    <p class="text-teal-600 font-medium text-base mt-0.5">Follow the circle. Inhale as it grows, hold, then exhale as it shrinks.</p>
                </div>
                <a href="{{ route('elderly.wellness.index') }}" class="back-nav-pill !text-teal-700 !bg-white/50 hover:!bg-white flex-shrink-0 ml-6 mt-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Back to Wellness
                </a>
            </div>
 
            {{-- Main content: circle LEFT, controls RIGHT --}}
            <div class="flex-1 flex items-center gap-10 min-h-0">
 
                {{-- LEFT: Breathing Circle --}}
                <div class="flex-1 flex items-center justify-center">
                    <div class="relative flex items-center justify-center" style="width: 320px; height: 320px;">
                        {{-- Outer glow rings --}}
                        <div class="absolute inset-0 bg-teal-200 rounded-full opacity-20 transition-transform ease-in-out duration-[4000ms]"
                             :style="isRunning && currentStep === 0 ? 'transform: scale(1.4)' : 'transform: scale(1)'"></div>
                        <div class="absolute inset-6 bg-teal-300 rounded-full opacity-20 transition-transform ease-in-out duration-[4000ms]"
                             :style="isRunning && currentStep === 0 ? 'transform: scale(1.3)' : 'transform: scale(1)'"></div>
 
                        {{-- Main circle --}}
                        <div
                            class="relative bg-white rounded-full shadow-2xl flex flex-col items-center justify-center border-8 border-teal-100 transition-all ease-in-out"
                            :style="circleStyle"
                        >
                            <span class="text-xl font-[800] text-teal-600 uppercase tracking-widest" x-text="text"></span>
                            <span class="text-6xl font-[900] text-teal-800 tabular-nums leading-none mt-1" x-text="secondsLeft"></span>
                        </div>
                    </div>
                </div>
 
                {{-- RIGHT: Controls panel --}}
                <div class="w-72 flex flex-col gap-5 flex-shrink-0">
 
                    {{-- Step indicator --}}
                    <div class="bg-white/70 backdrop-blur rounded-2xl px-5 py-4 border border-teal-100">
                        <p class="text-xs font-bold uppercase tracking-widest text-teal-500 mb-3">Current Cycle</p>
                        <div class="flex justify-between gap-1">
                            <template x-for="(step, i) in ['Inhale', 'Hold', 'Exhale', 'Hold']" :key="i">
                                <div class="flex-1 text-center">
                                    <div class="h-1.5 rounded-full mb-1.5 transition-all duration-300"
                                         :class="currentStep === i && isRunning ? 'bg-teal-500' : 'bg-teal-100'"></div>
                                    <span class="text-[10px] font-bold text-teal-400 uppercase" x-text="step"></span>
                                </div>
                            </template>
                        </div>
                    </div>
 
                    {{-- Start / Pause + Reset --}}
                    <div class="flex gap-3">
                        <button
                            @click="toggle()"
                            class="flex-1 py-5 rounded-2xl font-[800] text-lg shadow-lg transition-all flex items-center justify-center gap-2"
                            :class="isRunning ? 'bg-white text-teal-600 hover:bg-gray-50 border border-teal-200' : 'bg-teal-600 text-white hover:bg-teal-700'"
                        >
                            <svg x-show="!isRunning" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" /></svg>
                            <svg x-show="isRunning" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                            <span x-text="isRunning ? 'Pause' : 'Start'"></span>
                        </button>
 
                        <button
                            @click="reset()"
                            class="px-5 py-5 bg-white/60 text-teal-700 font-[800] rounded-2xl hover:bg-white transition-all border border-teal-200 text-lg"
                        >
                            Reset
                        </button>
                    </div>
 
                    {{-- Cycle Speed --}}
                    <div class="bg-white/70 backdrop-blur rounded-2xl px-5 py-4 border border-teal-100">
                        <p class="text-xs font-bold uppercase tracking-widest text-teal-500 mb-3">Cycle Speed</p>
                        <div class="grid grid-cols-4 gap-2">
                            <template x-for="sec in [3, 4, 5, 6]">
                                <button
                                    @click="setDuration(sec)"
                                    class="py-3 rounded-xl font-bold text-base transition-all"
                                    :class="stepDuration === sec ? 'bg-teal-600 text-white shadow-md' : 'bg-white text-teal-600 hover:bg-teal-50 border border-teal-100'"
                                    x-text="sec + 's'"
                                    :disabled="isRunning"
                                ></button>
                            </template>
                        </div>
                    </div>
 
                    {{-- Tip card --}}
                    <div class="bg-teal-600/10 border border-teal-200 rounded-2xl px-5 py-4">
                        <p class="text-xs font-bold uppercase tracking-widest text-teal-500 mb-1">Tip</p>
                        <p class="text-sm font-medium text-teal-800 leading-relaxed" x-text="tip"></p>
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