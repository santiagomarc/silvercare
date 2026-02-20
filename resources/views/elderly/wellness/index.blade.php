<x-app-layout>
    <div class="min-h-screen bg-[#EBEBEB] py-10">
        <div class="max-w-6xl mx-auto px-6">
            
            <!-- Header Area -->
            <div class="flex items-center justify-center mb-10">
                <div class="bg-white px-8 py-4 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4 transform hover:scale-105 transition-transform duration-300">
                    <!-- Red/Pink Heart Icon Background -->
                    <div class="p-3 bg-rose-100 text-rose-600 rounded-xl">
                        <!-- Heart Icon (Same as Dashboard) -->
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                    </div>
                    <span class="font-[900] text-gray-800 text-2xl tracking-tight uppercase">Wellness Center</span>
                </div>
            </div>

            <!-- Intro Banner (Updated to Red/Pink Theme) -->
            <div class="bg-gradient-to-br from-rose-500 to-pink-600 rounded-[32px] p-8 md:p-12 shadow-xl shadow-pink-200 text-white relative overflow-hidden mb-12 group hover:shadow-2xl transition-all duration-500">
                <div class="absolute top-0 right-0 -mt-10 -mr-10 w-64 h-64 bg-white/20 rounded-full blur-3xl group-hover:scale-110 transition-transform duration-700"></div>
                <div class="absolute bottom-0 left-0 -mb-10 -ml-10 w-40 h-40 bg-black/10 rounded-full blur-2xl"></div>
                
                <div class="relative z-10 max-w-3xl mx-auto text-center">
                    <h1 class="text-4xl md:text-5xl font-[900] mb-6 tracking-tight">Relax & Rejuvenate</h1>
                    <p class="text-pink-100 text-xl font-medium leading-relaxed opacity-95">
                        Take a moment for yourself. Explore activities designed to sharpen your mind, calm your body, and brighten your day.
                    </p>
                </div>
            </div>

            <!-- Activities Grid (2x2 Layout) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                
                <!-- Word of the Day -->
                <a href="{{ route('elderly.wellness.word') }}" class="group bg-white rounded-[32px] p-8 shadow-sm border border-gray-100 hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 relative overflow-hidden h-72 flex flex-col justify-between">
                    <div class="absolute top-0 right-0 w-40 h-40 bg-purple-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110 duration-500"></div>
                    
                    <div class="relative z-10">
                        <div class="w-16 h-16 bg-purple-100 rounded-2xl flex items-center justify-center text-purple-600 mb-6 shadow-sm group-hover:bg-purple-600 group-hover:text-white transition-colors duration-300">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                        </div>
                        <h3 class="text-3xl font-[800] text-gray-800 mb-2 group-hover:text-purple-700 transition-colors">Daily Wisdom</h3>
                        <p class="text-gray-500 font-medium text-lg">Start your day with inspiring quotes.</p>
                    </div>

                    <div class="flex items-center text-purple-600 font-bold text-lg group-hover:translate-x-2 transition-transform">
                        <span>Open Activity</span>
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                    </div>
                </a>

                <!-- Breathing Exercise -->
                <a href="{{ route('elderly.wellness.breathing') }}" class="group bg-white rounded-[32px] p-8 shadow-sm border border-gray-100 hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 relative overflow-hidden h-72 flex flex-col justify-between">
                    <div class="absolute top-0 right-0 w-40 h-40 bg-teal-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110 duration-500"></div>
                    
                    <div class="relative z-10">
                        <div class="w-16 h-16 bg-teal-100 rounded-2xl flex items-center justify-center text-teal-600 mb-6 shadow-sm group-hover:bg-teal-600 group-hover:text-white transition-colors duration-300">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <h3 class="text-3xl font-[800] text-gray-800 mb-2 group-hover:text-teal-700 transition-colors">Breathing Space</h3>
                        <p class="text-gray-500 font-medium text-lg">Reduce anxiety with guided breathing.</p>
                    </div>

                    <div class="flex items-center text-teal-600 font-bold text-lg group-hover:translate-x-2 transition-transform">
                        <span>Start Session</span>
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                    </div>
                </a>

                <!-- Morning Stretch -->
                <a href="{{ route('elderly.wellness.stretch') }}" class="group bg-white rounded-[32px] p-8 shadow-sm border border-gray-100 hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 relative overflow-hidden h-72 flex flex-col justify-between">
                    <div class="absolute top-0 right-0 w-40 h-40 bg-orange-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110 duration-500"></div>
                    
                    <div class="relative z-10">
                        <div class="w-16 h-16 bg-orange-100 rounded-2xl flex items-center justify-center text-orange-600 mb-6 shadow-sm group-hover:bg-orange-600 group-hover:text-white transition-colors duration-300">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3m0 0V11"></path></svg>
                        </div>
                        <h3 class="text-3xl font-[800] text-gray-800 mb-2 group-hover:text-orange-700 transition-colors">Body Movement</h3>
                        <p class="text-gray-500 font-medium text-lg">Exercises for mobility and balance.</p>
                    </div>

                    <div class="flex items-center text-orange-600 font-bold text-lg group-hover:translate-x-2 transition-transform">
                        <span>Start Exercises</span>
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                    </div>
                </a>

                <!-- Memory Match -->
                <a href="{{ route('elderly.wellness.memory') }}" class="group bg-white rounded-[32px] p-8 shadow-sm border border-gray-100 hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 relative overflow-hidden h-72 flex flex-col justify-between">
                    <div class="absolute top-0 right-0 w-40 h-40 bg-blue-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110 duration-500"></div>
                    
                    <div class="relative z-10">
                        <div class="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center text-blue-600 mb-6 shadow-sm group-hover:bg-blue-600 group-hover:text-white transition-colors duration-300">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
                        </div>
                        <h3 class="text-3xl font-[800] text-gray-800 mb-2 group-hover:text-blue-700 transition-colors">Mind Games</h3>
                        <p class="text-gray-500 font-medium text-lg">Challenge your memory with cards.</p>
                    </div>

                    <div class="flex items-center text-blue-600 font-bold text-lg group-hover:translate-x-2 transition-transform">
                        <span>Play Now</span>
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                    </div>
                </a>

            </div>
        </div>
    </div>
</x-app-layout>