<x-dashboard-layout>
    <x-slot:title>Care Messages - SilverCare</x-slot:title>

    <x-dashboard-nav
        title="Care Messages"
        role="elderly"
        :unread-notifications="$unreadNotifications"
        subtitle="Secure messages from your caregiver"
    />

    <main class="max-w-[1000px] mx-auto px-6 lg:px-12 py-6 space-y-5">
        <x-flash-messages />

        @if(!$caregiver)
            <section class="rounded-2xl border border-amber-200 bg-amber-50 p-6">
                <h2 class="text-lg font-extrabold text-amber-800">No caregiver linked yet</h2>
                <p class="text-sm text-amber-700 mt-1">Link a caregiver first to start in-app messaging.</p>
                <a href="{{ route('dashboard') }}#link-caregiver-card" class="inline-flex mt-4 rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-amber-700 transition-colors">
                    Link Caregiver
                </a>
            </section>
        @else
            <section class="rounded-3xl border border-white/70 bg-white/85 p-4 md:p-5 shadow-sm">
                <div class="mb-4">
                    <h3 class="text-lg font-extrabold text-gray-900">Conversation with {{ $caregiver->user?->name ?? 'Your Caregiver' }}</h3>
                    <p class="text-xs font-semibold text-gray-500">Messages stay in SilverCare and do not require SMS or phone relay.</p>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4 h-[420px] overflow-y-auto space-y-3">
                    @forelse($messages as $message)
                        @php $isMine = $message->sender_profile_id === Auth::user()->profile?->id; @endphp
                        <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[85%] rounded-2xl px-4 py-3 {{ $isMine ? 'bg-emerald-600 text-white rounded-br-md' : 'bg-white text-gray-800 border border-gray-100 rounded-bl-md' }}">
                                <p class="text-sm font-semibold leading-relaxed whitespace-pre-wrap">{{ $message->message }}</p>
                                <p class="mt-2 text-xs {{ $isMine ? 'text-emerald-100' : 'text-gray-400' }}">
                                    {{ $message->created_at->format('M j, g:i A') }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <div class="h-full flex items-center justify-center text-center text-gray-400 text-sm font-semibold">
                            No messages yet.
                        </div>
                    @endforelse
                </div>

                <form method="POST" action="{{ route('elderly.messages.store') }}" class="mt-4 space-y-3">
                    @csrf
                    <label for="message" class="sr-only">Message</label>
                    <textarea
                        id="message"
                        name="message"
                        rows="3"
                        maxlength="1200"
                        required
                        class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-800 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-200"
                        placeholder="Reply to your caregiver..."
                    ></textarea>
                    <div class="flex justify-end">
                        <button type="submit" class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-emerald-700 transition-colors">
                            Send Reply
                        </button>
                    </div>
                </form>
            </section>
        @endif
    </main>
</x-dashboard-layout>
