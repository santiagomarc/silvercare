<x-dashboard-layout>
    <x-slot:title>Care Messages - SilverCare</x-slot:title>

    <x-dashboard-nav
        title="Care Messages"
        role="caregiver"
        subtitle="Secure check-ins with your patient"
        :show-back="true"
    />

    <main class="max-w-[1100px] mx-auto px-6 lg:px-12 py-6 space-y-5">
        <x-flash-messages />

        @if($elderlyPatients->isEmpty())
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                <h2 class="text-lg font-extrabold text-amber-800">No linked patient yet</h2>
                <p class="text-sm text-amber-700 mt-1">Generate a linking PIN from your dashboard first.</p>
                <a href="{{ route('caregiver.dashboard') }}" class="inline-flex mt-4 rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-amber-700 transition-colors">
                    Back to Dashboard
                </a>
            </div>
        @else
            {{-- Patient Selector --}}
            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:bg-slate-800 dark:border-slate-700">
                <form method="GET" action="{{ route('caregiver.messages.index') }}" class="flex flex-col sm:flex-row sm:items-center gap-3">
                    <label for="elderly" class="text-sm font-bold text-gray-900 dark:text-slate-100">Conversation with</label>
                    <select
                        id="elderly"
                        name="elderly"
                        onchange="this.form.submit()"
                        class="rounded-xl border-2 border-gray-300 bg-white px-4 py-2.5 text-sm font-bold text-gray-900 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all dark:bg-slate-700 dark:border-slate-600 dark:text-slate-100 dark:focus:border-sky-400 dark:focus:ring-sky-400/20"
                    >
                        @foreach($elderlyPatients as $patient)
                            <option value="{{ $patient->id }}" @selected($selectedElderly && $selectedElderly->id === $patient->id)>
                                {{ $patient->user?->name ?? ('Patient #' . $patient->id) }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </section>

            @if($selectedElderly)
                <section class="rounded-3xl border border-gray-200 bg-white p-5 md:p-6 shadow-lg dark:bg-slate-800 dark:border-slate-700">
                    {{-- Header --}}
                    <div class="mb-5 pb-4 border-b border-gray-200 dark:border-slate-700">
                        <h3 class="text-xl font-[900] text-gray-900 dark:text-slate-100">{{ $selectedElderly->user?->name ?? 'Patient' }}</h3>
                        <p class="text-xs font-semibold text-gray-500 dark:text-slate-400 mt-1">🔒 Messages are encrypted and visible only to linked accounts</p>
                    </div>

                    {{-- Messages Container - Light in light mode, dark in dark mode --}}
                    <div class="rounded-2xl bg-gradient-to-b from-gray-50 to-gray-100 border border-gray-200 p-5 h-[450px] overflow-y-auto space-y-4 shadow-inner dark:from-[#1a1d29] dark:to-[#1a1d29] dark:border-slate-700">
                        @forelse($messages as $message)
                            @php $isMine = $message->sender_profile_id === Auth::user()->profile?->id; @endphp
                            <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}">
                                <div class="max-w-[75%] md:max-w-[65%]">
                                    {{-- Sender name if not mine --}}
                                    @if(!$isMine)
                                        <p class="text-xs font-bold text-gray-600 dark:text-slate-400 mb-1 ml-3">{{ $selectedElderly->user?->name ?? 'Patient' }}</p>
                                    @endif
                                    
                                    <div class="rounded-2xl px-5 py-3.5 shadow-md {{ $isMine ? 'bg-[#000080] text-white rounded-br-sm' : 'bg-white text-gray-900 border border-gray-200 rounded-bl-sm dark:bg-slate-700 dark:text-slate-100 dark:border-slate-600' }}">
                                        <p class="text-[15px] font-medium leading-relaxed whitespace-pre-wrap break-words">{{ $message->message }}</p>
                                        <p class="mt-2.5 text-[11px] font-semibold {{ $isMine ? 'text-blue-200' : 'text-gray-500 dark:text-slate-400' }}">
                                            {{ $message->created_at->format('M j, g:i A') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="h-full flex flex-col items-center justify-center text-center">
                                <div class="w-20 h-20 rounded-full bg-gray-200 dark:bg-slate-700 flex items-center justify-center mb-4">
                                    <svg class="w-10 h-10 text-gray-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                    </svg>
                                </div>
                                <p class="text-gray-500 dark:text-slate-300 font-bold text-base">No messages yet</p>
                                <p class="text-gray-400 dark:text-slate-500 text-sm mt-1">Send a quick check-in to your patient</p>
                            </div>
                        @endforelse
                    </div>

                    {{-- Message Form with Inline Send Button --}}
                    <form method="POST" action="{{ route('caregiver.messages.store') }}" class="mt-5">
                        @csrf
                        <input type="hidden" name="elderly_id" value="{{ $selectedElderly->id }}">
                        <div class="relative">
                            <label for="message" class="sr-only">Message</label>
                            <textarea
                                id="message"
                                name="message"
                                rows="3"
                                maxlength="1200"
                                required
                                class="w-full rounded-2xl border-2 border-gray-300 bg-white px-5 py-4 pr-16 text-base font-medium text-gray-900 placeholder-gray-400 focus:border-[#000080] focus:ring-4 focus:ring-[#000080]/20 transition-all resize-none dark:bg-slate-700 dark:border-slate-600 dark:text-slate-100 dark:placeholder-slate-400 dark:focus:border-sky-400 dark:focus:ring-sky-400/20"
                                placeholder="Type your message..."
                            ></textarea>
                            <span class="absolute bottom-3 left-4 text-xs font-semibold text-gray-500 dark:text-slate-500">
                                Max 1200 characters
                            </span>
                            {{-- Inline Send Button with proper hover for both modes --}}
                            <button 
                                type="submit" 
                                class="absolute bottom-3 right-3 w-10 h-10 rounded-xl bg-[#000080] flex items-center justify-center text-white hover:bg-blue-900 transition-all shadow-md hover:shadow-lg hover:scale-105 dark:bg-sky-500 dark:hover:bg-sky-600"
                                title="Send message"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                </svg>
                            </button>
                        </div>
                    </form>
                </section>
            @endif
        @endif
    </main>
</x-dashboard-layout>