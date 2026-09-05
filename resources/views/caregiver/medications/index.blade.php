<x-dashboard-layout sc>
    <x-slot:title>Manage Medications - SilverCare</x-slot:title>

    <x-dashboard-nav
        title="Manage Medications"
        subtitle="{{ now()->format('l, F j, Y') }}"
        role="caregiver"
        :show-back="true"
    />

    <!-- MAIN CONTENT -->
    <main id="main-content" class="sc-app-main">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-12 space-y-6">

            @if(session('success'))
                <div class="sc-flash sc-flash-ok" role="status">
                    {{ session('success') }}
                </div>
            @endif

            @if(($elderlyPatients ?? collect())->count() > 1)
                <div class="sc-card-quiet p-4">
                    <form method="GET" action="{{ route('caregiver.medications.index') }}" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <label for="elderly" class="sc-label !mb-0 text-sm font-semibold">Managing medications for</label>
                        <select
                            id="elderly"
                            name="elderly"
                            onchange="this.form.submit()"
                            class="sc-select text-sm font-semibold w-full sm:w-auto"
                        >
                            @foreach(($elderlyPatients ?? collect()) as $patient)
                                <option value="{{ $patient->id }}" @selected($selectedElderly && $selectedElderly->id === $patient->id)>
                                    {{ $patient->user?->name ?? ('Patient #' . $patient->id) }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>
            @endif

            <!-- HEADER WITH ADD BUTTON -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 class="sc-h3">Active Prescriptions</h2>
                    <p class="text-sm mt-0.5" style="color:var(--sc-muted)">Managing {{ $selectedElderly->user?->name ?? 'selected patient' }}</p>
                </div>
                <a href="{{ route('caregiver.medications.create', ['elderly' => $selectedElderly->id]) }}"
                   class="sc-btn sc-btn-primary w-full sm:w-auto">
                    <x-lucide-plus class="sc-i w-4 h-4" aria-hidden="true" />
                    <span>Add Medication</span>
                </a>
            </div>

            <!-- MEDICATIONS GRID -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($medications as $medication)
                    @php
                        $scheduleType = $medication->primaryScheduleType();
                        $weeklyDays = $medication->weeklyDays();
                        $specificDates = $medication->specificScheduleDates();
                        $displayTimes = $medication->scheduleTimesForDate(now());
                        if (empty($displayTimes)) {
                            $displayTimes = $medication->times_of_day ?? [];
                        }
                    @endphp
                    <div class="sc-card sc-lift flex flex-col justify-between overflow-hidden">
                        <!-- Card Header -->
                        <div class="p-5 sm:p-6" style="border-bottom: 1px solid var(--sc-line)">
                            <div class="flex justify-between items-start gap-3">
                                <div class="sc-plate flex-none" aria-hidden="true">
                                    <x-lucide-pill class="sc-i w-5 h-5" aria-hidden="true" />
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <a href="{{ route('caregiver.medications.edit', ['medication' => $medication->id, 'elderly' => $selectedElderly->id]) }}"
                                       class="sc-btn sc-btn-ghost sc-btn-sm !p-2"
                                       aria-label="Edit {{ $medication->name }}">
                                        <x-lucide-pencil class="sc-i w-4 h-4" aria-hidden="true" />
                                    </a>
                                    <form
                                        action="{{ route('caregiver.medications.destroy', $medication->id) }}"
                                        method="POST"
                                        data-confirm="Are you sure you want to delete this medication?"
                                        data-confirm-title="Delete medication?"
                                        data-confirm-icon="warning"
                                        data-confirm-confirm-text="Delete medication"
                                        data-confirm-cancel-text="Keep medication"
                                        data-confirm-elderly="false"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="elderly_id" value="{{ $selectedElderly->id }}">
                                        <button type="submit"
                                                class="sc-btn sc-btn-ghost sc-btn-sm !p-2 hover:!text-[var(--sc-alert)]"
                                                aria-label="Delete {{ $medication->name }}">
                                            <x-lucide-trash-2 class="sc-i w-4 h-4" aria-hidden="true" />
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <h3 class="sc-h3 text-lg mt-3" style="color:var(--sc-title)">{{ $medication->name }}</h3>
                            <p class="sc-num font-semibold text-sm mt-0.5" style="color:var(--sc-body)">
                                {{ $medication->dosage }} {{ $medication->dosage_unit }}
                            </p>
                        </div>

                        <!-- Card Body -->
                        <div class="p-5 sm:p-6 space-y-4 flex-grow">
                            <!-- Schedule -->
                            @if($scheduleType === 'weekly')
                                <div class="space-y-1.5">
                                    <p class="sc-eyebrow text-xs">Schedule</p>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $index => $shortDay)
                                            @php
                                                $fullDay = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'][$index];
                                                $isActiveDay = in_array($fullDay, $weeklyDays, true);
                                            @endphp
                                            <span class="sc-badge sc-num text-xs {{ $isActiveDay ? 'sc-badge-brand' : '' }}">
                                                {{ $shortDay }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if($scheduleType === 'daily')
                                <div class="space-y-1.5">
                                    <p class="sc-eyebrow text-xs">Schedule</p>
                                    <span class="sc-badge sc-badge-brand text-xs">Every Day</span>
                                </div>
                            @endif

                            @if($scheduleType === 'specific_date')
                                <div class="space-y-1.5">
                                    <p class="sc-eyebrow text-xs">Scheduled Dates</p>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($specificDates as $specificDate)
                                            <span class="sc-badge sc-badge-brand sc-num text-xs">
                                                {{ \Carbon\Carbon::parse($specificDate)->format('M j') }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Times of Day -->
                            @if(!empty($displayTimes))
                                <div class="space-y-1.5">
                                    <p class="sc-eyebrow text-xs">Times</p>
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach($displayTimes as $time)
                                            <span class="sc-badge sc-num text-xs inline-flex items-center gap-1">
                                                <x-lucide-clock class="sc-i w-3 h-3" aria-hidden="true" />
                                                {{ \Carbon\Carbon::parse($time)->format('g:i A') }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Instructions -->
                            @if($medication->instructions)
                                <div class="sc-card-quiet p-3 text-xs sm:text-sm" style="color:var(--sc-muted)">
                                    <p class="sc-quote">{{ Str::limit($medication->instructions, 100) }}</p>
                                </div>
                            @endif
                        </div>

                        <!-- Card Footer -->
                        <div class="sc-card-footer px-5 py-3 flex items-center justify-between gap-3 text-xs">
                            <span class="sc-mark {{ $medication->is_active ? 'sc-mark-ok' : '' }}">
                                <i></i>{{ $medication->is_active ? 'Active' : 'Inactive' }}
                            </span>
                            @if($medication->track_inventory)
                                @php $isLow = ($medication->current_stock <= ($medication->low_stock_threshold ?? 5)); @endphp
                                <span class="sc-num inline-flex items-center gap-1 font-semibold" style="color:var({{ $isLow ? '--sc-alert' : '--sc-muted' }})">
                                    <x-lucide-package class="sc-i w-3.5 h-3.5" aria-hidden="true" />
                                    <span>Stock: {{ $medication->current_stock ?? 0 }}</span>
                                </span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-span-full sc-empty py-12">
                        <span class="sc-plate sc-plate-lg mb-3" aria-hidden="true">
                            <x-lucide-pill class="sc-i w-8 h-8" aria-hidden="true" />
                        </span>
                        <h3 class="sc-h3 text-lg font-bold" style="color:var(--sc-ink)">No Medications Yet</h3>
                        <p class="max-w-md mx-auto text-sm" style="color:var(--sc-body)">
                            Get started by adding a medication schedule for your patient.
                        </p>
                        <div class="mt-4">
                            <a href="{{ route('caregiver.medications.create', ['elderly' => $selectedElderly->id]) }}" class="sc-btn sc-btn-primary">
                                <x-lucide-plus class="sc-i w-4 h-4" aria-hidden="true" />
                                <span>Add Medication</span>
                            </a>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </main>
</x-dashboard-layout>
