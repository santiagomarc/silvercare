<x-dashboard-layout>
    <x-slot:title>Care Messages - SilverCare</x-slot:title>

    <x-dashboard-nav
        title="Care Messages"
        role="caregiver"
        subtitle="Secure check-ins with your patient"
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
            <section class="rounded-2xl border border-white/70 bg-white/90 p-4 shadow-sm">
                <form method="GET" action="{{ route('caregiver.messages.index') }}" class="flex flex-col sm:flex-row sm:items-center gap-3">
                    <label for="elderly" class="text-sm font-bold text-gray-700">Conversation with</label>
                    <select
                        id="elderly"
                        name="elderly"
                        onchange="this.form.submit()"
                        class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-800"
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
                <section class="rounded-3xl border border-white/70 bg-white/85 p-4 md:p-5 shadow-sm">
                    <div class="mb-4">
                        <h3 class="text-lg font-extrabold text-gray-900">{{ $selectedElderly->user?->name ?? 'Patient' }}</h3>
                        <p class="text-xs font-semibold text-gray-500">Messages are encrypted in-app and visible only to linked accounts.</p>
                    </div>

                    <div class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4 h-[420px] overflow-y-auto space-y-3">
                        @forelse($messages as $message)
                            @php $isMine = $message->sender_profile_id === Auth::user()->profile?->id; @endphp
                            <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}">
                                <div class="max-w-[85%] rounded-2xl px-4 py-3 {{ $isMine ? 'bg-[#000080] text-white rounded-br-md' : 'bg-white text-gray-800 border border-gray-100 rounded-bl-md' }}">
                                    <p class="text-sm font-semibold leading-relaxed whitespace-pre-wrap">{{ $message->message }}</p>
                                    <p class="mt-2 text-[11px] {{ $isMine ? 'text-blue-100' : 'text-gray-400' }}">
                                        {{ $message->created_at->format('M j, g:i A') }}
                                    </p>
                                </div>
                            </div>
                        @empty
                            <div class="h-full flex items-center justify-center text-center text-gray-400 text-sm font-semibold">
                                No messages yet. Send a quick check-in.
                            </div>
                        @endforelse
                    </div>

                    <form method="POST" action="{{ route('caregiver.messages.store') }}" class="mt-4 space-y-3">
                        @csrf
                        <input type="hidden" name="elderly_id" value="{{ $selectedElderly->id }}">
                        <label for="message" class="sr-only">Message</label>
                        <textarea
                            id="message"
                            name="message"
                            rows="3"
                            maxlength="1200"
                            required
                            class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-800 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20"
                            placeholder="Type your message to your patient..."
                        ></textarea>
                        <div class="flex justify-end">
                            <button type="submit" class="rounded-xl bg-[#000080] px-5 py-2.5 text-sm font-bold text-white hover:bg-blue-900 transition-colors">
                                Send Message
                            </button>
                        </div>
                    </form>
                </section>
            @endif
        @endif
    </main>
</x-dashboard-layout>
