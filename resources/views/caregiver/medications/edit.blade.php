<x-dashboard-layout>
    <x-slot:title>Edit Medication - SilverCare</x-slot:title>

    <x-dashboard-nav
        title="Edit Medication"
        subtitle="{{ $medication->name }}"
        role="caregiver"
        :show-back="true"
        back-url="{{ route('caregiver.medications.index', ['elderly' => $selectedElderly->id]) }}"
        back-label="Back"
    />

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

        <form method="POST" action="{{ route('caregiver.medications.update', $medication) }}" id="medicationForm" x-data="medicationFormManager()" x-init="initPickers()">
            @csrf
            @method('PUT')
            <input type="hidden" name="elderly_id" value="{{ $selectedElderly->id }}">

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
                    <input type="text" name="name" id="name" value="{{ old('name', $medication->name) }}" class="w-full rounded-xl border-2 border-gray-100 bg-gray-50 px-4 py-3.5 font-[600] text-gray-900 transition-all focus:border-blue-500 focus:bg-white focus:ring-0 outline-none" placeholder="e.g. Lisinopril, Metformin" required>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <!-- Dosage -->
                    <div>
                        <label for="dosage" class="block text-xs font-[800] uppercase tracking-wider text-gray-400 mb-2">Dosage <span class="text-red-500">*</span></label>
                        <div class="flex">
                            <input type="text" name="dosage" id="dosage" value="{{ old('dosage', $medication->dosage) }}" class="w-2/3 rounded-l-xl border-2 border-r-0 border-gray-100 bg-gray-50 px-4 py-3.5 font-[600] text-gray-900 transition-all focus:border-blue-500 focus:bg-white focus:ring-0 outline-none" placeholder="e.g. 10, 500" required>
                            <div class="w-1/3 relative">
                                <select name="dosage_unit" x-ref="dosageUnitSelect" class="w-full rounded-r-xl border-2 border-gray-100 bg-gray-100 px-3 py-3.5 font-[600] text-gray-700 focus:border-blue-500 focus:ring-0 outline-none">
                                    @php $unit = old('dosage_unit', $medication->dosage_unit ?? 'mg'); @endphp
                                    <option value="mg" {{ $unit == 'mg' ? 'selected' : '' }}>mg</option>
                                    <option value="ml" {{ $unit == 'ml' ? 'selected' : '' }}>ml</option>
                                    <option value="tablet" {{ $unit == 'tablet' ? 'selected' : '' }}>tablet</option>
                                    <option value="capsule" {{ $unit == 'capsule' ? 'selected' : '' }}>capsule</option>
                                    <option value="puff" {{ $unit == 'puff' ? 'selected' : '' }}>puff</option>
                                    <option value="drop" {{ $unit == 'drop' ? 'selected' : '' }}>drop</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Start Date -->
                    <div>
                        <label for="start_date" class="block text-xs font-[800] uppercase tracking-wider text-gray-400 mb-2">Start Date</label>
                        <input type="text" name="start_date" id="start_date" value="{{ old('start_date', $medication->start_date?->format('Y-m-d')) }}" class="w-full rounded-xl border-2 border-gray-100 bg-gray-50 px-4 py-3.5 font-[600] text-gray-900 transition-all focus:border-blue-500 focus:bg-white focus:ring-0 outline-none cursor-pointer placeholder-gray-400" placeholder="Select date...">
                    </div>

                    <!-- End Date -->
                    <div>
                        <label for="end_date" class="block text-xs font-[800] uppercase tracking-wider text-gray-400 mb-2">End Date</label>
                        <input type="text" name="end_date" id="end_date" value="{{ old('end_date', $medication->end_date?->format('Y-m-d')) }}" class="w-full rounded-xl border-2 border-gray-100 bg-gray-50 px-4 py-3.5 font-[600] text-gray-900 transition-all focus:border-blue-500 focus:bg-white focus:ring-0 outline-none cursor-pointer placeholder-gray-400" placeholder="Select date...">
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

                <!-- Schedule Type -->
                <div class="mb-6">
                    <label for="schedule_type" class="block text-xs font-[800] uppercase tracking-wider text-gray-400 mb-2">Schedule Type <span class="text-red-500">*</span></label>
                    @php $scheduleType = old('schedule_type', $medication->primaryScheduleType()); @endphp
                    <select name="schedule_type" id="schedule_type" x-ref="scheduleTypeSelect" class="w-full rounded-xl border-2 border-gray-100 bg-gray-50 px-4 py-3.5 font-[700] text-gray-900 transition-all focus:border-green-500 focus:bg-white focus:ring-0 outline-none">
                        <option value="daily" {{ $scheduleType === 'daily' ? 'selected' : '' }}>Daily (Every day)</option>
                        <option value="weekly" {{ $scheduleType === 'weekly' ? 'selected' : '' }}>Weekly (Selected days)</option>
                        <option value="specific_date" {{ $scheduleType === 'specific_date' ? 'selected' : '' }}>Specific date(s)</option>
                    </select>
                </div>

                <!-- Days of Week -->
                <div class="mb-6" id="weeklyScheduleSection">
                    <label class="block text-xs font-[800] uppercase tracking-wider text-gray-400 mb-3">Recurrence Days <span class="text-red-500">*</span></label>
                    <div class="flex flex-wrap gap-2">
                        @php
                            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                            $selectedDays = old('days_of_week', $medication->weeklyDays());
                        @endphp
                        @foreach($days as $day)
                            <label class="relative cursor-pointer">
                                <input type="checkbox" name="days_of_week[]" value="{{ $day }}" class="peer sr-only" {{ in_array($day, $selectedDays) ? 'checked' : '' }}>
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

                <!-- Specific Dates -->
                <div class="mb-6 hidden" id="specificDatesSection">
                    <label class="block text-xs font-[800] uppercase tracking-wider text-gray-400 mb-3">Specific Date(s) <span class="text-red-500">*</span></label>

                    <div id="specificDateContainer" data-initial-dates='@json(old('specific_dates', $medication->specificScheduleDates()))' class="flex flex-wrap gap-2 mb-4"></div>

                    <div class="flex items-center gap-3">
                        <input type="text" id="newSpecificDateInput" class="rounded-xl border-2 border-gray-100 bg-gray-50 px-4 py-3 font-[600] text-gray-900 focus:border-indigo-500 focus:bg-white focus:ring-0 outline-none cursor-pointer placeholder-gray-400" placeholder="Select date...">
                        <button type="button" onclick="addSpecificDate()" class="flex items-center gap-2 px-4 py-3 bg-gradient-to-r from-indigo-500 to-blue-600 text-white rounded-xl font-[700] shadow-md hover:-translate-y-0.5 transition-all w-max min-w-max">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Add Date
                        </button>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">Use this for one-off or limited-date medication plans.</p>
                </div>

                <!-- Time Slots -->
                <div>
                    <label class="block text-xs font-[800] uppercase tracking-wider text-gray-400 mb-3">Time Slots <span class="text-red-500">*</span></label>
                    
                    <div id="timeSlotContainer" data-initial-times='@json(old('times_of_day', $medication->times_of_day ?? []))' class="flex flex-wrap gap-2 mb-4">
                        <!-- Time slots will be added here -->
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <input type="text" id="newTimeInput" class="rounded-xl border-2 border-gray-100 bg-gray-50 px-4 py-3 font-[600] text-gray-900 focus:border-amber-500 focus:bg-white focus:ring-0 outline-none cursor-pointer placeholder-gray-400" placeholder="Select time...">
                        <button type="button" onclick="addTimeSlot()" class="flex items-center gap-2 px-4 py-3 bg-gradient-to-r from-amber-400 to-orange-500 text-white rounded-xl font-[700] shadow-md hover:-translate-y-0.5 transition-all w-max min-w-max">
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
                    <textarea name="instructions" id="instructions" rows="3" class="w-full rounded-xl border-2 border-gray-100 bg-gray-50 px-4 py-3.5 font-[600] text-gray-900 transition-all focus:border-purple-500 focus:bg-white focus:ring-0 outline-none resize-none" placeholder="e.g. Take with food, do not crush, avoid grapefruit...">{{ old('instructions', $medication->instructions) }}</textarea>
                </div>

                <!-- Inventory Tracking -->
                <div class="bg-gray-50 p-5 rounded-2xl border border-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-sm font-[800] text-gray-700">Inventory Tracking</h4>
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="track_inventory" id="track_inventory" value="1" class="rounded border-gray-300 text-purple-600 shadow-sm focus:ring-purple-500" {{ old('track_inventory', $medication->track_inventory) ? 'checked' : '' }}>
                            <span class="ml-2 text-sm font-[600] text-gray-600">Enable</span>
                        </label>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="current_stock" class="block text-xs font-[700] text-gray-500 mb-1">Current Stock</label>
                            <input type="number" name="current_stock" id="current_stock" value="{{ old('current_stock', $medication->current_stock) }}" min="0" class="w-full rounded-xl border-2 border-gray-200 bg-white px-3 py-2 text-sm font-[600] focus:border-purple-500 focus:ring-0 outline-none" placeholder="e.g. 30">
                        </div>
                        <div>
                            <label for="low_stock_threshold" class="block text-xs font-[700] text-gray-500 mb-1">Low Stock Alert</label>
                            <input type="number" name="low_stock_threshold" id="low_stock_threshold" value="{{ old('low_stock_threshold', $medication->low_stock_threshold ?? 5) }}" min="0" class="w-full rounded-xl border-2 border-gray-200 bg-white px-3 py-2 text-sm font-[600] focus:border-purple-500 focus:ring-0 outline-none">
                        </div>
                    </div>
                </div>
            </div>

            <!-- ACTION BUTTONS -->
            <div class="flex items-center justify-end gap-4">
                <a href="{{ route('caregiver.medications.index', ['elderly' => $selectedElderly->id]) }}" class="px-6 py-3 rounded-xl font-[700] text-gray-600 bg-gray-100 hover:bg-gray-200 transition-all">
                    Cancel
                </a>
                <button type="submit" class="group flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-xl font-[700] shadow-lg shadow-blue-200 hover:-translate-y-0.5 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Update Medication
                </button>
            </div>
        </form>
    </main>

    <script>
        function medicationFormManager() {
            return {
                initialized: false,

                initPickers() {
                    if (this.initialized) {
                        return;
                    }

                    if (typeof window.flatpickr === 'function') {
                        window.flatpickr('#start_date, #end_date, #newSpecificDateInput', {
                            dateFormat: 'Y-m-d',
                            altInput: true,
                            altFormat: 'F j, Y',
                            allowInput: true,
                            disableMobile: true,
                        });

                        window.flatpickr('#newTimeInput', {
                            enableTime: true,
                            noCalendar: true,
                            dateFormat: 'H:i',
                            altInput: true,
                            altFormat: 'h:i K',
                            allowInput: true,
                            disableMobile: true,
                            minuteIncrement: 5,
                        });
                    }

                    if (typeof window.TomSelect === 'function') {
                        if (this.$refs.dosageUnitSelect) {
                            new window.TomSelect(this.$refs.dosageUnitSelect, {
                                create: false,
                                controlInput: null,
                            });
                        }

                        if (this.$refs.scheduleTypeSelect) {
                            new window.TomSelect(this.$refs.scheduleTypeSelect, {
                                create: false,
                                controlInput: null,
                                onChange: function(value) {
                                    if (typeof toggleScheduleSections === 'function') {
                                        toggleScheduleSections();
                                    }
                                }
                            });
                        }
                    }

                    this.initialized = true;
                },
            };
        }

        const timeSlotContainer = document.getElementById('timeSlotContainer');
        const specificDateContainer = document.getElementById('specificDateContainer');

        let timeSlots = JSON.parse(timeSlotContainer.dataset.initialTimes || '[]');
        let specificDates = JSON.parse(specificDateContainer.dataset.initialDates || '[]');

        function showMedicationFormAlert(message) {
            window.scAlert({
                title: 'Please review the form',
                text: message,
                icon: 'warning',
                confirmButtonText: 'Got it',
            });
        }

        function addTimeSlot() {
            const input = document.getElementById('newTimeInput');
            const time = input.value;
            
            if (!time) {
                showMedicationFormAlert('Please select a time first');
                return;
            }
            
            if (timeSlots.includes(time)) {
                showMedicationFormAlert('This time slot already exists');
                return;
            }
            
            timeSlots.push(time);
            timeSlots.sort();
            renderTimeSlots();
            
            if (input._flatpickr) {
                input._flatpickr.clear();
            } else {
                input.value = '';
            }
        }

        function removeTimeSlot(time) {
            timeSlots = timeSlots.filter(t => t !== time);
            renderTimeSlots();
        }

        function addSpecificDate() {
            const input = document.getElementById('newSpecificDateInput');
            const value = input.value;

            if (!value) {
                showMedicationFormAlert('Please select a date first');
                return;
            }

            if (specificDates.includes(value)) {
                showMedicationFormAlert('This date already exists');
                return;
            }

            specificDates.push(value);
            specificDates.sort();
            renderSpecificDates();

            const specificInput = document.getElementById('newSpecificDateInput');
            if (specificInput._flatpickr) {
                specificInput._flatpickr.clear();
            } else {
                specificInput.value = '';
            }
        }

        function removeSpecificDate(dateValue) {
            specificDates = specificDates.filter(d => d !== dateValue);
            renderSpecificDates();
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

        function renderSpecificDates() {
            const container = document.getElementById('specificDateContainer');
            container.innerHTML = '';

            if (specificDates.length === 0) {
                container.innerHTML = '<p class="text-sm text-gray-400 italic">No specific dates added yet</p>';
                return;
            }

            specificDates.forEach(dateValue => {
                const div = document.createElement('div');
                div.className = 'inline-flex items-center bg-gradient-to-r from-indigo-50 to-blue-50 text-indigo-700 px-4 py-2.5 rounded-xl border border-indigo-100';
                div.innerHTML = `
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <span class="font-[700]">${new Date(dateValue + 'T00:00:00').toLocaleDateString()}</span>
                    <input type="hidden" name="specific_dates[]" value="${dateValue}">
                    <button type="button" onclick="removeSpecificDate('${dateValue}')" class="ml-3 text-indigo-400 hover:text-red-500 transition-colors">
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

        function toggleScheduleSections() {
            const type = document.getElementById('schedule_type').value;
            const weekly = document.getElementById('weeklyScheduleSection');
            const specific = document.getElementById('specificDatesSection');

            weekly.classList.toggle('hidden', type !== 'weekly');
            specific.classList.toggle('hidden', type !== 'specific_date');
        }

        document.getElementById('schedule_type').addEventListener('change', toggleScheduleSections);

        document.getElementById('medicationForm').addEventListener('submit', function(e) {
            const scheduleType = document.getElementById('schedule_type').value;

            if (scheduleType === 'weekly') {
                const checkedDays = document.querySelectorAll('input[name="days_of_week[]"]:checked');
                if (checkedDays.length === 0) {
                    e.preventDefault();
                    showMedicationFormAlert('Please select at least one day for weekly schedule');
                    return;
                }
            }

            if (scheduleType === 'specific_date') {
                const specificDateVal = document.getElementById('newSpecificDateInput').value;
                if (specificDateVal) { addSpecificDate(); }
                
                if (specificDates.length === 0) {
                    e.preventDefault();
                    showMedicationFormAlert('Please add at least one specific date');
                    return;
                }
            }
            
            const timeVal = document.getElementById('newTimeInput').value;
            if (timeVal) { addTimeSlot(); }

            if (timeSlots.length === 0) {
                e.preventDefault();
                showMedicationFormAlert('Please add at least one time slot');
                return;
            }
        });

        document.getElementById('newTimeInput').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addTimeSlot();
            }
        });

        document.getElementById('newSpecificDateInput').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addSpecificDate();
            }
        });

        renderTimeSlots();
        renderSpecificDates();
        toggleScheduleSections();

    </script>

</x-dashboard-layout>
