<x-dashboard-layout>
    <x-slot:title>Morning Stretch - SilverCare</x-slot:title>
    <x-slot:bodyClass>h-screen overflow-hidden bg-[#FFF3E0]</x-slot:bodyClass>
 
    <div x-data="stretchGuide()" class="h-full flex flex-col">
        <x-dashboard-nav
            title="Body Movement"
            subtitle="Exercises for mobility and balance"
            role="elderly"
            :unread-notifications="$unreadNotifications"
        />
 
        {{-- Everything below nav fills remaining height --}}
        <div class="flex-1 overflow-hidden flex flex-col max-w-6xl w-full mx-auto px-6 py-4 min-h-0">
 
            {{-- Top bar: level tabs LEFT, back button RIGHT --}}
            <div class="flex items-center justify-between mb-4 flex-shrink-0">
                <div class="flex bg-white p-1.5 rounded-2xl shadow-sm border border-orange-100 gap-1">
                    <button @click="setLevel(0)" :class="level === 0 ? 'bg-orange-100 text-orange-700 shadow-sm' : 'text-gray-400 hover:text-gray-600'" class="px-6 py-2.5 rounded-xl font-[800] text-base transition-all">Seated</button>
                    <button @click="setLevel(1)" :class="level === 1 ? 'bg-orange-100 text-orange-700 shadow-sm' : 'text-gray-400 hover:text-gray-600'" class="px-6 py-2.5 rounded-xl font-[800] text-base transition-all">Standing</button>
                    <button @click="setLevel(2)" :class="level === 2 ? 'bg-orange-100 text-orange-700 shadow-sm' : 'text-gray-400 hover:text-gray-600'" class="px-6 py-2.5 rounded-xl font-[800] text-base transition-all">Balance</button>
                </div>
 
                <a href="{{ route('elderly.wellness.index') }}" class="back-nav-pill !text-orange-800 !bg-white/60 hover:!bg-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Back to Wellness
                </a>
            </div>
 
            {{-- Main 2-column layout: fills remaining space --}}
            <div class="flex-1 flex gap-6 min-h-0">
 
                {{-- LEFT: Exercise info card — fixed, no scroll --}}
                <div class="w-5/12 flex-shrink-0 bg-white rounded-[32px] shadow-xl relative overflow-hidden flex flex-col border border-orange-100">
                    <div class="h-3 bg-orange-400 flex-shrink-0"></div>
 
                    <div class="flex-1 flex flex-col items-center justify-center text-center px-8 py-6 gap-4">
                        {{-- Icon --}}
                        <div class="w-24 h-24 bg-orange-50 rounded-full flex items-center justify-center text-orange-500 shadow-inner flex-shrink-0">
                            <div x-html="current.icon"></div>
                        </div>
 
                        {{-- Title & meta --}}
                        <div>
                            <h2 class="text-3xl font-[900] text-gray-800 leading-tight" x-text="current.title"></h2>
                            <p class="text-orange-600 font-bold text-lg mt-1" x-text="current.duration + ' • ' + current.difficulty"></p>
                        </div>
 
                        {{-- Benefits --}}
                        <div class="bg-orange-50 rounded-2xl px-6 py-4 w-full text-left">
                            <h4 class="font-[800] text-orange-800 text-xs uppercase tracking-widest mb-3">Benefits</h4>
                            <ul class="space-y-2">
                                <template x-for="b in current.benefits">
                                    <li class="flex items-center gap-3 text-gray-700 font-semibold text-base">
                                        <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                        <span x-text="b"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>
 
                        {{-- Caution --}}
                        <div class="w-full bg-yellow-50 border-l-4 border-yellow-400 px-4 py-3 rounded-r-2xl flex items-start gap-3">
                            <x-lucide-triangle-alert class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" aria-hidden="true" />
                            <p class="text-yellow-800 text-sm font-bold leading-snug" x-text="current.caution"></p>
                        </div>
                    </div>
                </div>
 
                {{-- RIGHT: Checklist + nav --}}
                <div class="flex-1 flex flex-col min-h-0">
 
                    {{-- Header + progress --}}
                    <div class="flex-shrink-0 mb-3">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-2xl font-[900] text-gray-800">Steps Checklist</h3>
                            <span class="text-sm font-[800] text-gray-400 uppercase tracking-widest">
                                <span x-text="current.steps.filter(s => s.completed).length"></span> / <span x-text="current.steps.length"></span> done
                            </span>
                        </div>
                        {{-- Progress bar --}}
                        <div class="h-2.5 bg-orange-100 rounded-full overflow-hidden">
                            <div class="h-full bg-orange-400 rounded-full transition-all duration-500"
                                 :style="`width: ${(current.steps.filter(s => s.completed).length / current.steps.length) * 100}%`"></div>
                        </div>
                    </div>
 
                    {{-- Scrollable checklist steps --}}
                    <div class="flex-1 overflow-y-auto min-h-0 space-y-3 pr-1">
                        <template x-for="(step, idx) in current.steps" :key="idx">
                            <div
                                @click="toggleStep(idx)"
                                class="flex items-center gap-5 px-6 py-5 rounded-[20px] border cursor-pointer transition-all duration-200 hover:scale-[1.01] active:scale-[0.99] select-none"
                                :class="step.completed ? 'bg-green-50 border-green-200 shadow-sm shadow-green-100' : 'bg-white border-orange-100 hover:border-orange-300 shadow-sm'"
                            >
                                {{-- Large checkbox --}}
                                <div class="w-10 h-10 rounded-full border-2 flex items-center justify-center flex-shrink-0 transition-all duration-200"
                                     :class="step.completed ? 'bg-green-500 border-green-500 shadow-md shadow-green-200' : 'border-gray-300 bg-white'">
                                    <svg x-show="step.completed" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                </div>
 
                                {{-- Step label + text --}}
                                <div class="flex items-center gap-3 flex-1 min-w-0">
                                    <span class="text-xs font-[900] text-orange-300 uppercase tracking-widest flex-shrink-0" x-text="'Step ' + (idx + 1)"></span>
                                    <p class="text-lg font-bold leading-snug transition-colors"
                                       :class="step.completed ? 'text-green-700 line-through decoration-green-400/50' : 'text-gray-700'"
                                       x-text="step.text"></p>
                                </div>
                            </div>
                        </template>
                    </div>
 
                    {{-- Bottom nav: always visible, never scrolls away --}}
                    <div class="flex items-center justify-between mt-4 pt-4 border-t border-orange-100 flex-shrink-0">
                        <button
                            @click="prev()"
                            :disabled="currentIndex === 0"
                            class="px-8 py-4 rounded-2xl font-[800] text-base text-gray-500 hover:bg-white hover:shadow-sm disabled:opacity-30 transition-all"
                        >
                            ← Previous
                        </button>
 
                        <div class="text-sm font-[900] text-gray-400 uppercase tracking-widest">
                            Exercise <span x-text="currentIndex + 1"></span> of <span x-text="exercises[level].length"></span>
                        </div>
 
                        <button
                            @click="next()"
                            :disabled="!allStepsCompleted"
                            :class="allStepsCompleted ? 'bg-orange-500 hover:bg-orange-600 shadow-lg shadow-orange-200 hover:-translate-y-0.5 text-white' : 'bg-gray-200 text-gray-400 cursor-not-allowed'"
                            class="px-8 py-4 rounded-2xl font-[900] text-base transition-all flex items-center gap-2"
                        >
                            <span x-text="currentIndex === exercises[level].length - 1 ? 'Finish Session' : 'Next Exercise'"></span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                        </button>
                    </div>
                </div>
 
            </div>
        </div>
 
        {{-- Complete Modal --}}
        <template x-if="showComplete">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" x-transition>
                <div class="bg-white rounded-[40px] p-10 max-w-md w-full text-center shadow-2xl border border-orange-100">
                    <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner">
                        <x-lucide-party-popper class="w-12 h-12 text-green-600" aria-hidden="true" />
                    </div>
                    <h2 class="text-4xl font-[900] text-gray-800 mb-3 tracking-tight">Session Complete!</h2>
                    <p class="text-gray-600 mb-8 text-lg font-medium leading-relaxed">You've completed the <span x-text="levelNames[level]"></span> routine. Excellent work keeping your body moving.</p>
 
                    <div class="flex flex-col gap-3">
                        {{-- Primary: Stay on page (filled) --}}
                        <button
                            @click="showComplete = false; currentIndex = 0; resetChecklist();"
                            class="w-full py-5 bg-green-600 text-white font-[900] text-xl rounded-[24px] hover:bg-green-700 transition-all shadow-xl shadow-green-100 active:scale-95"
                        >
                            🎯 Try Another Routine
                        </button>
 
                        {{-- Secondary: Back to Wellness (outline only) --}}
                        <a href="{{ route('elderly.wellness.index') }}"
                           class="block w-full py-5 border-2 border-green-600 text-green-700 font-[900] text-xl rounded-[24px] hover:bg-green-50 transition-all active:scale-95">
                            Back to Wellness
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
                            icon: `<svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`,
                            benefits: ['Relieves neck tension', 'Improves flexibility'],
                            steps: [{text:'Sit straight with feet flat.', completed:false}, {text:'Tilt head to right shoulder.', completed:false}, {text:'Hold for 5 seconds.', completed:false}, {text:'Repeat on left side.', completed:false}]
                        },
                        {
                            title: 'Ankle Circles', duration: '2 mins', difficulty: 'Easy', caution: 'Keep movements smooth.',
                            icon: `<svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>`,
                            benefits: ['Improves circulation', 'Reduces stiffness'],
                            steps: [{text:'Lift right foot slightly.', completed:false}, {text:'Rotate ankle clockwise 5 times.', completed:false}, {text:'Rotate counter-clockwise 5 times.', completed:false}, {text:'Switch to left foot.', completed:false}]
                        }
                    ],
                    [
                        {
                            title: 'Marching in Place', duration: '3 mins', difficulty: 'Medium', caution: 'Use a chair for support if needed.',
                            icon: `<svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>`,
                            benefits: ['Boosts heart rate', 'Strengthens legs'],
                            steps: [{text:'Stand tall near a chair.', completed:false}, {text:'Lift knees alternately.', completed:false}, {text:'Swing arms gently.', completed:false}, {text:'Continue for 30 steps.', completed:false}]
                        }
                    ],
                    [
                        {
                            title: 'Single Leg Stand', duration: '2 mins', difficulty: 'Hard', caution: 'Hold onto a sturdy chair.',
                            icon: `<svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`,
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