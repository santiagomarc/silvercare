{{-- ============================================================
     MedicationList — Alpine-powered medication dose tracker.
     Wraps in x-data="medicationTracker(taken, total)".
     ============================================================ --}}

<div x-data="medicationTracker({{ $takenDoses }}, {{ $totalDoses }})"
    class="bg-gradient-to-br from-emerald-500 via-green-500 to-teal-500 rounded-card p-6 text-white flex flex-col relative overflow-hidden shadow-[0_30px_55px_-30px_rgba(5,150,105,0.62)]"
     role="region"
     aria-label="Today's medications">

    <div class="ambient-orb -right-10 -top-8 h-32 w-32 bg-white/15"></div>
    <div class="ambient-orb -bottom-10 left-0 h-24 w-24 bg-emerald-900/15 blur-2xl"></div>

    <div class="relative z-10 flex justify-between items-center mb-2">
        <div>
            <h3 class="font-extrabold text-lg">Today's Medications</h3>
            <p class="text-white/70 text-xs font-medium">
                <span x-text="taken"></span>/<span x-text="total"></span> doses taken
            </p>
        </div>
        <a href="{{ route('elderly.medications') }}"
           class="text-xs font-bold text-white/90 flex items-center gap-1 hover:text-white bg-white/20 px-3 py-1.5 rounded-full transition-colors">
            View All
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
        </a>
    </div>

    {{-- Progress Bar --}}
    <div class="relative z-10 progress-track bg-white/20 mb-4" aria-hidden="true">
        <div class="progress-fill bg-white"
             role="progressbar"
             :aria-valuenow="progress"
             aria-valuemin="0"
             aria-valuemax="100"
             :aria-label="'Medications: ' + taken + ' of ' + total + ' taken'"
             :style="'width:' + progress + '%'"></div>
    </div>

    {{-- Auto-collapsed summary once all doses are taken --}}
    <div x-show="!expanded && total > 0 && taken >= total" x-cloak
         class="relative z-10 rounded-xl border border-white/30 bg-white/20 px-3 py-2 flex items-center justify-between">
        <p class="text-sm font-extrabold text-white inline-flex items-center gap-1.5">
            <x-lucide-circle-check class="w-4 h-4" aria-hidden="true" />
            Medications - All taken
        </p>
        <button @click="expanded = true" class="text-xs font-bold text-white/90 hover:text-white transition-colors flex items-center gap-1">
            Expand
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
        </button>
    </div>

    <div x-show="expanded || !(total > 0 && taken >= total)"
         class="relative z-10 overflow-y-auto no-scrollbar space-y-4">
        
        @forelse($groupedDoses as $timeOfDay => $doses)
            <div class="time-group">
                <h4 class="text-white/90 font-extrabold text-sm uppercase tracking-wider mb-2 flex items-center gap-2">
                    @if($timeOfDay === 'Morning')
                        <svg class="w-4 h-4 text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    @elseif($timeOfDay === 'Afternoon')
                        <svg class="w-4 h-4 text-orange-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    @elseif($timeOfDay === 'Evening')
                        <svg class="w-4 h-4 text-indigo-300" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
                    @else
                        <svg class="w-4 h-4 text-blue-200" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
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
                     class="medication-entry rounded-2xl border p-4 transition-all duration-300 cursor-pointer active:scale-[0.98] hover:shadow-lg backdrop-blur-sm {{ $status['bg'] }} {{ $status['isTaken'] ? 'opacity-75' : '' }}"
                     style="box-shadow: inset 0 1px 0 rgba(255,255,255,0.35);"
                     data-medication-id="{{ $medication->id }}"
                     data-time="{{ $time }}"
                     data-taken="{{ $status['isTaken'] ? 'true' : 'false' }}"
                     data-can-take="{{ $status['canTake'] ? 'true' : 'false' }}"
                     data-can-undo="{{ $status['canUndo'] ? 'true' : 'false' }}">

                    <div class="flex items-start gap-4 min-h-[44px]"
                         @click="toggleEntry($event.currentTarget.closest('.medication-entry'))">
                        {{-- Status Icon and Pill Visual --}}
                        <div class="flex flex-col items-center justify-center gap-2 flex-shrink-0">
                            <div class="w-11 h-11 rounded-xl flex items-center justify-center text-xl {{ $status['iconBg'] }} {{ $status['isWithinWindow'] && !$status['isTaken'] ? 'animate-pulse' : '' }}">
                                <span data-icon>{{ $status['icon'] }}</span>
                            </div>
                        </div>

                        {{-- Content --}}
                        <div class="flex-grow min-w-0">
                            <div class="flex items-center justify-between">
                                <h4 data-med-name class="font-extrabold text-gray-900 text-base truncate {{ $status['isTaken'] ? 'line-through' : '' }}">
                                    {{ $medication->name }}
                                </h4>
                                <div class="text-right flex-shrink-0">
                                    <span class="text-sm font-bold text-gray-700 block">
                                        {{ \Carbon\Carbon::parse($time)->format('g:i A') }}
                                    </span>
                                    <span class="text-xs font-semibold text-gray-500" x-text="relativeText"></span>
                                </div>
                            </div>
                            
                            {{-- Medical Context: Purpose & Dosage --}}
                            <div class="mt-1 flex flex-wrap gap-x-2 gap-y-1">
                                <p class="text-gray-600 text-sm font-semibold">
                                    {{ $medication->dosage }} {{ $medication->dosage_unit }}
                                </p>
                                @if($medication->purpose)
                                    <span class="text-gray-400 text-xs font-bold leading-relaxed px-1.5 py-0.5 rounded bg-gray-100/50">
                                        For: {{ $medication->purpose }}
                                    </span>
                                @endif
                            </div>

                            {{-- Doctor & Tags --}}
                            @if (!empty($instructionTags))
                            <div class="flex flex-wrap gap-2 mt-2">
                                @foreach($instructionTags as $tag)
                                    <span class="px-2 pl-1 py-0.5 text-[10px] font-bold rounded flex items-center gap-1 {{ $tag['color'] }}">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        {{ $tag['text'] }}
                                    </span>
                                @endforeach
                            </div>
                            @endif

                            <div class="flex items-center justify-between mt-3">
                                <span data-status-label
                                      class="badge text-xs font-bold px-2 py-1 rounded shadow-sm border {{ $status['isTaken'] ? ($status['status'] === 'Taken Late' ? 'text-orange-700 border-orange-200 bg-orange-50' : 'text-green-700 border-green-200 bg-green-50') : ($status['status'] === 'Missed' ? 'text-red-700 border-red-200 bg-red-50' : ($status['isWithinWindow'] ? 'text-white border-blue-600 bg-blue-600' : 'text-gray-600 border-gray-300 bg-gray-100')) }}">
                                    {{ $status['status'] }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Expanded view: Instructions & Prescriber --}}
                    @if($medication->instructions || $medication->prescribing_doctor || $medication->appearance_color || $medication->appearance_shape)
                        <div class="mt-3 border-t border-dashed border-gray-300/50 pt-2" @click.stop="expanded = !expanded">
                            <button class="text-sm font-extrabold text-indigo-500 py-2 flex items-center justify-center gap-1 hover:text-indigo-700 transition-colors w-full focus:outline-none min-h-[44px]">
                                <span x-text="expanded ? 'Hide Info' : 'Show Details'"></span>
                                <svg x-show="!expanded" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                <svg x-show="expanded" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                            </button>
                            <div x-show="expanded" x-collapse class="mt-2 text-sm text-gray-700 bg-indigo-50/50 p-4 rounded-xl leading-relaxed space-y-3">
                                @if($medication->instructions)
                                    <div>
                                        <p class="font-extrabold text-indigo-900 text-xs uppercase mb-1">Instructions:</p>
                                        <p>{{ $medication->instructions }}</p>
                                    </div>
                                @endif
                                
                                @if($medication->appearance_color || $medication->appearance_shape)
                                    <div>
                                        <p class="font-extrabold text-indigo-900 text-xs uppercase mb-1">Pill Appearance:</p>
                                        <div class="flex items-center gap-2">
                                            @if($medication->appearance_color)
                                                <span class="inline-block w-4 h-4 rounded-full border border-gray-300 shadow-sm" style="background-color: {{ strtolower($medication->appearance_color) }}"></span>
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
                                        <p class="font-extrabold text-indigo-900 text-xs uppercase mb-1">Prescribed by:</p>
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
            <div class="text-center py-8 flex flex-col items-center bg-white/20 rounded-xl">
                <x-lucide-party-popper class="w-10 h-10 mb-2 text-white/80" aria-hidden="true" />
                <p class="text-white/90 text-sm font-bold">No medications today!</p>
                <p class="text-white/70 text-xs mt-1">Enjoy your day</p>
            </div>
        @endforelse
    </div>
</div>
