<x-dashboard-layout sc>
    <x-slot:title>Edit Task - SilverCare</x-slot:title>

    <x-dashboard-nav
        title="Edit Task"
        subtitle="{{ $checklist->task }}"
        role="caregiver"
        :show-back="true"
    />

    <main id="main-content" class="sc-app-main">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-12 py-6 space-y-6">
            
            <x-flash-messages />

            {{-- Back Navigation --}}
            <div class="flex justify-start">
                <a href="{{ route('caregiver.checklists.index', ['elderly' => $selectedElderly->id]) }}" class="sc-btn sc-btn-ghost inline-flex items-center gap-1.5 text-sm">
                    <x-lucide-arrow-left class="sc-i w-4 h-4" aria-hidden="true" />
                    <span>{{ __('Back to Checklists') }}</span>
                </a>
            </div>

            <form method="POST" action="{{ route('caregiver.checklists.update', $checklist) }}" class="space-y-6">
                @csrf
                @method('PUT')
                <input type="hidden" name="elderly_id" value="{{ $selectedElderly->id }}">

                {{-- CARD 1: Task Details --}}
                <div class="sc-card p-5 sm:p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="sc-plate sc-plate-sm flex-shrink-0">
                            <x-lucide-clipboard-check class="sc-i w-5 h-5 text-[var(--sc-brand)]" aria-hidden="true" />
                        </div>
                        <h2 class="text-lg sm:text-xl font-bold text-[var(--sc-ink)]">{{ __('Task Details') }}</h2>
                    </div>

                    {{-- Task Name --}}
                    <div class="sc-field mb-5">
                        <x-input-label for="task" :value="__('Task')" required />
                        <x-text-input type="text" name="task" id="task" :value="old('task', $checklist->task)" placeholder="e.g. Take afternoon walk, Drink water, Do stretching exercises" required />
                        <x-input-error field="task" />
                    </div>

                    {{-- Category Selection --}}
                    <div>
                        <span class="block text-xs font-bold uppercase tracking-wider text-[var(--sc-muted)] mb-3">{{ __('Category') }} <span class="text-[var(--sc-alert)]">*</span></span>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            @php
                                $categories = [
                                    'Health' => 'heart-pulse',
                                    'Exercise' => 'activity',
                                    'Nutrition' => 'apple',
                                    'Social' => 'users',
                                    'Hygiene' => 'sparkles',
                                    'Mental' => 'brain',
                                    'Medication' => 'pill',
                                    'Other' => 'clipboard-list',
                                ];
                                $selectedCategory = old('category', $checklist->category);
                            @endphp
                            @foreach($categories as $catName => $iconName)
                                <label class="sc-choice flex-col items-center text-center p-3 sm:p-4">
                                    <input type="radio" name="category" value="{{ $catName }}" class="sr-only" {{ $selectedCategory == $catName ? 'checked' : '' }} required>
                                    <div class="sc-plate sc-plate-sm mb-2">
                                        @switch($iconName)
                                            @case('heart-pulse')
                                                <x-lucide-heart-pulse class="sc-i w-5 h-5 text-[var(--sc-brand)]" aria-hidden="true" />
                                                @break
                                            @case('activity')
                                                <x-lucide-activity class="sc-i w-5 h-5 text-[var(--sc-brand)]" aria-hidden="true" />
                                                @break
                                            @case('apple')
                                                <x-lucide-apple class="sc-i w-5 h-5 text-[var(--sc-brand)]" aria-hidden="true" />
                                                @break
                                            @case('users')
                                                <x-lucide-users class="sc-i w-5 h-5 text-[var(--sc-brand)]" aria-hidden="true" />
                                                @break
                                            @case('sparkles')
                                                <x-lucide-sparkles class="sc-i w-5 h-5 text-[var(--sc-brand)]" aria-hidden="true" />
                                                @break
                                            @case('brain')
                                                <x-lucide-brain class="sc-i w-5 h-5 text-[var(--sc-brand)]" aria-hidden="true" />
                                                @break
                                            @case('pill')
                                                <x-lucide-pill class="sc-i w-5 h-5 text-[var(--sc-brand)]" aria-hidden="true" />
                                                @break
                                            @default
                                                <x-lucide-clipboard-list class="sc-i w-5 h-5 text-[var(--sc-brand)]" aria-hidden="true" />
                                        @endswitch
                                    </div>
                                    <span class="text-xs font-bold text-[var(--sc-ink)]">{{ $catName }}</span>
                                </label>
                            @endforeach
                        </div>
                        <x-input-error field="category" />
                    </div>
                </div>

                {{-- CARD 2: Schedule --}}
                <div class="sc-card p-5 sm:p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="sc-plate sc-plate-sm flex-shrink-0">
                            <x-lucide-calendar class="sc-i w-5 h-5 text-[var(--sc-brand)]" aria-hidden="true" />
                        </div>
                        <h2 class="text-lg sm:text-xl font-bold text-[var(--sc-ink)]">{{ __('Schedule') }}</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                        {{-- Due Date --}}
                        <div class="sc-field">
                            <x-input-label for="due_date" :value="__('Due Date')" required />
                            <x-text-input type="text" name="due_date" id="due_date" :value="old('due_date', $checklist->due_date?->format('Y-m-d'))" placeholder="Select date..." required />
                            <x-input-error field="due_date" />
                        </div>

                        {{-- Due Time --}}
                        <div class="sc-field">
                            <x-input-label for="due_time" :value="__('Due Time (Optional)')" />
                            <x-text-input type="text" name="due_time" id="due_time" :value="old('due_time', $checklist->due_time ? \Carbon\Carbon::parse($checklist->due_time)->format('H:i') : '')" placeholder="Select time..." />
                            <x-input-error field="due_time" />
                        </div>
                    </div>

                    {{-- Priority --}}
                    <div>
                        <span class="block text-xs font-bold uppercase tracking-wider text-[var(--sc-muted)] mb-3">{{ __('Priority') }}</span>
                        <div class="flex flex-wrap gap-3">
                            @php
                                $priorities = ['low' => __('Low'), 'medium' => __('Medium'), 'high' => __('High')];
                                $selectedPriority = old('priority', $checklist->priority ?? 'medium');
                            @endphp
                            @foreach($priorities as $val => $label)
                                <label class="sc-choice !py-2.5 !px-4 cursor-pointer items-center">
                                    <input type="radio" name="priority" value="{{ $val }}" class="sr-only" {{ $selectedPriority == $val ? 'checked' : '' }}>
                                    <span class="text-sm font-semibold text-[var(--sc-ink)]">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        <x-input-error field="priority" />
                    </div>
                </div>

                {{-- CARD 3: Status & Notes --}}
                <div class="sc-card p-5 sm:p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="sc-plate sc-plate-sm flex-shrink-0">
                            <x-lucide-check-circle-2 class="sc-i w-5 h-5 text-[var(--sc-brand)]" aria-hidden="true" />
                        </div>
                        <h2 class="text-lg sm:text-xl font-bold text-[var(--sc-ink)]">{{ __('Status & Notes') }}</h2>
                    </div>

                    {{-- Completion Status --}}
                    <div class="sc-card-quiet p-4 rounded-xl mb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <span class="block text-sm font-bold text-[var(--sc-ink)]">{{ __('Task Status') }}</span>
                            <span class="block text-xs text-[var(--sc-muted)] mt-0.5">
                                @if($checklist->is_completed)
                                    {{ __('Completed on') }} {{ $checklist->completed_at?->format('M d, Y \a\t h:i A') }}
                                @else
                                    {{ __('Not yet completed') }}
                                @endif
                            </span>
                        </div>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_completed" value="1" class="rounded border-[var(--sc-line-strong)] text-[var(--sc-brand)] focus:ring-[var(--sc-brand)] w-5 h-5" {{ old('is_completed', $checklist->is_completed) ? 'checked' : '' }}>
                            <span class="text-sm font-semibold text-[var(--sc-ink)]">{{ __('Completed') }}</span>
                        </label>
                    </div>

                    {{-- Notes --}}
                    <div class="sc-field">
                        <x-input-label for="notes" :value="__('Additional Notes (Optional)')" />
                        <textarea name="notes" id="notes" rows="3" class="sc-textarea w-full" placeholder="Any additional notes or reminders...">{{ old('notes', $checklist->notes) }}</textarea>
                        <x-input-error field="notes" />
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('caregiver.checklists.index', ['elderly' => $selectedElderly->id]) }}" class="sc-btn sc-btn-ghost">
                        {{ __('Cancel') }}
                    </a>
                    <button type="submit" class="sc-btn sc-btn-primary inline-flex items-center gap-2">
                        <span>{{ __('Update Task') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof window.flatpickr === 'function') {
                window.flatpickr("#due_date", {
                    dateFormat: "Y-m-d",
                    allowInput: true
                });
                window.flatpickr("#due_time", {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: "H:i",
                    altInput: true,
                    altFormat: "h:i K",
                    allowInput: true,
                    onReady: function(selectedDates, dateStr, instance) {
                        if (instance.altInput) {
                            instance.altInput.setAttribute('aria-label', 'Due Time');
                        }
                    }
                });
            }
        });
    </script>
</x-dashboard-layout>
