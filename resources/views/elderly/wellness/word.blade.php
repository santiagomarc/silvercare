<x-app-layout>
    <div x-data="wordOfDay()" class="min-h-screen bg-[#FFF9C4] py-8 px-4 overflow-hidden relative">
        
        <!-- Header -->
        <div class="max-w-3xl mx-auto flex justify-between items-center mb-10">
            <a href="{{ route('elderly.wellness.index') }}" class="flex items-center text-yellow-800 font-bold hover:text-yellow-900 transition group">
                <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center shadow-sm mr-3 group-hover:shadow-md transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </div>
                Back
            </a>
            <div class="px-4 py-1 bg-yellow-200 text-yellow-800 rounded-full text-sm font-bold shadow-sm" x-text="dateString"></div>
        </div>

        <!-- Main Card Container -->
        <div class="max-w-2xl mx-auto relative h-[600px]">
            
            <!-- Slide Animation Container -->
            <div x-show="show"
                 x-transition:enter="transition ease-out duration-500"
                 x-transition:enter-start="opacity-0 transform translate-x-20"
                 x-transition:enter-end="opacity-100 transform translate-x-0"
                 x-transition:leave="transition ease-in duration-300"
                 x-transition:leave-start="opacity-100 transform translate-x-0"
                 x-transition:leave-end="opacity-0 transform -translate-x-20"
                 class="absolute inset-0"
            >
                <div class="bg-white rounded-[40px] shadow-xl p-8 md:p-16 text-center relative overflow-hidden border border-yellow-100 h-full flex flex-col justify-center">
                    <!-- Decoration -->
                    <div class="absolute top-0 left-0 w-full h-3 bg-gradient-to-r from-yellow-400 to-orange-400"></div>
                    <div class="absolute -top-10 -left-10 w-32 h-32 bg-yellow-50 rounded-full mix-blend-multiply"></div>
                    <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-orange-50 rounded-full mix-blend-multiply"></div>

                    <div class="relative z-10">
                        <!-- Quote Icon -->
                        <div class="w-20 h-20 mx-auto bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full flex items-center justify-center shadow-lg mb-8 text-white transform transition hover:scale-110 duration-300">
                            <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 24 24"><path d="M14.017 21L14.017 18C14.017 16.8954 14.9124 16 16.017 16H19.017C19.5693 16 20.017 15.5523 20.017 15V9C20.017 8.44772 19.5693 8 19.017 8H15.017C14.4647 8 14.017 8.44772 14.017 9V11C14.017 11.5523 13.5693 12 13.017 12H12.017V5H19.017C21.2261 5 23.017 6.79086 23.017 9V15C23.017 17.2091 21.2261 19 19.017 19H14.017V21ZM5.0166 21L5.0166 18C5.0166 16.8954 5.91203 16 7.0166 16H10.0166C10.5689 16 11.0166 15.5523 11.0166 15V9C11.0166 8.44772 10.5689 8 10.0166 8H6.0166C5.46432 8 5.0166 8.44772 5.0166 9V11C5.0166 11.5523 4.56889 12 4.0166 12H3.0166V5H10.0166C12.2257 5 14.0166 6.79086 14.0166 9V15C14.0166 17.2091 12.2257 19 10.0166 19H5.0166V21Z"/></svg>
                        </div>

                        <h1 class="text-3xl md:text-4xl font-[800] text-gray-900 leading-tight mb-6 font-montserrat" x-text="current.quote"></h1>
                        
                        <div class="inline-block px-6 py-2 rounded-full bg-gray-50 mb-10 shadow-sm">
                            <p class="text-gray-600 font-bold italic text-lg" x-text="'- ' + current.author"></p>
                        </div>

                        <div class="bg-orange-50 rounded-2xl p-6 border border-orange-100 shadow-sm">
                            <div class="flex items-center justify-center gap-2 text-orange-600 font-bold mb-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                <span class="uppercase tracking-wide text-xs">Today's Action</span>
                            </div>
                            <p class="text-xl font-bold text-gray-800" x-text="current.action"></p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Controls -->
        <div class="flex justify-center items-center gap-8 mt-2">
            <button @click="slide('prev')" class="w-16 h-16 bg-white rounded-full shadow-lg flex items-center justify-center text-gray-400 hover:text-orange-500 hover:scale-110 transition-all">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 19l-7-7 7-7"></path></svg>
            </button>
            
            <button @click="copy()" class="px-10 py-4 bg-gray-900 text-white font-bold rounded-2xl shadow-xl hover:bg-black hover:-translate-y-1 transition-all flex items-center gap-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                Copy Quote
            </button>

            <button @click="slide('next')" class="w-16 h-16 bg-white rounded-full shadow-lg flex items-center justify-center text-gray-400 hover:text-orange-500 hover:scale-110 transition-all">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"></path></svg>
            </button>
        </div>

        <!-- Success Toast -->
        <div 
            x-show="showToast"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform translate-y-8"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100 transform translate-y-0"
            x-transition:leave-end="opacity-0 transform translate-y-8"
            class="fixed bottom-10 left-1/2 transform -translate-x-1/2 bg-black/80 text-white px-6 py-3 rounded-full shadow-2xl flex items-center gap-3 z-50"
            style="display: none;"
        >
            <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <span class="font-bold text-sm">Quote successfully copied!</span>
        </div>

    </div>

    <script>
        function wordOfDay() {
            return {
                idx: 0,
                show: true,
                showToast: false,
                quotes: [
                    { quote: 'Every day is a new beginning. Take a deep breath, smile, and start again.', author: 'Unknown', action: 'Start your day with a smile! 😊' },
                    { quote: 'Age is just a number. It\'s never too late to learn something new.', author: 'Unknown', action: 'Try something new today! 🌟' },
                    { quote: 'Happiness is not by chance, but by choice.', author: 'Jim Rohn', action: 'Choose joy today! ✨' },
                    { quote: 'Do not regret growing older. It is a privilege denied to many.', author: 'Unknown', action: 'Be grateful for today! 🙏' },
                    { quote: 'Laughter is timeless, imagination has no age, and dreams are forever.', author: 'Walt Disney', action: 'Laugh with a friend! 😂' }
                ],
                get current() { return this.quotes[this.idx]; },
                get dateString() {
                    return new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });
                },
                slide(direction) {
                    this.show = false;
                    setTimeout(() => {
                        if (direction === 'next') {
                            this.idx = (this.idx + 1) % this.quotes.length;
                        } else {
                            this.idx = (this.idx - 1 + this.quotes.length) % this.quotes.length;
                        }
                        this.show = true;
                    }, 300);
                },
                copy() {
                    navigator.clipboard.writeText(`"${this.current.quote}" - ${this.current.author}`);
                    this.showToast = true;
                    setTimeout(() => this.showToast = false, 2500);
                }
            }
        }
    </script>
</x-app-layout>