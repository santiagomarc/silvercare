<x-dashboard-layout sc>
    <x-slot:title>Daily Checklists - SilverCare</x-slot:title>

    <x-dashboard-nav
        title="Daily Checklists"
        subtitle="{{ now()->format('l, F j, Y') }}"
        role="caregiver"
        :show-back="true"
    />

    <main id="main-content" class="sc-app-main">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-12 py-6 space-y-6">
            
            <x-flash-messages />

            @if(($elderlyPatients ?? collect())->count() > 1)
                <div class="sc-card p-4">
                    <form method="GET" action="{{ route('caregiver.checklists.index') }}" class="flex flex-col sm:flex-row sm:items-center gap-3">
                        <label for="elderly" class="text-sm font-bold text-[var(--sc-ink)]">{{ __('Managing tasks for') }}</label>
                        <select
                            id="elderly"
                            name="elderly"
                            onchange="this.form.submit()"
                            class="rounded-xl border border-[var(--sc-line-strong)] bg-[var(--sc-surface)] px-3 py-2 text-sm font-semibold text-[var(--sc-ink)] focus:border-[var(--sc-brand)] focus:ring-1 focus:ring-[var(--sc-brand)]"
                        >
                            @foreach(($elderlyPatients ?? collect()) as $patient)
                                <option value="{{ $patient->id }}" @selected($selectedElderly && $selectedElderly->id === $patient->id)>
                                    {{ $patient->user?->name ?? ('Patient #' . $patient->id) }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>
            @endif

            {{-- Header with Add Button --}}
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-bold text-[var(--sc-ink)]">{{ __('Checklists') }}</h2>
                    <p class="text-sm text-[var(--sc-muted)] font-medium mt-0.5">{{ __('Managing') }} {{ $selectedElderly->user?->name ?? __('selected patient') }}</p>
                </div>
                <a href="{{ route('caregiver.checklists.create', ['elderly' => $selectedElderly->id]) }}" class="sc-btn sc-btn-primary inline-flex items-center gap-2">
                    <x-lucide-plus class="sc-i w-4 h-4" aria-hidden="true" />
                    <span>{{ __('Add Task') }}</span>
                </a>
            </div>

            {{-- Progress Bar Card --}}
            @php
                $total = $checklists->count();
                $completed = $checklists->where('is_completed', true)->count();
                $progress = $total > 0 ? round(($completed / $total) * 100) : 0;
            @endphp
            <div class="sc-card p-5 sm:p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 class="text-base sm:text-lg font-bold text-[var(--sc-ink)]">{{ __('Overall Progress') }}</h2>
                        <p class="text-sm text-[var(--sc-muted)] font-medium sc-num mt-0.5">{{ $completed }} {{ __('of') }} {{ $total }} {{ __('tasks completed') }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex-1 sm:w-48 bg-[var(--sc-surface-3)] rounded-full h-3 overflow-hidden border border-[var(--sc-line)]">
                            <div class="bg-[var(--sc-brand)] h-full rounded-full transition-all duration-500" style="width: {{ $progress }}%"></div>
                        </div>
                        <span class="sc-num font-bold text-lg text-[var(--sc-ink)]">{{ $progress }}%</span>
                    </div>
                </div>
            </div>

            {{-- Checklist Items --}}
            <div class="sc-card overflow-hidden">
                <ul class="divide-y divide-[var(--sc-line)]">
                    @forelse($checklists as $checklist)
                        <li class="hover:bg-[var(--sc-surface-2)] transition-colors duration-150">
                            <div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
                                <div class="flex items-start gap-3 min-w-0 flex-1">
                                    {{-- Toggle Checkbox --}}
                                    <div class="flex-shrink-0 mt-0.5" x-data="{
                                        completed: {{ $checklist->is_completed ? 'true' : 'false' }},
                                        processing: false,
                                        async toggle() {
                                            if(this.processing) return;
                                            this.processing = true;
                                            const prev = this.completed;
                                            this.completed = !this.completed;
                                            
                                            try {
                                                const res = await window.fetch('{{ route('caregiver.checklists.toggle', $checklist) }}', {
                                                    method: 'POST',
                                                    headers: {
                                                        'Content-Type': 'application/json',
                                                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                                        'Accept': 'application/json'
                                                    },
                                                    body: JSON.stringify({ elderly_id: {{ $selectedElderly->id }} })
                                                });
                                                const data = await res.json();
                                                if(!res.ok) throw new Error(data.message || 'Failed');
                                                this.completed = Boolean(data.is_completed);
                                            } catch(e) {
                                                this.completed = prev;
                                                if (typeof window.scToast === 'function') {
                                                    window.scToast('Failed to update task.', 'error', { elderly: false });
                                                } else if (window.Alpine && window.Alpine.store('toast')) {
                                                    window.Alpine.store('toast').error('Failed to update task.');
                                                }
                                            } finally {
                                                this.processing = false;
                                            }
                                        }
                                    }">
                                        <button
                                            type="button"
                                            @click="toggle()"
                                            :disabled="processing"
                                            :class="completed ? 'sc-check-on' : 'sc-check-off'"
                                            class="sc-check-btn"
                                            role="checkbox"
                                            :aria-checked="completed.toString()"
                                            aria-label="{{ __('Toggle task') }}: {{ $checklist->task }}"
                                        >
                                            <x-lucide-check class="sc-i w-4 h-4" aria-hidden="true" x-show="completed" x-cloak />
                                        </button>
                                    </div>

                                    {{-- Category Icon Plate --}}
                                    <div class="sc-plate sc-plate-sm flex-shrink-0 mt-0.5">
                                        @switch($checklist->category)
                                            @case('Health')
                                                <x-lucide-heart-pulse class="sc-i w-4 h-4 text-[var(--sc-brand)]" aria-hidden="true" />
                                                @break
                                            @case('Exercise')
                                                <x-lucide-activity class="sc-i w-4 h-4 text-[var(--sc-brand)]" aria-hidden="true" />
                                                @break
                                            @case('Nutrition')
                                                <x-lucide-apple class="sc-i w-4 h-4 text-[var(--sc-brand)]" aria-hidden="true" />
                                                @break
                                            @case('Social')
                                                <x-lucide-users class="sc-i w-4 h-4 text-[var(--sc-brand)]" aria-hidden="true" />
                                                @break
                                            @case('Hygiene')
                                                <x-lucide-sparkles class="sc-i w-4 h-4 text-[var(--sc-brand)]" aria-hidden="true" />
                                                @break
                                            @case('Mental')
                                                <x-lucide-brain class="sc-i w-4 h-4 text-[var(--sc-brand)]" aria-hidden="true" />
                                                @break
                                            @case('Medication')
                                                <x-lucide-pill class="sc-i w-4 h-4 text-[var(--sc-brand)]" aria-hidden="true" />
                                                @break
                                            @default
                                                <x-lucide-clipboard-list class="sc-i w-4 h-4 text-[var(--sc-brand)]" aria-hidden="true" />
                                        @endswitch
                                    </div>

                                    {{-- Task Details --}}
                                    <div class="flex-grow min-w-0" :class="completed ? 'opacity-70' : ''">
                                        <div class="flex flex-wrap items-center gap-2 mb-1">
                                            <h3 class="text-base font-bold text-[var(--sc-ink)] leading-snug break-words" :class="completed ? 'line-through text-[var(--sc-muted)]' : ''">{{ $checklist->task }}</h3>
                                            @if($checklist->priority == 'high')
                                                <span class="sc-mark sc-mark-alert text-xs"><i></i>{{ __('High') }}</span>
                                            @elseif($checklist->priority == 'low')
                                                <span class="sc-mark sc-mark-subtle text-xs"><i></i>{{ __('Low') }}</span>
                                            @endif
                                            
                                            @if($checklist->is_recurring)
                                                <span class="sc-mark sc-mark-brand text-xs">
                                                    <i></i>{{ ucfirst($checklist->frequency ?? 'Recurring') }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="flex flex-wrap items-center gap-2 sm:gap-3 text-xs text-[var(--sc-muted)] font-medium">
                                            <span class="px-2 py-0.5 rounded-md bg-[var(--sc-surface-3)] text-[var(--sc-muted)] border border-[var(--sc-line)] font-semibold">{{ $checklist->category }}</span>
                                            @if($checklist->due_date)
                                                <span class="flex items-center gap-1 sc-num">
                                                    <x-lucide-calendar class="sc-i w-3.5 h-3.5" aria-hidden="true" />
                                                    <span>{{ $checklist->due_date->format('M d') }}</span>
                                                </span>
                                            @endif
                                            @if($checklist->due_time)
                                                <span class="flex items-center gap-1 sc-num">
                                                    <x-lucide-clock class="sc-i w-3.5 h-3.5" aria-hidden="true" />
                                                    <span>{{ \Carbon\Carbon::parse($checklist->due_time)->format('g:i A') }}</span>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- Meta & Actions Row --}}
                                <div class="flex items-center justify-between sm:justify-end gap-3 pl-12 sm:pl-0 flex-shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-[var(--sc-line)]">
                                    {{-- Status Badge --}}
                                    @if(!$checklist->is_completed && $checklist->due_date)
                                        @php
                                            $isOverdue = $checklist->due_date->isPast() && !$checklist->due_date->isToday();
                                            $isToday = $checklist->due_date->isToday();
                                        @endphp
                                        <div>
                                            @if($isOverdue)
                                                <span class="sc-mark sc-mark-alert text-xs">
                                                    <i></i>{{ __('Overdue') }}
                                                </span>
                                            @elseif($isToday)
                                                <span class="sc-mark sc-mark-warn text-xs">
                                                    <i></i>{{ __('Due Today') }}
                                                </span>
                                            @endif
                                        </div>
                                    @endif

                                    @if($checklist->is_completed)
                                        <div>
                                            <span class="sc-mark sc-mark-ok text-xs">
                                                <i></i>{{ __('Done') }}
                                            </span>
                                        </div>
                                    @endif

                                    {{-- Actions --}}
                                    <div class="flex items-center gap-1 ml-auto sm:ml-0">
                                        <a href="{{ route('caregiver.checklists.edit', ['checklist' => $checklist, 'elderly' => $selectedElderly->id]) }}"
                                           class="sc-btn sc-btn-ghost !p-2 min-h-touch min-w-touch inline-flex items-center justify-center text-[var(--sc-ink)]"
                                           aria-label="{{ __('Edit task') }}: {{ $checklist->task }}">
                                            <x-lucide-pencil class="sc-i w-4 h-4 text-[var(--sc-ink)]" aria-hidden="true" />
                                        </a>
                                        <form
                                            action="{{ route('caregiver.checklists.destroy', $checklist) }}"
                                            method="POST"
                                            class="inline"
                                            data-confirm="Are you sure you want to delete this task?"
                                            data-confirm-title="Delete checklist task?"
                                            data-confirm-icon="warning"
                                            data-confirm-confirm-text="Delete task"
                                            data-confirm-cancel-text="Keep task"
                                            data-confirm-elderly="false"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="elderly_id" value="{{ $selectedElderly->id }}">
                                            <button
                                                type="submit"
                                                class="sc-btn sc-btn-ghost !p-2 min-h-touch min-w-touch inline-flex items-center justify-center text-[var(--sc-alert)] hover:bg-[var(--sc-alert-tint)]"
                                                aria-label="{{ __('Delete task') }}: {{ $checklist->task }}"
                                            >
                                                <x-lucide-trash-2 class="sc-i w-4 h-4 text-[var(--sc-alert)]" aria-hidden="true" />
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="py-12">
                            <div class="sc-empty">
                                <div class="sc-plate mb-3">
                                    <x-lucide-clipboard-list class="sc-i w-6 h-6 text-[var(--sc-muted)]" aria-hidden="true" />
                                </div>
                                <h3 class="sc-h3">{{ __('No Tasks Yet') }}</h3>
                                <p style="color:var(--sc-muted)">{{ __('Get started by adding a daily task for your patient.') }}</p>
                                <a href="{{ route('caregiver.checklists.create', ['elderly' => $selectedElderly->id]) }}" class="sc-btn sc-btn-primary mt-4 inline-flex items-center gap-2">
                                    <x-lucide-plus class="sc-i w-4 h-4" aria-hidden="true" />
                                    <span>{{ __('Add Task') }}</span>
                                </a>
                            </div>
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </main>

</x-dashboard-layout>
