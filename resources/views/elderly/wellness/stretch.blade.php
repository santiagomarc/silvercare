<x-dashboard-layout>
    <x-slot:title>Morning Stretch - SilverCare</x-slot:title>
    <x-slot:bodyClass>min-h-screen bg-[#FFF3E0]</x-slot:bodyClass>

    <div x-data="stretchGuide()">
        <x-dashboard-nav
            title="Body Movement"
            subtitle="Exercises for mobility and balance"
            role="elderly"
            :unread-notifications="$unreadNotifications"
        />

        <main id="main-content" class="max-w-6xl mx-auto px-6 py-8 pb-32">
            
            {{-- Back Navigation & Level Selector --}}
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-8">
                <a href="{{ route('elderly.wellness.index') }}" class="back-nav-pill order-last !text-orange-800 !bg-white/50 hover:!bg-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Back to Wellness
                </a>
                
                <div class="flex bg-white p-1.5 rounded-2xl shadow-sm border border-orange-100">
                    <button @click="setLevel(0)" :class="level === 0 ? 'bg-orange-100 text-orange-700' : 'text-gray-500 hover:text-gray-700'" class="px-5 py-2.5 rounded-xl text-sm font-bold transition-all">Seated</button>
                    <button @click="setLevel(1)" :class="level === 1 ? 'bg-orange-100 text-orange-700' : 'text-gray-500 hover:text-gray-700'" class="px-5 py-2.5 rounded-xl text-sm font-bold transition-all">Standing</button>
                    <button @click="setLevel(2)" :class="level === 2 ? 'bg-orange-100 text-orange-700' : 'text-gray-500 hover:text-gray-700'" class="px-5 py-2.5 rounded-xl text-sm font-bold transition-all">Balance</button>
                </div>
            </div>

            <!-- Content Area -->
            <div class="flex flex-col lg:flex-row gap-8 items-start">
                
                <!-- Left: Exercise Card -->
                <div class="w-full lg:w-5/12 bg-white rounded-[32px] shadow-xl p-8 relative overflow-hidden min-h-[420px] flex flex-col justify-center items-center text-center border border-orange-100">
                    <div class="absolute top-0 left-0 w-full h-3 bg-orange-400"></div>
                    
                    <div class="w-28 h-28 bg-orange-50 rounded-full flex items-center justify-center text-orange-500 mb-6 shadow-inner">
                        <div x-html="current.icon"></div>
                    </div>

                    <h2 class="text-3xl font-[900] text-gray-800 mb-2" x-text="current.title"></h2>
                    <p class="text-orange-600 font-bold mb-6 text-lg" x-text="current.duration + ' • ' + current.difficulty"></p>

                    <div class="bg-orange-50 p-6 rounded-2xl text-left w-full shadow-sm">
                        <h4 class="font-bold text-orange-800 text-xs mb-4 uppercase tracking-wider">Benefits</h4>
                        <ul class="space-y-3">
                            <template x-for="b in current.benefits">
                                <li class="flex items-start text-gray-700 text-sm font-medium leading-tight">
                                    <svg class="w-5 h-5 text-green-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    <span x-text="b"></span>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>

                <!-- Right: Step Checklist -->
                <div class="w-full lg:w-7/12">
                    <div class="flex justify-between items-end mb-6">
                        <h3 class="text-2xl font-[900] text-gray-800">Checklist</h3>
                        <p class="text-sm text-gray-400 font-bold uppercase tracking-widest">Mark when done</p>
                    </div>
                    
                    <div class="space-y-4">
                        <template x-for="(step, idx) in current.steps" :key="idx">
                            <div 
                                @click="toggleStep(idx)"
                                class="flex items-start p-6 rounded-[24px] shadow-sm border cursor-pointer transition-all duration-300 transform hover:scale-[1.01]"
                                :class="step.completed ? 'bg-green-50 border-green-200' : 'bg-white border-orange-100 hover:border-orange-300 shadow-orange-100'"
                            >
                                <!-- Checkbox -->
                                <div class="w-8 h-8 rounded-full flex items-center justify-center border-2 transition-all flex-shrink-0 mt-0.5 mr-5"
                                     :class="step.completed ? 'bg-green-500 border-green-500 shadow-md shadow-green-200' : 'border-gray-200 bg-white'">
                                    <svg x-show="step.completed" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                </div>
                                
                                <!-- Text -->
                                <div>
                                    <p class="text-lg font-bold leading-relaxed transition-colors"
                                       :class="step.completed ? 'text-green-800 line-through decoration-green-800/30' : 'text-gray-700'">
                                        <span x-text="step.text"></span>
                                    </p>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="mt-8 bg-yellow-50 border-l-4 border-yellow-400 p-5 rounded-r-[24px] flex items-start shadow-sm shadow-yellow-100">
                        <x-lucide-triangle-alert class="w-6 h-6 text-yellow-600 mr-4 flex-shrink-0 mt-0.5" aria-hidden="true" />
                        <p class="text-yellow-800 text-sm font-bold leading-relaxed" x-text="current.caution"></p>
                    </div>
                </div>
            </div>
        </main>

        <!-- Navigation Bar -->
        <div class="fixed bottom-0 left-0 w-full bg-white/80 backdrop-blur-md border-t border-gray-100 p-6 shadow-[0_-10px_40px_rgba(0,0,0,0.05)] z-40">
            <div class="max-w-6xl mx-auto flex justify-between items-center">
                <button @click="prev()" :disabled="currentIndex === 0" class="px-10 py-4 rounded-2xl font-bold text-gray-500 hover:bg-gray-100 disabled:opacity-30 transition-all active:scale-95">
                    Previous
                </button>

                <div class="hidden sm:block text-xs font-[900] text-gray-400 uppercase tracking-[2px]">
                    Exercise <span x-text="currentIndex + 1"></span> of <span x-text="exercises[level].length"></span>
                </div>

                <button 
                    @click="next()"
                    :class="allStepsCompleted ? 'bg-orange-600 hover:bg-orange-700 hover:-translate-y-1 shadow-xl shadow-orange-200 active:scale-95' : 'bg-gray-300 cursor-not-allowed'"
                    class="px-12 py-4 text-white rounded-2xl font-[900] transition-all flex items-center"
                    :disabled="!allStepsCompleted"
                >
                    <span x-text="currentIndex === exercises[level].length - 1 ? 'Finish Session' : 'Next Exercise'"></span>
                    <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                </button>
            </div>
        </div>

        <!-- Complete Modal -->
        <template x-if="showComplete">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" x-transition>
                <div class="bg-white rounded-[40px] p-10 max-w-md w-full text-center shadow-2xl border border-orange-100">
                    <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-8 shadow-inner">
                        <x-lucide-party-popper class="w-12 h-12 text-green-600" aria-hidden="true" />
                    </div>
                    <h2 class="text-4xl font-[900] text-gray-800 mb-3 tracking-tight">Session Complete!</h2>
                    <p class="text-gray-600 mb-10 text-lg font-medium leading-relaxed">You've completed the <span x-text="levelNames[level]"></span> routine. Excellent work keeping your body moving.</p>
                    <a href="{{ route('elderly.wellness.index') }}" class="block w-full py-5 bg-green-600 text-white font-[900] text-xl rounded-[24px] hover:bg-green-700 transition-all shadow-xl shadow-green-100 active:scale-95">
                        Back to Wellness
                    </a>
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
                        window.scrollTo({top: 0, behavior: 'smooth'});
                    } else {
                        this.showComplete = true;
                    }
                },

                prev() {
                    if (this.currentIndex > 0) {
                        this.currentIndex--;
                        window.scrollTo({top: 0, behavior: 'smooth'});
                    }
                }
            }
        }
    </script>
    @endpush
</x-dashboard-layout>