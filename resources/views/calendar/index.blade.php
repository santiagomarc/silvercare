<x-dashboard-layout sc>
    <x-slot:title>Schedule - SilverCare</x-slot:title>

    <div id="main-content" x-data="calendarSchedulerForm()" x-init="initDateTimePicker()">
        <x-dashboard-nav
            title="Schedule"
            subtitle="Manage your health appointments and reminders"
            role="elderly"
            :unread-notifications="$unreadNotifications ?? 0"
        />

        <main class="sc-app-main">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
                <x-flash-messages />

                {{-- Top bar: Back to Dashboard --}}
                <div class="flex justify-end">
                    <a href="{{ route('dashboard') }}" class="sc-btn sc-btn-ghost inline-flex items-center gap-1.5 text-sm">
                        <x-lucide-arrow-left class="sc-i w-4 h-4" aria-hidden="true" />
                        <span>{{ __('Back to Dashboard') }}</span>
                    </a>
                </div>

                {{-- Main 2-column layout --}}
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

                    {{-- LEFT: Date card + History button --}}
                    <div class="lg:col-span-4 flex flex-col gap-4">

                        {{-- Date + Quick Tip card --}}
                        <div class="sc-card p-6 sm:p-7 flex flex-col justify-between gap-6">
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider text-[var(--sc-ink-muted)]">{{ __('TODAY') }}</span>
                                <div class="text-5xl sm:text-6xl font-bold tracking-tight text-[var(--sc-ink)] mt-2 sc-num">{{ now()->format('d') }}</div>
                                <p class="text-xl font-bold text-[var(--sc-ink)] mt-1">{{ now()->format('l') }}</p>
                                <p class="text-sm font-semibold text-[var(--sc-ink-muted)] mt-0.5">{{ now()->format('F Y') }}</p>
                            </div>

                            <div class="sc-card-quiet rounded-2xl p-4 sm:p-5 w-full text-left">
                                <div class="flex items-start gap-3">
                                    <div class="sc-plate sc-plate-sm flex-shrink-0 mt-0.5">
                                        <x-lucide-info class="sc-i w-4 h-4 text-[var(--sc-brand)]" aria-hidden="true" />
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-wider text-[var(--sc-ink-muted)] mb-1">{{ __('Quick Tip') }}</p>
                                        <p class="text-xs sm:text-sm font-medium text-[var(--sc-ink)] leading-relaxed">
                                            {{ __('Staying organized helps reduce stress. Check your schedule and tasks daily!') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- History button --}}
                        @if($pastEvents->isNotEmpty())
                            <button
                                type="button"
                                @click="showHistory = true"
                                class="sc-card sc-lift w-full flex items-center justify-between p-4 transition-all"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="sc-plate sc-plate-sm flex-shrink-0">
                                        <x-lucide-history class="sc-i w-4 h-4 text-[var(--sc-ink)]" aria-hidden="true" />
                                    </div>
                                    <div class="text-left">
                                        <p class="text-sm font-bold text-[var(--sc-ink)]">{{ __('Past Events') }}</p>
                                        <p class="text-xs font-semibold text-[var(--sc-ink-muted)] sc-num">
                                            {{ $pastEvents->count() }} {{ Str::plural('event', $pastEvents->count()) }} {{ __('completed') }}
                                        </p>
                                    </div>
                                </div>
                                <x-lucide-chevron-right class="sc-i w-4 h-4 text-[var(--sc-ink-muted)]" aria-hidden="true" />
                            </button>
                        @endif
                    </div>

                    {{-- RIGHT: Upcoming events list or empty state --}}
                    <div class="lg:col-span-8">
                        @if($events->isEmpty())
                            <div class="sc-card p-10 text-center flex flex-col items-center justify-center">
                                <div class="sc-plate mb-4">
                                    <x-lucide-calendar class="sc-i w-6 h-6" aria-hidden="true" />
                                </div>
                                <h2 class="sc-h3 mb-2">{{ __('No Upcoming Events') }}</h2>
                                <p class="text-sm text-[var(--sc-ink-muted)] max-w-sm mb-6">
                                    {{ __('Your schedule is clear for now. Add an entry to plan ahead.') }}
                                </p>
                                <button
                                    type="button"
                                    @click="openModal()"
                                    class="sc-btn sc-btn-primary inline-flex items-center gap-2"
                                >
                                    <x-lucide-plus class="sc-i w-4 h-4" aria-hidden="true" />
                                    <span>{{ __('Add New Entry') }}</span>
                                </button>
                            </div>
                        @else
                            <div class="sc-card p-5 sm:p-7">
                                <div class="flex items-center justify-between gap-4 mb-6 pb-4 border-b border-[var(--sc-border)]">
                                    <h2 class="text-xl font-bold text-[var(--sc-ink)]">
                                        {{ __('Upcoming Events') }}
                                    </h2>
                                    <button
                                        type="button"
                                        @click="openModal()"
                                        class="sc-btn sc-btn-primary sc-btn-sm inline-flex items-center gap-1.5"
                                    >
                                        <x-lucide-plus class="sc-i w-4 h-4" aria-hidden="true" />
                                        <span>{{ __('Add Entry') }}</span>
                                    </button>
                                </div>

                                <div class="space-y-3">
                                    @foreach($events as $event)
                                        <div class="sc-card sc-lift flex items-center justify-between p-4 sm:p-5 gap-4">
                                            <div class="flex items-center gap-4 min-w-0 flex-1">
                                                {{-- Date badge --}}
                                                <div class="sc-plate sc-plate-sm flex-col rounded-xl flex-shrink-0 text-center">
                                                    <span class="text-[10px] font-bold uppercase tracking-wider text-[var(--sc-ink-muted)] leading-none">{{ \Carbon\Carbon::parse($event->start_time)->format('M') }}</span>
                                                    <span class="text-base font-bold text-[var(--sc-ink)] leading-none mt-1 sc-num">{{ \Carbon\Carbon::parse($event->start_time)->format('d') }}</span>
                                                </div>

                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                                                        @if($event->type === 'Appointment')
                                                            <span class="sc-mark sc-mark-alert"><i></i>{{ __('Appointment') }}</span>
                                                        @elseif($event->type === 'Medication')
                                                            <span class="sc-mark sc-mark-ok"><i></i>{{ __('Medication') }}</span>
                                                        @else
                                                            <span class="sc-mark sc-mark-brand"><i></i>{{ $event->type }}</span>
                                                        @endif
                                                        <span class="text-xs font-semibold text-[var(--sc-ink-muted)] flex items-center gap-1 sc-num">
                                                            <x-lucide-clock class="sc-i w-3.5 h-3.5" aria-hidden="true" />
                                                            <span>{{ \Carbon\Carbon::parse($event->start_time)->format('h:i A') }}</span>
                                                        </span>
                                                    </div>
                                                    <h3 class="text-base font-bold text-[var(--sc-ink)] truncate">{{ $event->title }}</h3>
                                                    @if($event->description)
                                                        <p class="text-sm text-[var(--sc-ink-muted)] truncate mt-0.5">{{ $event->description }}</p>
                                                    @endif
                                                </div>
                                            </div>

                                            {{-- Delete button form --}}
                                            <form
                                                method="POST"
                                                action="{{ route('calendar.destroy', $event->id) }}"
                                                class="flex-shrink-0"
                                                data-confirm="Delete this event?"
                                                data-confirm-title="Delete calendar entry?"
                                                data-confirm-icon="warning"
                                                data-confirm-confirm-text="Delete event"
                                                data-confirm-cancel-text="Keep event"
                                                data-confirm-elderly="true"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="sc-btn sc-btn-ghost !p-2 text-[var(--sc-ink-muted)] hover:text-[var(--sc-alert)]"
                                                    title="{{ __('Delete event') }}"
                                                    aria-label="{{ __('Delete event') }}: {{ $event->title }}"
                                                >
                                                    <x-lucide-trash-2 class="sc-i w-4 h-4" aria-hidden="true" />
                                                </button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </main>

        {{-- Add New Entry Modal --}}
        <div
            class="sc-scrim flex items-center justify-center p-4"
            x-cloak
            x-show="showModal"
            x-transition.opacity.duration.200ms
            @click.self="closeModal()"
        >
            <div
                class="sc-dialog max-w-lg w-full"
                x-show="showModal"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
            >
                <div class="flex items-center justify-between pb-4 mb-6 border-b border-[var(--sc-border)]">
                    <h3 class="text-2xl font-bold text-[var(--sc-ink)] tracking-tight">{{ __('New Entry') }}</h3>
                    <button
                        type="button"
                        @click="closeModal()"
                        class="sc-btn sc-btn-ghost !p-2"
                        aria-label="{{ __('Close dialog') }}"
                    >
                        <x-lucide-x class="sc-i w-5 h-5" aria-hidden="true" />
                    </button>
                </div>

                @if($errors->has('start_time'))
                    <div class="mb-4 sc-flash-alert p-3 rounded-xl text-sm font-semibold flex items-center gap-2">
                        <x-lucide-alert-circle class="sc-i w-4 h-4" aria-hidden="true" />
                        <span>{{ $errors->first('start_time') }}</span>
                    </div>
                @endif

                <form action="{{ route('calendar.store') }}" method="POST" class="space-y-5">
                    @csrf

                    <div class="sc-field">
                        <x-input-label for="calendar-title" :value="__('Title')" required />
                        <x-text-input id="calendar-title" name="title" type="text" required placeholder="What needs to be done?" value="{{ old('title') }}" aria-label="{{ __('Title') }}" />
                        <x-input-error field="title" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sc-field">
                            <x-input-label for="calendar-start-time" :value="__('When?')" required />
                            <input
                                id="calendar-start-time"
                                type="text"
                                name="start_time"
                                x-ref="startTimeInput"
                                required
                                autocomplete="off"
                                placeholder="Select date and time"
                                aria-label="{{ __('When?') }}"
                                class="sc-input"
                            >
                            <x-input-error field="start_time" />
                        </div>
                        <div class="sc-field">
                            <x-input-label for="calendar-type" :value="__('Type')" required />
                            <select id="calendar-type" name="type" x-ref="typeSelect" autocomplete="off" aria-label="{{ __('Type') }}" class="sc-select">
                                <option value="Event">{{ __('Event') }}</option>
                                <option value="Reminder">{{ __('Reminder') }}</option>
                                <option value="Appointment">{{ __('Appointment') }}</option>
                            </select>
                            <x-input-error field="type" />
                        </div>
                    </div>

                    <div class="sc-field">
                        <x-input-label for="calendar-description" :value="__('Notes (Optional)')" />
                        <textarea
                            id="calendar-description"
                            name="description"
                            rows="3"
                            placeholder="Add any details here…"
                            aria-label="{{ __('Notes (Optional)') }}"
                            class="sc-textarea resize-none"
                        >{{ old('description') }}</textarea>
                        <x-input-error field="description" />
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="sc-btn sc-btn-primary w-full py-3.5 text-base justify-center">
                            <span>{{ __('Save Entry') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- History Modal --}}
        <div
            class="sc-scrim flex items-center justify-center p-4"
            x-cloak
            x-show="showHistory"
            x-transition.opacity.duration.200ms
            @click.self="showHistory = false"
        >
            <div
                class="sc-dialog max-w-xl w-full"
                x-show="showHistory"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
            >
                {{-- Header --}}
                <div class="flex items-center justify-between pb-4 mb-4 border-b border-[var(--sc-border)]">
                    <div class="flex items-center gap-3">
                        <div class="sc-plate sc-plate-sm flex-shrink-0">
                            <x-lucide-history class="sc-i w-4 h-4 text-[var(--sc-ink)]" aria-hidden="true" />
                        </div>
                        <h3 class="text-xl font-bold text-[var(--sc-ink)]">{{ __('Past Events') }}</h3>
                    </div>
                    <button
                        type="button"
                        @click="showHistory = false"
                        class="sc-btn sc-btn-ghost !p-2"
                        aria-label="{{ __('Close history') }}"
                    >
                        <x-lucide-x class="sc-i w-5 h-5" aria-hidden="true" />
                    </button>
                </div>

                {{-- Scrollable list grouped by month --}}
                <div class="overflow-y-auto max-h-[60vh] space-y-4 pr-1">
                    @php
                        $grouped = $pastEvents->groupBy(fn($e) => \Carbon\Carbon::parse($e->start_time)->format('F Y'));
                    @endphp

                    @forelse($grouped as $monthYear => $monthEvents)
                        <div>
                            <div class="flex items-center gap-3 mb-3">
                                <span class="text-xs font-bold uppercase tracking-wider text-[var(--sc-ink-muted)] sc-num">{{ $monthYear }}</span>
                                <div class="flex-1 h-px bg-[var(--sc-border)]"></div>
                            </div>

                            <div class="space-y-2.5">
                                @foreach($monthEvents as $event)
                                    <div class="sc-card-quiet flex items-start gap-3.5 p-3.5 rounded-xl">
                                        {{-- Date badge --}}
                                        <div class="sc-plate sc-plate-sm flex-col rounded-lg flex-shrink-0 text-center">
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-[var(--sc-ink-muted)] leading-none">{{ \Carbon\Carbon::parse($event->start_time)->format('M') }}</span>
                                            <span class="text-sm font-bold text-[var(--sc-ink)] leading-none mt-0.5 sc-num">{{ \Carbon\Carbon::parse($event->start_time)->format('d') }}</span>
                                        </div>

                                        {{-- Info --}}
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-1 flex-wrap">
                                                @if($event->type === 'Appointment')
                                                    <span class="sc-mark sc-mark-alert"><i></i>{{ __('Appointment') }}</span>
                                                @elseif($event->type === 'Medication')
                                                    <span class="sc-mark sc-mark-ok"><i></i>{{ __('Medication') }}</span>
                                                @else
                                                    <span class="sc-mark sc-mark-brand"><i></i>{{ $event->type }}</span>
                                                @endif
                                                <span class="text-xs font-semibold text-[var(--sc-ink-muted)] flex items-center gap-1 sc-num">
                                                    <x-lucide-clock class="sc-i w-3 h-3" aria-hidden="true" />
                                                    <span>{{ \Carbon\Carbon::parse($event->start_time)->format('h:i A') }}</span>
                                                </span>
                                            </div>
                                            <p class="font-bold text-sm text-[var(--sc-ink)] leading-snug">{{ $event->title }}</p>
                                            @if($event->description)
                                                <p class="text-xs text-[var(--sc-ink-muted)] mt-0.5 leading-normal">{{ $event->description }}</p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-sm text-[var(--sc-ink-muted)]">
                            {{ __('No past events yet.') }}
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function calendarSchedulerForm() {
            return {
                showModal: {{ $errors->has('start_time') || $errors->any() ? 'true' : 'false' }},
                showHistory: false,
                startTimePicker: null,
                typeSelect: null,

                initDateTimePicker() {
                    if (this.$refs.startTimeInput && typeof window.flatpickr === 'function') {
                        this.startTimePicker = window.flatpickr(this.$refs.startTimeInput, {
                            enableTime: true,
                            time_24hr: false,
                            minuteIncrement: 5,
                            dateFormat: 'Y-m-d H:i',
                            altInput: true,
                            altFormat: 'F j, Y h:i K',
                            allowInput: false,
                            disableMobile: true,
                            minDate: 'today',  // blocks past dates in the picker UI
                            defaultDate: new Date(),
                            onReady: function(selectedDates, dateStr, instance) {
                                if (instance.altInput) {
                                    instance.altInput.setAttribute('aria-label', 'When?');
                                }
                            }
                        });
                    }

                    if (this.$refs.typeSelect && typeof window.TomSelect === 'function') {
                        this.typeSelect = new window.TomSelect(this.$refs.typeSelect, {
                            create: false,
                            searchField: ['text'],
                            placeholder: 'Select event type...',
                            controlInput: null
                        });
                    }
                },

                openModal() {
                    this.showModal = true;
                    this.$nextTick(() => {
                        if (this.startTimePicker) {
                            this.startTimePicker.setDate(new Date(), true);
                            this.startTimePicker.open();
                        }
                    });
                },

                closeModal() {
                    this.showModal = false;
                    this.startTimePicker?.close();
                },
            };
        }
    </script>
    @endpush
</x-dashboard-layout>