<x-dashboard-layout>
    <x-slot:title>Schedule - SilverCare</x-slot:title>
    <x-slot:bodyClass>h-screen overflow-hidden bg-[#F3F4F6]</x-slot:bodyClass>
 
    <div class="h-full flex flex-col" x-data="calendarSchedulerForm()" x-init="initDateTimePicker()">
        <x-dashboard-nav
            title="Schedule"
            subtitle="Manage your health appointments and reminders"
            role="elderly"
            :unread-notifications="$unreadNotifications ?? 0"
        />
 
        {{-- Everything below nav fills remaining height --}}
        <div class="flex-1 overflow-hidden flex flex-col max-w-7xl w-full mx-auto px-6 py-4 min-h-0">
 
            {{-- Top bar: Back to Dashboard RIGHT only --}}
            <div class="flex items-center justify-end mb-4 flex-shrink-0">
                <a href="{{ route('dashboard') }}" class="back-nav-pill !text-gray-600 !bg-white/70 hover:!bg-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Back to Dashboard
                </a>
            </div>
 
            {{-- Main content: fills remaining height --}}
            <div class="flex-1 grid grid-cols-1 lg:grid-cols-12 gap-6 min-h-0 overflow-hidden">
 
                {{-- LEFT: Date card + History button --}}
                <div class="lg:col-span-4 min-h-0 flex flex-col gap-3">
 
                    {{-- Date + Quick Tip card --}}
                    <div class="bg-gradient-to-br from-indigo-600 to-purple-700 rounded-[2rem] p-7 shadow-xl text-white relative overflow-hidden flex-1 flex flex-col justify-between group">
 
                        <div class="absolute top-0 right-0 w-56 h-56 bg-white/10 rounded-full -mr-16 -mt-16 blur-2xl group-hover:scale-110 transition-transform duration-700"></div>
                        <div class="absolute bottom-0 left-0 w-40 h-40 bg-purple-500/30 rounded-full -ml-8 -mb-8 blur-xl"></div>
 
                        <div class="relative z-10">
                            <span class="inline-block px-4 py-1.5 bg-white/20 backdrop-blur-md rounded-full text-sm font-bold tracking-wide mb-5">TODAY</span>
                            <h3 class="text-6xl font-extrabold mb-1">{{ now()->format('d') }}</h3>
                            <p class="text-2xl font-medium text-indigo-100">{{ now()->format('l') }}</p>
                            <p class="text-lg text-indigo-200 mt-0.5">{{ now()->format('F Y') }}</p>
                        </div>
 
                        <div class="relative z-10 mt-4 bg-black/20 backdrop-blur-sm rounded-2xl p-5 border border-white/10">
                            <div class="flex items-start gap-3">
                                <div class="p-2 bg-white/20 rounded-lg flex-shrink-0">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <div>
                                    <p class="font-bold text-base mb-0.5">Quick Tip</p>
                                    <p class="text-sm text-indigo-100 leading-relaxed">Staying organized helps reduce stress. Check your tasks daily!</p>
                                </div>
                            </div>
                        </div>
                    </div>
 
                    {{-- History button — outside the card, below it --}}
                    @if($pastEvents->isNotEmpty())
                        <button
                            @click="showHistory = true"
                            class="w-full flex items-center justify-between px-5 py-4 bg-white rounded-2xl shadow-sm border border-gray-100 hover:border-indigo-200 hover:bg-indigo-50/40 transition-all group"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 bg-indigo-100 rounded-xl flex items-center justify-center group-hover:bg-indigo-200 transition-colors">
                                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <div class="text-left">
                                    <p class="font-bold text-gray-800 text-sm">Past Events</p>
                                    <p class="text-xs text-gray-400">{{ $pastEvents->count() }} {{ Str::plural('event', $pastEvents->count()) }} completed</p>
                                </div>
                            </div>
                            <svg class="w-4 h-4 text-gray-400 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                        </button>
                    @endif
                </div>
 
                {{-- RIGHT: Upcoming events list or empty state --}}
                <div class="lg:col-span-8 min-h-0 overflow-y-auto">
                    @if($events->isEmpty())
                        <div class="bg-white rounded-[2rem] p-10 text-center shadow-sm border border-gray-100 flex flex-col items-center justify-center h-full">
                            <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-5">
                                <svg class="w-9 h-9 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 mb-2">No Upcoming Events</h3>
                            <p class="text-gray-500 max-w-sm mx-auto mb-6">Your schedule is clear for now. Add an entry to plan ahead.</p>
                            <button @click="openModal()" class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white px-7 py-3.5 rounded-2xl font-bold shadow-lg shadow-blue-200 flex items-center gap-2 transform transition hover:-translate-y-1 hover:shadow-xl group">
                                <div class="bg-white/20 p-1 rounded-lg group-hover:rotate-90 transition-transform duration-300">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                </div>
                                Add New Entry
                            </button>
                        </div>
                    @else
                        <div class="bg-white rounded-[2rem] shadow-sm border border-gray-100 overflow-hidden">
                            <div class="p-7">
                                <div class="flex items-center justify-between mb-5">
                                    <h3 class="text-lg font-bold text-gray-900 flex items-center">
                                        <span class="w-2 h-7 bg-blue-500 rounded-full mr-3"></span>
                                        Upcoming List
                                    </h3>
                                    <button @click="openModal()" class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white px-5 py-2.5 rounded-xl font-bold shadow-md shadow-blue-200 flex items-center gap-2 transition hover:-translate-y-0.5 group text-sm">
                                        <div class="bg-white/20 p-0.5 rounded-lg group-hover:rotate-90 transition-transform duration-300">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                        </div>
                                        Add New Entry
                                    </button>
                                </div>
 
                                <div class="space-y-3">
                                    @foreach($events as $event)
                                        <div class="group flex items-center p-5 rounded-2xl border border-gray-100 hover:border-blue-100 hover:bg-blue-50/30 transition-all duration-300">
 
                                            <div class="flex-shrink-0 w-14 h-14 bg-white rounded-2xl shadow-sm border border-gray-100 flex flex-col items-center justify-center mr-5 group-hover:scale-105 transition-transform">
                                                <span class="text-xs font-bold text-gray-400 uppercase">{{ \Carbon\Carbon::parse($event->start_time)->format('M') }}</span>
                                                <span class="text-xl font-extrabold text-gray-800">{{ \Carbon\Carbon::parse($event->start_time)->format('d') }}</span>
                                            </div>
 
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2 mb-1">
                                                    <span class="px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wide
                                                        {{ $event->type == 'Appointment' ? 'bg-red-100 text-red-600' :
                                                          ($event->type == 'Medication' ? 'bg-emerald-100 text-emerald-600' : 'bg-blue-100 text-blue-600') }}">
                                                        {{ $event->type }}
                                                    </span>
                                                    <span class="text-sm font-semibold text-gray-400 flex items-center">
                                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                        {{ \Carbon\Carbon::parse($event->start_time)->format('h:i A') }}
                                                    </span>
                                                </div>
                                                <h4 class="text-lg font-bold text-gray-900 truncate group-hover:text-blue-600 transition-colors">{{ $event->title }}</h4>
                                                @if($event->description)
                                                    <p class="text-sm text-gray-500 truncate mt-0.5">{{ $event->description }}</p>
                                                @endif
                                            </div>
 
                                            <form
                                                method="POST"
                                                action="{{ route('calendar.destroy', $event->id) }}"
                                                class="ml-4 opacity-0 group-hover:opacity-100 transition-opacity"
                                                data-confirm="Delete this event?"
                                                data-confirm-title="Delete calendar entry?"
                                                data-confirm-icon="warning"
                                                data-confirm-confirm-text="Delete event"
                                                data-confirm-cancel-text="Keep event"
                                                data-confirm-elderly="true"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-3 text-gray-300 hover:text-red-500 hover:bg-red-50 rounded-xl transition-all" title="Delete">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
 
        {{-- Add New Entry Modal --}}
        <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity duration-300" @click="closeModal()"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg bg-white rounded-[2rem] shadow-2xl p-10 transform transition-all scale-100">
 
                    <div class="flex justify-between items-center mb-8">
                        <h3 class="text-3xl font-extrabold text-gray-900">New Entry</h3>
                        <button @click="closeModal()" class="text-gray-400 hover:text-gray-600 p-2 hover:bg-gray-100 rounded-full transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
 
                    @if($errors->has('start_time'))
                        <div class="mb-6 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-red-600 text-sm font-semibold">
                            {{ $errors->first('start_time') }}
                        </div>
                    @endif
 
                    <form action="{{ route('calendar.store') }}" method="POST" class="space-y-6">
                        @csrf
 
                        <div class="space-y-2">
                            <label class="text-sm font-bold text-gray-900">Title</label>
                            <input type="text" name="title" required placeholder="What needs to be done?"
                                class="w-full bg-gray-50 border-transparent rounded-xl px-5 py-4 text-gray-900 placeholder-gray-400 font-semibold focus:ring-4 focus:ring-blue-100 focus:bg-white transition-all"
                                value="{{ old('title') }}">
                        </div>
 
                        <div class="grid grid-cols-2 gap-5">
                            <div class="space-y-2">
                                <label class="text-sm font-bold text-gray-900">When?</label>
                                <input
                                    type="text"
                                    name="start_time"
                                    x-ref="startTimeInput"
                                    required
                                    autocomplete="off"
                                    placeholder="Select date and time"
                                    class="w-full bg-gray-50 border-transparent rounded-xl px-4 py-4 text-gray-900 font-semibold focus:ring-4 focus:ring-blue-100 focus:bg-white transition-all text-sm"
                                >
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-bold text-gray-900">Type</label>
                                <select name="type" x-ref="typeSelect" autocomplete="off">
                                    <option value="Event">📅 Event</option>
                                    <option value="Reminder">🔔 Reminder</option>
                                    <option value="Appointment">🩺 Appointment</option>
                                </select>
                            </div>
                        </div>
 
                        <div class="space-y-2">
                            <label class="text-sm font-bold text-gray-900">Notes (Optional)</label>
                            <textarea name="description" rows="3" placeholder="Add any details here..."
                                class="w-full bg-gray-50 border-transparent rounded-xl px-5 py-4 text-gray-900 placeholder-gray-400 font-semibold focus:ring-4 focus:ring-blue-100 focus:bg-white transition-all resize-none">{{ old('description') }}</textarea>
                        </div>
 
                        <div class="pt-4">
                            <button type="submit" class="w-full bg-[#2563EB] hover:bg-[#1D4ED8] text-white text-lg font-bold py-4 rounded-xl shadow-xl shadow-blue-200 transform transition hover:-translate-y-1">
                                Save Entry
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
 
        {{-- History Modal --}}
        <div x-show="showHistory" class="fixed inset-0 z-50 flex items-center justify-center p-6" style="display: none;">
            <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showHistory = false"></div>
 
            <div class="relative w-full max-w-xl bg-white rounded-[2rem] shadow-2xl overflow-hidden" style="max-height: 80vh;">
                {{-- Header --}}
                <div class="flex items-center justify-between px-8 pt-8 pb-5 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 bg-indigo-100 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <h3 class="text-2xl font-extrabold text-gray-900">Past Events</h3>
                    </div>
                    <button @click="showHistory = false" class="text-gray-400 hover:text-gray-600 p-2 hover:bg-gray-100 rounded-full transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
 
                {{-- Scrollable list grouped by month --}}
                <div class="overflow-y-auto px-8 py-5" style="max-height: calc(80vh - 90px);">
                    @php
                        $grouped = $pastEvents->groupBy(fn($e) => \Carbon\Carbon::parse($e->start_time)->format('F Y'));
                    @endphp
 
                    @forelse($grouped as $monthYear => $monthEvents)
                        {{-- Month header --}}
                        <div class="flex items-center gap-3 mb-3 {{ !$loop->first ? 'mt-6' : '' }}">
                            <span class="text-xs font-[900] uppercase tracking-widest text-indigo-400">{{ $monthYear }}</span>
                            <div class="flex-1 h-px bg-gray-100"></div>
                        </div>
 
                        <div class="space-y-2">
                            @foreach($monthEvents as $event)
                                <div class="flex items-start gap-4 p-4 rounded-2xl bg-gray-50 border border-gray-100">
 
                                    {{-- Date badge --}}
                                    <div class="flex-shrink-0 w-12 h-12 bg-white rounded-xl shadow-sm border border-gray-100 flex flex-col items-center justify-center">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase leading-none">{{ \Carbon\Carbon::parse($event->start_time)->format('M') }}</span>
                                        <span class="text-lg font-extrabold text-gray-700 leading-none">{{ \Carbon\Carbon::parse($event->start_time)->format('d') }}</span>
                                    </div>
 
                                    {{-- Info --}}
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide
                                                {{ $event->type == 'Appointment' ? 'bg-red-100 text-red-500' :
                                                  ($event->type == 'Medication' ? 'bg-emerald-100 text-emerald-600' : 'bg-blue-100 text-blue-500') }}">
                                                {{ $event->type }}
                                            </span>
                                            <span class="text-xs font-semibold text-gray-400 flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                {{ \Carbon\Carbon::parse($event->start_time)->format('h:i A') }}
                                            </span>
                                        </div>
                                        <p class="font-bold text-gray-700 text-base leading-tight">{{ $event->title }}</p>
                                        @if($event->description)
                                            <p class="text-sm text-gray-400 mt-0.5 leading-snug">{{ $event->description }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @empty
                        <div class="text-center py-10 text-gray-400 font-medium">No past events yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
 
    </div>
 
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
</x-dashboard-layout>