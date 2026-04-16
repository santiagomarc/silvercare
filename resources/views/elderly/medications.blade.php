<x-dashboard-layout>
    <x-slot:title>My Medications - SilverCare</x-slot:title>

    <x-dashboard-nav
        title="My Medications"
        subtitle="{{ $medications->count() }} active medications"
        role="elderly"
        :unread-notifications="$unreadNotifications"
    />

    <main id="main-content" class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Back Navigation --}}
        <div class="mb-6">
            <a href="{{ route('dashboard') }}" class="back-nav-pill">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Back to Home
            </a>
        </div>
        
        @php
            $today = now();
        @endphp

        @forelse($medications as $medication)
            @php
                $isToday = $medication->isScheduledForDate($today);
                $scheduleType = $medication->primaryScheduleType();
                $weeklyDays = $medication->weeklyDays();
                $specificDates = $medication->specificScheduleDates();
                $displayTimes = $medication->scheduleTimesForDate($today);
                if (empty($displayTimes)) {
                    $displayTimes = $medication->times_of_day ?? [];
                }
            @endphp
            <div class="mb-6 bg-white rounded-2xl shadow-lg overflow-hidden {{ $isToday ? 'ring-2 ring-blue-500' : '' }}">
                @if($isToday)
                    <div class="bg-blue-500 text-white text-center py-1 text-sm font-bold">
                        <span class="inline-flex items-center gap-1.5">
                            <x-lucide-calendar-days class="w-4 h-4" aria-hidden="true" />
                            Scheduled for Today
                        </span>
                    </div>
                @endif
                <div class="p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h2 class="text-2xl font-[900] text-gray-900">{{ $medication->name }}</h2>
                            <p class="text-lg text-blue-600 font-bold">{{ $medication->dosage }} {{ $medication->dosage_unit }}</p>
                        </div>
                        <div class="text-right">
                            @if($medication->is_active)
                                <span class="px-3 py-1 bg-green-100 text-green-700 text-sm rounded-full font-medium">Active</span>
                            @else
                                <span class="px-3 py-1 bg-gray-100 text-gray-600 text-sm rounded-full font-medium">Inactive</span>
                            @endif
                        </div>
                    </div>

                    <!-- Schedule Days -->
                    @if($scheduleType === 'weekly')
                        <div class="mb-4">
                            <p class="text-sm text-gray-500 mb-2 font-medium">Schedule</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                                    <span class="px-3 py-1 text-sm rounded-full {{ in_array($day, $weeklyDays, true) ? 'bg-blue-500 text-white font-bold' : 'bg-gray-100 text-gray-400' }}">
                                        {{ substr($day, 0, 3) }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($scheduleType === 'daily')
                        <div class="mb-4">
                            <p class="text-sm text-gray-500 mb-2 font-medium">Schedule</p>
                            <span class="inline-flex items-center px-3 py-1 text-sm rounded-full bg-blue-100 text-blue-700 font-bold">
                                Every day
                            </span>
                        </div>
                    @endif

                    @if($scheduleType === 'specific_date')
                        <div class="mb-4">
                            <p class="text-sm text-gray-500 mb-2 font-medium">Scheduled Dates</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($specificDates as $specificDate)
                                    <span class="px-3 py-1 text-sm rounded-full bg-indigo-100 text-indigo-700 font-bold">
                                        {{ \Carbon\Carbon::parse($specificDate)->format('M j, Y') }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Times -->
                    @if(!empty($displayTimes))
                        <div class="mb-4">
                            <p class="text-sm text-gray-500 mb-2 font-medium">Times to Take</p>
                            <div class="flex flex-wrap gap-3">
                                @foreach($displayTimes as $time)
                                    <div class="flex items-center bg-amber-50 text-amber-700 px-4 py-2 rounded-xl border border-amber-200">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        <span class="font-bold text-lg">{{ \Carbon\Carbon::parse($time)->format('g:i A') }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Instructions -->
                    @if($medication->instructions)
                        <div class="mt-4 p-4 bg-gray-50 rounded-xl border border-gray-200">
                            <p class="text-sm text-gray-500 font-medium mb-1 inline-flex items-center gap-1.5">
                                <x-lucide-notebook-pen class="w-4 h-4 text-slate-400" aria-hidden="true" />
                                Instructions
                            </p>
                            <p class="text-gray-700">{{ $medication->instructions }}</p>
                        </div>
                    @endif

                    <!-- Stock Warning -->
                    @if($medication->track_inventory && $medication->current_stock <= ($medication->low_stock_threshold ?? 5))
                        <div class="mt-4 p-3 bg-red-50 rounded-xl border border-red-200 flex items-center">
                            <x-lucide-triangle-alert class="w-6 h-6 mr-3 text-red-600" aria-hidden="true" />
                            <div>
                                <p class="text-red-700 font-bold">Low Stock Alert</p>
                                <p class="text-red-600 text-sm">Only {{ $medication->current_stock }} left. Please refill soon!</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="empty-state">
                <div class="empty-state-icon">
                    <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                    </svg>
                </div>
                <h3>No medications yet</h3>
                <p class="inline-flex items-center gap-2 text-gray-600">
                    <span>Your caregiver will add your medications here when needed. Nothing to worry about.</span>
                    <x-lucide-heart class="w-5 h-5 text-emerald-600" aria-hidden="true" />
                </p>
                <a href="{{ route('dashboard') }}" class="mt-6 inline-flex items-center gap-2 px-6 py-3 bg-navy-500 text-white font-bold rounded-xl hover:bg-navy-600 transition-colors min-h-touch shadow-glow-brand">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    Back to Dashboard
                </a>
            </div>
        @endforelse

    </main>

</x-dashboard-layout>
