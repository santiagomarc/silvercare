<x-dashboard-layout sc>
    <x-slot:title>My Medications - SilverCare</x-slot:title>

    <x-dashboard-nav
        title="My Medications"
        subtitle="{{ $medications->count() }} active medications"
        role="elderly"
        :unread-notifications="$unreadNotifications"
    />

    <main id="main-content" class="sc-app-main">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-12 space-y-6">

            {{-- Back Navigation --}}
            <div class="flex justify-between items-center">
                <a href="{{ route('dashboard') }}" class="sc-btn sc-btn-ghost sc-btn-sm">
                    <x-lucide-arrow-left class="sc-i w-4 h-4" aria-hidden="true" />
                    <span>Back to Dashboard</span>
                </a>

                @if($medications->count() > 0)
                    <span class="sc-badge sc-num text-xs sm:text-sm">
                        {{ $medications->where('is_active', true)->count() }} active
                    </span>
                @endif
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
                <section class="sc-card sc-lift p-6 md:p-8 space-y-5" aria-labelledby="medication-{{ $medication->id }}-heading">
                    <!-- Header -->
                    <div class="flex flex-wrap justify-between items-start gap-3 pb-4" style="border-bottom: 1px solid var(--sc-line)">
                        <div class="flex items-start gap-3">
                            <div class="sc-plate sc-plate-sm flex-none" aria-hidden="true">
                                <x-lucide-pill class="sc-i w-5 h-5" aria-hidden="true" />
                            </div>
                            <div>
                                <h2 id="medication-{{ $medication->id }}-heading" class="sc-h3 text-xl" style="color:var(--sc-title)">
                                    {{ $medication->name }}
                                </h2>
                                <p class="sc-num font-semibold text-base mt-0.5" style="color:var(--sc-body)">
                                    {{ $medication->dosage }} {{ $medication->dosage_unit }}
                                </p>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            @if($isToday)
                                <span class="sc-badge sc-badge-brand sc-num text-xs inline-flex items-center gap-1.5 font-semibold">
                                    <x-lucide-calendar class="sc-i w-3.5 h-3.5" aria-hidden="true" />
                                    <span>Scheduled for Today</span>
                                </span>
                            @endif
                            <span class="sc-mark {{ $medication->is_active ? 'sc-mark-ok' : '' }}">
                                <i></i>{{ $medication->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </div>

                    <!-- Schedule Days -->
                    @if($scheduleType === 'weekly')
                        <div class="space-y-1.5">
                            <p class="sc-eyebrow text-xs">Schedule</p>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                                    @php $isActiveDay = in_array($day, $weeklyDays, true); @endphp
                                    <span class="sc-badge sc-num text-xs {{ $isActiveDay ? 'sc-badge-brand font-bold' : '' }}">
                                        {{ substr($day, 0, 3) }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($scheduleType === 'daily')
                        <div class="space-y-1.5">
                            <p class="sc-eyebrow text-xs">Schedule</p>
                            <span class="sc-badge sc-badge-brand text-xs font-semibold">
                                Every day
                            </span>
                        </div>
                    @endif

                    @if($scheduleType === 'specific_date')
                        <div class="space-y-1.5">
                            <p class="sc-eyebrow text-xs">Scheduled Dates</p>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($specificDates as $specificDate)
                                    <span class="sc-badge sc-badge-brand sc-num text-xs">
                                        {{ \Carbon\Carbon::parse($specificDate)->format('M j, Y') }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Times -->
                    @if(!empty($displayTimes))
                        <div class="space-y-1.5">
                            <p class="sc-eyebrow text-xs">Times to Take</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($displayTimes as $time)
                                    <span class="sc-badge sc-num text-sm py-1.5 px-3 inline-flex items-center gap-1.5 font-semibold">
                                        <x-lucide-clock class="sc-i w-4 h-4" aria-hidden="true" />
                                        {{ \Carbon\Carbon::parse($time)->format('g:i A') }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Instructions -->
                    @if($medication->instructions)
                        <div class="sc-card-quiet p-4 text-sm" style="color:var(--sc-body)">
                            <p class="sc-eyebrow text-xs mb-1 inline-flex items-center gap-1">
                                <x-lucide-notebook-pen class="sc-i w-3.5 h-3.5" aria-hidden="true" />
                                <span>Instructions</span>
                            </p>
                            <p class="sc-quote text-base">{{ $medication->instructions }}</p>
                        </div>
                    @endif

                    <!-- Stock Warning & Refill -->
                    @if($medication->track_inventory && $medication->current_stock <= ($medication->low_stock_threshold ?? 5))
                        <div class="sc-card-quiet p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
                             style="border-color: var(--sc-alert-line)"
                             x-data="{ 
                                loading: false,
                                requested: false,
                                async requestRefill() {
                                    this.loading = true;
                                    try {
                                        const token = document.querySelector('meta[name=\'csrf-token\']')?.content;
                                        const res = typeof sendJsonRequest === 'function'
                                            ? await sendJsonRequest('{{ route('elderly.medications.refill', $medication) }}', 'POST')
                                            : await fetch('{{ route('elderly.medications.refill', $medication) }}', {
                                                method: 'POST',
                                                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token }
                                            }).then(r => r.json());
                                        if (res && (res.success || res.ok)) {
                                            this.requested = true;
                                            if (window.scAlert) {
                                                window.scAlert({ title: 'Refill Requested!', text: 'Your caregiver has been notified.', icon: 'success' });
                                            } else if (window.Swal) {
                                                window.Swal.fire({
                                                    title: 'Refill Requested!',
                                                    text: 'Your caregiver has been notified.',
                                                    icon: 'success',
                                                    confirmButtonColor: '#3085d6'
                                                });
                                            }
                                        }
                                    } catch (e) {
                                        console.error(e);
                                    } finally {
                                        this.loading = false;
                                    }
                                }
                             }">
                            <div class="flex items-center gap-3">
                                <div class="sc-plate sc-plate-sm flex-none" style="color:var(--sc-alert)" aria-hidden="true">
                                    <x-lucide-triangle-alert class="sc-i w-5 h-5" aria-hidden="true" />
                                </div>
                                <div>
                                    <p class="font-bold text-sm" style="color:var(--sc-alert)">Low Stock Alert</p>
                                    <p class="text-xs sm:text-sm sc-num mt-0.5" style="color:var(--sc-body)">
                                        Only {{ $medication->current_stock }} {{ $medication->dosage_unit }} remaining.
                                    </p>
                                </div>
                            </div>
                            <button type="button"
                                    @click="requestRefill()" 
                                    :disabled="loading || requested"
                                    class="sc-btn sc-btn-sm w-full sm:w-auto"
                                    :class="requested ? 'sc-btn-ghost cursor-default' : 'sc-btn-danger'">
                                <template x-if="loading">
                                    <svg class="animate-spin h-4 w-4 text-current" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </template>
                                <template x-if="!loading">
                                    <span class="flex items-center gap-1.5">
                                        <x-lucide-package-plus x-show="!requested" class="sc-i w-4 h-4" aria-hidden="true" />
                                        <x-lucide-check x-show="requested" class="sc-i w-4 h-4" aria-hidden="true" />
                                        <span x-text="requested ? 'Refill Requested' : 'Request Refill'"></span>
                                    </span>
                                </template>
                            </button>
                        </div>
                    @endif
                </section>
            @empty
                <div class="sc-empty py-12">
                    <span class="sc-plate sc-plate-lg mb-3" aria-hidden="true">
                        <x-lucide-pill class="sc-i w-8 h-8" aria-hidden="true" />
                    </span>
                    <h2 class="sc-h3 text-lg font-bold" style="color:var(--sc-ink)">No medications yet</h2>
                    <p class="max-w-md mx-auto text-sm" style="color:var(--sc-body)">
                        Your caregiver will add your medications here when needed. Nothing to worry about.
                    </p>
                    <div class="mt-4">
                        <a href="{{ route('dashboard') }}" class="sc-btn sc-btn-primary">
                            <x-lucide-arrow-left class="sc-i w-4 h-4" aria-hidden="true" />
                            <span>Back to Dashboard</span>
                        </a>
                    </div>
                </div>
            @endforelse

        </div>
    </main>
</x-dashboard-layout>
