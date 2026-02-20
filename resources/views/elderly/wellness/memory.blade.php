<x-app-layout>
    <div x-data="memoryGame()" class="min-h-screen bg-[#F0F7FF] py-8 px-4">
        
        <!-- Header -->
        <div class="max-w-4xl mx-auto flex justify-between items-center mb-8">
            <a href="{{ route('elderly.wellness.index') }}" class="flex items-center text-blue-600 font-bold hover:text-blue-800 transition group">
                <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center shadow-sm mr-3 group-hover:shadow-md transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </div>
                Exit
            </a>
            
            <div class="bg-white px-5 py-2 rounded-xl shadow-sm border border-blue-100 flex items-center gap-4">
                <div>
                    <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Level</p>
                    <p class="text-xl font-[900] text-blue-600" x-text="level + 1"></p>
                </div>
                <div class="h-8 w-[1px] bg-gray-100"></div>
                <div>
                    <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Pairs</p>
                    <p class="text-xl font-[900] text-blue-600" x-text="score + '/6'"></p>
                </div>
                <button @click="restartLevel()" class="ml-2 p-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition" title="Restart Level">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                </button>
            </div>
        </div>

        <div class="text-center mb-8">
            <h1 class="text-3xl font-[900] text-gray-800 tracking-tight" x-text="levelTitles[level]"></h1>
            <p class="text-gray-500">Find the matching colored shapes!</p>
        </div>

        <!-- Game Grid -->
        <div class="max-w-2xl mx-auto grid grid-cols-3 sm:grid-cols-4 gap-4">
            <template x-for="(card, index) in cards" :key="card.id">
                <div 
                    @click="flipCard(index)"
                    class="aspect-[3/4] rounded-2xl shadow-lg cursor-pointer relative transition-all duration-500 transform hover:-translate-y-1 perspective-1000"
                    :class="card.revealed || card.matched ? 'rotate-y-180' : ''"
                >
                    <!-- Card Back (Theme Color) -->
                    <div 
                        x-show="!card.revealed && !card.matched" 
                        class="absolute inset-0 rounded-2xl flex items-center justify-center border-b-4 transition-colors duration-300"
                        :class="cardBackClasses[level]"
                    >
                        <span class="text-4xl text-white/30 font-[900]">?</span>
                    </div>

                    <!-- Card Front (White with Specific Icon) -->
                    <div 
                        x-show="card.revealed || card.matched" 
                        class="absolute inset-0 bg-white rounded-2xl flex items-center justify-center border-2 shadow-inner p-2"
                        :class="'border-' + themeColors[level] + '-200'"
                    >
                        <!-- Dynamic SVG Icon -->
                        <div x-html="getIconHtml(card.iconName)" class="transform transition-all duration-300 scale-100 drop-shadow-sm"></div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Level Complete Modal (Levels 1-4) -->
        <div x-show="showLevelModal" style="display: none;" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4" x-transition.opacity>
            <div class="bg-white rounded-[32px] p-8 max-w-sm w-full text-center shadow-2xl transform transition-all scale-100 relative overflow-hidden">
                <div class="w-20 h-20 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-4xl mx-auto mb-6 shadow-inner">👍</div>
                <h2 class="text-2xl font-[900] text-gray-800 mb-2">Good Job!</h2>
                <p class="text-gray-500 mb-8">Level <span x-text="level + 1"></span> complete.</p>
                
                <button @click="nextLevel()" class="w-full py-3 bg-blue-600 text-white font-[800] rounded-2xl hover:bg-blue-700 shadow-lg transition-all hover:scale-[1.02]">
                    Next Level →
                </button>
            </div>
        </div>

        <!-- Grand Finale Modal (Level 5 Complete) -->
        <div x-show="showGrandModal" style="display: none;" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4" x-transition.opacity>
            <div class="bg-white rounded-[32px] p-10 max-w-md w-full text-center shadow-2xl transform transition-all scale-100 relative overflow-hidden border-4 border-yellow-300">
                
                <!-- Confetti BG (Behind) -->
                <div class="absolute inset-0 opacity-10 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCI+PGNpcmNsZSBjeD0iMiIgY3k9IjIiIHI9IjIiIGZpbGw9IiMwMDAiLz48L3N2Zz4=')]"></div>
                
                <!-- Content Container (Forces buttons to Front) -->
                <div class="relative z-10">
                    <div class="w-24 h-24 bg-yellow-100 text-yellow-500 rounded-full flex items-center justify-center text-5xl mx-auto mb-6 shadow-inner animate-bounce">
                        🏆
                    </div>
                    
                    <h2 class="text-3xl font-[900] text-gray-800 mb-2">Congratulations!</h2>
                    <p class="text-gray-600 text-lg mb-8 font-medium">You have completed all 5 stages of Memory Match!</p>
                    
                    <div class="space-y-3">
                        <button @click="restartGame()" class="w-full py-4 bg-blue-600 text-white font-[800] rounded-2xl hover:bg-blue-700 shadow-lg transition-all hover:scale-[1.02] cursor-pointer">
                            Play Again 🔄
                        </button>
                        
                        <a href="{{ route('elderly.wellness.index') }}" class="block w-full py-4 bg-white text-gray-600 font-[800] rounded-2xl hover:bg-gray-50 border-2 border-gray-200 transition-all cursor-pointer">
                            Back to Wellness Center
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        function memoryGame() {
            return {
                cards: [],
                flippedIndices: [],
                score: 0,
                level: 0,
                isProcessing: false,
                showLevelModal: false,
                showGrandModal: false,
                
                levelTitles: ['Level 1: Nature', 'Level 2: Food & Drink', 'Level 3: Travel', 'Level 4: Shapes', 'Level 5: Animals'],
                
                // Theme colors for Card Backs and Borders (Purely visual theming per level)
                themeColors: ['green', 'orange', 'blue', 'purple', 'red'],
                cardBackClasses: [
                    'bg-gradient-to-br from-green-400 to-green-600 border-green-700', // Nature
                    'bg-gradient-to-br from-orange-400 to-orange-600 border-orange-700', // Food
                    'bg-gradient-to-br from-blue-400 to-blue-600 border-blue-700', // Travel
                    'bg-gradient-to-br from-purple-400 to-purple-600 border-purple-700', // Shapes
                    'bg-gradient-to-br from-red-400 to-red-600 border-red-700', // Animals
                ],

                // DEFINING THE ICONS WITH SPECIFIC COLORS
                // Each icon has its own hardcoded color to guide the user
                iconLibrary: {
                    // Level 1: Nature
                    leaf: { color: '#16a34a', path: '<path d="M12 2C7 2 3 7 3 13s5 9 9 9 9-5 9-11-4-9-9-9z M12 18c-3 0-5-2-5-5s2-5 5-5 5 2 5 5-2 5-5 5z"/>' }, 
                    tree: { color: '#15803d', path: '<path d="M12 2L4 14h6v8h4v-8h6L12 2z"/>' }, 
                    flower: { color: '#db2777', path: '<path d="M12 2c-3 0-5 3-5 5 0 2 2 3 2 3s-3 0-5 2c-2 2-2 5 0 7 2 2 5 2 5 2s0 3 3 5c2 2 5 2 7 0 2-2 3-5 3-5s3 0 5-2c2-2 2-5 0-7-2-2-5-2-5-2s2-1 2-3c0-2-2-5-5-5z"/>' }, 
                    sun: { color: '#f59e0b', path: '<circle cx="12" cy="12" r="5"/><path d="M12 1v2m0 18v2M4.22 4.22l1.42 1.42m12.72 12.72l1.42 1.42M1 12h2m18 0h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>' }, 
                    cloud: { color: '#3b82f6', path: '<path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/>' }, 
                    mountain: { color: '#78350f', path: '<path d="M2 20h20L12 4z"/>' }, 
                    
                    // Level 2: Food
                    apple: { color: '#ef4444', path: '<path d="M12 2c0 0-2 2-2 4s2 2 2 4c0 4-3 9-6 9s-4-5-4-9c0-2 2-4 4-4 2 0 4 2 6 2s4-2 6-2c2 0 4 2 4 4 0 4-1 9-4 9s-6-5-6-9c0-2 2-4 2-4s-2-2-2-4z"/>' }, 
                    pizza: { color: '#ea580c', path: '<path d="M12 2L2 22h20L12 2z M12 6l2 4h-4l2-4z M8 16a2 2 0 1 1 0 4 2 2 0 0 1 0-4z M16 16a2 2 0 1 1 0 4 2 2 0 0 1 0-4z"/>' }, 
                    icecream: { color: '#ec4899', path: '<path d="M12 2C8 2 5 5 5 9c0 2 2 4 2 4l5 9 5-9c0 0 2-2 2-4 0-4-3-7-7-7z"/>' }, 
                    coffee: { color: '#713f12', path: '<path d="M18 8h1a4 4 0 0 1 0 8h-1v2a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V4h12v4z M4 22h16"/>' }, 
                    burger: { color: '#d97706', path: '<path d="M4 8a8 8 0 0 1 16 0v2H4V8z m0 4h16v4H4v-4z m0 6h16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"/>' }, 
                    carrot: { color: '#f97316', path: '<path d="M19 2l-2 2-2-2-2 2 6 6L7 22 2 17l12-12 2-2 2 2 1-1z"/>' }, 

                    // Level 3: Travel
                    car: { color: '#ef4444', path: '<path d="M5 10l2-5h10l2 5v8h-2v-2H7v2H5v-8z M7 10h10M7 14a2 2 0 1 0 0 4 2 2 0 0 0 0-4z m10 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/>' }, 
                    plane: { color: '#0ea5e9', path: '<path d="M2 12h2l3-6h4l-2 6h6l2-3h2l-2 3h3v2h-3l2 3h-2l-2-3h-6l2 6H7l-3-6H2v-2z"/>' }, 
                    boat: { color: '#2563eb', path: '<path d="M2 14l2 6h16l2-6H2z M12 2L8 12h8L12 2z"/>' }, 
                    bus: { color: '#fbbf24', path: '<path d="M4 4h16v14H4V4z M2 20h20v2H2v-2z M6 14a2 2 0 1 0 0 4 2 2 0 0 0 0-4z m12 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/>' }, 
                    bike: { color: '#84cc16', path: '<circle cx="5" cy="17" r="4"/><circle cx="19" cy="17" r="4"/><path d="M5 17h4l2-5h4l1 3M12 6a2 2 0 1 1 0 4 2 2 0 0 1 0-4z"/>' }, 
                    map: { color: '#10b981', path: '<path d="M2 6l7-3 6 3 7-3v15l-7 3-6-3-7 3V6z"/>' }, 

                    // Level 4: Shapes
                    star: { color: '#eab308', path: '<path d="M12 2l3 7h7l-6 5 2 7-6-5-6 5 2-7-6-5h7z"/>' }, 
                    heart: { color: '#dc2626', path: '<path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>' }, 
                    diamond: { color: '#3b82f6', path: '<path d="M12 2L2 12l10 10 10-10L12 2z"/>' }, 
                    bell: { color: '#eab308', path: '<path d="M12 2a2 2 0 0 0-2 2v1a6 6 0 0 0-6 6v5l-2 2v1h20v-1l-2-2v-5a6 6 0 0 0-6-6V4a2 2 0 0 0-2-2z"/>' }, 
                    key: { color: '#f59e0b', path: '<path d="M7 11a5 5 0 1 1 0 10 5 5 0 0 1 0-10z m5 5h10v4h-2v-2h-2v2h-2v-4H12"/>' }, 
                    lock: { color: '#64748b', path: '<path d="M12 2a5 5 0 0 0-5 5v2H5v10h14V9h-2V7a5 5 0 0 0-5-5z m0 2a3 3 0 0 1 3 3v2H9V7a3 3 0 0 1 3-3z"/>' }, 

                    // Level 5: Animals (Refined)
                    paw: { color: '#a855f7', path: '<path d="M12 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm-4 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm8 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM6 9c-1.66 0-3 1.34-3 3 0 1.66 1.34 3 3 3 .2 0 .38-.04.56-.09.68 1.23 2.37 2.09 4.44 2.09s3.76-.86 4.44-2.09c.18.05.36.09.56.09 1.66 0 3-1.34 3-3 0-1.66-1.34-3-3-3-1.66 0-3 1.34-3 3 0 .21.04.41.09.61C15.41 9.41 13.8 9 12 9s-3.41.41-4.09 1.61C7.96 10.41 8 10.21 8 9c0-1.66-1.34-3-2-3z"/>' }, // Purple Paw
                    fish: { color: '#f97316', path: '<path d="M21.5,12c0,0-2.5,2-5.5,2s-4-2-4-2s1-2,4-2S21.5,12,21.5,12z M3,12l2-2h2c0,0,3-2,6-2c3,0,5,2,5,2l2,2l-2,2c0,0-2,2-5,2 c-3,0-6-2-6-2H5L3,12z M15,12c0-0.6-0.4-1-1-1s-1,0.4-1,1s0.4,1,1,1S15,12.6,15,12z"/>' }, // Orange Fish
                    bug: { color: '#16a34a', path: '<path d="M12,2C9,2,6,4,6,7v2H4v2h2v2H4v2h2v1c0,3,2,6,6,6s6-3,6-6v-1h2v-2h-2v-2h2V9h-2V7C18,4,15,2,12,2z M12,4c2,0,4,1,4,3v2 h-3V6h-2v3H8V7C8,5,10,4,12,4z M8,16H11v3H11C9,19,8,18,8,16z M16,16c0,2-1,3-3,3v-3H16z"/>' }, // Green Beetle
                    bird: { color: '#0ea5e9', path: '<path d="M21,6c-1,1-2,2-4,2c0-3-3-5-6-5C7,3,4,6,4,10c0,2,1,4,2,5l-4,4l2,2l4-4c1,1,3,2,5,2c4,0,7-3,7-7c0-2-1-4-3-5 c2,0,3-1,4-2L21,6z"/>' }, // Sky Bird
                    cat: { color: '#f43f5e', path: '<path d="M12 2C8.5 2 5.5 4.5 5 8v2c-1.5 1-2.5 2.5-2.5 4.5 0 3 2.5 5.5 5.5 5.5h8c3 0 5.5-2.5 5.5-5.5 0-2-1-3.5-2.5-4.5V8c-.5-3.5-3.5-6-7-6zm0 2c2 0 3.5 1.5 4 3.5V8h-8V7.5C8.5 5.5 10 4 12 4zm-4 8c0-1.5 1-2.5 2.5-2.5S13 10.5 13 12s-1 2.5-2.5 2.5S8 13.5 8 12zm8 0c0-1.5 1-2.5 2.5-2.5S21 10.5 21 12s-1 2.5-2.5 2.5S16 13.5 16 12z"/>' }, // Rose Cat
                    dog: { color: '#854d0e', path: '<path d="M12,2C9,2,6,4,6,7v3c-2,1-3,3-3,6c0,3,2,6,5,6h8c3,0,5-3,5-6c0-3-1-5-3-6V7C18,4,15,2,12,2z M8,14c-1,0-2-1-2-2 s1-2,2-2s2,1,2,2S9,14,8,14z M16,14c-1,0-2-1-2-2s1-2,2-2s2,1,2,2S17,14,16,14z"/>' } // Brown Dog
                },

                levelSets: [
                    ['leaf', 'tree', 'flower', 'sun', 'cloud', 'mountain'], // L1
                    ['apple', 'pizza', 'icecream', 'coffee', 'burger', 'carrot'], // L2
                    ['car', 'plane', 'boat', 'bus', 'bike', 'map'], // L3
                    ['star', 'heart', 'diamond', 'bell', 'key', 'lock'], // L4
                    ['paw', 'fish', 'bug', 'bird', 'cat', 'dog'] // L5 (Updated)
                ],

                init() { this.startLevel(); },

                restartLevel() {
                    this.startLevel();
                },

                restartGame() {
                    this.level = 0;
                    this.startLevel();
                },

                startLevel() {
                    const currentSetKeys = this.levelSets[this.level];
                    
                    // Create deck: 6 pairs = 12 cards
                    let deck = [...currentSetKeys, ...currentSetKeys].map(key => ({ 
                        iconName: key,
                        id: Math.random(),
                        revealed: false,
                        matched: false 
                    }));
                    
                    // Shuffle
                    this.cards = deck.sort(() => Math.random() - 0.5);
                    this.score = 0;
                    this.flippedIndices = [];
                    this.isProcessing = false;
                    this.showLevelModal = false;
                    this.showGrandModal = false;
                },

                flipCard(idx) {
                    if(this.isProcessing || this.cards[idx].revealed || this.cards[idx].matched) return;
                    
                    this.cards[idx].revealed = true;
                    this.flippedIndices.push(idx);
                    
                    if(this.flippedIndices.length === 2) this.checkForMatch();
                },

                checkForMatch() {
                    this.isProcessing = true;
                    const [i1, i2] = this.flippedIndices;
                    
                    if(this.cards[i1].iconName === this.cards[i2].iconName) {
                        // Match found
                        this.cards[i1].matched = true;
                        this.cards[i2].matched = true;
                        this.score++;
                        this.flippedIndices = [];
                        this.isProcessing = false;
                        
                        // Check win condition
                        if(this.cards.every(c => c.matched)) {
                            setTimeout(() => {
                                if (this.level === 4) {
                                    this.showGrandModal = true;
                                } else {
                                    this.showLevelModal = true;
                                }
                            }, 600);
                        }
                    } else {
                        // No match
                        setTimeout(() => {
                            this.cards[i1].revealed = false;
                            this.cards[i2].revealed = false;
                            this.flippedIndices = [];
                            this.isProcessing = false;
                        }, 1000);
                    }
                },

                nextLevel() {
                    if (this.level < 4) {
                        this.level++;
                        this.startLevel();
                    }
                },

                getIconHtml(name) {
                    const icon = this.iconLibrary[name];
                    return `<svg class="w-14 h-14" style="color:${icon.color}" fill="currentColor" viewBox="0 0 24 24">${icon.path}</svg>`;
                }
            }
        }
    </script>
</x-app-layout>