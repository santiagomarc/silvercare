<x-dashboard-layout sc>
    <x-slot:title>Add Medication - SilverCare</x-slot:title>

    <x-dashboard-nav
        title="Add Medication"
        subtitle="{{ $selectedElderly->user->name }}'s Plan"
        role="caregiver"
        :show-back="true"
        back-url="{{ route('caregiver.medications.index', ['elderly' => $selectedElderly->id]) }}"
        back-label="Back"
    />

    <!-- MAIN CONTENT -->
    <main id="main-content" class="sc-app-main">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-12 py-6 space-y-6">

            <x-flash-messages />

            @if ($errors->any())
                <div class="sc-error-summary" role="alert">
                    <p class="font-semibold mb-2">Please correct the following errors:</p>
                    <ul class="list-disc list-inside space-y-1 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(($elderlyPatients ?? collect())->count() > 1)
                <div class="sc-card-quiet p-4 border border-[var(--sc-line)] flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-[var(--sc-ink)]">Creating medication for {{ $selectedElderly->user?->name ?? 'selected patient' }}</p>
                        <p class="text-xs text-[var(--sc-ink-muted)]">Switch patient from the medications list if needed.</p>
                    </div>
                    <a href="{{ route('caregiver.medications.index') }}" class="sc-btn sc-btn-ghost text-xs font-semibold">Change</a>
                </div>
            @endif

            <form method="POST" action="{{ route('caregiver.medications.store') }}" id="medicationForm" class="space-y-6">
                @csrf
                <input type="hidden" name="elderly_id" value="{{ $selectedElderly->id }}">

                <!-- CARD 1: Basic Info -->
                <div class="sc-card p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-9 h-9 rounded-lg bg-[var(--sc-canvas)] border border-[var(--sc-line)] flex items-center justify-center flex-shrink-0">
                            <x-lucide-pill class="sc-i w-4 h-4 text-[var(--sc-ink)]" aria-hidden="true" />
                        </div>
                        <div>
                            <h2 class="text-lg font-serif font-bold text-[var(--sc-ink)]">Medication Details</h2>
                            <p class="text-xs text-[var(--sc-ink-muted)]">Name, dosage, and date range</p>
                        </div>
                    </div>

                    <!-- Medication Name -->
                    <div class="sc-field">
                        <label for="name" class="sc-label sc-label-req">Medication Name</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" class="sc-input" placeholder="e.g. Lisinopril, Metformin" required>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <!-- Dosage -->
                        <div class="sc-field mb-0">
                            <label for="dosage" class="sc-label sc-label-req">Dosage</label>
                            <div class="flex gap-2">
                                <input type="text" name="dosage" id="dosage" value="{{ old('dosage') }}" class="sc-input flex-1 min-w-0" placeholder="e.g. 10, 500" required>
                                <div class="w-28 flex-shrink-0">
                                    <select name="dosage_unit" id="dosage_unit" class="sc-select w-full" aria-label="Dosage Unit">
                                        <option value="mg" {{ old('dosage_unit') == 'mg' ? 'selected' : '' }}>mg</option>
                                        <option value="ml" {{ old('dosage_unit') == 'ml' ? 'selected' : '' }}>ml</option>
                                        <option value="tablet" {{ old('dosage_unit') == 'tablet' ? 'selected' : '' }}>tablet</option>
                                        <option value="capsule" {{ old('dosage_unit') == 'capsule' ? 'selected' : '' }}>capsule</option>
                                        <option value="puff" {{ old('dosage_unit') == 'puff' ? 'selected' : '' }}>puff</option>
                                        <option value="drop" {{ old('dosage_unit') == 'drop' ? 'selected' : '' }}>drop</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Start Date -->
                        <div class="sc-field mb-0">
                            <label for="start_date" class="sc-label">Start Date</label>
                            <input type="text" name="start_date" id="start_date" value="{{ old('start_date', date('Y-m-d')) }}" class="sc-input cursor-pointer" placeholder="Select date..." aria-label="Start Date">
                        </div>

                        <!-- End Date -->
                        <div class="sc-field mb-0">
                            <label for="end_date" class="sc-label">End Date <span class="sc-label-opt">(Optional)</span></label>
                            <input type="text" name="end_date" id="end_date" value="{{ old('end_date') }}" class="sc-input cursor-pointer" placeholder="Select date..." aria-label="End Date">
                        </div>
                    </div>
                </div>

                <!-- CARD 2: Schedule -->
                <div class="sc-card p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-9 h-9 rounded-lg bg-[var(--sc-canvas)] border border-[var(--sc-line)] flex items-center justify-center flex-shrink-0">
                            <x-lucide-calendar class="sc-i w-4 h-4 text-[var(--sc-ink)]" aria-hidden="true" />
                        </div>
                        <div>
                            <h2 class="text-lg font-serif font-bold text-[var(--sc-ink)]">Schedule</h2>
                            <p class="text-xs text-[var(--sc-ink-muted)]">When and how often doses are taken</p>
                        </div>
                    </div>

                    <!-- Schedule Type -->
                    <div class="sc-field">
                        <label for="schedule_type" class="sc-label sc-label-req">Schedule Type</label>
                        @php $scheduleType = old('schedule_type', 'daily'); @endphp
                        <select name="schedule_type" id="schedule_type" class="sc-select w-full">
                            <option value="daily" {{ $scheduleType === 'daily' ? 'selected' : '' }}>Daily (Every day)</option>
                            <option value="weekly" {{ $scheduleType === 'weekly' ? 'selected' : '' }}>Weekly (Selected days)</option>
                            <option value="specific_date" {{ $scheduleType === 'specific_date' ? 'selected' : '' }}>Specific date(s)</option>
                        </select>
                    </div>

                    <!-- Days of Week -->
                    <div class="sc-field {{ $scheduleType === 'weekly' ? '' : 'hidden' }}" id="weeklyScheduleSection">
                        <span class="sc-label sc-label-req">Recurrence Days</span>
                        <div class="flex flex-wrap gap-2 pt-1">
                            @php
                                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                $oldDays = old('days_of_week', []);
                            @endphp
                            @foreach($days as $day)
                                <label class="cursor-pointer">
                                    <input type="checkbox" name="days_of_week[]" value="{{ $day }}" class="peer sr-only" {{ in_array($day, $oldDays) ? 'checked' : '' }}>
                                    <span class="inline-flex items-center justify-center min-w-touch min-h-touch px-3.5 py-2 rounded-[var(--sc-radius)] text-sm font-semibold border transition-colors peer-checked:bg-[var(--sc-ink)] peer-checked:text-[var(--sc-paper)] peer-checked:border-[var(--sc-ink)] bg-[var(--sc-canvas)] text-[var(--sc-ink-muted)] border-[var(--sc-line)] hover:border-[var(--sc-ink)]">
                                        {{ substr($day, 0, 3) }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <div class="mt-3 flex items-center gap-3">
                            <button type="button" onclick="selectAllDays()" class="text-xs font-semibold text-[var(--sc-ink)] hover:underline min-h-touch flex items-center">Select All</button>
                            <span class="text-[var(--sc-line)]" aria-hidden="true">|</span>
                            <button type="button" onclick="selectWeekdays()" class="text-xs font-semibold text-[var(--sc-ink)] hover:underline min-h-touch flex items-center">Weekdays</button>
                            <span class="text-[var(--sc-line)]" aria-hidden="true">|</span>
                            <button type="button" onclick="clearDays()" class="text-xs font-semibold text-[var(--sc-ink-muted)] hover:underline min-h-touch flex items-center">Clear</button>
                        </div>
                    </div>

                    <!-- Specific Dates -->
                    <div class="sc-field {{ $scheduleType === 'specific_date' ? '' : 'hidden' }}" id="specificDatesSection">
                        <span class="sc-label sc-label-req">Specific Date(s)</span>
                        <div id="specificDateContainer" data-initial-dates='@json(old('specific_dates', []))' class="flex flex-wrap gap-2 mb-3"></div>
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                            <input type="text" id="newSpecificDateInput" class="sc-input flex-1 min-w-0 cursor-pointer" placeholder="Select date..." aria-label="Specific Date">
                            <button type="button" onclick="addSpecificDate()" class="sc-btn sc-btn-primary min-h-touch flex items-center justify-center gap-2 flex-shrink-0">
                                <x-lucide-plus class="sc-i w-4 h-4" aria-hidden="true" />
                                <span>Add Date</span>
                            </button>
                        </div>
                        <p class="sc-help text-xs text-[var(--sc-ink-muted)]">Use this for one-off or limited-date medication plans.</p>
                    </div>

                    <!-- Time Slots -->
                    <div class="sc-field mb-0">
                        <span class="sc-label sc-label-req">Time Slots</span>
                        <div id="timeSlotContainer" data-initial-times='@json(old('times_of_day', []))' class="flex flex-wrap gap-2 mb-3"></div>
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                            <input type="text" id="newTimeInput" class="sc-input flex-1 min-w-0 cursor-pointer" placeholder="Select time..." aria-label="Time Slot">
                            <button type="button" onclick="addTimeSlot()" class="sc-btn sc-btn-primary min-h-touch flex items-center justify-center gap-2 flex-shrink-0">
                                <x-lucide-plus class="sc-i w-4 h-4" aria-hidden="true" />
                                <span>Add Time</span>
                            </button>
                        </div>
                        <p class="sc-help text-xs text-[var(--sc-ink-muted)]">Examples: 08:00 (morning), 14:00 (afternoon), 21:00 (night)</p>
                    </div>
                </div>

                <!-- CARD 3: Instructions & Inventory -->
                <div class="sc-card p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-9 h-9 rounded-lg bg-[var(--sc-canvas)] border border-[var(--sc-line)] flex items-center justify-center flex-shrink-0">
                            <x-lucide-file-text class="sc-i w-4 h-4 text-[var(--sc-ink)]" aria-hidden="true" />
                        </div>
                        <div>
                            <h2 class="text-lg font-serif font-bold text-[var(--sc-ink)]">Additional Info</h2>
                            <p class="text-xs text-[var(--sc-ink-muted)]">Instructions and stock tracking</p>
                        </div>
                    </div>

                    <!-- Instructions -->
                    <div class="sc-field">
                        <label for="instructions" class="sc-label">Instructions <span class="sc-label-opt">(Optional)</span></label>
                        <textarea name="instructions" id="instructions" rows="3" class="sc-textarea" placeholder="e.g. Take with food, do not crush, avoid grapefruit...">{{ old('instructions') }}</textarea>
                    </div>

                    <!-- Inventory Tracking -->
                    <div class="sc-card-quiet p-5 rounded-[var(--sc-radius)] border border-[var(--sc-line)]">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-sm font-semibold text-[var(--sc-ink)]">Inventory Tracking</h3>
                                <p class="text-xs text-[var(--sc-ink-muted)]">Track pills remaining and get low-stock reminders</p>
                            </div>
                            <label class="sc-check m-0 p-0 min-h-touch flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="track_inventory" id="track_inventory" value="1" {{ old('track_inventory') ? 'checked' : '' }}>
                                <span class="text-sm font-semibold text-[var(--sc-ink)]">Enable</span>
                            </label>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sc-field mb-0">
                                <label for="current_stock" class="sc-label text-xs">Current Stock</label>
                                <input type="number" name="current_stock" id="current_stock" value="{{ old('current_stock') }}" min="0" class="sc-input text-sm" placeholder="e.g. 30">
                            </div>
                            <div class="sc-field mb-0">
                                <label for="low_stock_threshold" class="sc-label text-xs">Low Stock Alert</label>
                                <input type="number" name="low_stock_threshold" id="low_stock_threshold" value="{{ old('low_stock_threshold', 5) }}" min="0" class="sc-input text-sm">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ACTION BUTTONS -->
                <div class="flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-end gap-3 pt-2">
                    <a href="{{ route('caregiver.medications.index', ['elderly' => $selectedElderly->id]) }}" class="sc-btn sc-btn-ghost min-h-touch justify-center">
                        Cancel
                    </a>
                    <button type="submit" class="sc-btn sc-btn-primary min-h-touch justify-center gap-2">
                        <x-lucide-check class="sc-i w-4 h-4" aria-hidden="true" />
                        <span>Save Medication</span>
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        const timeSlotContainer = document.getElementById('timeSlotContainer');
        const specificDateContainer = document.getElementById('specificDateContainer');

        let timeSlots = JSON.parse(timeSlotContainer.dataset.initialTimes || '[]');
        let specificDates = JSON.parse(specificDateContainer.dataset.initialDates || '[]');

        function showMedicationFormAlert(message) {
            if (window.Swal) {
                window.Swal.fire({
                    title: 'Review form',
                    text: message,
                    icon: 'warning',
                    confirmButtonText: 'Got it',
                    confirmButtonColor: '#1E293B',
                    customClass: {
                        popup: 'sc-card !p-6',
                        title: 'text-lg font-serif font-bold text-[var(--sc-ink)]',
                        htmlContainer: 'text-sm text-[var(--sc-ink-muted)]'
                    }
                });
            } else {
                alert(message);
            }
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
                container.innerHTML = '<p class="text-sm text-[var(--sc-ink-muted)] italic">No time slots added yet</p>';
                return;
            }
            
            timeSlots.forEach(time => {
                const div = document.createElement('div');
                div.className = 'inline-flex items-center gap-2 px-3 py-1.5 rounded-[var(--sc-radius)] bg-[var(--sc-canvas)] border border-[var(--sc-line)] text-sm text-[var(--sc-ink)] font-medium';
                div.innerHTML = `
                    <svg class="w-4 h-4 text-[var(--sc-ink-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <span class="font-semibold">${formatTime(time)}</span>
                    <input type="hidden" name="times_of_day[]" value="${time}">
                    <button type="button" onclick="removeTimeSlot('${time}')" class="min-h-touch min-w-touch inline-flex items-center justify-center p-1 text-[var(--sc-ink-muted)] hover:text-[var(--sc-alert)] transition-colors" aria-label="Remove time ${formatTime(time)}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                `;
                container.appendChild(div);
            });
        }

        function renderSpecificDates() {
            const container = document.getElementById('specificDateContainer');
            container.innerHTML = '';

            if (specificDates.length === 0) {
                container.innerHTML = '<p class="text-sm text-[var(--sc-ink-muted)] italic">No specific dates added yet</p>';
                return;
            }

            specificDates.forEach(dateValue => {
                const formatted = new Date(dateValue + 'T00:00:00').toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
                const div = document.createElement('div');
                div.className = 'inline-flex items-center gap-2 px-3 py-1.5 rounded-[var(--sc-radius)] bg-[var(--sc-canvas)] border border-[var(--sc-line)] text-sm text-[var(--sc-ink)] font-medium';
                div.innerHTML = `
                    <svg class="w-4 h-4 text-[var(--sc-ink-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <span class="font-semibold">${formatted}</span>
                    <input type="hidden" name="specific_dates[]" value="${dateValue}">
                    <button type="button" onclick="removeSpecificDate('${dateValue}')" class="min-h-touch min-w-touch inline-flex items-center justify-center p-1 text-[var(--sc-ink-muted)] hover:text-[var(--sc-alert)] transition-colors" aria-label="Remove date ${formatted}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
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

        document.addEventListener('DOMContentLoaded', function() {
            if (typeof window.flatpickr === 'function') {
                window.flatpickr('#start_date', {
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'F j, Y',
                    allowInput: true,
                    onReady: function(d, s, instance) {
                        if (instance.altInput) instance.altInput.setAttribute('aria-label', 'Start Date');
                    }
                });
                window.flatpickr('#end_date', {
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'F j, Y',
                    allowInput: true,
                    onReady: function(d, s, instance) {
                        if (instance.altInput) instance.altInput.setAttribute('aria-label', 'End Date');
                    }
                });
                window.flatpickr('#newSpecificDateInput', {
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'F j, Y',
                    allowInput: true,
                    onReady: function(d, s, instance) {
                        if (instance.altInput) instance.altInput.setAttribute('aria-label', 'Specific Date');
                    }
                });
                window.flatpickr('#newTimeInput', {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: 'H:i',
                    altInput: true,
                    altFormat: 'h:i K',
                    allowInput: true,
                    minuteIncrement: 5,
                    onReady: function(d, s, instance) {
                        if (instance.altInput) instance.altInput.setAttribute('aria-label', 'Time Slot');
                    }
                });
            }

            renderTimeSlots();
            renderSpecificDates();
            toggleScheduleSections();
        });
    </script>
</x-dashboard-layout>
