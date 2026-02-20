<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Add Task - SilverCare</title>
    
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
                    <h2 class="text-lg font-[800] text-gray-900">Add Task</h2>
                    <p class="text-xs text-gray-500 font-medium -mt-0.5">Create a new checklist item</p>
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

        <form method="POST" action="{{ route('caregiver.checklists.store') }}">
            @csrf

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
                    <input type="text" name="task" id="task" value="{{ old('task') }}" class="w-full rounded-xl border-2 border-gray-100 bg-gray-50 px-4 py-3.5 font-[600] text-gray-900 transition-all focus:border-green-500 focus:bg-white focus:ring-0 outline-none" placeholder="e.g. Take afternoon walk, Drink water, Do stretching exercises" required>
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
                        @endphp
                        @foreach($categories as $catName => $catData)
                            <label class="relative cursor-pointer">
                                <input type="radio" name="category" value="{{ $catName }}" class="peer sr-only" {{ old('category') == $catName ? 'checked' : '' }} required>
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
                        <input type="date" name="due_date" id="due_date" value="{{ old('due_date', date('Y-m-d')) }}" min="{{ date('Y-m-d') }}" class="w-full rounded-xl border-2 border-gray-100 bg-gray-50 px-4 py-3.5 font-[600] text-gray-900 transition-all focus:border-amber-500 focus:bg-white focus:ring-0 outline-none" required>
                    </div>

                    <!-- Due Time -->
                    <div>
                        <label for="due_time" class="block text-xs font-[800] uppercase tracking-wider text-gray-400 mb-2">Due Time (Optional)</label>
                        <input type="time" name="due_time" id="due_time" value="{{ old('due_time') }}" class="w-full rounded-xl border-2 border-gray-100 bg-gray-50 px-4 py-3.5 font-[600] text-gray-900 transition-all focus:border-amber-500 focus:bg-white focus:ring-0 outline-none">
                    </div>
                </div>

                <!-- Priority -->
                <div>
                    <label class="block text-xs font-[800] uppercase tracking-wider text-gray-400 mb-3">Priority</label>
                    <div class="flex flex-wrap gap-3">
                        @php $priorities = ['Low' => 'bg-gray-100 text-gray-700', 'Medium' => 'bg-yellow-100 text-yellow-700', 'High' => 'bg-red-100 text-red-700']; @endphp
                        @foreach($priorities as $priority => $classes)
                            <label class="relative cursor-pointer">
                                <input type="radio" name="priority" value="{{ strtolower($priority) }}" class="peer sr-only" {{ old('priority', 'medium') == strtolower($priority) ? 'checked' : '' }}>
                                <div class="px-5 py-2.5 rounded-xl border-2 border-gray-200 {{ $classes }} font-[700] text-sm transition-all duration-200 peer-checked:border-green-500 peer-checked:ring-2 peer-checked:ring-green-200 hover:border-green-300">
                                    {{ $priority }}
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- CARD 3: Additional -->
            <div class="bg-white rounded-[24px] p-6 md:p-8 shadow-md border border-gray-100 mb-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-violet-600 rounded-xl flex items-center justify-center text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path></svg>
                    </div>
                    <h3 class="font-[800] text-xl text-gray-900">Notes</h3>
                </div>

                <div class="mb-4">
                    <label for="notes" class="block text-xs font-[800] uppercase tracking-wider text-gray-400 mb-2">Additional Notes (Optional)</label>
                    <textarea name="notes" id="notes" rows="3" class="w-full rounded-xl border-2 border-gray-100 bg-gray-50 px-4 py-3.5 font-[600] text-gray-900 transition-all focus:border-purple-500 focus:bg-white focus:ring-0 outline-none resize-none" placeholder="Any additional notes or reminders...">{{ old('notes') }}</textarea>
                </div>

                <!-- Quick Templates -->
                <div class="bg-green-50 p-4 rounded-2xl border border-green-100">
                    <h4 class="text-sm font-[800] text-green-800 mb-3">Quick Templates</h4>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" onclick="setTemplate('Take morning walk', 'Exercise')" class="px-3 py-1.5 bg-white text-green-700 text-xs font-[700] rounded-lg border border-green-200 hover:bg-green-100 transition-colors">🏃 Morning Walk</button>
                        <button type="button" onclick="setTemplate('Drink 8 glasses of water', 'Health')" class="px-3 py-1.5 bg-white text-green-700 text-xs font-[700] rounded-lg border border-green-200 hover:bg-green-100 transition-colors">💧 Hydration</button>
                        <button type="button" onclick="setTemplate('Do stretching exercises', 'Exercise')" class="px-3 py-1.5 bg-white text-green-700 text-xs font-[700] rounded-lg border border-green-200 hover:bg-green-100 transition-colors">🧘 Stretching</button>
                        <button type="button" onclick="setTemplate('Call family member', 'Social')" class="px-3 py-1.5 bg-white text-green-700 text-xs font-[700] rounded-lg border border-green-200 hover:bg-green-100 transition-colors">📞 Family Call</button>
                        <button type="button" onclick="setTemplate('Read for 30 minutes', 'Mental')" class="px-3 py-1.5 bg-white text-green-700 text-xs font-[700] rounded-lg border border-green-200 hover:bg-green-100 transition-colors">📖 Reading</button>
                    </div>
                </div>
            </div>

            <!-- ACTION BUTTONS -->
            <div class="flex items-center justify-end gap-4">
                <a href="{{ route('caregiver.checklists.index') }}" class="px-6 py-3 rounded-xl font-[700] text-gray-600 bg-gray-100 hover:bg-gray-200 transition-all">
                    Cancel
                </a>
                <button type="submit" class="group flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl font-[700] shadow-lg shadow-green-200 hover:-translate-y-0.5 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Add Task
                </button>
            </div>
        </form>
    </main>

    <script>
        function setTemplate(task, category) {
            document.getElementById('task').value = task;
            document.querySelector(`input[name="category"][value="${category}"]`).checked = true;
        }
    </script>

</body>
</html>
