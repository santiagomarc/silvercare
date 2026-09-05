<x-dashboard-layout sc>
    <x-slot:title>Care Messages - SilverCare</x-slot:title>

    <x-dashboard-nav
        title="Care Messages"
        role="caregiver"
        subtitle="Secure check-ins with your patient"
        :show-back="true"
    />

    <main id="main-content" class="sc-app-main">
        <div class="max-w-4xl mx-auto px-6 lg:px-12 space-y-6">
            <x-flash-messages />

            @if($elderlyPatients->isEmpty())
                <div class="sc-empty">
                    <div class="sc-plate mb-2">
                        <x-lucide-users class="sc-i w-6 h-6" aria-hidden="true" />
                    </div>
                    <h2 class="sc-h3">No linked patient yet</h2>
                    <p style="color:var(--sc-body)">Generate a linking PIN from your dashboard first.</p>
                    <a href="{{ route('caregiver.dashboard') }}" class="sc-btn sc-btn-primary mt-2">
                        <span>Back to Dashboard</span>
                    </a>
                </div>
            @else
                {{-- Patient Selector --}}
                <section class="sc-card p-5" aria-labelledby="patient-selector-label">
                    <form method="GET" action="{{ route('caregiver.messages.index') }}" class="flex flex-col sm:flex-row sm:items-center gap-3">
                        <label id="patient-selector-label" for="elderly" class="sc-label !mb-0 font-semibold">Conversation with</label>
                        <select
                            id="elderly"
                            name="elderly"
                            onchange="this.form.submit()"
                            class="sc-select sm:w-auto"
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
                    <section class="sc-card p-6 md:p-8" aria-labelledby="convo-heading">
                        {{-- Header --}}
                        <div class="mb-6 pb-4 flex flex-wrap items-center justify-between gap-2" style="border-bottom: 1px solid var(--sc-line)">
                            <div>
                                <h2 id="convo-heading" class="sc-h3">{{ $selectedElderly->user?->name ?? 'Patient' }}</h2>
                                <p class="text-sm mt-1 flex items-center gap-1.5" style="color:var(--sc-muted)">
                                    <x-lucide-lock class="sc-i w-4 h-4" aria-hidden="true" />
                                    <span>Messages are encrypted and visible only to linked accounts</span>
                                </p>
                            </div>
                        </div>

                        {{-- Messages Container --}}
                        <div class="sc-card-quiet p-4 md:p-6 h-[450px] overflow-y-auto space-y-4">
                            @forelse($messages as $message)
                                @php $isMine = $message->sender_profile_id === Auth::user()->profile?->id; @endphp
                                <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}">
                                    <div class="max-w-[85%] md:max-w-[70%]">
                                        {{-- Sender name if not mine --}}
                                        @if(!$isMine)
                                            <p class="text-xs font-semibold mb-1 ml-2" style="color:var(--sc-muted)">
                                                {{ $selectedElderly->user?->name ?? 'Patient' }}
                                            </p>
                                        @endif

                                        <div class="px-5 py-3.5 shadow-sm {{ $isMine ? 'rounded-2xl rounded-br-sm' : 'rounded-2xl rounded-bl-sm border' }}"
                                             style="{{ $isMine ? 'background:var(--sc-brand); color:var(--sc-brand-on);' : 'background:var(--sc-surface); color:var(--sc-ink); border-color:var(--sc-line);' }}">
                                            <p class="text-base font-normal leading-relaxed whitespace-pre-wrap break-words">{{ $message->message }}</p>
                                            <p class="mt-2 text-xs font-semibold sc-num" style="{{ $isMine ? 'color:var(--sc-brand-tint); opacity:0.85;' : 'color:var(--sc-muted);' }}">
                                                {{ $message->created_at->format('M j, g:i A') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="h-full flex flex-col items-center justify-center text-center p-6">
                                    <div class="sc-plate mb-3">
                                        <x-lucide-message-square class="sc-i w-6 h-6" aria-hidden="true" />
                                    </div>
                                    <p class="sc-h3">No messages yet</p>
                                    <p class="text-sm mt-1" style="color:var(--sc-muted)">Send a quick check-in to your patient.</p>
                                </div>
                            @endforelse
                        </div>

                        {{-- Message Form --}}
                        <form method="POST" action="{{ route('caregiver.messages.store') }}" class="mt-6">
                            @csrf
                            <input type="hidden" name="elderly_id" value="{{ $selectedElderly->id }}">
                            <div class="sc-field !mb-0">
                                <label for="message" class="sc-label font-semibold">Write message</label>
                                <textarea
                                    id="message"
                                    name="message"
                                    rows="3"
                                    maxlength="1200"
                                    required
                                    class="sc-textarea"
                                    placeholder="Type your message…"
                                ></textarea>
                                <div class="mt-3 flex items-center justify-between gap-3">
                                    <span class="sc-help !mt-0 sc-num">
                                        Max 1,200 characters
                                    </span>
                                    <button
                                        type="submit"
                                        class="sc-btn sc-btn-primary sc-btn-sm"
                                        title="Send message"
                                    >
                                        <span>Send message</span>
                                        <x-lucide-send class="sc-i w-4 h-4" aria-hidden="true" />
                                    </button>
                                </div>
                                <x-input-error field="message" />
                            </div>
                        </form>
                    </section>
                @endif
            @endif
        </div>
    </main>
</x-dashboard-layout>