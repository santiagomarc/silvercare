<x-dashboard-layout>
    <x-slot:title>My Tasks - SilverCare</x-slot:title>

    <x-dashboard-nav
        title="My Tasks"
        subtitle="{{ $checklists->count() }} tasks"
        role="elderly"
        :unread-notifications="$unreadNotifications"
    />

    <main id="main-content" class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Back Navigation --}}
        <div class="mb-6">
            <a href="{{ route('dashboard') }}" class="back-nav-pill">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Back to Home
            </a>
        </div>
        
        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        @forelse($groupedChecklists as $date => $dayChecklists)
            @php
                if ($date === 'no-date') {
                    $header = [
                        'label' => 'No Due Date',
                        'css' => 'bg-slate-500 text-white',
                        'isToday' => false,
                        'isPast' => false,
                    ];
                } else {
                    $header = \App\Presenters\ChecklistPresenter::dateHeader($date);
                }

                $isPast = $header['isPast'];
            @endphp
            
            <div class="mb-8">
                <!-- Date Header -->
                <div class="flex items-center mb-4">
                    <div class="flex-grow h-px bg-gray-300"></div>
                    <div class="px-4 py-2 {{ $header['css'] }} rounded-full font-bold text-sm">
                        {{ $header['label'] }}
                    </div>
                    <div class="flex-grow h-px bg-gray-300"></div>
                </div>

                <!-- Tasks for this date -->
                <div class="space-y-3">
                    @foreach($dayChecklists as $checklist)
                        <div class="bg-white rounded-2xl shadow-md overflow-hidden {{ $checklist->is_completed ? 'opacity-75' : '' }} transition-all duration-300">
                            <div class="p-5 flex items-center">
                                <!-- Toggle Button -->
                                <form action="{{ route('elderly.checklists.toggle', $checklist) }}" method="POST" class="mr-4">
                                    @csrf
                                    <button type="submit" class="w-10 h-10 {{ $checklist->is_completed ? 'bg-green-500 border-green-500 text-white' : 'bg-white border-gray-300 hover:border-green-500' }} border-2 rounded-full flex items-center justify-center transition-all duration-200 hover:scale-110 shadow-sm">
                                        @if($checklist->is_completed)
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                        @endif
                                    </button>
                                </form>

                                <!-- Category Icon -->
                                <div class="flex-shrink-0 mr-4 text-3xl">
                                    {{ \App\Presenters\ChecklistPresenter::categoryIcon($checklist->category) }}
                                </div>

                                <!-- Task Content -->
                                <div class="flex-grow">
                                    <h3 class="text-lg font-bold text-gray-900 {{ $checklist->is_completed ? 'line-through text-gray-500' : '' }}">
                                        {{ $checklist->task }}
                                    </h3>
                                    <div class="flex flex-wrap items-center gap-2 mt-1">
                                        <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded-full">{{ $checklist->category ?? 'General' }}</span>
                                        @if($checklist->due_time)
                                            <span class="text-sm text-gray-500">🕐 {{ \Carbon\Carbon::parse($checklist->due_time)->format('g:i A') }}</span>
                                        @endif
                                        @if($checklist->priority == 'high')
                                            <span class="px-2 py-0.5 bg-red-100 text-red-700 text-xs rounded-full font-bold">❗ High Priority</span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Status Badge -->
                                <div class="flex-shrink-0 ml-4">
                                    @if($checklist->is_completed)
                                        <div class="text-center">
                                            <span class="inline-flex items-center px-3 py-1 bg-green-100 text-green-700 text-sm rounded-full font-bold">
                                                ✓ Done
                                            </span>
                                            @if($checklist->completed_at)
                                                <p class="text-xs text-gray-400 mt-1">{{ $checklist->completed_at->format('g:i A') }}</p>
                                            @endif
                                        </div>
                                    @elseif($isPast)
                                        <span class="inline-flex items-center px-3 py-1 bg-red-100 text-red-700 text-sm rounded-full font-bold">
                                            Overdue
                                        </span>
                                    @endif
                                </div>
                            </div>

                            @if($checklist->notes)
                                <div class="px-5 pb-4">
                                    <p class="text-sm text-gray-500 bg-gray-50 p-3 rounded-lg">📝 {{ $checklist->notes }}</p>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="empty-state">
                <div class="empty-state-icon">
                    <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3>No tasks right now</h3>
                <p>Your caregiver hasn't added any tasks for you yet. Enjoy your free time! 🌿</p>
                <a href="{{ route('dashboard') }}" class="mt-6 inline-flex items-center gap-2 px-6 py-3 bg-navy-500 text-white font-bold rounded-xl hover:bg-navy-600 transition-colors min-h-touch shadow-glow-brand">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    Back to Dashboard
                </a>
            </div>
        @endforelse

    </main>

</x-dashboard-layout>
