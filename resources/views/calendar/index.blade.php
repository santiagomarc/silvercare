<x-app-layout>
    <div class="py-10 bg-[#F3F4F6] min-h-screen font-sans" x-data="{ showModal: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Dynamic Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-10 gap-4">
                <div>
                    <h2 class="text-4xl font-extrabold text-gray-900 tracking-tight">Schedule</h2>
                    <p class="text-gray-500 mt-2 font-medium">Manage your health appointments and reminders.</p>
                </div>
                <button @click="showModal = true" class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white px-8 py-3.5 rounded-2xl font-bold shadow-lg shadow-blue-200 flex items-center transform transition hover:-translate-y-1 hover:shadow-xl group">
                    <div class="bg-white/20 p-1 rounded-lg mr-3 group-hover:rotate-90 transition-transform duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    </div>
                    Add New Entry
                </button>
            </div>

            <!-- Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                
                <!-- Left: Visual Calendar Card -->
                <div class="lg:col-span-4">
                    <div class="bg-gradient-to-br from-indigo-600 to-purple-700 rounded-[2.5rem] p-8 shadow-xl text-white relative overflow-hidden h-full min-h-[400px] flex flex-col justify-between group">
                        
                        <!-- Background decoration -->
                        <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-20 -mt-20 blur-2xl group-hover:scale-110 transition-transform duration-700"></div>
                        <div class="absolute bottom-0 left-0 w-48 h-48 bg-purple-500/30 rounded-full -ml-10 -mb-10 blur-xl"></div>

                        <div class="relative z-10">
                            <span class="inline-block px-4 py-1.5 bg-white/20 backdrop-blur-md rounded-full text-sm font-bold tracking-wide mb-6">TODAY</span>
                            <h3 class="text-6xl font-extrabold mb-2">{{ now()->format('d') }}</h3>
                            <p class="text-2xl font-medium text-indigo-100">{{ now()->format('l') }}</p>
                            <p class="text-lg text-indigo-200 mt-1">{{ now()->format('F Y') }}</p>
                        </div>

                        <div class="relative z-10 mt-8 bg-black/20 backdrop-blur-sm rounded-2xl p-6 border border-white/10">
                            <div class="flex items-start">
                                <div class="p-2 bg-white/20 rounded-lg mr-3">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <div>
                                    <p class="font-bold text-lg mb-1">Quick Tip</p>
                                    <p class="text-sm text-indigo-100 leading-relaxed">Staying organized helps reduce stress. Check your tasks daily!</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Events List -->
                <div class="lg:col-span-8 space-y-6">
                    @if($events->isEmpty())
                        <div class="bg-white rounded-[2.5rem] p-16 text-center shadow-sm border border-gray-100 flex flex-col items-center justify-center h-full">
                            <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mb-6">
                                <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 mb-2">No Upcoming Events</h3>
                            <p class="text-gray-500 max-w-sm mx-auto">Your schedule is clear for now. Click the "Add Entry" button to plan ahead.</p>
                        </div>
                    @else
                        <!-- List Container -->
                        <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden">
                            <div class="p-8">
                                <h3 class="text-lg font-bold text-gray-900 mb-6 flex items-center">
                                    <span class="w-2 h-8 bg-blue-500 rounded-full mr-3"></span>
                                    Upcoming List
                                </h3>
                                
                                <div class="space-y-4">
                                    @foreach($events as $event)
                                        <div class="group flex items-center p-5 rounded-2xl border border-gray-100 hover:border-blue-100 hover:bg-blue-50/30 transition-all duration-300">
                                            
                                            <!-- Date Badge -->
                                            <div class="flex-shrink-0 w-16 h-16 bg-white rounded-2xl shadow-sm border border-gray-100 flex flex-col items-center justify-center mr-6 group-hover:scale-105 transition-transform">
                                                <span class="text-xs font-bold text-gray-400 uppercase">{{ \Carbon\Carbon::parse($event->start_time)->format('M') }}</span>
                                                <span class="text-xl font-extrabold text-gray-800">{{ \Carbon\Carbon::parse($event->start_time)->format('d') }}</span>
                                            </div>

                                            <!-- Content -->
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-3 mb-1">
                                                    <span class="px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wide
                                                        {{ $event->type == 'Appointment' ? 'bg-red-100 text-red-600' : 
                                                          ($event->type == 'Medication' ? 'bg-emerald-100 text-emerald-600' : 'bg-blue-100 text-blue-600') }}">
                                                        {{ $event->type }}
                                                    </span>
                                                    <span class="text-sm font-semibold text-gray-400 flex items-center">
                                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                        {{ \Carbon\Carbon::parse($event->start_time)->format('h:i A') }}
                                                    </span>
                                                </div>
                                                <h4 class="text-lg font-bold text-gray-900 truncate group-hover:text-blue-600 transition-colors">{{ $event->title }}</h4>
                                                @if($event->description)
                                                    <p class="text-sm text-gray-500 truncate mt-1">{{ $event->description }}</p>
                                                @endif
                                            </div>

                                            <!-- Delete Action -->
                                            <form method="POST" action="{{ route('calendar.destroy', $event->id) }}" onsubmit="return confirm('Delete this event?');" class="ml-4 opacity-0 group-hover:opacity-100 transition-opacity">
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

        <!-- Modern Modal -->
        <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity duration-300" @click="showModal = false"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg bg-white rounded-[2rem] shadow-2xl p-10 transform transition-all scale-100">
                    
                    <div class="flex justify-between items-center mb-8">
                        <h3 class="text-3xl font-extrabold text-gray-900">New Entry</h3>
                        <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 p-2 hover:bg-gray-100 rounded-full transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <form action="{{ route('calendar.store') }}" method="POST" class="space-y-6">
                        @csrf
                        
                        <div class="space-y-2">
                            <label class="text-sm font-bold text-gray-900">Title</label>
                            <input type="text" name="title" required placeholder="What needs to be done?" 
                                class="w-full bg-gray-50 border-transparent rounded-xl px-5 py-4 text-gray-900 placeholder-gray-400 font-semibold focus:ring-4 focus:ring-blue-100 focus:bg-white transition-all">
                        </div>

                        <div class="grid grid-cols-2 gap-5">
                            <div class="space-y-2">
                                <label class="text-sm font-bold text-gray-900">When?</label>
                                <input type="datetime-local" name="start_time" required 
                                    class="w-full bg-gray-50 border-transparent rounded-xl px-4 py-4 text-gray-900 font-semibold focus:ring-4 focus:ring-blue-100 focus:bg-white transition-all text-sm">
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-bold text-gray-900">Type</label>
                                <div class="relative">
                                    <select name="type" class="w-full bg-gray-50 border-transparent rounded-xl px-4 py-4 text-gray-900 font-semibold focus:ring-4 focus:ring-blue-100 focus:bg-white transition-all appearance-none">
                                        <option value="Event">📅 Event</option>
                                        <option value="Reminder">🔔 Reminder</option>
                                        <option value="Appointment">🩺 Appointment</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-500">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-bold text-gray-900">Notes (Optional)</label>
                            <textarea name="description" rows="3" placeholder="Add any details here..." 
                                class="w-full bg-gray-50 border-transparent rounded-xl px-5 py-4 text-gray-900 placeholder-gray-400 font-semibold focus:ring-4 focus:ring-blue-100 focus:bg-white transition-all resize-none"></textarea>
                        </div>

                        <div class="pt-6">
                            <button type="submit" class="w-full bg-[#2563EB] hover:bg-[#1D4ED8] text-white text-lg font-bold py-4 rounded-xl shadow-xl shadow-blue-200 transform transition hover:-translate-y-1">
                                Save Entry
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>