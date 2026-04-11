<x-dashboard-layout>
    <x-slot:title>My Tasks - SilverCare</x-slot:title>

    <x-dashboard-nav
        title="My Tasks"
        subtitle="{{ $checklists->count() }} tasks"
        role="elderly"
        :unread-notifications="$unreadNotifications"
    />

    <main id="main-content" class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- H6 FIX: Flash messages now shown via toast JS; keep server-side fallback for non-JS submissions --}}
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
                        {{-- H6 FIX: Use Alpine x-data + AJAX toggle, no form POST page reload --}}
                        <div x-data="checklistPageItem({{ $checklist->id }}, {{ $checklist->is_completed ? 'true' : 'false' }})"
                             class="bg-white rounded-2xl shadow-md overflow-hidden transition-all duration-300"
                             :class="completed ? 'opacity-75' : ''">
                            <div class="p-5 flex items-center">
                                <!-- Toggle Button -->
                                <button
                                    @click="toggle"
                                    :disabled="processing"
                                    class="mr-4 w-10 h-10 rounded-full flex items-center justify-center transition-all duration-200 hover:scale-110 shadow-sm border-2 focus:outline-none focus:ring-4 focus:ring-green-300"
                                    :class="completed ? 'bg-green-500 border-green-500 text-white' : 'bg-white border-gray-300 hover:border-green-500'"
                                    :aria-label="completed ? 'Mark incomplete' : 'Mark complete'"
                                    :aria-pressed="completed">
                                    <svg x-show="completed" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <svg x-show="processing && !completed" class="w-5 h-5 animate-spin opacity-50" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                    </svg>
                                </button>

                                <!-- Category Icon -->
                                <div class="flex-shrink-0 mr-4 text-3xl" aria-hidden="true">
                                    {{ \App\Presenters\ChecklistPresenter::categoryIcon($checklist->category) }}
                                </div>

                                <!-- Task Content -->
                                <div class="flex-grow">
                                    <h3 class="text-lg font-bold text-gray-900" :class="completed ? 'line-through text-gray-500' : ''">
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
                                    <template x-if="completed">
                                        <div class="text-center">
                                            <span class="inline-flex items-center px-3 py-1 bg-green-100 text-green-700 text-sm rounded-full font-bold">
                                                ✓ Done
                                            </span>
                                            @if($checklist->completed_at)
                                                <p class="text-xs text-gray-400 mt-1">{{ $checklist->completed_at->format('g:i A') }}</p>
                                            @endif
                                        </div>
                                    </template>
                                    <template x-if="!completed && {{ $isPast ? 'true' : 'false' }}">
                                        <span class="inline-flex items-center px-3 py-1 bg-red-100 text-red-700 text-sm rounded-full font-bold">
                                            Overdue
                                        </span>
                                    </template>
                                </div>
                            </div>

                            @if($checklist->notes)
                                <div class="px-5 pb-4">
                                    <p class="text-sm text-gray-500 bg-gray-50 p-3 rounded-lg">📝 {{ $checklist->notes }}</p>
                                </div>
                            @endif
                        </div>{{-- end x-data checklist item --}}
                    @endforeach
                </div>
            </div>
        @empty
            <div class="text-center py-16">
                <div class="text-6xl mb-4">✅</div>
                <h2 class="text-2xl font-bold text-gray-700 mb-2">No Tasks</h2>
                <p class="text-gray-500">Your caregiver hasn't added any tasks for you yet.</p>
                <a href="{{ route('dashboard') }}" class="mt-6 inline-flex items-center text-green-600 hover:underline font-medium">
                    ← Back to Dashboard
                </a>
            </div>
        @endforelse

    </main>

    <x-ai-chat-widget />
</x-dashboard-layout>
