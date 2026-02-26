<div class="bg-gradient-to-br from-green-500 to-green-600 rounded-[24px] p-6 shadow-lg shadow-green-900/20 text-white flex flex-col">
    <div class="flex justify-between items-center mb-2">
        <div>
            <h3 class="font-[800] text-lg">Today's Medications</h3>
            <p class="text-white/70 text-xs font-medium">
                {{ $takenDoses }}/{{ $totalDoses }} doses taken
            </p>
        </div>
        <a href="{{ route('elderly.medications') }}" class="text-xs font-bold text-white/90 flex items-center gap-1 hover:text-white bg-white/20 px-3 py-1.5 rounded-full transition-colors">
            View All <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
        </a>
    </div>

    <!-- Mini Progress Bar -->
    <div class="h-2 bg-white/20 rounded-full mb-4 overflow-hidden">
        <div id="medicationProgressBar" class="h-full bg-white rounded-full transition-all duration-500" 
             style="width: {{ $progress }}%"></div>
    </div>
    
    <div class="overflow-y-auto no-scrollbar space-y-2" id="medicationContainer">
        @forelse($medications as $medication)
            @php
                $medTimes = $medication->times_of_day ?? [];
            @endphp
            @foreach($medTimes as $time)
                @php
                    $logKey = $medication->id . '_' . $time;
                    $log = $logs->get($logKey);
                    $status = \App\Presenters\MedicationPresenter::getDoseStatus($time, $log);
                @endphp
                <div x-data="{ expanded: false }" class="medication-entry rounded-xl p-3 border-2 transition-all duration-300 cursor-pointer hover:shadow-md active:scale-[0.98] {{ $status['bg'] }} {{ $status['isTaken'] ? 'opacity-75' : '' }}" 
                     data-medication-id="{{ $medication->id }}"
                     data-time="{{ $time }}"
                     data-taken="{{ $status['isTaken'] ? 'true' : 'false' }}"
                     data-can-take="{{ $status['canTake'] ? 'true' : 'false' }}"
                     data-can-undo="{{ $status['canUndo'] ? 'true' : 'false' }}">
                     
                    <div class="flex items-center gap-3" onclick="toggleMedicationEntry(this.closest('.medication-entry'))">
                        <!-- Status Icon -->
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center text-lg flex-shrink-0 {{ $status['iconBg'] }} {{ $status['isWithinWindow'] && !$status['isTaken'] ? 'animate-pulse' : '' }}">
                            <span class="status-icon">{{ $status['icon'] }}</span>
                        </div>
                        
                        <!-- Content -->
                        <div class="flex-grow min-w-0">
                            <div class="flex items-center justify-between">
                                <h4 class="font-[800] text-gray-900 text-sm truncate {{ $status['isTaken'] ? 'line-through' : '' }}">{{ $medication->name }}</h4>
                                <span class="text-[10px] font-bold text-gray-500 flex-shrink-0">{{ \Carbon\Carbon::parse($time)->format('g:i A') }}</span>
                            </div>
                            <div class="flex items-center justify-between mt-0.5">
                                <p class="text-gray-500 text-[11px] font-medium">{{ $medication->dosage }} {{ $medication->dosage_unit }}</p>
                                <span class="text-[9px] font-bold {{ $status['isTaken'] ? ($status['status'] === 'Taken Late' ? 'text-orange-600' : 'text-green-600') : ($status['status'] === 'Missed' ? 'text-red-600' : ($status['isWithinWindow'] ? 'text-amber-600' : 'text-gray-400')) }}">
                                    {{ $status['status'] }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- INSTRUCTIONS TOGGLE (NEW) -->
                    @if($medication->instructions)
                        <div class="mt-2 border-t border-dashed border-gray-200 pt-1" @click.stop="expanded = !expanded">
                            <button class="text-[10px] font-bold text-blue-500 flex items-center gap-1 hover:text-blue-700 transition-colors w-full focus:outline-none">
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
                <div class="text-4xl mb-2 opacity-50">🎉</div>
                <p class="text-white/90 text-sm font-bold">No medications today!</p>
                <p class="text-white/70 text-xs mt-1">Enjoy your day</p>
            </div>
        @endforelse
    </div>
</div>