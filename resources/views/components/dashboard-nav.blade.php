{{--
    Dashboard Navigation Component
    
    Shared nav bar for elderly and caregiver dashboard pages.
    Handles logo, page title, notifications (elderly only), profile link, and utilities.

    Props:
        - title: string — Page title displayed in the nav
        - subtitle: string|null — Optional subtitle
        - role: 'elderly'|'caregiver' — Controls color scheme and feature visibility
        - unreadNotifications: int — Badge count for notification bell (elderly only)
--}}

@props([
    'title' => 'Dashboard',
    'subtitle' => null,
    'role' => 'elderly',
    'unreadNotifications' => 0,
])

@php
    $isCaregiver = $role === 'caregiver';
    $profileBgColor = $isCaregiver
        ? 'bg-purple-100 text-purple-700 group-hover:bg-purple-600'
        : 'bg-blue-100 text-[#000080] group-hover:bg-[#000080]';
    $profileNameHover = $isCaregiver ? 'group-hover:text-purple-600' : 'group-hover:text-[#000080]';
    $roleLabel = $isCaregiver ? 'Caregiver' : 'Patient';
@endphp

<nav x-data="{ mobileMenuOpen: false }" class="sticky top-0 z-50 border-b border-white/60 bg-white/70 backdrop-blur-xl shadow-[0_18px_40px_-32px_rgba(15,23,42,0.42)]">
    <div class="max-w-[1600px] mx-auto px-6 lg:px-12 h-16 flex justify-between items-center">

        {{-- Left Side: Logo + Title --}}
        <div class="flex items-center gap-6">
            <div class="flex items-center gap-3">
                <img src="{{ asset('assets/icons/silvercare.png') }}" alt="SilverCare" class="w-9 h-9 object-contain">
                <h1 class="text-xl font-[900] tracking-tight text-gray-900 hidden sm:block">SILVER<span class="text-[#000080]">CARE</span></h1>
            </div>
            <div class="h-6 w-[1px] bg-gray-200 hidden md:block"></div>
            <div class="hidden md:block">
                <h2 class="text-lg font-[800] text-gray-900">{{ $title }}</h2>
                @if(!empty($subtitle))
                    <p class="text-xs text-gray-500 font-medium -mt-0.5">{{ $subtitle }}</p>
                @endif
            </div>
        </div>

        {{-- Right Side: Actions --}}
        <div class="flex items-center gap-4">

            {{-- Notifications Bell (Elderly Only) --}}
            @if(!$isCaregiver)
                <a href="{{ route('elderly.notifications.index') }}" aria-label="Notifications" class="hidden sm:flex relative rounded-xl border border-transparent p-2 transition-all group hover:border-white/70 hover:bg-white/60" title="Notifications">
                    <svg class="w-6 h-6 text-gray-600 group-hover:text-[#000080] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    @if($unreadNotifications > 0)
                        <span class="absolute top-0 right-0 w-5 h-5 bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center">
                            {{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}
                        </span>
                    @endif
                </a>
            @endif

            {{-- Messages (both roles) --}}
            <a
                href="{{ $isCaregiver ? route('caregiver.messages.index') : route('elderly.messages.index') }}"
                aria-label="Messages"
                class="hidden sm:flex relative rounded-xl border border-transparent p-2 transition-all group hover:border-white/70 hover:bg-white/60"
                title="Messages"
            >
                <svg class="w-6 h-6 text-gray-600 group-hover:text-[#000080] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h8m-8 4h5m-7 6l-3-3H3a2 2 0 01-2-2V7a2 2 0 012-2h18a2 2 0 012 2v8a2 2 0 01-2 2h-8l-5 5z"/>
                </svg>
            </a>

            {{-- Dark mode toggle --}}
            <button
                type="button"
                x-data="{ dark: document.documentElement.classList.contains('dark') }"
                @click="dark = window.toggleSilverCareTheme ? window.toggleSilverCareTheme() : dark"
                class="hidden sm:flex rounded-xl border border-transparent p-2 transition-all hover:border-white/70 hover:bg-white/60"
                :title="dark ? 'Switch to light mode' : 'Switch to dark mode'"
                :aria-label="dark ? 'Switch to light mode' : 'Switch to dark mode'"
            >
                <svg x-show="!dark" class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646a9 9 0 1011.708 11.708z"/>
                </svg>
                <svg x-show="dark" x-cloak class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v2m0 14v2m9-9h-2M5 12H3m15.364 6.364l-1.414-1.414M7.05 7.05 5.636 5.636m12.728 0L16.95 7.05M7.05 16.95l-1.414 1.414M12 8a4 4 0 100 8 4 4 0 000-8z"/>
                </svg>
            </button>

            {{-- Header SOS (elderly only when linked) --}}
            @if(!$isCaregiver)
                @php $linkedCg = Auth::user()->profile?->caregiver; @endphp
                @if($linkedCg)
                    <div x-data="{
                            confirming: false,
                            sending: false,
                            async sendSos() {
                                if (this.sending) return;
                                this.sending = true;
                                try {
                                    const resp = await fetch('{{ route('elderly.sos') }}', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'Accept': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                        },
                                    });
                                    const data = await resp.json();
                                    if (data.success) {
                                        Alpine.store('toast')?.success('SOS sent to your caregiver!');
                                        if (navigator.vibrate) navigator.vibrate([200, 100, 200]);
                                    } else {
                                        Alpine.store('toast')?.error(data.message || 'Failed to send SOS');
                                    }
                                } catch (e) {
                                    Alpine.store('toast')?.error('Failed to send SOS. Try calling your caregiver.');
                                } finally {
                                    this.sending = false;
                                    this.confirming = false;
                                }
                            }
                        }"
                        class="relative">
                        <button x-show="!confirming"
                                @click="confirming = true"
                                class="rounded-xl bg-red-50 hover:bg-red-100 border border-red-200 px-3 py-1.5 text-xs font-bold text-red-700 transition-colors">
                            SOS
                        </button>
                        <div x-show="confirming" 
                             @keydown.escape.window="confirming = false"
                             role="dialog"
                             aria-modal="true"
                             x-cloak 
                             class="absolute right-0 top-full mt-2 w-48 rounded-xl border border-red-200 bg-white p-3 shadow-lg z-50">
                            <p class="text-xs font-semibold text-gray-600 mb-2">Send emergency alert now?</p>
                            <div class="flex items-center gap-2">
                                <button @click="sendSos()" :disabled="sending" class="rounded-lg bg-red-600 px-2.5 py-1.5 text-xs font-bold text-white hover:bg-red-700 disabled:opacity-60">
                                    <span x-show="!sending">Yes, send</span>
                                    <span x-show="sending">Sending...</span>
                                </button>
                                <button @click="confirming = false" class="rounded-lg border border-gray-200 px-2.5 py-1.5 text-xs font-bold text-gray-600">Cancel</button>
                            </div>
                        </div>
                    </div>
                @endif
            @endif

            {{-- Profile Link --}}
            <a href="{{ route('profile.edit') }}" class="hidden sm:flex cursor-pointer items-center gap-2 rounded-xl border border-transparent px-2 py-1.5 transition-all group hover:border-white/70 hover:bg-white/60" title="Manage Profile">
                <div class="relative">
                    <div class="w-9 h-9 rounded-full {{ $profileBgColor }} font-[900] text-base group-hover:text-white transition-colors overflow-hidden flex items-center justify-center">
                        @if(Auth::user()->profile && Auth::user()->profile->profile_photo)
                            <img src="{{ Storage::url(Auth::user()->profile->profile_photo) }}" alt="{{ Auth::user()->name }}" class="w-full h-full object-cover">
                        @else
                            {{ substr(Auth::user()->name, 0, 1) }}
                        @endif
                    </div>
                </div>
                <div class="hidden sm:block">
                    <p class="text-sm font-bold text-gray-900 leading-tight {{ $profileNameHover }} transition-colors">{{ Auth::user()->name }}</p>
                    <p class="text-xs text-gray-500 font-medium">{{ $roleLabel }}</p>
                </div>
            </a>
            
            {{-- Mobile Menu Button --}}
            <button type="button" @click="mobileMenuOpen = true" aria-label="Open mobile menu" :aria-expanded="mobileMenuOpen.toString()" class="sm:hidden p-2 text-gray-600 hover:text-gray-900 focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>

        </div>
    </div>
    
    {{-- Mobile Drawer Panel --}}
    <div x-show="mobileMenuOpen" 
         class="fixed inset-0 z-[60] sm:hidden" 
         aria-modal="true" 
         role="dialog"
         x-cloak>
        <!-- Backdrop -->
        <div x-show="mobileMenuOpen" 
             x-transition.opacity 
             class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm"
             @click="mobileMenuOpen = false"></div>

        <!-- Drawer -->
        <div x-show="mobileMenuOpen" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full"
             class="fixed right-0 top-0 bottom-0 w-64 bg-white shadow-xl overflow-y-auto"
             @click.away="mobileMenuOpen = false">
             
             <div class="p-4 border-b border-gray-100 flex justify-between items-center">
                <h2 class="font-bold text-lg text-navy-800">Menu</h2>
                <button @click="mobileMenuOpen = false" aria-label="Close menu" class="p-2 text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
             </div>
             
             <div class="p-4 space-y-4">
                <div class="flex items-center gap-3 mb-4 pb-4 border-b border-gray-100">
                    <div class="w-10 h-10 rounded-full {{ $profileBgColor }} font-[900] flex items-center justify-center overflow-hidden">
                        @if(Auth::user()->profile && Auth::user()->profile->profile_photo)
                            <img src="{{ Storage::url(Auth::user()->profile->profile_photo) }}" alt="Profile" class="w-full h-full object-cover">
                        @else
                            {{ substr(Auth::user()->name, 0, 1) }}
                        @endif
                    </div>
                    <div>
                        <p class="font-bold text-gray-900">{{ Auth::user()->name }}</p>
                        <p class="text-xs text-gray-500">{{ $roleLabel }}</p>
                    </div>
                </div>
             
                <a href="{{ route('profile.edit') }}" class="block font-bold text-gray-800 hover:text-navy-600">Manage Profile</a>
                <a href="{{ $isCaregiver ? route('caregiver.messages.index') : route('elderly.messages.index') }}" class="block font-bold text-gray-800 hover:text-navy-600">Messages</a>
                @if(!$isCaregiver)
                    <a href="{{ route('elderly.notifications.index') }}" class="block font-bold text-gray-800 hover:text-navy-600">
                        Notifications
                        @if($unreadNotifications > 0)
                            <span class="ml-2 bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">{{ $unreadNotifications }}</span>
                        @endif
                    </a>
                @endif
                
                <div class="pt-4 mt-4 border-t border-gray-100">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left font-bold text-rose-600 hover:text-rose-800">
                            Log Out
                        </button>
                    </form>
                </div>
             </div>
        </div>
    </div>
</nav>
