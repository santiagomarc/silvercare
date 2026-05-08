<x-dashboard-layout>
    <x-slot:title>Wellness Center - SilverCare</x-slot:title>
 
    <x-dashboard-nav
        title="Wellness Center"
        subtitle="Relax & Rejuvenate"
        role="elderly"
        :unread-notifications="$unreadNotifications"
    />
 
    <main id="main-content" class="max-w-6xl mx-auto px-6 py-2 relative">
 
        {{-- Row 1: Back button aligned right --}}
        <div class="flex justify-end mb-2">
            <a href="{{ route('dashboard') }}" class="back-nav-pill">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Back to Dashboard
            </a>
        </div>
 
        {{-- Row 2: Banner --}}
        <div class="bg-gradient-to-br from-rose-500 to-pink-600 rounded-2xl px-10 py-5 shadow-lg shadow-pink-200 text-white relative overflow-hidden mb-3">
            <div class="absolute top-0 right-0 -mt-8 -mr-8 w-36 h-36 bg-white/20 rounded-full blur-2xl"></div>
            <div class="absolute bottom-0 left-0 -mb-5 -ml-5 w-24 h-24 bg-black/10 rounded-full blur-xl"></div>
            <div class="relative z-10 text-center">
                <h1 class="text-2xl font-[900] tracking-tight leading-tight">Relax & Rejuvenate</h1>
                <p class="text-pink-100 text-sm font-medium mt-1 opacity-90">
                    Activities to sharpen your mind, calm your body, and brighten your day.
                </p>
            </div>
        </div>
 
        {{-- Row 3: Activities Grid 2x2 --}}
        <div class="grid grid-cols-2 gap-3">
 
            {{-- Daily Wisdom --}}
            <a href="{{ route('elderly.wellness.word') }}" class="group bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden flex flex-col justify-between" style="min-height: 175px;">
                <div class="absolute top-0 right-0 w-24 h-24 bg-purple-50 rounded-bl-[80px] -mr-3 -mt-3 transition-transform group-hover:scale-110 duration-500"></div>
                <div class="relative z-10">
                    <div class="w-11 h-11 bg-purple-100 rounded-xl flex items-center justify-center text-purple-600 mb-2 shadow-sm group-hover:bg-purple-600 group-hover:text-white transition-colors duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    </div>
                    <h3 class="text-xl font-[800] text-gray-800 mb-0.5 group-hover:text-purple-700 transition-colors">Daily Wisdom</h3>
                    <p class="text-gray-500 font-medium text-sm">Start your day with inspiring quotes.</p>
                </div>
                <div class="flex items-center text-purple-600 font-bold text-sm mt-2 group-hover:translate-x-2 transition-transform">
                    <span>Open Activity</span>
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                </div>
            </a>
 
            {{-- Breathing Space --}}
            <a href="{{ route('elderly.wellness.breathing') }}" class="group bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden flex flex-col justify-between" style="min-height: 175px;">
                <div class="absolute top-0 right-0 w-24 h-24 bg-teal-50 rounded-bl-[80px] -mr-3 -mt-3 transition-transform group-hover:scale-110 duration-500"></div>
                <div class="relative z-10">
                    <div class="w-11 h-11 bg-teal-100 rounded-xl flex items-center justify-center text-teal-600 mb-2 shadow-sm group-hover:bg-teal-600 group-hover:text-white transition-colors duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <h3 class="text-xl font-[800] text-gray-800 mb-0.5 group-hover:text-teal-700 transition-colors">Breathing Space</h3>
                    <p class="text-gray-500 font-medium text-sm">Reduce anxiety with guided breathing.</p>
                </div>
                <div class="flex items-center text-teal-600 font-bold text-sm mt-2 group-hover:translate-x-2 transition-transform">
                    <span>Start Session</span>
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                </div>
            </a>
 
            {{-- Body Movement --}}
            <a href="{{ route('elderly.wellness.stretch') }}" class="group bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden flex flex-col justify-between" style="min-height: 175px;">
                <div class="absolute top-0 right-0 w-24 h-24 bg-orange-50 rounded-bl-[80px] -mr-3 -mt-3 transition-transform group-hover:scale-110 duration-500"></div>
                <div class="relative z-10">
                    <div class="w-11 h-11 bg-orange-100 rounded-xl flex items-center justify-center text-orange-600 mb-2 shadow-sm group-hover:bg-orange-600 group-hover:text-white transition-colors duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3m0 0V11"></path></svg>
                    </div>
                    <h3 class="text-xl font-[800] text-gray-800 mb-0.5 group-hover:text-orange-700 transition-colors">Body Movement</h3>
                    <p class="text-gray-500 font-medium text-sm">Exercises for mobility and balance.</p>
                </div>
                <div class="flex items-center text-orange-600 font-bold text-sm mt-2 group-hover:translate-x-2 transition-transform">
                    <span>Start Exercises</span>
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                </div>
            </a>
 
            {{-- Mind Games --}}
            <a href="{{ route('elderly.wellness.memory') }}" class="group bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden flex flex-col justify-between" style="min-height: 175px;">
                <div class="absolute top-0 right-0 w-24 h-24 bg-blue-50 rounded-bl-[80px] -mr-3 -mt-3 transition-transform group-hover:scale-110 duration-500"></div>
                <div class="relative z-10">
                    <div class="w-11 h-11 bg-blue-100 rounded-xl flex items-center justify-center text-blue-600 mb-2 shadow-sm group-hover:bg-blue-600 group-hover:text-white transition-colors duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
                    </div>
                    <h3 class="text-xl font-[800] text-gray-800 mb-0.5 group-hover:text-blue-700 transition-colors">Mind Games</h3>
                    <p class="text-gray-500 font-medium text-sm">Challenge your memory with cards.</p>
                </div>
                <div class="flex items-center text-blue-600 font-bold text-sm mt-2 group-hover:translate-x-2 transition-transform">
                    <span>Play Now</span>
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                </div>
            </a>
 
        </div>
    </main>
</x-dashboard-layout>