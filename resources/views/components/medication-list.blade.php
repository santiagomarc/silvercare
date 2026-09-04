{{-- ============================================================
     MedicationList — today's doses, grouped by time of day.

     The row's state class (`dose-taken`, `dose-active`, …) comes from
     MedicationPresenter and is the same class medication-tracker.js
     swaps at runtime, so the server and the browser never disagree.
     Every state carries an icon and a word as well as a tint.

     The hook classes (`medication-entry`, `data-icon`,
     `data-status-label`, `data-med-name`) are what the tracker selects
     on. Leave them.
     ============================================================ --}}

<div x-data="medicationTracker({{ $takenDoses }}, {{ $totalDoses }})"
     class="sc-card p-6 flex flex-col"
     role="region"
     aria-label="Today's medications">

    <div class="flex flex-wrap justify-between items-center gap-x-3 gap-y-2 mb-2">
        <div>
            <h3 class="sc-h3">Today's Medications</h3>
            <p class="sc-num" style="color:var(--sc-muted)">
                <span x-text="taken"></span>/<span x-text="total"></span> doses taken
            </p>
        </div>
        <a href="{{ route('elderly.medications') }}" class="sc-textlink inline-flex items-center gap-1">
            View All
            <x-lucide-chevron-right class="sc-i w-4 h-4" aria-hidden="true" />
        </a>
    </div>

    {{-- Progress --}}
    <div class="sc-progress mb-4">
        <div class="sc-progress-fill"
             role="progressbar"
             :aria-valuenow="progress"
             aria-valuemin="0"
             aria-valuemax="100"
             :aria-label="'Medications: ' + taken + ' of ' + total + ' taken'"
             :style="'width:' + progress + '%'"></div>
    </div>

    {{-- Auto-collapsed summary once all doses are taken --}}
    <div x-show="!expanded && total > 0 && taken >= total" x-cloak
         class="sc-card-quiet px-4 py-2.5 flex items-center justify-between gap-3">
        <p class="font-semibold inline-flex items-center gap-1.5" style="color:var(--sc-ok)">
            <x-lucide-circle-check class="sc-i w-5 h-5" aria-hidden="true" />
            Medications - All taken
        </p>
        <button type="button" @click="expanded = true" class="sc-textlink inline-flex items-center gap-1">
            Expand
            <x-lucide-chevron-down class="sc-i w-4 h-4" aria-hidden="true" />
        </button>
    </div>

    <div x-show="expanded || !(total > 0 && taken >= total)"
         class="overflow-y-auto no-scrollbar space-y-5">

        @forelse($groupedDoses as $timeOfDay => $doses)
            <div class="time-group">
                <h4 class="sc-eyebrow mb-2 flex items-center gap-2">
                    @if($timeOfDay === 'Morning' || $timeOfDay === 'Afternoon')
                        <x-lucide-sun class="sc-i w-5 h-5" aria-hidden="true" />
                    @else
                        <x-lucide-moon class="sc-i w-5 h-5" aria-hidden="true" />
                    @endif
                    {{ $timeOfDay }}
                </h4>
                <div class="space-y-3">
            @foreach($doses as $doseInfo)
                @php
                    $medication = $doseInfo['medication'];
                    $time = $doseInfo['time_carbon']->format('H:i');
                    $logKey = $doseInfo['log_key'];
                    $log = $doseInfo['log'];
                    $status = \App\Presenters\MedicationPresenter::getDoseStatus($time, $log);
                    $instructionTags = \App\Presenters\MedicationPresenter::parseInstructionTags($medication->instructions);
                @endphp
                <div x-data="{
                        expanded: false,
                        relativeText: '',
                        ticker: null,
                        init() {
                            this.updateRelative();
                            this.ticker = setInterval(() => this.updateRelative(), 60000);
                        },
                        destroy() {
                            if (this.ticker) clearInterval(this.ticker);
                        },
                        updateRelative() {
                            const now = new Date();
                            const target = new Date('{{ now()->toDateString() }}T{{ \Carbon\Carbon::parse($time)->format('H:i:s') }}');
                            const minutes = Math.round((target.getTime() - now.getTime()) / 60000);

                            if (Math.abs(minutes) < 1) {
                                this.relativeText = 'now';
                                return;
                            }

                            if (Math.abs(minutes) < 60) {
                                this.relativeText = minutes > 0
                                    ? ('in ' + minutes + ' min')
                                    : (Math.abs(minutes) + ' min ago');
                                return;
                            }

                            const hours = Math.round(Math.abs(minutes) / 60);
                            const unit = hours === 1 ? 'hour' : 'hours';
                            this.relativeText = minutes > 0
                                ? ('in ' + hours + ' ' + unit)
                                : (hours + ' ' + unit + ' ago');
                        }
                    }"
                     class="medication-entry sc-dose {{ $status['doseClass'] }} cursor-pointer active:scale-[0.98] transition-transform"
                     data-medication-id="{{ $medication->id }}"
                     data-time="{{ $time }}"
                     data-taken="{{ $status['isTaken'] ? 'true' : 'false' }}"
                     data-can-take="{{ $status['canTake'] ? 'true' : 'false' }}"
                     data-can-undo="{{ $status['canUndo'] ? 'true' : 'false' }}">

                    <div class="flex items-start gap-4 min-h-[44px]"
                         @click="toggleEntry($event.currentTarget.closest('.medication-entry'))">
                        {{-- Status --}}
                        <span class="sc-plate sc-plate-sm" data-icon aria-hidden="true">
                            <svg class="sc-i w-6 h-6" aria-hidden="true" focusable="false"><use href="#i-{{ $status['icon'] }}"/></svg>
                        </span>

                        {{-- Content --}}
                        <div class="flex-grow min-w-0">
                            <div class="flex items-center justify-between gap-3">
                                <h4 data-med-name class="font-semibold truncate {{ $status['isTaken'] ? 'line-through sc-task-text-done' : '' }}" style="color:var(--sc-ink)">
                                    {{ $medication->name }}
                                </h4>
                                <div class="text-right flex-shrink-0">
                                    <span class="font-semibold sc-num block" style="color:var(--sc-body)">
                                        {{ \Carbon\Carbon::parse($time)->format('g:i A') }}
                                    </span>
                                    <span class="sc-num" style="color:var(--sc-muted)" x-text="relativeText"></span>
                                </div>
                            </div>

                            {{-- Dose and purpose --}}
                            <div class="mt-1 flex flex-wrap gap-x-2 gap-y-1 items-center">
                                <p class="font-medium sc-num" style="color:var(--sc-body)">
                                    {{ $medication->dosage }} {{ $medication->dosage_unit }}
                                </p>
                                @if($medication->purpose)
                                    <span class="sc-badge">
                                        For: {{ $medication->purpose }}
                                    </span>
                                @endif
                            </div>

                            {{-- Instruction tags --}}
                            @if (!empty($instructionTags))
                            <div class="flex flex-wrap gap-2 mt-2">
                                @foreach($instructionTags as $tag)
                                    <span class="sc-badge {{ $tag['tone'] ? 'sc-badge-' . $tag['tone'] : '' }}">
                                        <x-lucide-info class="sc-i w-4 h-4" aria-hidden="true" />
                                        {{ $tag['text'] }}
                                    </span>
                                @endforeach
                            </div>
                            @endif

                            <div class="flex items-center justify-between mt-3">
                                <span data-status-label class="sc-badge {{ $status['tone'] ? 'sc-badge-' . $status['tone'] : '' }}">
                                    {{ $status['status'] }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Expanded: instructions, appearance, prescriber --}}
                    @if($medication->instructions || $medication->prescribing_doctor || $medication->appearance_color || $medication->appearance_shape)
                        <div class="mt-3 pt-2" style="border-top:1px dashed var(--sc-line-strong)" @click.stop="expanded = !expanded">
                            <button type="button" class="sc-textlink w-full flex items-center justify-center gap-1 min-h-[44px]">
                                <span x-text="expanded ? 'Hide Info' : 'Show Details'">Show Details</span>
                                <x-lucide-chevron-down class="sc-i w-5 h-5" x-show="!expanded" aria-hidden="true" />
                                <x-lucide-chevron-up class="sc-i w-5 h-5" x-show="expanded" x-cloak aria-hidden="true" />
                            </button>
                            <div x-show="expanded" x-collapse class="sc-card-quiet mt-2 p-4 leading-relaxed space-y-3" style="color:var(--sc-body)">
                                @if($medication->instructions)
                                    <div>
                                        <p class="sc-eyebrow mb-1">Instructions</p>
                                        <p>{{ $medication->instructions }}</p>
                                    </div>
                                @endif

                                @if($medication->appearance_color || $medication->appearance_shape)
                                    <div>
                                        <p class="sc-eyebrow mb-1">Pill appearance</p>
                                        <div class="flex items-center gap-2">
                                            @if($medication->appearance_color)
                                                {{-- The one place a literal colour is correct: it is the
                                                     colour of the tablet in the reader's hand, not a
                                                     design decision. --}}
                                                <span class="inline-block w-5 h-5 rounded-full"
                                                      style="background-color: {{ strtolower($medication->appearance_color) }}; border:1px solid var(--sc-line-strong)"
                                                      aria-hidden="true"></span>
                                                <span class="capitalize">{{ $medication->appearance_color }}</span>
                                            @endif
                                            @if($medication->appearance_shape)
                                                <span class="capitalize">({{ $medication->appearance_shape }})</span>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                @if($medication->prescribing_doctor)
                                    <div>
                                        <p class="sc-eyebrow mb-1">Prescribed by</p>
                                        <p>{{ $medication->prescribing_doctor }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
                </div>
            </div>
        @empty
            <div class="sc-empty">
                <span class="sc-plate sc-plate-ok" aria-hidden="true">
                    <x-lucide-circle-check class="sc-i w-6 h-6" aria-hidden="true" />
                </span>
                <p class="font-semibold" style="color:var(--sc-ink)">No medications today</p>
                <p>There is nothing scheduled for you to take.</p>
            </div>
        @endforelse
    </div>
</div>
