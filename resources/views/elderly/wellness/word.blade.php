<x-dashboard-layout>
    <x-slot:title>Daily Wisdom - SilverCare</x-slot:title>
    <x-slot:bodyClass>h-screen overflow-hidden bg-[#FFF9C4]</x-slot:bodyClass>
 
    <div x-data="wordOfDay()" class="h-full flex flex-col">
        <x-dashboard-nav
            title="Daily Wisdom"
            subtitle="Start your day with inspiring quotes"
            role="elderly"
            :unread-notifications="$unreadNotifications"
        />
 
        {{-- Main fills all remaining height after nav, no overflow --}}
        <main id="main-content" class="flex-1 overflow-hidden max-w-3xl w-full mx-auto px-6 flex flex-col py-5">
 
            {{-- Back Navigation & Date --}}
            <div class="flex justify-between items-center mb-4 flex-shrink-0">
                <div class="px-5 py-2.5 bg-yellow-200 text-yellow-900 rounded-2xl text-base font-[800] shadow-sm border border-yellow-300" x-text="dateString"></div>
                <a href="{{ route('elderly.wellness.index') }}" class="back-nav-pill !text-yellow-800 !bg-white/50 hover:!bg-white text-base">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Back to Wellness
                </a>
            </div>
 
            {{-- Card — flex-1 so it fills whatever space is left between header and controls --}}
            <div class="max-w-2xl w-full mx-auto flex-1 relative min-h-0 mb-4">
                <div x-show="show"
                     x-transition:enter="transition ease-out duration-500"
                     x-transition:enter-start="opacity-0 transform translate-x-20"
                     x-transition:enter-end="opacity-100 transform translate-x-0"
                     x-transition:leave="transition ease-in duration-300"
                     x-transition:leave-start="opacity-100 transform translate-x-0"
                     x-transition:leave-end="opacity-0 transform -translate-x-20"
                     class="absolute inset-0"
                >
                    <div class="bg-white rounded-[48px] shadow-2xl px-10 text-center relative overflow-hidden border border-yellow-100 h-full flex flex-col justify-center gap-4">
                        {{-- Top color bar --}}
                        <div class="absolute top-0 left-0 w-full h-3 bg-gradient-to-r from-yellow-400 to-orange-400"></div>
                        {{-- Decorative blobs --}}
                        <div class="absolute -top-10 -left-10 w-36 h-36 bg-yellow-50 rounded-full mix-blend-multiply opacity-50 pointer-events-none"></div>
                        <div class="absolute -bottom-10 -right-10 w-44 h-44 bg-orange-50 rounded-full mix-blend-multiply opacity-50 pointer-events-none"></div>
 
                        <div class="relative z-10 flex flex-col items-center gap-4">
                            {{-- Quote icon --}}
                            <div class="w-16 h-16 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-2xl flex items-center justify-center shadow-xl text-white transition hover:rotate-6 duration-300">
                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24"><path d="M14.017 21L14.017 18C14.017 16.8954 14.9124 16 16.017 16H19.017C19.5693 16 20.017 15.5523 20.017 15V9C20.017 8.44772 19.5693 8 19.017 8H15.017C14.4647 8 14.017 8.44772 14.017 9V11C14.017 11.5523 13.5693 12 13.017 12H12.017V5H19.017C21.2261 5 23.017 6.79086 23.017 9V15C23.017 17.2091 21.2261 19 19.017 19H14.017V21ZM5.0166 21L5.0166 18C5.0166 16.8954 5.91203 16 7.0166 16H10.0166C10.5689 16 11.0166 15.5523 11.0166 15V9C11.0166 8.44772 10.5689 8 10.0166 8H6.0166C5.46432 8 5.0166 8.44772 5.0166 9V11C5.0166 11.5523 4.56889 12 4.0166 12H3.0166V5H10.0166C12.2257 5 14.0166 6.79086 14.0166 9V15C14.0166 17.2091 12.2257 19 10.0166 19H5.0166V21Z"/></svg>
                            </div>
 
                            {{-- Quote --}}
                            <h1 class="text-3xl md:text-4xl font-[900] text-gray-900 leading-snug tracking-tight" x-text="current.quote"></h1>
 
                            {{-- Author --}}
                            <div class="inline-block px-6 py-2 rounded-full bg-gray-50 shadow-inner">
                                <p class="text-gray-500 font-bold italic text-lg" x-text="'- ' + current.author"></p>
                            </div>
 
                            {{-- Today's Action --}}
                            <div class="w-full bg-orange-50 rounded-2xl px-6 py-4 border border-orange-100 shadow-sm">
                                <div class="flex items-center justify-center gap-2 text-orange-600 font-bold mb-1">
                                    <x-lucide-zap class="w-5 h-5" aria-hidden="true" />
                                    <span class="uppercase tracking-widest text-[11px]">Today's Action</span>
                                </div>
                                <p class="text-xl font-bold text-gray-800" x-text="current.action"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
 
            {{-- Controls — always anchored at bottom, never shrinks --}}
            <div class="flex justify-center items-center gap-8 flex-shrink-0">
                <button @click="slide('prev')" class="w-16 h-16 bg-white rounded-2xl shadow-lg flex items-center justify-center text-gray-400 hover:text-yellow-600 hover:scale-110 transition-all active:scale-95 border border-yellow-100">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 19l-7-7 7-7"></path></svg>
                </button>
 
                <button @click="copy()" class="px-10 py-5 bg-gray-900 text-white font-[800] text-lg rounded-[24px] shadow-2xl hover:bg-black hover:-translate-y-1 transition-all flex items-center gap-3 active:scale-95">
                    <x-lucide-copy class="w-6 h-6" aria-hidden="true" />
                    Copy Quote
                </button>
 
                <button @click="slide('next')" class="w-16 h-16 bg-white rounded-2xl shadow-lg flex items-center justify-center text-gray-400 hover:text-yellow-600 hover:scale-110 transition-all active:scale-95 border border-yellow-100">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"></path></svg>
                </button>
            </div>
 
        </main>
    </div>
 
    @push('scripts')
    <script>
        function wordOfDay() {
            return {
                idx: 0,
                show: true,
                quotes: [
                    { quote: 'Every day is a new beginning. Take a deep breath, smile, and start again.', author: 'Unknown', action: 'Start your day with a smile.' },
                    { quote: 'Age is just a number. It\'s never too late to learn something new.', author: 'Unknown', action: 'Try something new today.' },
                    { quote: 'Happiness is not by chance, but by choice.', author: 'Jim Rohn', action: 'Choose joy today.' },
                    { quote: 'Do not regret growing older. It is a privilege denied to many.', author: 'Unknown', action: 'Be grateful for today.' },
                    { quote: 'Laughter is timeless, imagination has no age, and dreams are forever.', author: 'Walt Disney', action: 'Laugh with a friend.' }
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
                async copy() {
                    try {
                        await navigator.clipboard.writeText(`"${this.current.quote}" - ${this.current.author}`);
                        window.scToast('Quote successfully copied!', 'success', { elderly: true });
                    } catch (_) {
                        window.scToast('Unable to copy quote right now. Please try again.', 'error', { elderly: true });
                    }
                }
            }
        }
    </script>
    @endpush
</x-dashboard-layout>