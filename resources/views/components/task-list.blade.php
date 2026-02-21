<div class="bg-blue-50 rounded-[24px] p-6 shadow-lg border border-blue-200 relative overflow-hidden">
    <!-- Background decoration -->
    <div class="absolute -bottom-8 -right-8 w-32 h-32 bg-blue-100 rounded-full opacity-50"></div>
    
    <div class="relative z-10">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h3 class="font-[800] text-lg text-gray-900">Today's Tasks</h3>
                <p class="text-xs text-gray-400 font-medium">
                    <span id="completedCount">{{ $completedCount }}</span>/{{ $totalCount }} completed
                </p>
            </div>
            <a href="{{ route('elderly.checklists') }}" class="text-xs font-bold text-[#000080] hover:underline flex items-center gap-1">
                See All
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </a>
        </div>

        <!-- Mini Progress Bar -->
        <div class="h-2 bg-blue-100 rounded-full mb-4 overflow-hidden">
            <div id="progressBar" class="h-full bg-gradient-to-r from-blue-400 to-blue-500 rounded-full transition-all duration-500" 
                 style="width: {{ $progress }}%"></div>
        </div>

        <div class="space-y-2" id="checklistContainer">
            @php
                $categoryIcons = [
                    'Health' => '❤️',
                    'Exercise' => '🏃',
                    'Nutrition' => '🍎',
                    'Social' => '👥',
                    'Hygiene' => '🧼',
                    'Mental' => '🧠',
                    'Medication' => '💊',
                    'Medical' => '🏥',
                    'Daily' => '☀️',
                    'Home' => '🏠',
                    'Other' => '📋',
                ];
            @endphp
            @forelse($checklists->take(5) as $checklist)
                <div x-data="{ expanded: false }" class="checklist-item flex items-start gap-3 p-3 rounded-xl border transition-all duration-300 {{ $checklist->is_completed ? 'bg-green-50/50 border-green-200 opacity-75' : 'bg-white border-gray-100 hover:border-green-200 hover:bg-green-50/30' }}" 
                     data-id="{{ $checklist->id }}"
                     data-completed="{{ $checklist->is_completed ? 'true' : 'false' }}">
                    
                    <!-- Animated Checkbox -->
                    <button 
                        onclick="toggleChecklist({{ $checklist->id }}, this)"
                        class="checkbox-btn flex-shrink-0 w-7 h-7 rounded-lg border-2 {{ $checklist->is_completed ? 'bg-green-500 border-green-500' : 'bg-white border-gray-300 hover:border-green-400' }} flex items-center justify-center transition-all duration-300 hover:scale-110 active:scale-95 mt-0.5">
                        <svg class="check-icon w-4 h-4 text-white transition-all duration-300 {{ $checklist->is_completed ? 'opacity-100 scale-100' : 'opacity-0 scale-0' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </button>

                    <!-- Category Icon -->
                    <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-sm flex-shrink-0 mt-0.5">
                        {{ $categoryIcons[$checklist->category] ?? '📋' }}
                    </div>

                    <!-- Task Content -->
                    <div class="flex-grow min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="task-text text-sm font-bold transition-all duration-300 {{ $checklist->is_completed ? 'line-through text-gray-400' : 'text-gray-900' }}">
                                {{ $checklist->task }}
                            </p>
                            <!-- Priority Badge -->
                            @if($checklist->priority === 'high')
                                <span class="text-[9px] px-1.5 py-0.5 rounded font-bold bg-red-100 text-red-600">🔴 High</span>
                            @elseif($checklist->priority === 'medium')
                                <span class="text-[9px] px-1.5 py-0.5 rounded font-bold bg-yellow-100 text-yellow-700">🟡 Medium</span>
                            @elseif($checklist->priority === 'low')
                                <span class="text-[9px] px-1.5 py-0.5 rounded font-bold bg-gray-100 text-gray-500">🟢 Low</span>
                            @endif
                        </div>
                        
                        <div class="flex items-center gap-2 mt-1 flex-wrap">
                            <!-- Category -->
                            <span class="text-[10px] bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded font-medium">{{ $checklist->category ?? 'Other' }}</span>
                            
                            <!-- Time -->
                            @if($checklist->due_time)
                                <span class="text-[10px] text-gray-500 font-medium flex items-center gap-0.5">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    {{ \Carbon\Carbon::parse($checklist->due_time)->format('g:i A') }}
                                </span>
                            @endif
                            
                            <!-- Recurring indicator -->
                            @if($checklist->is_recurring)
                                <span class="text-[10px] text-blue-500 font-medium flex items-center gap-0.5">
                                    🔄 {{ ucfirst($checklist->frequency ?? 'Recurring') }}
                                </span>
                            @endif
                        </div>
                        
                        <!-- Description preview -->
                        @if($checklist->description)
                            <div class="mt-1">
                                <div x-show="!expanded" class="text-[10px] text-gray-500 cursor-pointer hover:text-gray-700" @click="expanded = true">
                                    📝 {{ Str::limit($checklist->description, 60) }}
                                    @if(strlen($checklist->description) > 60)
                                        <span class="text-blue-500 font-bold ml-1 hover:underline">Read more</span>
                                    @endif
                                </div>
                                <div x-show="expanded" class="text-[10px] text-gray-700 bg-gray-50 p-2 rounded border border-gray-100 mt-1" x-cloak>
                                    {{ $checklist->description }}
                                    <button @click="expanded = false" class="block mt-1 text-blue-500 font-bold hover:underline">Show less</button>
                                </div>
                            </div>
                        @endif
                        <!-- Notes preview -->
                        @if($checklist->notes && !$checklist->description)
                            <p class="text-[10px] text-gray-400 mt-1 truncate italic">💬 {{ Str::limit($checklist->notes, 60) }}</p>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-10">
                    <div class="text-5xl mb-3">🎉</div>
                    <p class="text-gray-600 text-sm font-bold">All caught up!</p>
                    <p class="text-gray-400 text-xs mt-1">No tasks for today</p>
                </div>
            @endforelse
        </div>
    </div>
</div>