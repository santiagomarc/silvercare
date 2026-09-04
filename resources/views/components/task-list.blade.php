{{-- ============================================================
     TaskList — today's checklist, five at a time.

     The hook classes (`checklist-item`, `checkbox-btn`, `check-icon`,
     `task-text`) are what checklist-tracker.js selects on. Leave them.
     A done row carries three signals at once: the box is ticked, the
     text is struck through, and the row is tinted.
     ============================================================ --}}

<div x-data="checklistTracker({{ $completedCount }}, {{ $totalCount }})"
     class="sc-card p-6"
     role="region"
     aria-label="Today's tasks">

    <div class="flex flex-wrap justify-between items-center gap-x-3 gap-y-2 mb-4">
        <div>
            <h3 class="sc-h3">Today's Tasks</h3>
            <p class="sc-num" style="color:var(--sc-muted)">
                <span x-text="completed"></span>/<span x-text="total"></span> completed
            </p>
        </div>
        <a href="{{ route('elderly.checklists') }}" class="sc-textlink inline-flex items-center gap-1">
            See All
            <x-lucide-chevron-right class="sc-i w-4 h-4" aria-hidden="true" />
        </a>
    </div>

    {{-- Progress --}}
    <div class="sc-progress mb-4">
        <div class="sc-progress-fill sc-progress-fill-ok"
             role="progressbar"
             :aria-valuenow="progress"
             aria-valuemin="0"
             aria-valuemax="100"
             :aria-label="'Tasks: ' + completed + ' of ' + total + ' completed'"
             :style="'width:' + progress + '%'"></div>
    </div>

    {{-- Auto-collapsed summary once all tasks are done --}}
    <div x-show="!expanded && total > 0 && completed >= total" x-cloak
         class="sc-card-quiet px-4 py-2.5 mb-3 flex items-center justify-between gap-3">
        <p class="font-semibold inline-flex items-center gap-1.5" style="color:var(--sc-ok)">
            <x-lucide-circle-check class="sc-i w-5 h-5" aria-hidden="true" />
            Tasks - All completed
        </p>
        <button type="button" @click="expanded = true" class="sc-textlink inline-flex items-center gap-1">
            Expand
            <x-lucide-chevron-down class="sc-i w-4 h-4" aria-hidden="true" />
        </button>
    </div>

    <div class="space-y-2" x-show="expanded || !(total > 0 && completed >= total)">
        @php
            $categoryIcons = [
                'Health' => 'heart-pulse',
                'Exercise' => 'footprints',
                'Nutrition' => 'apple',
                'Social' => 'users',
                'Hygiene' => 'shower-head',
                'Mental' => 'brain',
                'Medication' => 'pill',
                'Medical' => 'hospital',
                'Daily' => 'sun',
                'Home' => 'house',
                'Other' => 'clipboard-list',
            ];
        @endphp

        @forelse($checklists->take(5) as $checklist)
            <div x-data="{ expanded: false }"
                 class="checklist-item sc-task {{ $checklist->is_completed ? 'sc-task-done' : 'sc-task-todo' }}"
                 data-id="{{ $checklist->id }}"
                 data-completed="{{ $checklist->is_completed ? 'true' : 'false' }}">

                {{-- Tick box --}}
                <button
                    type="button"
                    @click="toggle({{ $checklist->id }}, $event.currentTarget)"
                    class="checkbox-btn sc-check-btn {{ $checklist->is_completed ? 'sc-check-on' : 'sc-check-off' }}"
                    aria-label="{{ $checklist->is_completed ? 'Mark incomplete' : 'Mark complete' }}: {{ $checklist->task }}">
                    <svg class="check-icon sc-i w-4 h-4 transition-all duration-300 {{ $checklist->is_completed ? 'opacity-100 scale-100' : 'opacity-0 scale-0' }}" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                    </svg>
                </button>

                {{-- Category --}}
                @php
                    $categoryIcon = $categoryIcons[$checklist->category] ?? 'clipboard-list';
                @endphp
                <span class="sc-plate sc-plate-sm" aria-hidden="true">
                    <x-dynamic-component :component="'lucide-' . $categoryIcon" class="sc-i w-5 h-5" />
                </span>

                {{-- The task --}}
                <div class="flex-grow min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <p class="task-text font-semibold transition-all duration-300 {{ $checklist->is_completed ? 'line-through sc-task-text-done' : '' }}"
                           @style(['color:var(--sc-ink)' => ! $checklist->is_completed])>
                            {{ $checklist->task }}
                        </p>
                        @if($checklist->priority === 'high')
                            <span class="sc-badge sc-badge-alert">High</span>
                        @elseif($checklist->priority === 'medium')
                            <span class="sc-badge sc-badge-warn">Med</span>
                        @endif
                    </div>

                    <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                        <span class="sc-badge">
                            {{ $checklist->category ?? 'Other' }}
                        </span>
                        @if($checklist->due_time)
                            <span class="inline-flex items-center gap-1 sc-num" style="color:var(--sc-muted)">
                                <x-lucide-clock class="sc-i w-4 h-4" aria-hidden="true" />
                                {{ \Carbon\Carbon::parse($checklist->due_time)->format('g:i A') }}
                            </span>
                        @endif
                        @if($checklist->is_recurring)
                            <span class="inline-flex items-center gap-1" style="color:var(--sc-brand-text)">
                                <x-lucide-refresh-cw class="sc-i w-4 h-4" aria-hidden="true" />
                                {{ ucfirst($checklist->frequency ?? 'Recurring') }}
                            </span>
                        @endif
                    </div>

                    {{-- Description --}}
                    @if($checklist->description)
                        <div class="mt-1.5">
                            <button type="button" x-show="!expanded" class="text-left inline-flex items-start gap-1.5" style="color:var(--sc-muted)" @click="expanded = true">
                                <x-lucide-notebook-pen class="sc-i w-4 h-4 mt-1" aria-hidden="true" />
                                <span>{{ Str::limit($checklist->description, 60) }}</span>
                                @if(strlen($checklist->description) > 60)
                                    <span class="sc-textlink">Read more</span>
                                @endif
                            </button>
                            <div x-show="expanded" class="sc-card-quiet p-3 mt-1" style="color:var(--sc-body)" x-cloak>
                                {{ $checklist->description }}
                                <button type="button" @click="expanded = false" class="sc-textlink block mt-1">Show less</button>
                            </div>
                        </div>
                    @endif
                    @if($checklist->notes && !$checklist->description)
                        <p class="mt-1.5 truncate inline-flex items-center gap-1.5" style="color:var(--sc-muted)">
                            <x-lucide-message-circle class="sc-i w-4 h-4" aria-hidden="true" />
                            <span class="sc-quote">{{ Str::limit($checklist->notes, 60) }}</span>
                        </p>
                    @endif
                </div>
            </div>
        @empty
            <div class="sc-empty">
                <span class="sc-plate sc-plate-ok" aria-hidden="true">
                    <x-lucide-circle-check class="sc-i w-6 h-6" aria-hidden="true" />
                </span>
                <p class="font-semibold" style="color:var(--sc-ink)">All caught up</p>
                <p>There are no tasks on your list for today.</p>
            </div>
        @endforelse
    </div>
</div>
