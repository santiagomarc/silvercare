<x-dashboard-layout sc>
    <x-slot:title>Daily Wisdom - SilverCare</x-slot:title>

    <div x-data="wordOfDay()">
        <x-dashboard-nav
            title="Daily Wisdom"
            subtitle="Start your day with inspiring quotes"
            role="elderly"
            :unread-notifications="$unreadNotifications"
        />

        <main id="main-content" class="sc-app-main">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                {{-- Back Navigation & Date --}}
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <a href="{{ route('elderly.wellness.index') }}"
                       class="sc-btn sc-btn-ghost sc-btn-sm">
                        <x-lucide-arrow-left class="sc-i w-4 h-4" aria-hidden="true" />
                        <span>Back to Wellness</span>
                    </a>

                    <div class="sc-badge sc-num text-xs sm:text-sm font-semibold" x-text="dateString"></div>
                </div>

                {{-- Quote Section --}}
                <section class="min-h-[380px] sm:min-h-[420px] flex items-center justify-center relative" aria-labelledby="quote-heading">
                    <h2 id="quote-heading" class="sr-only">Quote of the Day</h2>

                    <div x-show="show"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform scale-95"
                         x-transition:enter-end="opacity-100 transform scale-100"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100 transform scale-100"
                         x-transition:leave-end="opacity-0 transform scale-95"
                         class="w-full"
                    >
                        <div class="sc-card sc-lift p-8 sm:p-10 md:p-12 text-center flex flex-col items-center justify-between gap-6 relative overflow-hidden">
                            {{-- Quote plate --}}
                            <div class="sc-plate sc-plate-lg" aria-hidden="true">
                                <x-lucide-quote class="sc-i w-7 h-7" aria-hidden="true" />
                            </div>

                            {{-- Quote text: Newsreader serif for warmth and editorial reflection --}}
                            <blockquote class="sc-quote text-2xl sm:text-3xl md:text-4xl text-center leading-relaxed tracking-tight" style="color:var(--sc-title)" x-text="current.quote"></blockquote>

                            {{-- Author --}}
                            <cite class="not-italic text-base sm:text-lg font-medium" style="color:var(--sc-muted)" x-text="'- ' + current.author"></cite>

                            {{-- Today's Action inset --}}
                            <div class="sc-card-quiet p-4 sm:p-5 w-full max-w-md mx-auto text-center mt-2">
                                <div class="sc-eyebrow flex items-center justify-center gap-2 mb-1">
                                    <x-lucide-sparkles class="sc-i w-4 h-4" aria-hidden="true" />
                                    <span>Today's Action</span>
                                </div>
                                <p class="text-lg sm:text-xl font-bold" style="color:var(--sc-title)" x-text="current.action"></p>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Controls --}}
                <div class="flex justify-center items-center gap-4 sm:gap-6 pt-2">
                    <button type="button"
                            @click="slide('prev')"
                            aria-label="Previous quote"
                            class="sc-btn sc-btn-ghost w-12 h-12 sm:w-14 sm:h-14 !p-0 rounded-full flex items-center justify-center">
                        <x-lucide-chevron-left class="sc-i w-6 h-6" aria-hidden="true" />
                    </button>

                    <button type="button"
                            @click="copy()"
                            class="sc-btn sc-btn-primary px-6 sm:px-8 py-3.5 text-base sm:text-lg flex items-center gap-2.5">
                        <x-lucide-copy class="sc-i w-5 h-5" aria-hidden="true" />
                        <span>Copy Quote</span>
                    </button>

                    <button type="button"
                            @click="slide('next')"
                            aria-label="Next quote"
                            class="sc-btn sc-btn-ghost w-12 h-12 sm:w-14 sm:h-14 !p-0 rounded-full flex items-center justify-center">
                        <x-lucide-chevron-right class="sc-i w-6 h-6" aria-hidden="true" />
                    </button>
                </div>

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