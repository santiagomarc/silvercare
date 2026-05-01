<x-dashboard-layout>
    <x-slot:title>Add Task - SilverCare</x-slot:title>

    <x-dashboard-nav
        title="Add Task"
        subtitle="Create a new checklist item"
        role="caregiver"
    />

    <!-- MAIN CONTENT -->
    <main class="max-w-3xl mx-auto px-6 py-8">
        
        @if ($errors->any())
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded-lg shadow-sm">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(($elderlyPatients ?? collect())->count() > 1)
            <div class="mb-6 rounded-2xl border border-blue-100 bg-blue-50/80 p-4 shadow-sm">
                <p class="text-sm font-bold text-blue-900">Creating task for {{ $selectedElderly->user?->name ?? 'selected patient' }}</p>
                <p class="text-xs text-blue-700 mt-0.5">Return to checklists and switch patient if needed.</p>
            </div>
        @endif

        <form method="POST" action="{{ route('caregiver.checklists.store') }}">
            @csrf
            <input type="hidden" name="elderly_id" value="{{ $selectedElderly->id }}">

            <!-- CARD 1: Task Details -->
            <div class="bg-white rounded-[24px] p-6 md:p-8 shadow-md border border-gray-100 mb-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    </div>
                    <h3 class="font-[800] text-xl text-gray-900">Task Details</h3>
                </div>

                <!-- Task Name -->
                <div class="mb-5">
                    <label for="task" class="block text-xs font-[800] uppercase tracking-wider text-gray-400 mb-2">Task <span class="text-red-500">*</span></label>
                    <input type="text" name="task" id="task" value="{{ old('task') }}" class="w-full rounded-xl border-2 border-gray-100 bg-gray-50 px-4 py-3.5 font-[600] text-gray-900 transition-all focus:border-green-500 focus:bg-white focus:ring-0 outline-none" placeholder="e.g. Take afternoon walk, Drink water, Do stretching exercises" required>
                </div>

                <!-- Category Selection -->
                <div>
                    <label class="block text-xs font-[800] uppercase tracking-wider text-gray-400 mb-3">Category <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        @php
                            $categories = [
                                'Health' => ['icon' => '❤️', 'color' => 'red'],
                                'Exercise' => ['icon' => '🏃', 'color' => 'orange'],
                                'Nutrition' => ['icon' => '🍎', 'color' => 'green'],
                                'Social' => ['icon' => '👥', 'color' => 'blue'],
                                'Hygiene' => ['icon' => '🧼', 'color' => 'cyan'],
                                'Mental' => ['icon' => '🧠', 'color' => 'purple'],
                                'Medication' => ['icon' => '💊', 'color' => 'pink'],
                                'Other' => ['icon' => '📋', 'color' => 'gray'],
                            ];
                        @endphp
                        @foreach($categories as $catName => $catData)
                            <label class="relative cursor-pointer">
                                <input type="radio" name="category" value="{{ $catName }}" class="peer sr-only" {{ old('category') == $catName ? 'checked' : '' }} required>
                                <div class="p-4 rounded-2xl border-2 border-gray-200 bg-white text-center transition-all duration-200 peer-checked:border-green-500 peer-checked:bg-green-50 hover:border-green-300 hover:bg-green-50/50">
                                    <div class="text-3xl mb-2">{{ $catData['icon'] }}</div>
                                    <div class="text-xs font-[700] text-gray-700">{{ $catName }}</div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- CARD 2: Schedule -->
            <div class="bg-white rounded-[24px] p-6 md:p-8 shadow-md border border-gray-100 mb-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-gradient-to-br from-amber-400 to-orange-500 rounded-xl flex items-center justify-center text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <h3 class="font-[800] text-xl text-gray-900">Schedule</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
                    <!-- Due Date -->
                    <div>
                        <label for="due_date" class="block text-xs font-[800] uppercase tracking-wider text-gray-400 mb-2">Due Date <span class="text-red-500">*</span></label>
                        <input type="text" name="due_date" id="due_date" value="{{ old('due_date', date('Y-m-d')) }}" min="{{ date('Y-m-d') }}" class="w-full rounded-xl border-2 border-gray-100 bg-gray-50 px-4 py-3.5 font-[600] text-gray-900 transition-all focus:border-amber-500 focus:bg-white focus:ring-0 outline-none cursor-pointer placeholder-gray-400" placeholder="Select date..." required>
                    </div>

                    <!-- Due Time -->
                    <div>
                        <label for="due_time" class="block text-xs font-[800] uppercase tracking-wider text-gray-400 mb-2">Due Time (Optional)</label>
                        <input type="text" name="due_time" id="due_time" value="{{ old('due_time') }}" class="w-full rounded-xl border-2 border-gray-100 bg-gray-50 px-4 py-3.5 font-[600] text-gray-900 transition-all focus:border-amber-500 focus:bg-white focus:ring-0 outline-none cursor-pointer placeholder-gray-400" placeholder="Select time...">
                    </div>
                </div>

                <!-- Priority -->
                <div>
                    <label class="block text-xs font-[800] uppercase tracking-wider text-gray-400 mb-3">Priority</label>
                    <div class="flex flex-wrap gap-3">
                        @php $priorities = ['Low' => 'bg-gray-100 text-gray-700', 'Medium' => 'bg-yellow-100 text-yellow-700', 'High' => 'bg-red-100 text-red-700']; @endphp
                        @foreach($priorities as $priority => $classes)
                            <label class="relative cursor-pointer">
                                <input type="radio" name="priority" value="{{ strtolower($priority) }}" class="peer sr-only" {{ old('priority', 'medium') == strtolower($priority) ? 'checked' : '' }}>
                                <div class="px-5 py-2.5 rounded-xl border-2 border-gray-200 {{ $classes }} font-[700] text-sm transition-all duration-200 peer-checked:border-green-500 peer-checked:ring-2 peer-checked:ring-green-200 hover:border-green-300">
                                    {{ $priority }}
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- CARD 3: Additional -->
            <div class="bg-white rounded-[24px] p-6 md:p-8 shadow-md border border-gray-100 mb-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-violet-600 rounded-xl flex items-center justify-center text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path></svg>
                    </div>
                    <h3 class="font-[800] text-xl text-gray-900">Notes</h3>
                </div>

                <div class="mb-4">
                    <label for="notes" class="block text-xs font-[800] uppercase tracking-wider text-gray-400 mb-2">Additional Notes (Optional)</label>
                    <textarea name="notes" id="notes" rows="3" class="w-full rounded-xl border-2 border-gray-100 bg-gray-50 px-4 py-3.5 font-[600] text-gray-900 transition-all focus:border-purple-500 focus:bg-white focus:ring-0 outline-none resize-none" placeholder="Any additional notes or reminders...">{{ old('notes') }}</textarea>
                </div>

                <!-- Quick Templates -->
                <div class="bg-green-50 p-4 rounded-2xl border border-green-100">
                    <h4 class="text-sm font-[800] text-green-800 mb-3">Quick Templates</h4>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" onclick="setTemplate('Take morning walk', 'Exercise')" class="px-3 py-1.5 bg-white text-green-700 text-xs font-[700] rounded-lg border border-green-200 hover:bg-green-100 transition-colors">🏃 Morning Walk</button>
                        <button type="button" onclick="setTemplate('Drink 8 glasses of water', 'Health')" class="px-3 py-1.5 bg-white text-green-700 text-xs font-[700] rounded-lg border border-green-200 hover:bg-green-100 transition-colors">💧 Hydration</button>
                        <button type="button" onclick="setTemplate('Do stretching exercises', 'Exercise')" class="px-3 py-1.5 bg-white text-green-700 text-xs font-[700] rounded-lg border border-green-200 hover:bg-green-100 transition-colors">🧘 Stretching</button>
                        <button type="button" onclick="setTemplate('Call family member', 'Social')" class="px-3 py-1.5 bg-white text-green-700 text-xs font-[700] rounded-lg border border-green-200 hover:bg-green-100 transition-colors">📞 Family Call</button>
                        <button type="button" onclick="setTemplate('Read for 30 minutes', 'Mental')" class="px-3 py-1.5 bg-white text-green-700 text-xs font-[700] rounded-lg border border-green-200 hover:bg-green-100 transition-colors">📖 Reading</button>
                    </div>
                </div>
            </div>

            <!-- ACTION BUTTONS -->
            <div class="flex items-center justify-end gap-4">
                <a href="{{ route('caregiver.checklists.index', ['elderly' => $selectedElderly->id]) }}" class="px-6 py-3 rounded-xl font-[700] text-gray-600 bg-gray-100 hover:bg-gray-200 transition-all">
                    Cancel
                </a>
                <button type="submit" class="group flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl font-[700] shadow-lg shadow-green-200 hover:-translate-y-0.5 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Add Task
                </button>
            </div>
        </form>
    </main>

    <script>
        function setTemplate(task, category) {
            document.getElementById('task').value = task;
            document.querySelector(`input[name="category"][value="${category}"]`).checked = true;
        }

        document.addEventListener('DOMContentLoaded', function() {
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
                allowInput: true
            });
        });
    </script>

</x-dashboard-layout>
