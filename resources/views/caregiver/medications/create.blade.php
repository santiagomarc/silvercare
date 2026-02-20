<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Add Medication - SilverCare</title>
    
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
                    <h2 class="text-lg font-[800] text-gray-900">Add Medication</h2>
                    <p class="text-xs text-gray-500 font-medium -mt-0.5">Create a new schedule</p>
                </div>
            </div>
            
            <div class="flex items-center gap-4">
                <a href="{{ route('caregiver.medications.index') }}" class="flex items-center gap-1.5 px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-bold text-sm transition-colors">
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

        <form method="POST" action="{{ route('caregiver.medications.store') }}" id="medicationForm">
            @csrf

            <!-- CARD 1: Basic Info -->
            <div class="bg-white rounded-[24px] p-6 md:p-8 shadow-md border border-gray-100 mb-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                    </div>
                    <h3 class="font-[800] text-xl text-gray-900">Medication Details</h3>
                </div>

                <!-- Medication Name -->
                <div class="mb-5">
                    <label for="name" class="block text-xs font-[800] uppercase tracking-wider text-gray-400 mb-2">Medication Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" class="w-full rounded-xl border-2 border-gray-100 bg-gray-50 px-4 py-3.5 font-[600] text-gray-900 transition-all focus:border-blue-500 focus:bg-white focus:ring-0 outline-none" placeholder="e.g. Lisinopril, Metformin" required>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- Dosage -->
                    <div>
                        <label for="dosage" class="block text-xs font-[800] uppercase tracking-wider text-gray-400 mb-2">Dosage <span class="text-red-500">*</span></label>
                        <div class="flex">
                            <input type="text" name="dosage" id="dosage" value="{{ old('dosage') }}" class="w-2/3 rounded-l-xl border-2 border-r-0 border-gray-100 bg-gray-50 px-4 py-3.5 font-[600] text-gray-900 transition-all focus:border-blue-500 focus:bg-white focus:ring-0 outline-none" placeholder="e.g. 10, 500" required>
                            <select name="dosage_unit" class="w-1/3 rounded-r-xl border-2 border-gray-100 bg-gray-100 px-3 py-3.5 font-[600] text-gray-700 focus:border-blue-500 focus:ring-0 outline-none">
                                <option value="mg" {{ old('dosage_unit') == 'mg' ? 'selected' : '' }}>mg</option>
                                <option value="ml" {{ old('dosage_unit') == 'ml' ? 'selected' : '' }}>ml</option>
                                <option value="tablet" {{ old('dosage_unit') == 'tablet' ? 'selected' : '' }}>tablet</option>
                                <option value="capsule" {{ old('dosage_unit') == 'capsule' ? 'selected' : '' }}>capsule</option>
                                <option value="puff" {{ old('dosage_unit') == 'puff' ? 'selected' : '' }}>puff</option>
                                <option value="drop" {{ old('dosage_unit') == 'drop' ? 'selected' : '' }}>drop</option>
                            </select>
                        </div>
                    </div>

                    <!-- Start Date -->
                    <div>
                        <label for="start_date" class="block text-xs font-[800] uppercase tracking-wider text-gray-400 mb-2">Start Date</label>
                        <input type="date" name="start_date" id="start_date" value="{{ old('start_date', date('Y-m-d')) }}" class="w-full rounded-xl border-2 border-gray-100 bg-gray-50 px-4 py-3.5 font-[600] text-gray-900 transition-all focus:border-blue-500 focus:bg-white focus:ring-0 outline-none">
                    </div>
                </div>
            </div>

            <!-- CARD 2: Schedule -->
            <div class="bg-white rounded-[24px] p-6 md:p-8 shadow-md border border-gray-100 mb-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <h3 class="font-[800] text-xl text-gray-900">Schedule</h3>
                </div>

                <!-- Days of Week -->
                <div class="mb-6">
                    <label class="block text-xs font-[800] uppercase tracking-wider text-gray-400 mb-3">Recurrence Days <span class="text-red-500">*</span></label>
                    <div class="flex flex-wrap gap-2">
                        @php
                            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                            $oldDays = old('days_of_week', []);
                        @endphp
                        @foreach($days as $day)
                            <label class="relative cursor-pointer">
                                <input type="checkbox" name="days_of_week[]" value="{{ $day }}" class="peer sr-only" {{ in_array($day, $oldDays) ? 'checked' : '' }}>
                                <div class="px-4 py-2.5 rounded-xl border-2 border-gray-200 bg-white text-gray-600 font-[700] text-sm transition-all duration-200 peer-checked:border-green-500 peer-checked:bg-green-500 peer-checked:text-white hover:border-green-300 hover:bg-green-50">
                                    {{ substr($day, 0, 3) }}
                                </div>
                            </label>
                        @endforeach
                    </div>
                    <div class="mt-3 flex gap-3">
                        <button type="button" onclick="selectAllDays()" class="text-xs font-[700] text-green-600 hover:underline">Select All</button>
                        <span class="text-gray-300">|</span>
                        <button type="button" onclick="selectWeekdays()" class="text-xs font-[700] text-green-600 hover:underline">Weekdays</button>
                        <span class="text-gray-300">|</span>
                        <button type="button" onclick="clearDays()" class="text-xs font-[700] text-gray-500 hover:underline">Clear</button>
                    </div>
                </div>

                <!-- Time Slots -->
                <div>
                    <label class="block text-xs font-[800] uppercase tracking-wider text-gray-400 mb-3">Time Slots <span class="text-red-500">*</span></label>
                    
                    <div id="timeSlotContainer" class="flex flex-wrap gap-2 mb-4">
                        <!-- Time slots will be added here -->
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <input type="time" id="newTimeInput" class="rounded-xl border-2 border-gray-100 bg-gray-50 px-4 py-3 font-[600] text-gray-900 focus:border-amber-500 focus:bg-white focus:ring-0 outline-none">
                        <button type="button" onclick="addTimeSlot()" class="flex items-center gap-2 px-4 py-3 bg-gradient-to-r from-amber-400 to-orange-500 text-white rounded-xl font-[700] shadow-md hover:-translate-y-0.5 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Add Time
                        </button>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">Examples: 08:00 (morning), 14:00 (afternoon), 21:00 (night)</p>
                </div>
            </div>

            <!-- CARD 3: Instructions & Inventory -->
            <div class="bg-white rounded-[24px] p-6 md:p-8 shadow-md border border-gray-100 mb-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-violet-600 rounded-xl flex items-center justify-center text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <h3 class="font-[800] text-xl text-gray-900">Additional Info</h3>
                </div>

                <!-- Instructions -->
                <div class="mb-6">
                    <label for="instructions" class="block text-xs font-[800] uppercase tracking-wider text-gray-400 mb-2">Instructions (Optional)</label>
                    <textarea name="instructions" id="instructions" rows="3" class="w-full rounded-xl border-2 border-gray-100 bg-gray-50 px-4 py-3.5 font-[600] text-gray-900 transition-all focus:border-purple-500 focus:bg-white focus:ring-0 outline-none resize-none" placeholder="e.g. Take with food, do not crush, avoid grapefruit...">{{ old('instructions') }}</textarea>
                </div>

                <!-- Inventory Tracking -->
                <div class="bg-gray-50 p-5 rounded-2xl border border-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-sm font-[800] text-gray-700">Inventory Tracking</h4>
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="track_inventory" id="track_inventory" value="1" class="rounded border-gray-300 text-purple-600 shadow-sm focus:ring-purple-500" {{ old('track_inventory') ? 'checked' : '' }}>
                            <span class="ml-2 text-sm font-[600] text-gray-600">Enable</span>
                        </label>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="current_stock" class="block text-xs font-[700] text-gray-500 mb-1">Current Stock</label>
                            <input type="number" name="current_stock" id="current_stock" value="{{ old('current_stock') }}" min="0" class="w-full rounded-xl border-2 border-gray-200 bg-white px-3 py-2 text-sm font-[600] focus:border-purple-500 focus:ring-0 outline-none" placeholder="e.g. 30">
                        </div>
                        <div>
                            <label for="low_stock_threshold" class="block text-xs font-[700] text-gray-500 mb-1">Low Stock Alert</label>
                            <input type="number" name="low_stock_threshold" id="low_stock_threshold" value="{{ old('low_stock_threshold', 5) }}" min="0" class="w-full rounded-xl border-2 border-gray-200 bg-white px-3 py-2 text-sm font-[600] focus:border-purple-500 focus:ring-0 outline-none">
                        </div>
                    </div>
                </div>
            </div>

            <!-- ACTION BUTTONS -->
            <div class="flex items-center justify-end gap-4">
                <a href="{{ route('caregiver.medications.index') }}" class="px-6 py-3 rounded-xl font-[700] text-gray-600 bg-gray-100 hover:bg-gray-200 transition-all">
                    Cancel
                </a>
                <button type="submit" class="group flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-xl font-[700] shadow-lg shadow-blue-200 hover:-translate-y-0.5 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Save Medication
                </button>
            </div>
        </form>
    </main>

    <script>
        let timeSlots = [];

        @if(old('times_of_day'))
            timeSlots = @json(old('times_of_day'));
            renderTimeSlots();
        @endif

        function addTimeSlot() {
            const input = document.getElementById('newTimeInput');
            const time = input.value;
            
            if (!time) {
                alert('Please select a time first');
                return;
            }
            
            if (timeSlots.includes(time)) {
                alert('This time slot already exists');
                return;
            }
            
            timeSlots.push(time);
            timeSlots.sort();
            renderTimeSlots();
            input.value = '';
        }

        function removeTimeSlot(time) {
            timeSlots = timeSlots.filter(t => t !== time);
            renderTimeSlots();
        }

        function renderTimeSlots() {
            const container = document.getElementById('timeSlotContainer');
            container.innerHTML = '';
            
            if (timeSlots.length === 0) {
                container.innerHTML = '<p class="text-sm text-gray-400 italic">No time slots added yet</p>';
                return;
            }
            
            timeSlots.forEach(time => {
                const div = document.createElement('div');
                div.className = 'inline-flex items-center bg-gradient-to-r from-amber-50 to-orange-50 text-amber-700 px-4 py-2.5 rounded-xl border border-amber-100';
                div.innerHTML = `
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span class="font-[700]">${formatTime(time)}</span>
                    <input type="hidden" name="times_of_day[]" value="${time}">
                    <button type="button" onclick="removeTimeSlot('${time}')" class="ml-3 text-amber-400 hover:text-red-500 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                `;
                container.appendChild(div);
            });
        }

        function formatTime(time24) {
            const [hours, minutes] = time24.split(':');
            const h = parseInt(hours);
            const ampm = h >= 12 ? 'PM' : 'AM';
            const h12 = h % 12 || 12;
            return `${h12}:${minutes} ${ampm}`;
        }

        function selectAllDays() {
            document.querySelectorAll('input[name="days_of_week[]"]').forEach(cb => cb.checked = true);
        }

        function selectWeekdays() {
            const weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
            document.querySelectorAll('input[name="days_of_week[]"]').forEach(cb => {
                cb.checked = weekdays.includes(cb.value);
            });
        }

        function clearDays() {
            document.querySelectorAll('input[name="days_of_week[]"]').forEach(cb => cb.checked = false);
        }

        document.getElementById('medicationForm').addEventListener('submit', function(e) {
            const checkedDays = document.querySelectorAll('input[name="days_of_week[]"]:checked');
            if (checkedDays.length === 0) {
                e.preventDefault();
                alert('Please select at least one day for the medication schedule');
                return;
            }
            
            if (timeSlots.length === 0) {
                e.preventDefault();
                alert('Please add at least one time slot');
                return;
            }
        });

        renderTimeSlots();
    </script>

</body>
</html>
