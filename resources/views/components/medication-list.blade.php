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
        <p class="text-sm font-extrabold text-white">✅ Medications — All taken</p>
        <button @click="expanded = true" class="text-xs font-bold text-white/90 hover:text-white transition-colors flex items-center gap-1">
            Expand
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
        </button>
    </div>

    <div x-show="expanded || !(total > 0 && taken >= total)"
         class="relative z-10 overflow-y-auto no-scrollbar space-y-2">
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
                                    <span class="text-xs font-semibold text-gray-400" x-text="relativeText"></span>
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
