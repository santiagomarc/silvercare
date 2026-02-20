<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Tasks - SilverCare</title>
    
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
                    <h1 class="text-xl font-[900] text-gray-900">My Tasks</h1>
                    <p class="text-xs text-gray-500 font-semibold">{{ $checklists->count() }} tasks</p>
                </div>
            </div>
            <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center">
                <span class="text-white text-2xl">✅</span>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        @php
            $categoryIcons = [
                'Health' => '❤️',
                'Exercise' => '🏃',
                'Nutrition' => '🍎',
                'Social' => '👥',
                'Hygiene' => '🧼',
                'Mental' => '🧠',
                'Medication' => '💊',
                'Other' => '📋',
            ];
            $today = now()->format('Y-m-d');
        @endphp

        @forelse($groupedChecklists as $date => $dayChecklists)
            @php
                $dateObj = \Carbon\Carbon::parse($date);
                $isToday = $dateObj->isToday();
                $isPast = $dateObj->isPast() && !$isToday;
                $isFuture = $dateObj->isFuture();
            @endphp
            
            <div class="mb-8">
                <!-- Date Header -->
                <div class="flex items-center mb-4">
                    <div class="flex-grow h-px bg-gray-300"></div>
                    <div class="px-4 py-2 {{ $isToday ? 'bg-green-500 text-white' : ($isPast ? 'bg-gray-400 text-white' : 'bg-blue-500 text-white') }} rounded-full font-bold text-sm">
                        @if($isToday)
                            📅 Today - {{ $dateObj->format('M d') }}
                        @elseif($dateObj->isYesterday())
                            Yesterday - {{ $dateObj->format('M d') }}
                        @elseif($dateObj->isTomorrow())
                            Tomorrow - {{ $dateObj->format('M d') }}
                        @else
                            {{ $dateObj->format('l, M d') }}
                        @endif
                    </div>
                    <div class="flex-grow h-px bg-gray-300"></div>
                </div>

                <!-- Tasks for this date -->
                <div class="space-y-3">
                    @foreach($dayChecklists as $checklist)
                        <div class="bg-white rounded-2xl shadow-md overflow-hidden {{ $checklist->is_completed ? 'opacity-75' : '' }} transition-all duration-300">
                            <div class="p-5 flex items-center">
                                <!-- Toggle Button -->
                                <form action="{{ route('elderly.checklists.toggle', $checklist) }}" method="POST" class="mr-4">
                                    @csrf
                                    <button type="submit" class="w-10 h-10 {{ $checklist->is_completed ? 'bg-green-500 border-green-500 text-white' : 'bg-white border-gray-300 hover:border-green-500' }} border-2 rounded-full flex items-center justify-center transition-all duration-200 hover:scale-110 shadow-sm">
                                        @if($checklist->is_completed)
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                        @endif
                                    </button>
                                </form>

                                <!-- Category Icon -->
                                <div class="flex-shrink-0 mr-4 text-3xl">
                                    {{ $categoryIcons[$checklist->category] ?? '📋' }}
                                </div>

                                <!-- Task Content -->
                                <div class="flex-grow">
                                    <h3 class="text-lg font-bold text-gray-900 {{ $checklist->is_completed ? 'line-through text-gray-500' : '' }}">
                                        {{ $checklist->task }}
                                    </h3>
                                    <div class="flex flex-wrap items-center gap-2 mt-1">
                                        <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded-full">{{ $checklist->category }}</span>
                                        @if($checklist->due_time)
                                            <span class="text-sm text-gray-500">🕐 {{ \Carbon\Carbon::parse($checklist->due_time)->format('g:i A') }}</span>
                                        @endif
                                        @if($checklist->priority == 'high')
                                            <span class="px-2 py-0.5 bg-red-100 text-red-700 text-xs rounded-full font-bold">❗ High Priority</span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Status Badge -->
                                <div class="flex-shrink-0 ml-4">
                                    @if($checklist->is_completed)
                                        <div class="text-center">
                                            <span class="inline-flex items-center px-3 py-1 bg-green-100 text-green-700 text-sm rounded-full font-bold">
                                                ✓ Done
                                            </span>
                                            @if($checklist->completed_at)
                                                <p class="text-xs text-gray-400 mt-1">{{ $checklist->completed_at->format('g:i A') }}</p>
                                            @endif
                                        </div>
                                    @elseif($isPast)
                                        <span class="inline-flex items-center px-3 py-1 bg-red-100 text-red-700 text-sm rounded-full font-bold">
                                            Overdue
                                        </span>
                                    @endif
                                </div>
                            </div>

                            @if($checklist->notes)
                                <div class="px-5 pb-4">
                                    <p class="text-sm text-gray-500 bg-gray-50 p-3 rounded-lg">📝 {{ $checklist->notes }}</p>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="text-center py-16">
                <div class="text-6xl mb-4">✅</div>
                <h2 class="text-2xl font-bold text-gray-700 mb-2">No Tasks</h2>
                <p class="text-gray-500">Your caregiver hasn't added any tasks for you yet.</p>
                <a href="{{ route('dashboard') }}" class="mt-6 inline-flex items-center text-green-600 hover:underline font-medium">
                    ← Back to Dashboard
                </a>
            </div>
        @endforelse

    </div>

</body>
</html>
