{{--
    Flash Messages Component

    Displays session success and error flash messages in a consistent modern style.
    Features a modern glassmorphic aesthetic matching the toast notifications.
--}}

@if(session('success'))
    <div x-data="{ show: true }" 
         x-show="show" 
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-4"
         class="mb-6 relative px-5 py-4 pl-4 pr-11 rounded-2xl shadow-xl overflow-hidden pointer-events-auto border border-white/20 bg-gray-900/80 backdrop-blur-xl supports-[backdrop-filter]:bg-gray-900/70"
         style="border-bottom: 3px solid #10b981;">
         
         <div class="absolute inset-0 bg-gradient-to-br from-white/10 to-transparent pointer-events-none"></div>

         <div class="relative z-10 flex items-center gap-3">
             <span class="flex-shrink-0">
                 <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
             </span>
             <span class="text-white font-medium text-sm leading-snug">{{ session('success') }}</span>
         </div>

         <button @click="show = false" class="absolute right-3 top-1/2 -translate-y-1/2 text-white/50 hover:text-white/90 p-1 focus:outline-none z-10 hover:bg-white/10 rounded-full transition-colors">
             <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
         </button>
    </div>
@endif

@if(session('error'))
    <div x-data="{ show: true }" 
         x-show="show" 
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-4"
         class="mb-6 relative px-5 py-4 pl-4 pr-11 rounded-2xl shadow-xl overflow-hidden pointer-events-auto border border-white/20 bg-gray-900/80 backdrop-blur-xl supports-[backdrop-filter]:bg-gray-900/70"
         style="border-bottom: 3px solid #ef4444;">
         
         <div class="absolute inset-0 bg-gradient-to-br from-white/10 to-transparent pointer-events-none"></div>

         <div class="relative z-10 flex items-center gap-3">
             <span class="flex-shrink-0">
                 <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
             </span>
             <span class="text-white font-medium text-sm leading-snug">{{ session('error') }}</span>
         </div>

         <button @click="show = false" class="absolute right-3 top-1/2 -translate-y-1/2 text-white/50 hover:text-white/90 p-1 focus:outline-none z-10 hover:bg-white/10 rounded-full transition-colors">
             <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
         </button>
    </div>
@endif
