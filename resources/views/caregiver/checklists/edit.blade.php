<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Edit Task - SilverCare</title>
    
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
                    <h2 class="text-lg font-[800] text-gray-900">Edit Task</h2>
                    <p class="text-xs text-gray-500 font-medium -mt-0.5">{{ $checklist->task }}</p>
                </div>
            </div>
            
            <div class="flex items-center gap-4">
                <a href="{{ route('caregiver.checklists.index') }}" class="flex items-center gap-1.5 px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-bold text-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    <span class="hidden sm:inline">Back</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="max-w-3xl mx-auto px-6 py-8">
        
        @if ($errors->any())
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded-lg shadow-sm">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('caregiver.checklists.update', $checklist) }}">
            @csrf
            @method('PUT')

            <!-- CARD 1: Task Details -->
            <div class="bg-white rounded-[24px] p-6 md:p-8 shadow-md border border-gray-100 mb-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    </div>
                    <h3 class="font-[800] text-xl text-gray-900">Task Details</h3>
                </div>

                <!-- Task Name -->
                <div class="mb-5">
                    <label for="task" class="block text-xs font-[800] uppercase tracking-wider text-gray-400 mb-2">Task <span class="text-red-500">*</span></label>
                    <input type="text" name="task" id="task" value="{{ old('task', $checklist->task) }}" class="w-full rounded-xl border-2 border-gray-100 bg-gray-50 px-4 py-3.5 font-[600] text-gray-900 transition-all focus:border-green-500 focus:bg-white focus:ring-0 outline-none" placeholder="e.g. Take afternoon walk, Drink water, Do stretching exercises" required>
                </div>

                <!-- Category Selection -->
                <div>
                    <label class="block text-xs font-[800] uppercase tracking-wider text-gray-400 mb-3">Category <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        @php
                            $categories = [
                                'Health' => ['icon' => '❤️', 'color' => 'red'],
                                'Exercise' => ['icon' => '🏃', 'color' => 'orange'],
                                'Nutrition' => ['icon' => '🍎', 'color' => 'green'],
                                'Social' => ['icon' => '👥', 'color' => 'blue'],
                                'Hygiene' => ['icon' => '🧼', 'color' => 'cyan'],
                                'Mental' => ['icon' => '🧠', 'color' => 'purple'],
                                'Medication' => ['icon' => '💊', 'color' => 'pink'],
                                'Other' => ['icon' => '📋', 'color' => 'gray'],
                            ];
                            $selectedCategory = old('category', $checklist->category);
                        @endphp
                        @foreach($categories as $catName => $catData)
                            <label class="relative cursor-pointer">
                                <input type="radio" name="category" value="{{ $catName }}" class="peer sr-only" {{ $selectedCategory == $catName ? 'checked' : '' }} required>
                                <div class="p-4 rounded-2xl border-2 border-gray-200 bg-white text-center transition-all duration-200 peer-checked:border-green-500 peer-checked:bg-green-50 hover:border-green-300 hover:bg-green-50/50">
                                    <div class="text-3xl mb-2">{{ $catData['icon'] }}</div>
                                    <div class="text-xs font-[700] text-gray-700">{{ $catName }}</div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- CARD 2: Schedule -->
            <div class="bg-white rounded-[24px] p-6 md:p-8 shadow-md border border-gray-100 mb-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-gradient-to-br from-amber-400 to-orange-500 rounded-xl flex items-center justify-center text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <h3 class="font-[800] text-xl text-gray-900">Schedule</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
                    <!-- Due Date -->
                    <div>
                        <label for="due_date" class="block text-xs font-[800] uppercase tracking-wider text-gray-400 mb-2">Due Date <span class="text-red-500">*</span></label>
                        <input type="date" name="due_date" id="due_date" value="{{ old('due_date', $checklist->due_date?->format('Y-m-d')) }}" class="w-full rounded-xl border-2 border-gray-100 bg-gray-50 px-4 py-3.5 font-[600] text-gray-900 transition-all focus:border-amber-500 focus:bg-white focus:ring-0 outline-none" required>
                    </div>

                    <!-- Due Time -->
                    <div>
                        <label for="due_time" class="block text-xs font-[800] uppercase tracking-wider text-gray-400 mb-2">Due Time (Optional)</label>
                        <input type="time" name="due_time" id="due_time" value="{{ old('due_time', $checklist->due_time ? \Carbon\Carbon::parse($checklist->due_time)->format('H:i') : '') }}" class="w-full rounded-xl border-2 border-gray-100 bg-gray-50 px-4 py-3.5 font-[600] text-gray-900 transition-all focus:border-amber-500 focus:bg-white focus:ring-0 outline-none">
                    </div>
                </div>

                <!-- Priority -->
                <div>
                    <label class="block text-xs font-[800] uppercase tracking-wider text-gray-400 mb-3">Priority</label>
                    <div class="flex flex-wrap gap-3">
                        @php 
                            $priorities = ['Low' => 'bg-gray-100 text-gray-700', 'Medium' => 'bg-yellow-100 text-yellow-700', 'High' => 'bg-red-100 text-red-700'];
                            $selectedPriority = old('priority', $checklist->priority ?? 'medium');
                        @endphp
                        @foreach($priorities as $priority => $classes)
                            <label class="relative cursor-pointer">
                                <input type="radio" name="priority" value="{{ strtolower($priority) }}" class="peer sr-only" {{ $selectedPriority == strtolower($priority) ? 'checked' : '' }}>
                                <div class="px-5 py-2.5 rounded-xl border-2 border-gray-200 {{ $classes }} font-[700] text-sm transition-all duration-200 peer-checked:border-green-500 peer-checked:ring-2 peer-checked:ring-green-200 hover:border-green-300">
                                    {{ $priority }}
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- CARD 3: Status & Notes -->
            <div class="bg-white rounded-[24px] p-6 md:p-8 shadow-md border border-gray-100 mb-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-violet-600 rounded-xl flex items-center justify-center text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <h3 class="font-[800] text-xl text-gray-900">Status & Notes</h3>
                </div>

                <!-- Completion Status -->
                <div class="bg-gray-50 p-5 rounded-2xl border border-gray-100 mb-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-[800] text-gray-700">Task Status</h4>
                            <p class="text-xs text-gray-500 mt-1">
                                @if($checklist->is_completed)
                                    Completed on {{ $checklist->completed_at?->format('M d, Y \a\t h:i A') }}
                                @else
                                    Not yet completed
                                @endif
                            </p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_completed" value="1" class="sr-only peer" {{ old('is_completed', $checklist->is_completed) ? 'checked' : '' }}>
                            <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-green-500"></div>
                            <span class="ml-3 text-sm font-[700] text-gray-700">Completed</span>
                        </label>
                    </div>
                </div>

                <!-- Notes -->
                <div>
                    <label for="notes" class="block text-xs font-[800] uppercase tracking-wider text-gray-400 mb-2">Additional Notes (Optional)</label>
                    <textarea name="notes" id="notes" rows="3" class="w-full rounded-xl border-2 border-gray-100 bg-gray-50 px-4 py-3.5 font-[600] text-gray-900 transition-all focus:border-purple-500 focus:bg-white focus:ring-0 outline-none resize-none" placeholder="Any additional notes or reminders...">{{ old('notes', $checklist->notes) }}</textarea>
                </div>
            </div>

            <!-- ACTION BUTTONS -->
            <div class="flex items-center justify-end gap-4">
                <a href="{{ route('caregiver.checklists.index') }}" class="px-6 py-3 rounded-xl font-[700] text-gray-600 bg-gray-100 hover:bg-gray-200 transition-all">
                    Cancel
                </a>
                <button type="submit" class="group flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl font-[700] shadow-lg shadow-green-200 hover:-translate-y-0.5 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Update Task
                </button>
            </div>
        </form>
    </main>

</body>
</html>
