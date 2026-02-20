<nav x-data="{ open: false }" class="bg-white border-b border-gray-200 font-sans sticky top-0 z-50">
    @php
        // KEEPING YOUR LOGIC: Determine routes based on user type
        $user = Auth::user();
        $isCaregiver = $user->profile?->user_type === 'caregiver';
        $dashboardRoute = $isCaregiver ? 'caregiver.dashboard' : 'dashboard';
        $profileRoute = $isCaregiver ? 'caregiver.profile.edit' : 'profile.edit';
        
        // Check if we're currently on the dashboard
        $isOnDashboard = request()->routeIs($dashboardRoute) || request()->routeIs('dashboard') || request()->routeIs('caregiver.dashboard');
    @endphp

    <!-- Primary Navigation Menu -->
    <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-12">
        <div class="flex justify-between h-20">
            
            <!-- LEFT: Logo (Clicking goes to Dashboard) -->
            <div class="flex items-center">
                <a href="{{ route($dashboardRoute) }}" class="flex items-center gap-3 group transition-all">
                    <img src="{{ asset('assets/icons/silvercare.png') }}" alt="SilverCare" class="w-10 h-10 object-contain group-hover:scale-105 transition-transform">
                    <h1 class="text-2xl font-black tracking-tight text-gray-900 group-hover:opacity-80 transition-opacity">
                        SILVER<span class="text-[#000080]">CARE</span>
                    </h1>
                </a>
            </div>

            <!-- RIGHT: Actions -->
            <div class="hidden sm:flex sm:items-center sm:gap-6">
                
                <!-- 1. BACK TO DASHBOARD BUTTON - Only show when NOT on dashboard -->
                @if(!$isOnDashboard)
                <a href="{{ route($dashboardRoute) }}" class="text-sm font-bold text-gray-500 hover:text-[#000080] hover:bg-blue-50 px-5 py-2.5 rounded-xl transition-all flex items-center gap-2 group">
                    <svg class="w-4 h-4 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Back to Dashboard
                </a>

                <div class="h-8 w-[1px] bg-gray-200"></div>
                @endif

                <!-- 2. USER DROPDOWN -->
                <div class="relative ms-3">
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center gap-3 px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-full text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150 group">
                                <div class="text-right hidden md:block">
                                    <div class="font-bold text-gray-900">{{ $user->name }}</div>
                                    <div class="text-xs {{ $isCaregiver ? 'text-indigo-600' : 'text-green-600' }} font-bold uppercase tracking-wide">
                                        {{ $isCaregiver ? 'Caregiver' : 'Patient' }}
                                    </div>
                                </div>
                                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-[#000080] font-black text-lg border-2 border-white shadow-sm group-hover:shadow-md transition-all overflow-hidden">
                                    @if($user->profile && $user->profile->profile_photo)
                                        <img src="{{ Storage::url($user->profile->profile_photo) }}" alt="{{ $user->name }}" class="w-full h-full object-cover">
                                    @else
                                        {{ substr($user->name, 0, 1) }}
                                    @endif
                                </div>
                                <div class="ms-1">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <!-- Account Management -->
                            <div class="block px-4 py-2 text-xs text-gray-400">
                                {{ __('Manage Account') }}
                            </div>

                            <x-dropdown-link :href="route($profileRoute)">
                                {{ __('Profile') }}
                            </x-dropdown-link>

                            <div class="border-t border-gray-100"></div>

                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault();
                                                    this.closest('form').submit();" class="text-red-600 font-bold">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </div>
            </div>

            <!-- Hamburger (Mobile) -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden bg-white border-t border-gray-100">
        <div class="pt-2 pb-3 space-y-1">
            <!-- Mobile Back Button - Only show when NOT on dashboard -->
            @if(!$isOnDashboard)
            <x-responsive-nav-link :href="route($dashboardRoute)" class="text-[#000080] font-bold">
                ← {{ __('Back to Dashboard') }}
            </x-responsive-nav-link>
            @endif
        </div>

        <!-- Mobile Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200 bg-gray-50">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route($profileRoute)">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();" class="text-red-600">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>