<x-app-layout>
    <div x-data="stretchGuide()" class="min-h-screen bg-[#FFF3E0] py-6 px-4 flex flex-col">
        
        <!-- Header -->
        <div class="max-w-5xl mx-auto w-full flex justify-between items-center mb-6">
            <a href="{{ route('elderly.wellness.index') }}" class="flex items-center text-orange-800 font-bold hover:text-orange-900 transition group">
                <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center shadow-sm mr-3 group-hover:shadow-md transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </div>
                Exit
            </a>
            
            <!-- Level Selector -->
            <div class="flex bg-white p-1 rounded-xl shadow-sm">
                <button @click="setLevel(0)" :class="level === 0 ? 'bg-orange-100 text-orange-700' : 'text-gray-500 hover:text-gray-700'" class="px-4 py-2 rounded-lg text-sm font-bold transition">Seated</button>
                <button @click="setLevel(1)" :class="level === 1 ? 'bg-orange-100 text-orange-700' : 'text-gray-500 hover:text-gray-700'" class="px-4 py-2 rounded-lg text-sm font-bold transition">Standing</button>
                <button @click="setLevel(2)" :class="level === 2 ? 'bg-orange-100 text-orange-700' : 'text-gray-500 hover:text-gray-700'" class="px-4 py-2 rounded-lg text-sm font-bold transition">Balance</button>
            </div>
        </div>

        <!-- Content -->
        <div class="flex-1 max-w-5xl mx-auto w-full flex flex-col lg:flex-row gap-8 items-start">
            
            <!-- Left: Card -->
            <div class="w-full lg:w-5/12 bg-white rounded-[32px] shadow-xl p-8 relative overflow-hidden min-h-[400px] flex flex-col justify-center items-center text-center border border-orange-100">
                <div class="absolute top-0 left-0 w-full h-3 bg-orange-400"></div>
                
                <div class="w-28 h-28 bg-orange-50 rounded-full flex items-center justify-center text-orange-500 mb-6 shadow-inner">
                    <!-- Dynamic Icon -->
                    <div x-html="current.icon"></div>
                </div>

                <h2 class="text-3xl font-[900] text-gray-800 mb-2" x-text="current.title"></h2>
                <p class="text-orange-600 font-bold mb-6 text-lg" x-text="current.duration + ' • ' + current.difficulty"></p>

                <div class="bg-orange-50 p-5 rounded-2xl text-left w-full shadow-sm">
                    <h4 class="font-bold text-orange-800 text-xs mb-3 uppercase tracking-wide">Why do this?</h4>
                    <ul class="space-y-2">
                        <template x-for="b in current.benefits">
                            <li class="flex items-center text-gray-700 text-sm font-medium">
                                <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <span x-text="b"></span>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>

            <!-- Right: Checklist -->
            <div class="w-full lg:w-7/12">
                <div class="flex justify-between items-end mb-6">
                    <h3 class="text-2xl font-[900] text-gray-800">Checklist</h3>
                    <p class="text-sm text-gray-500 font-medium">Click circle when done</p>
                </div>
                
                <div class="space-y-4">
                    <template x-for="(step, idx) in current.steps" :key="idx">
                        <div 
                            @click="toggleStep(idx)"
                            class="flex items-start p-5 rounded-2xl shadow-sm border cursor-pointer transition-all duration-300 transform hover:scale-[1.01]"
                            :class="step.completed ? 'bg-green-50 border-green-200' : 'bg-white border-orange-100 hover:border-orange-300'"
                        >
                            <!-- Checkbox -->
                            <div class="w-8 h-8 rounded-full flex items-center justify-center border-2 transition-all flex-shrink-0 mt-0.5 mr-4"
                                 :class="step.completed ? 'bg-green-500 border-green-500' : 'border-gray-300 bg-white'">
                                <svg x-show="step.completed" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            
                            <!-- Text -->
                            <div>
                                <p class="text-lg font-medium leading-relaxed transition-colors"
                                   :class="step.completed ? 'text-green-800 line-through decoration-green-800/30' : 'text-gray-700'">
                                    <span x-text="step.text"></span>
                                </p>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="mt-6 bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-r-xl flex items-start">
                    <svg class="w-6 h-6 text-yellow-600 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <p class="text-yellow-800 text-sm font-bold" x-text="current.caution"></p>
                </div>
            </div>

        </div>

        <!-- Navigation Footer -->
        <div class="fixed bottom-0 left-0 w-full bg-white border-t border-gray-100 p-4 shadow-[0_-10px_30px_rgba(0,0,0,0.05)] z-40">
            <div class="max-w-5xl mx-auto flex justify-between items-center">
                <button @click="prev()" :disabled="currentIndex === 0" class="px-8 py-3 rounded-xl font-bold text-gray-500 hover:bg-gray-100 disabled:opacity-30 transition-all">
                    Previous
                </button>

                <div class="hidden md:block text-sm font-bold text-gray-400">
                    Exercise <span x-text="currentIndex + 1"></span> of <span x-text="exercises[level].length"></span>
                </div>

                <button 
                    @click="next()"
                    :class="allStepsCompleted ? 'bg-orange-600 hover:bg-orange-700 hover:-translate-y-1 shadow-lg' : 'bg-gray-300 cursor-not-allowed'"
                    class="px-10 py-3 text-white rounded-xl font-[800] transition-all flex items-center"
                    :disabled="!allStepsCompleted"
                >
                    <span x-text="currentIndex === exercises[level].length - 1 ? 'Finish Session' : 'Next Exercise'"></span>
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                </button>
            </div>
        </div>

        <!-- Complete Modal -->
        <div x-show="showComplete" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" style="display: none;" x-transition.opacity>
            <div class="bg-white rounded-[32px] p-8 max-w-md w-full text-center shadow-2xl transform transition-all scale-100">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6 text-4xl">🎉</div>
                <h2 class="text-3xl font-[900] text-gray-800 mb-2">Session Complete!</h2>
                <p class="text-gray-600 mb-8">You've completed the <span x-text="levelNames[level]"></span> routine. Excellent work keeping your body moving.</p>
                <a href="{{ route('elderly.wellness.index') }}" class="block w-full py-4 bg-green-600 text-white font-bold rounded-xl hover:bg-green-700 transition-colors shadow-lg">
                    Back to Wellness Center
                </a>
            </div>
        </div>

    </div>

    <script>
        function stretchGuide() {
            return {
                level: 0, // 0: Seated, 1: Standing, 2: Balance
                levelNames: ['Seated', 'Standing', 'Balance'],
                currentIndex: 0,
                showComplete: false,
                
                // Data structure
                exercises: [
                    // Level 0: Seated (Easy)
                    [
                        {
                            title: 'Neck Rolls', duration: '2 mins', difficulty: 'Easy', caution: 'Stop if you feel dizzy.',
                            icon: `<svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`,
                            benefits: ['Relieves neck tension', 'Improves flexibility'],
                            steps: [{text:'Sit straight with feet flat.', completed:false}, {text:'Tilt head to right shoulder.', completed:false}, {text:'Hold for 5 seconds.', completed:false}, {text:'Repeat on left side.', completed:false}]
                        },
                        {
                            title: 'Ankle Circles', duration: '2 mins', difficulty: 'Easy', caution: 'Keep movements smooth.',
                            icon: `<svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>`,
                            benefits: ['Improves circulation', 'Reduces stiffness'],
                            steps: [{text:'Lift right foot slightly.', completed:false}, {text:'Rotate ankle clockwise 5 times.', completed:false}, {text:'Rotate counter-clockwise 5 times.', completed:false}, {text:'Switch to left foot.', completed:false}]
                        }
                    ],
                    // Level 1: Standing (Medium)
                    [
                        {
                            title: 'Marching in Place', duration: '3 mins', difficulty: 'Medium', caution: 'Use a chair for support if needed.',
                            icon: `<svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>`,
                            benefits: ['Boosts heart rate', 'Strengthens legs'],
                            steps: [{text:'Stand tall near a chair.', completed:false}, {text:'Lift knees alternately.', completed:false}, {text:'Swing arms gently.', completed:false}, {text:'Continue for 30 steps.', completed:false}]
                        }
                    ],
                    // Level 2: Balance (Hard)
                    [
                        {
                            title: 'Single Leg Stand', duration: '2 mins', difficulty: 'Hard', caution: 'Hold onto a sturdy chair.',
                            icon: `<svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`,
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
                    // Reset completed status for the current level when switching
                    this.exercises[this.level].forEach(ex => {
                        ex.steps.forEach(s => s.completed = false);
                    });
                },

                next() {
                    if (this.currentIndex < this.exercises[this.level].length - 1) {
                        this.currentIndex++;
                        window.scrollTo(0,0);
                    } else {
                        this.showComplete = true;
                    }
                },

                prev() {
                    if (this.currentIndex > 0) {
                        this.currentIndex--;
                        window.scrollTo(0,0);
                    }
                }
            }
        }
    </script>
</x-app-layout>