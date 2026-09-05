<x-dashboard-layout sc>
    <x-slot:title>Wellness Center - SilverCare</x-slot:title>

    <x-dashboard-nav
        title="Wellness Center"
        subtitle="Activities to sharpen your mind, calm your body, and brighten your day."
        role="elderly"
        :unread-notifications="$unreadNotifications"
    />

    <main id="main-content" class="sc-app-main">
        <div class="max-w-4xl mx-auto px-6 lg:px-12">

            {{-- Back button --}}
            <div class="flex justify-end mb-6">
                <a href="{{ route('dashboard') }}" class="sc-btn sc-btn-ghost sc-btn-sm">
                    <x-lucide-arrow-left class="sc-i w-4 h-4" aria-hidden="true" />
                    <span>Back to Dashboard</span>
                </a>
            </div>

            {{-- Activities Grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 md:gap-6">

                {{-- Daily Wisdom --}}
                <a href="{{ route('elderly.wellness.word') }}"
                   class="sc-card sc-lift p-6 md:p-8 flex flex-col justify-between group">
                    <div>
                        <div class="sc-plate mb-4">
                            <x-lucide-book-open class="sc-i w-6 h-6" aria-hidden="true" />
                        </div>
                        <h2 class="sc-h3 mb-2">Daily Wisdom</h2>
                        <p class="text-base" style="color:var(--sc-body)">Start your day with inspiring quotes.</p>
                    </div>
                    <div class="mt-6 pt-4 flex items-center gap-2 font-semibold text-base" style="color:var(--sc-brand-text); border-top: 1px solid var(--sc-line)">
                        <span>Open Activity</span>
                        <x-lucide-arrow-right class="sc-i w-4 h-4 transition-transform group-hover:translate-x-1" aria-hidden="true" />
                    </div>
                </a>

                {{-- Breathing Space --}}
                <a href="{{ route('elderly.wellness.breathing') }}"
                   class="sc-card sc-lift p-6 md:p-8 flex flex-col justify-between group">
                    <div>
                        <div class="sc-plate mb-4">
                            <x-lucide-wind class="sc-i w-6 h-6" aria-hidden="true" />
                        </div>
                        <h2 class="sc-h3 mb-2">Breathing Space</h2>
                        <p class="text-base" style="color:var(--sc-body)">Reduce anxiety with guided breathing.</p>
                    </div>
                    <div class="mt-6 pt-4 flex items-center gap-2 font-semibold text-base" style="color:var(--sc-brand-text); border-top: 1px solid var(--sc-line)">
                        <span>Start Session</span>
                        <x-lucide-arrow-right class="sc-i w-4 h-4 transition-transform group-hover:translate-x-1" aria-hidden="true" />
                    </div>
                </a>

                {{-- Body Movement --}}
                <a href="{{ route('elderly.wellness.stretch') }}"
                   class="sc-card sc-lift p-6 md:p-8 flex flex-col justify-between group">
                    <div>
                        <div class="sc-plate mb-4">
                            <x-lucide-activity class="sc-i w-6 h-6" aria-hidden="true" />
                        </div>
                        <h2 class="sc-h3 mb-2">Body Movement</h2>
                        <p class="text-base" style="color:var(--sc-body)">Exercises for mobility and balance.</p>
                    </div>
                    <div class="mt-6 pt-4 flex items-center gap-2 font-semibold text-base" style="color:var(--sc-brand-text); border-top: 1px solid var(--sc-line)">
                        <span>Start Exercises</span>
                        <x-lucide-arrow-right class="sc-i w-4 h-4 transition-transform group-hover:translate-x-1" aria-hidden="true" />
                    </div>
                </a>

                {{-- Mind Games --}}
                <a href="{{ route('elderly.wellness.memory') }}"
                   class="sc-card sc-lift p-6 md:p-8 flex flex-col justify-between group">
                    <div>
                        <div class="sc-plate mb-4">
                            <x-lucide-brain class="sc-i w-6 h-6" aria-hidden="true" />
                        </div>
                        <h2 class="sc-h3 mb-2">Mind Games</h2>
                        <p class="text-base" style="color:var(--sc-body)">Challenge your memory with cards.</p>
                    </div>
                    <div class="mt-6 pt-4 flex items-center gap-2 font-semibold text-base" style="color:var(--sc-brand-text); border-top: 1px solid var(--sc-line)">
                        <span>Play Now</span>
                        <x-lucide-arrow-right class="sc-i w-4 h-4 transition-transform group-hover:translate-x-1" aria-hidden="true" />
                    </div>
                </a>

            </div>
        </div>
    </main>
</x-dashboard-layout>