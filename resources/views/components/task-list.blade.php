{{-- ============================================================
     TaskList — Alpine-powered checklist tracker.
     Wraps in x-data="checklistTracker(completed, total)".
     ============================================================ --}}

<div x-data="checklistTracker({{ $completedCount }}, {{ $totalCount }})"
    class="surface-sky relative overflow-hidden p-6"
     role="region"
     aria-label="Today's tasks">

    {{-- Background decoration --}}
    <div class="ambient-orb -bottom-10 -right-6 h-36 w-36 bg-blue-200/45" aria-hidden="true"></div>
    <div class="ambient-orb left-10 top-4 h-16 w-16 bg-white/40 blur-2xl" aria-hidden="true"></div>

    <div class="relative z-10">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h3 class="font-extrabold text-lg text-gray-900">Today's Tasks</h3>
                <p class="text-xs text-gray-400 font-medium">
                    <span x-text="completed"></span>/<span x-text="total"></span> completed
                </p>
            </div>
            <a href="{{ route('elderly.checklists') }}"
               class="text-xs font-bold text-navy hover:underline flex items-center gap-1">
                See All
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </a>
        </div>

        {{-- Progress Bar --}}
        <div class="progress-track bg-blue-100 mb-4">
            <div class="progress-fill bg-gradient-to-r from-blue-400 to-blue-500"
                 :style="'width:' + progress + '%'"></div>
        </div>

        {{-- Auto-collapsed summary once all tasks are done --}}
        <div x-show="!expanded && total > 0 && completed >= total" x-cloak
             class="rounded-xl border border-blue-100 bg-white/80 px-3 py-2 mb-3 flex items-center justify-between">
            <p class="text-sm font-extrabold text-emerald-700">✅ Tasks — All completed</p>
            <button @click="expanded = true" class="text-xs font-bold text-[#000080] hover:text-blue-900 transition-colors flex items-center gap-1">
                Expand
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
        </div>

        <div class="space-y-2" x-show="expanded || !(total > 0 && completed >= total)">
            @php
                $categoryIcons = [
                    'Health' => '❤️', 'Exercise' => '🏃', 'Nutrition' => '🍎',
                    'Social' => '👥', 'Hygiene' => '🧼', 'Mental' => '🧠',
                    'Medication' => '💊', 'Medical' => '🏥', 'Daily' => '☀️',
                    'Home' => '🏠', 'Other' => '📋',
                ];
            @endphp

            @forelse($checklists->take(5) as $checklist)
                <div x-data="{ expanded: false }"
                     class="checklist-item flex items-start gap-3 p-3 rounded-xl border transition-all duration-300 backdrop-blur-sm {{ $checklist->is_completed ? 'bg-green-50/75 border-green-200 opacity-75' : 'bg-white/75 border-white/70 hover:border-green-200 hover:bg-green-50/40' }}"
                     data-id="{{ $checklist->id }}"
                     data-completed="{{ $checklist->is_completed ? 'true' : 'false' }}">

                    {{-- Checkbox --}}
                    <button
                        @click="toggle({{ $checklist->id }}, $event.currentTarget)"
                        class="checkbox-btn flex-shrink-0 w-7 h-7 rounded-lg border-2 {{ $checklist->is_completed ? 'bg-green-500 border-green-500' : 'bg-white border-gray-300 hover:border-green-400' }} flex items-center justify-center transition-all duration-300 hover:scale-110 active:scale-95 mt-0.5"
                        aria-label="{{ $checklist->is_completed ? 'Mark incomplete' : 'Mark complete' }}: {{ $checklist->task }}">
                        <svg class="check-icon w-4 h-4 text-white transition-all duration-300 {{ $checklist->is_completed ? 'opacity-100 scale-100' : 'opacity-0 scale-0' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </button>

                    {{-- Category Icon --}}
                    <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-sm flex-shrink-0 mt-0.5" aria-hidden="true">
                        {{ $categoryIcons[$checklist->category] ?? '📋' }}
                    </div>

                    {{-- Task Content --}}
                    <div class="flex-grow min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="task-text text-sm font-bold transition-all duration-300 {{ $checklist->is_completed ? 'line-through text-gray-400' : 'text-gray-900' }}">
                                {{ $checklist->task }}
                            </p>
                            @if($checklist->priority === 'high')
                                <span class="text-xs px-1.5 py-0.5 rounded font-bold bg-red-100 text-red-600">High</span>
                            @elseif($checklist->priority === 'medium')
                                <span class="text-xs px-1.5 py-0.5 rounded font-bold bg-yellow-100 text-yellow-700">Med</span>
                            @endif
                        </div>

                        <div class="flex items-center gap-2 mt-1 flex-wrap">
                            <span class="text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded font-medium">
                                {{ $checklist->category ?? 'Other' }}
                            </span>
                            @if($checklist->due_time)
                                <span class="text-xs text-gray-500 font-medium flex items-center gap-0.5">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    {{ \Carbon\Carbon::parse($checklist->due_time)->format('g:i A') }}
                                </span>
                            @endif
                            @if($checklist->is_recurring)
                                <span class="text-xs text-blue-500 font-medium">
                                    🔄 {{ ucfirst($checklist->frequency ?? 'Recurring') }}
                                </span>
                            @endif
                        </div>

                        {{-- Description --}}
                        @if($checklist->description)
                            <div class="mt-1">
                                <div x-show="!expanded" class="text-xs text-gray-500 cursor-pointer hover:text-gray-700" @click="expanded = true">
                                    📝 {{ Str::limit($checklist->description, 60) }}
                                    @if(strlen($checklist->description) > 60)
                                        <span class="text-blue-500 font-bold ml-1 hover:underline">Read more</span>
                                    @endif
                                </div>
                                <div x-show="expanded" class="text-xs text-gray-700 bg-gray-50 p-2 rounded border border-gray-100 mt-1" x-cloak>
                                    {{ $checklist->description }}
                                    <button @click="expanded = false" class="block mt-1 text-blue-500 font-bold hover:underline">Show less</button>
                                </div>
                            </div>
                        @endif
                        @if($checklist->notes && !$checklist->description)
                            <p class="text-xs text-gray-400 mt-1 truncate italic">💬 {{ Str::limit($checklist->notes, 60) }}</p>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-10">
                    <div class="text-5xl mb-3" aria-hidden="true">🎉</div>
                    <p class="text-gray-600 text-sm font-bold">All caught up!</p>
                    <p class="text-gray-400 text-xs mt-1">No tasks for today</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
