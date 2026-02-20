<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Medications - SilverCare</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Montserrat', sans-serif; }
    </style>
</head>
<body class="antialiased bg-[#F5F5F5] min-h-screen">

    <!-- Header -->
    <div class="bg-white shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard') }}" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </a>
                <div>
                    <h1 class="text-xl font-[900] text-gray-900">My Medications</h1>
                    <p class="text-xs text-gray-500 font-semibold">{{ $medications->count() }} active medications</p>
                </div>
            </div>
            <div class="w-12 h-12 bg-[#000080] rounded-xl flex items-center justify-center">
                <span class="text-white text-2xl">💊</span>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        @php
            $todayName = now()->format('l');
        @endphp

        @forelse($medications as $medication)
            @php
                $isToday = empty($medication->days_of_week) || in_array($todayName, $medication->days_of_week);
            @endphp
            <div class="mb-6 bg-white rounded-2xl shadow-lg overflow-hidden {{ $isToday ? 'ring-2 ring-blue-500' : '' }}">
                @if($isToday)
                    <div class="bg-blue-500 text-white text-center py-1 text-sm font-bold">
                        📅 Scheduled for Today
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
                    @if(!empty($medication->days_of_week))
                        <div class="mb-4">
                            <p class="text-sm text-gray-500 mb-2 font-medium">Schedule</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                                    <span class="px-3 py-1 text-sm rounded-full {{ in_array($day, $medication->days_of_week) ? 'bg-blue-500 text-white font-bold' : 'bg-gray-100 text-gray-400' }}">
                                        {{ substr($day, 0, 3) }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Times -->
                    @if(!empty($medication->times_of_day))
                        <div class="mb-4">
                            <p class="text-sm text-gray-500 mb-2 font-medium">Times to Take</p>
                            <div class="flex flex-wrap gap-3">
                                @foreach($medication->times_of_day as $time)
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
                            <p class="text-sm text-gray-500 font-medium mb-1">📝 Instructions</p>
                            <p class="text-gray-700">{{ $medication->instructions }}</p>
                        </div>
                    @endif

                    <!-- Stock Warning -->
                    @if($medication->track_inventory && $medication->current_stock <= ($medication->low_stock_threshold ?? 5))
                        <div class="mt-4 p-3 bg-red-50 rounded-xl border border-red-200 flex items-center">
                            <span class="text-2xl mr-3">⚠️</span>
                            <div>
                                <p class="text-red-700 font-bold">Low Stock Alert</p>
                                <p class="text-red-600 text-sm">Only {{ $medication->current_stock }} left. Please refill soon!</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center py-16">
                <div class="text-6xl mb-4">💊</div>
                <h2 class="text-2xl font-bold text-gray-700 mb-2">No Medications</h2>
                <p class="text-gray-500">Your caregiver hasn't added any medications yet.</p>
                <a href="{{ route('dashboard') }}" class="mt-6 inline-flex items-center text-[#000080] hover:underline font-medium">
                    ← Back to Dashboard
                </a>
            </div>
        @endforelse

    </div>

</body>
</html>
