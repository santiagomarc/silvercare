{{-- ============================================================
     MedicationList — Alpine-powered medication dose tracker.
     Wraps in x-data="medicationTracker(taken, total)".
     ============================================================ --}}

<style>
    .hide-completed-meds .medication-entry[data-taken="true"] {
        display: none !important;
    }
</style>

<div x-data="{ ...medicationTracker({{ $takenDoses }}, {{ $totalDoses }}), showCompleted: true }"
    class="bg-gradient-to-br from-emerald-500 via-green-500 to-teal-500 rounded-card p-6 text-white flex flex-col relative overflow-hidden shadow-[0_30px_55px_-30px_rgba(5,150,105,0.62)]"
     role="region"
     aria-label="Today's medications">

    <div class="ambient-orb -right-10 -top-8 h-32 w-32 bg-white/15"></div>
    <div class="ambient-orb -bottom-10 left-0 h-24 w-24 bg-emerald-900/15 blur-2xl"></div>

    <div class="relative z-10 flex justify-between items-center mb-2">
        <div>
            <h3 class="font-extrabold text-lg">Today's Medications</h3>
            <div class="flex items-center gap-2 mt-0.5">
                <p class="text-white/70 text-xs font-medium">
                    <span x-text="taken"></span>/<span x-text="total"></span> doses taken
                </p>
                <button
                    x-show="taken > 0"
                    @click="showCompleted = !showCompleted"
                    class="text-[10px] font-bold text-white/90 hover:text-white bg-white/20 hover:bg-white/30 px-2 py-0.5 rounded transition-colors"
                    x-text="showCompleted ? 'Hide Taken' : 'Show Taken'">
                </button>
            </div>
        </div>
        <a href="{{ route('elderly.medications') }}"
           class="text-xs font-bold text-white/90 flex items-center gap-1 hover:text-white bg-white/20 px-3 py-1.5 rounded-full transition-colors">
            View All
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
        </a>
    </div>

    {{-- Progress Bar --}}
    <div class="relative z-10 progress-track bg-white/20 mb-4">
        <div class="progress-fill bg-white" :style="'width:' + progress + '%'"></div>
    </div>

    <div class="relative z-10 overflow-y-auto no-scrollbar space-y-2" :class="{ 'hide-completed-meds': !showCompleted }">
        @forelse($medications as $medication)
            @php
                $medTimes = $medication->scheduleTimesForDate(now());
            @endphp
            @foreach($medTimes as $time)
                @php
                    $logKey = $medication->id . '_' . $time;
                    $log = $logs->get($logKey);
                    $status = \App\Presenters\MedicationPresenter::getDoseStatus($time, $log);
                @endphp
                <div x-data="{ expanded: false }"
                     class="medication-entry rounded-xl border p-3 transition-all duration-300 cursor-pointer active:scale-[0.98] hover:shadow-lg backdrop-blur-sm {{ $status['bg'] }} {{ $status['isTaken'] ? 'opacity-75' : '' }}"
                     style="box-shadow: inset 0 1px 0 rgba(255,255,255,0.35);"
                     data-medication-id="{{ $medication->id }}"
                     data-time="{{ $time }}"
                     data-taken="{{ $status['isTaken'] ? 'true' : 'false' }}"
                     data-can-take="{{ $status['canTake'] ? 'true' : 'false' }}"
                     data-can-undo="{{ $status['canUndo'] ? 'true' : 'false' }}">

                    <div class="flex items-center gap-3"
                         @click="toggleEntry($event.currentTarget.closest('.medication-entry'))">
                        {{-- Status Icon --}}
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center text-lg flex-shrink-0 {{ $status['iconBg'] }} {{ $status['isWithinWindow'] && !$status['isTaken'] ? 'animate-pulse' : '' }}">
                            <span data-icon>{{ $status['icon'] }}</span>
                        </div>

                        {{-- Content --}}
                        <div class="flex-grow min-w-0">
                            <div class="flex items-center justify-between">
                                <h4 data-med-name class="font-extrabold text-gray-900 text-sm truncate {{ $status['isTaken'] ? 'line-through' : '' }}">
                                    {{ $medication->name }}
                                </h4>
                                <div class="text-right flex-shrink-0">
                                    <span class="text-xs font-bold text-gray-500 block">
                                        {{ \Carbon\Carbon::parse($time)->format('g:i A') }}
                                    </span>
                                    <span class="text-[10px] text-gray-400 font-semibold block leading-tight">
                                        {{ \Carbon\Carbon::parse(today()->format('Y-m-d').' '.$time)->diffForHumans() }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between mt-0.5">
                                <p class="text-gray-500 text-xs font-medium">
                                    {{ $medication->dosage }} {{ $medication->dosage_unit }}
                                </p>
                                <span data-status-label
                                      class="badge text-xs font-bold {{ $status['isTaken'] ? ($status['status'] === 'Taken Late' ? 'text-orange-600' : 'text-green-600') : ($status['status'] === 'Missed' ? 'text-red-600' : ($status['isWithinWindow'] ? 'text-amber-600' : 'text-gray-400')) }}">
                                    {{ $status['status'] }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Instructions toggle --}}
                    @if($medication->instructions)
                        <div class="mt-2 border-t border-dashed border-gray-200 pt-1" @click.stop="expanded = !expanded">
                            <button class="text-xs font-bold text-blue-500 flex items-center gap-1 hover:text-blue-700 transition-colors w-full focus:outline-none">
                                <svg x-show="!expanded" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <svg x-show="expanded" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                <span x-text="expanded ? 'Hide Info' : 'Show Instructions'"></span>
                            </button>
                            <div x-show="expanded" x-collapse class="mt-1 text-xs text-gray-600 bg-blue-50 p-2 rounded-lg leading-relaxed shadow-inner">
                                <span class="font-bold">Note:</span> {{ $medication->instructions }}
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        @empty
            <div class="text-center py-8 flex flex-col items-center bg-white/20 rounded-xl">
                <div class="text-4xl mb-2 opacity-50" aria-hidden="true">🎉</div>
                <p class="text-white/90 text-sm font-bold">No medications today!</p>
                <p class="text-white/70 text-xs mt-1">Enjoy your day</p>
            </div>
        @endforelse
    </div>
</div>
