{{--
    Dashboard Navigation Component
    
    Shared nav bar for elderly and caregiver dashboard pages.
    Handles logo, page title, notifications (elderly only), profile link, and logout.

    Props:
        - title: string — Page title displayed in the nav
        - subtitle: string|null — Subtitle (defaults to current date)
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

<nav class="bg-white border-b border-gray-200 sticky top-0 z-50">
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
                <p class="text-xs text-gray-500 font-medium -mt-0.5">{{ $subtitle ?? now()->format('l, F j, Y') }}</p>
            </div>
        </div>

        {{-- Right Side: Actions --}}
        <div class="flex items-center gap-4">

            {{-- Notifications Bell (Elderly Only) --}}
            @if(!$isCaregiver)
                <a href="{{ route('elderly.notifications.index') }}" class="relative p-2 hover:bg-gray-50 rounded-xl transition-all group" title="Notifications">
                    <svg class="w-6 h-6 text-gray-600 group-hover:text-[#000080] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    @if($unreadNotifications > 0)
                        <span class="absolute top-0 right-0 w-5 h-5 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center">
                            {{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}
                        </span>
                    @endif
                </a>
            @endif

            {{-- Profile Link --}}
            <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 group hover:bg-gray-50 rounded-xl py-1.5 px-2 transition-all cursor-pointer" title="Manage Profile">
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
                    <p class="text-[10px] text-gray-500 font-medium">{{ $roleLabel }}</p>
                </div>
            </a>

            {{-- Logout Button --}}
            <form method="POST" action="{{ route('logout') }}" class="ml-1">
                @csrf
                <button type="submit" class="flex items-center gap-1.5 px-3 py-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-xl font-bold text-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    <span class="hidden sm:inline">Logout</span>
                </button>
            </form>

        </div>
    </div>
</nav>
