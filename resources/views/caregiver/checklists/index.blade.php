<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Daily Checklists - SilverCare</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('assets/icons/silvercare.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/icons/silvercare.png') }}">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Montserrat', sans-serif; }
    </style>
</head>
<body class="bg-[#EBEBEB] min-h-screen">

    <!-- NAV BAR -->
    <nav class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-[1600px] mx-auto px-6 lg:px-12 h-16 flex justify-between items-center">
            <div class="flex items-center gap-6">
                <a href="{{ route('caregiver.dashboard') }}" class="flex items-center gap-3 group">
                    <img src="{{ asset('assets/icons/silvercare.png') }}" alt="SilverCare" class="w-9 h-9 object-contain group-hover:scale-105 transition-transform">
                    <h1 class="text-xl font-[900] tracking-tight text-gray-900 hidden sm:block">SILVER<span class="text-[#000080]">CARE</span></h1>
                </a>
                <div class="h-6 w-[1px] bg-gray-200 hidden md:block"></div>
                <div class="hidden md:block">
                    <h2 class="text-lg font-[800] text-gray-900">Daily Checklists</h2>
                    <p class="text-xs text-gray-500 font-medium -mt-0.5">{{ now()->format('l, F j, Y') }}</p>
                </div>
            </div>
            
            <div class="flex items-center gap-4">
                <a href="{{ route('caregiver.dashboard') }}" class="flex items-center gap-1.5 px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-bold text-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    <span class="hidden sm:inline">Back to Dashboard</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="max-w-[1600px] mx-auto px-6 lg:px-12 py-6">
        
        @if(session('success'))
            <div class="mb-6 bg-green-50 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded-lg shadow-sm flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                {{ session('success') }}
            </div>
        @endif

        <!-- HEADER WITH ADD BUTTON -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-[900] text-gray-900">Checklists</h1>
                <p class="text-gray-500 font-medium text-sm">Manage daily tasks for your patient</p>
            </div>
            <a href="{{ route('caregiver.checklists.create') }}" class="group flex items-center gap-2 bg-gradient-to-r from-green-500 to-emerald-600 text-white px-5 py-3 rounded-2xl font-[700] shadow-lg shadow-green-200 hover:-translate-y-0.5 transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Add Task
            </a>
        </div>

        <!-- Progress Bar Card -->
        @php
            $total = $checklists->count();
            $completed = $checklists->where('is_completed', true)->count();
            $progress = $total > 0 ? round(($completed / $total) * 100) : 0;
            
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
        @endphp
        <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-[24px] p-6 mb-6 shadow-lg shadow-green-200/50">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="text-white">
                    <h3 class="font-[800] text-lg">Today's Progress</h3>
                    <p class="text-green-100 text-sm font-medium">{{ $completed }} of {{ $total }} tasks completed</p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="flex-1 md:w-48 bg-white/20 rounded-full h-4 overflow-hidden">
                        <div class="bg-white h-full rounded-full transition-all duration-500" style="width: {{ $progress }}%"></div>
                    </div>
                    <span class="text-white font-[900] text-xl">{{ $progress }}%</span>
                </div>
            </div>
        </div>

        <!-- Checklist Items -->
        <div class="bg-white rounded-[24px] shadow-md border border-gray-100 overflow-hidden">
            <ul class="divide-y divide-gray-100">
                @forelse($checklists as $checklist)
                    <li class="group hover:bg-gray-50 transition-colors duration-200">
                        <div class="px-6 py-5 flex items-center">
                            <!-- Toggle Checkbox -->
                            <div class="flex-shrink-0 mr-4">
                                <form action="{{ route('caregiver.checklists.toggle', $checklist) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-8 h-8 {{ $checklist->is_completed ? 'bg-green-500 border-green-500 text-white' : 'bg-white border-gray-300 hover:border-green-500' }} border-2 rounded-full flex items-center justify-center transition-all duration-200 hover:scale-110 shadow-sm">
                                        @if($checklist->is_completed)
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                        @endif
                                    </button>
                                </form>
                            </div>

                            <!-- Category Icon -->
                            <div class="flex-shrink-0 mr-4 w-12 h-12 bg-gray-50 rounded-xl flex items-center justify-center">
                                <span class="text-2xl">{{ $categoryIcons[$checklist->category] ?? '📋' }}</span>
                            </div>

                            <!-- Task Details -->
                            <div class="flex-grow min-w-0 {{ $checklist->is_completed ? 'opacity-60' : '' }}">
                                <div class="flex items-center gap-2 mb-1">
                                    <h4 class="text-base font-[700] text-gray-900 {{ $checklist->is_completed ? 'line-through' : '' }}">{{ $checklist->task }}</h4>
                                    @if($checklist->priority == 'high')
                                        <span class="px-2.5 py-0.5 bg-red-100 text-red-700 text-[10px] font-[800] rounded-full uppercase">High</span>
                                    @elseif($checklist->priority == 'low')
                                        <span class="px-2.5 py-0.5 bg-gray-100 text-gray-600 text-[10px] font-[800] rounded-full uppercase">Low</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-3 text-xs text-gray-500 font-medium">
                                    <span class="bg-gray-100 px-2.5 py-1 rounded-lg font-[700]">{{ $checklist->category }}</span>
                                    @if($checklist->due_date)
                                        <span class="flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                            {{ $checklist->due_date->format('M d') }}
                                        </span>
                                    @endif
                                    @if($checklist->due_time)
                                        <span class="flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            {{ \Carbon\Carbon::parse($checklist->due_time)->format('g:i A') }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Status Badge -->
                            @if(!$checklist->is_completed && $checklist->due_date)
                                @php
                                    $isOverdue = $checklist->due_date->isPast() && !$checklist->due_date->isToday();
                                    $isToday = $checklist->due_date->isToday();
                                @endphp
                                <div class="flex-shrink-0 ml-4">
                                    @if($isOverdue)
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-[700] bg-red-100 text-red-700">
                                            ⚠️ Overdue
                                        </span>
                                    @elseif($isToday)
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-[700] bg-amber-100 text-amber-700">
                                            📅 Due Today
                                        </span>
                                    @endif
                                </div>
                            @endif

                            @if($checklist->is_completed)
                                <div class="flex-shrink-0 ml-4">
                                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-[700] bg-green-100 text-green-700">
                                        ✓ Done
                                    </span>
                                </div>
                            @endif

                            <!-- Actions -->
                            <div class="flex-shrink-0 ml-4 flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                <a href="{{ route('caregiver.checklists.edit', $checklist) }}" class="w-9 h-9 rounded-xl bg-gray-100 flex items-center justify-center text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </a>
                                <form action="{{ route('caregiver.checklists.destroy', $checklist) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this task?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="w-9 h-9 rounded-xl bg-gray-100 flex items-center justify-center text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="py-16 text-center">
                        <div class="w-20 h-20 bg-gradient-to-br from-green-100 to-emerald-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-10 h-10 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                        </div>
                        <h3 class="text-xl font-[800] text-gray-900 mb-2">No Tasks Yet</h3>
                        <p class="text-gray-500 font-medium mb-6">Get started by adding a daily task for your patient.</p>
                        <a href="{{ route('caregiver.checklists.create') }}" class="inline-flex items-center gap-2 bg-gradient-to-r from-green-500 to-emerald-600 text-white px-6 py-3 rounded-2xl font-[700] shadow-lg shadow-green-200 hover:-translate-y-0.5 transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Add Task
                        </a>
                    </li>
                @endforelse
            </ul>
        </div>
    </main>

</body>
</html>
