<x-app-layout>
    <div x-data="breathingApp()" class="min-h-screen bg-[#E0F7FA] flex flex-col">
        
        <!-- Navbar -->
        <div class="px-6 py-6 flex items-center justify-between">
            <a href="{{ route('elderly.wellness.index') }}" class="flex items-center text-teal-700 font-bold hover:text-teal-900 transition group">
                <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center shadow-sm mr-3 group-hover:shadow-md transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </div>
                Exit
            </a>
        </div>

        <!-- Content -->
        <div class="flex-1 flex flex-col items-center justify-center pb-20 px-4">
            
            <h1 class="text-3xl md:text-4xl font-[900] text-teal-900 mb-2 tracking-tight text-center">Breathe with Me</h1>
            <p class="text-teal-600 font-medium text-lg mb-10 text-center max-w-md">
                Follow the circle: Inhale as it grows, hold, and exhale as it shrinks.
            </p>

            <!-- Breathing Circle Animation -->
            <div class="relative w-80 h-80 flex items-center justify-center mb-12">
                <!-- Outer Glow Rings -->
                <div class="absolute inset-0 bg-teal-200 rounded-full opacity-20 animate-pulse" :class="isRunning ? 'scale-150' : ''" style="transition: transform 4s ease-in-out"></div>
                <div class="absolute inset-4 bg-teal-300 rounded-full opacity-20" :class="isRunning ? 'scale-125' : ''" style="transition: transform 4s ease-in-out"></div>

                <!-- Main Circle -->
                <div 
                    class="relative bg-white rounded-full shadow-2xl flex flex-col items-center justify-center transition-all ease-in-out z-10 border-8 border-teal-100"
                    :class="animationClass"
                    :style="circleStyle"
                >
                    <span class="text-2xl md:text-3xl font-[800] text-teal-600 uppercase tracking-widest" x-text="text">Ready</span>
                    <span class="text-4xl md:text-6xl font-[900] text-teal-800 mt-2 tabular-nums" x-text="secondsLeft"></span>
                </div>
            </div>

            <!-- Controls -->
            <div class="flex gap-6">
                <button 
                    @click="toggle()"
                    class="px-10 py-4 rounded-2xl font-[800] text-lg shadow-lg transform hover:-translate-y-1 transition-all flex items-center gap-2 min-w-[160px] justify-center"
                    :class="isRunning ? 'bg-white text-teal-600 hover:bg-gray-50' : 'bg-teal-600 text-white hover:bg-teal-700'"
                >
                    <svg x-show="!isRunning" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" /></svg>
                    <svg x-show="isRunning" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                    <span x-text="isRunning ? 'Pause' : 'Start'"></span>
                </button>

                <button 
                    @click="reset()"
                    class="px-8 py-4 bg-white/50 text-teal-700 font-[800] rounded-2xl hover:bg-white transition-all border border-teal-200"
                >
                    Reset
                </button>
            </div>

            <!-- Duration Setting -->
            <div class="mt-8 bg-white/60 backdrop-blur px-6 py-3 rounded-xl flex items-center gap-4">
                <span class="text-teal-800 font-bold">Cycle Speed:</span>
                <div class="flex gap-2">
                    <template x-for="sec in [3, 4, 5, 6]">
                        <button 
                            @click="setDuration(sec)"
                            class="w-8 h-8 rounded-lg font-bold text-sm transition-all"
                            :class="stepDuration === sec ? 'bg-teal-600 text-white shadow-md' : 'bg-white text-teal-600 hover:bg-teal-50'"
                            x-text="sec + 's'"
                            :disabled="isRunning"
                        ></button>
                    </template>
                </div>
            </div>

        </div>
    </div>

    <script>
        function breathingApp() {
            return {
                isRunning: false,
                text: 'Ready',
                secondsLeft: 4,
                stepDuration: 4,
                currentStep: -1, 
                timer: null,

                get circleStyle() {
                    // Base size
                    let size = 200; 
                    let scale = 1;

                    if (this.currentStep === 0) scale = 1.5; // Inhale (Grow)
                    if (this.currentStep === 1) scale = 1.5; // Hold (Stay Big)
                    if (this.currentStep === 2) scale = 1.0; // Exhale (Shrink)
                    if (this.currentStep === 3) scale = 1.0; // Hold (Stay Small)

                    return `width: ${size}px; height: ${size}px; transform: scale(${scale});`;
                },

                get animationClass() {
                    if (!this.isRunning) return '';
                    // Add smooth transition duration matching the step time
                    return `duration-[${this.stepDuration}000ms]`;
                },

                setDuration(sec) {
                    if(this.isRunning) return;
                    this.stepDuration = sec;
                    this.secondsLeft = sec;
                },

                toggle() {
                    this.isRunning ? this.pause() : this.start();
                },

                start() {
                    if (this.currentStep === -1) this.currentStep = 0; // Start cycle
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
                },

                tick() {
                    if (!this.isRunning) return;
                    this.secondsLeft--;
                    if (this.secondsLeft <= 0) {
                        this.nextStep();
                    }
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
</x-app-layout>