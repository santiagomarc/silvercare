<x-dashboard-layout sc>
    <x-slot:title>My Tasks - SilverCare</x-slot:title>

    <x-dashboard-nav
        title="My Tasks"
        subtitle="{{ $checklists->count() }} tasks"
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

                @if($checklists->count() > 0)
                    <span class="sc-badge sc-num text-xs sm:text-sm">
                        {{ $checklists->where('is_completed', true)->count() }} of {{ $checklists->count() }} completed
                    </span>
                @endif
            </div>

            @if(session('success'))
                <div class="sc-flash sc-flash-ok" role="status">
                    {{ session('success') }}
                </div>
            @endif

            @forelse($groupedChecklists as $date => $dayChecklists)
                @php
                    if ($date === 'no-date') {
                        $header = [
                            'label' => 'No Due Date',
                            'isToday' => false,
                            'isPast' => false,
                        ];
                    } else {
                        $header = \App\Presenters\ChecklistPresenter::dateHeader($date);
                    }

                    $cleanLabel = trim(str_replace('📅', '', $header['label']));
                    $isPast = $header['isPast'];
                    $isToday = $header['isToday'];
                @endphp

                <section class="mb-8" aria-label="{{ $cleanLabel }}">
                    <!-- Date Header -->
                    <div class="flex items-center gap-3 mb-4">
                        <div class="flex-grow h-px" style="background-color: var(--sc-line)"></div>
                        <span class="sc-badge sc-num text-xs sm:text-sm font-semibold {{ $isToday ? 'sc-badge-brand' : '' }}">
                            @if($isToday)
                                <span class="sc-dot sc-dot-ok mr-1.5" aria-hidden="true"></span>
                            @elseif($isPast)
                                <span class="sc-dot sc-dot-warn mr-1.5" aria-hidden="true"></span>
                            @endif
                            {{ $cleanLabel }}
                        </span>
                        <div class="flex-grow h-px" style="background-color: var(--sc-line)"></div>
                    </div>

                    <!-- Tasks for this date -->
                    <div class="space-y-3">
                        @foreach($dayChecklists as $checklist)
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
                                $categoryIcon = $categoryIcons[$checklist->category] ?? 'clipboard-list';
                            @endphp

                            <div x-data="checklistPageItem({{ $checklist->id }}, {{ $checklist->is_completed ? 'true' : 'false' }})"
                                 class="sc-task transition-all duration-300"
                                 :class="completed ? 'sc-task-done' : 'sc-task-todo'"
                                 data-id="{{ $checklist->id }}"
                                 data-completed="{{ $checklist->is_completed ? 'true' : 'false' }}">

                                <!-- Toggle Button -->
                                <button type="button"
                                        @click="toggle()"
                                        :disabled="processing"
                                        class="sc-check-btn"
                                        :class="completed ? 'sc-check-on' : 'sc-check-off'"
                                        :aria-label="completed ? 'Mark incomplete: {{ addslashes($checklist->task) }}' : 'Mark complete: {{ addslashes($checklist->task) }}'">
                                    <svg class="sc-i w-3.5 h-3.5 transition-all duration-200"
                                         :class="completed ? 'opacity-100 scale-100' : 'opacity-0 scale-0'"
                                         aria-hidden="true" focusable="false" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </button>

                                <!-- Category Icon Plate -->
                                <span class="sc-plate sc-plate-sm flex-none" aria-hidden="true">
                                    <x-dynamic-component :component="'lucide-' . $categoryIcon" class="sc-i w-5 h-5" aria-hidden="true" />
                                </span>

                                <!-- Task Content -->
                                <div class="flex-grow min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <p class="font-semibold text-base transition-all duration-300"
                                           :class="completed ? 'line-through sc-task-text-done' : ''"
                                           @style(['color:var(--sc-ink)' => ! $checklist->is_completed])>
                                            {{ $checklist->task }}
                                        </p>
                                        @if($checklist->priority === 'high')
                                            <span class="sc-mark sc-mark-alert"><i></i>High</span>
                                        @elseif($checklist->priority === 'medium')
                                            <span class="sc-mark sc-mark-warn"><i></i>Medium</span>
                                        @endif
                                        @if($isPast)
                                            <span x-show="!completed" class="sc-mark sc-mark-alert" x-cloak><i></i>Overdue</span>
                                        @endif
                                        <span x-show="completed" class="sc-mark sc-mark-ok" x-cloak>
                                            <i></i>Done
                                            @if($checklist->completed_at)
                                                <span class="sc-num text-xs ml-1" style="color:var(--sc-muted)">({{ $checklist->completed_at->format('g:i A') }})</span>
                                            @endif
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-2.5 mt-1.5 flex-wrap text-xs">
                                        <span class="sc-mark"><i></i>{{ $checklist->category ?? 'Other' }}</span>
                                        @if($checklist->due_time)
                                            <span class="inline-flex items-center gap-1 sc-num" style="color:var(--sc-muted)">
                                                <x-lucide-clock class="sc-i w-3.5 h-3.5" aria-hidden="true" />
                                                {{ \Carbon\Carbon::parse($checklist->due_time)->format('g:i A') }}
                                            </span>
                                        @endif
                                        @if($checklist->is_recurring)
                                            <span class="inline-flex items-center gap-1" style="color:var(--sc-brand-text)">
                                                <x-lucide-refresh-cw class="sc-i w-3.5 h-3.5" aria-hidden="true" />
                                                {{ ucfirst($checklist->frequency ?? 'Recurring') }}
                                            </span>
                                        @endif
                                    </div>

                                    @if($checklist->notes)
                                        <div class="sc-card-quiet p-3 mt-2 text-xs sm:text-sm" style="color:var(--sc-body)">
                                            <p class="inline-flex items-start gap-1.5">
                                                <x-lucide-notebook-pen class="sc-i w-4 h-4 mt-0.5 flex-shrink-0" aria-hidden="true" />
                                                <span>{{ $checklist->notes }}</span>
                                            </p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @empty
                <div class="sc-empty">
                    <span class="sc-plate sc-plate-ok" aria-hidden="true">
                        <x-lucide-circle-check class="sc-i w-6 h-6" aria-hidden="true" />
                    </span>
                    <h2 class="font-semibold text-base sm:text-lg" style="color:var(--sc-ink)">No tasks right now</h2>
                    <p class="max-w-md mx-auto text-sm" style="color:var(--sc-body)">
                        Your caregiver hasn't added any tasks for you yet. Enjoy your free time!
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
