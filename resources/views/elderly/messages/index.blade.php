<x-dashboard-layout sc>
    <x-slot:title>Care Messages - SilverCare</x-slot:title>

    <x-dashboard-nav
        title="Care Messages"
        role="elderly"
        :unread-notifications="$unreadNotifications"
        subtitle="Secure messages from your caregiver"
    />

    <main id="main-content" class="sc-app-main">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-12 py-6 space-y-6">
            <x-flash-messages />

            {{-- Back Navigation --}}
            <div class="flex justify-end">
                <a href="{{ route('dashboard') }}" class="sc-btn sc-btn-ghost inline-flex items-center gap-1.5 text-sm">
                    <x-lucide-arrow-left class="sc-i w-4 h-4" aria-hidden="true" />
                    <span>{{ __('Back to Home') }}</span>
                </a>
            </div>

            @if(!$caregiver)
                {{-- No Caregiver Linked Empty State --}}
                <div class="sc-empty">
                    <div class="sc-plate mb-2">
                        <x-lucide-users class="sc-i w-6 h-6" aria-hidden="true" />
                    </div>
                    <h2 class="sc-h3">{{ __('No caregiver linked yet') }}</h2>
                    <p style="color:var(--sc-muted)">{{ __('Link a caregiver first to start in-app messaging. It only takes a minute!') }}</p>
                    <a href="{{ route('dashboard') }}#link-caregiver-card" class="sc-btn sc-btn-primary mt-3 inline-flex items-center gap-2">
                        <x-lucide-user-plus class="sc-i w-4 h-4" aria-hidden="true" />
                        <span>{{ __('Link Caregiver') }}</span>
                    </a>
                </div>
            @else
                {{-- Chat Container --}}
                <section class="sc-card p-5 sm:p-7" aria-labelledby="convo-heading" x-data="chatApp()" x-init="scrollToBottom()">

                    {{-- Chat Header --}}
                    <div class="mb-5 pb-4 flex items-center justify-between gap-3 border-b border-[var(--sc-border)]">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-11 h-11 rounded-full bg-[var(--sc-surface-quiet)] border border-[var(--sc-border)] flex items-center justify-center text-[var(--sc-ink)] font-bold text-base flex-shrink-0">
                                {{ strtoupper(substr($caregiver->user?->name ?? 'C', 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <h2 id="convo-heading" class="text-lg font-bold text-[var(--sc-ink)] truncate">{{ $caregiver->user?->name ?? 'Your Caregiver' }}</h2>
                                <p class="text-xs font-semibold text-[var(--sc-ink-muted)] flex items-center gap-1.5">
                                    <x-lucide-lock class="sc-i w-3.5 h-3.5" aria-hidden="true" />
                                    <span>{{ __('Secure SilverCare messaging') }}</span>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5 flex-shrink-0">
                            <span class="sc-mark sc-mark-ok"><i></i>{{ __('Active') }}</span>
                        </div>
                    </div>

                    {{-- Scrollable Message Area --}}
                    <div class="sc-card-quiet p-4 sm:p-6 h-[450px] overflow-y-auto space-y-3" id="chatScrollArea" x-ref="chatArea">
                        @forelse($messages as $message)
                            @php
                                $isMine = $message->sender_profile_id === Auth::user()->profile?->id;
                                $messageDate = $message->created_at->format('Y-m-d');
                                $prevDate = isset($prevMessageDate) ? $prevMessageDate : null;
                                $showDate = $messageDate !== $prevDate;
                                $prevMessageDate = $messageDate;
                            @endphp

                            {{-- Date Separator --}}
                            @if($showDate)
                                <div class="flex items-center justify-center my-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-[var(--sc-surface)] border border-[var(--sc-border)] text-[var(--sc-ink-muted)] shadow-xs sc-num">
                                        @if($message->created_at->isToday())
                                            {{ __('Today') }}
                                        @elseif($message->created_at->isYesterday())
                                            {{ __('Yesterday') }}
                                        @else
                                            {{ $message->created_at->format('M j, Y') }}
                                        @endif
                                    </span>
                                </div>
                            @endif

                            {{-- Message Bubble --}}
                            @php
                                $displayMessage = trim(preg_replace('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', '', $message->message));
                            @endphp
                            <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }} mb-2">
                                <div class="max-w-[85%] sm:max-w-[75%]">
                                    <div class="px-4 py-3 shadow-xs {{ $isMine ? 'rounded-2xl rounded-br-sm' : 'rounded-2xl rounded-bl-sm border' }}"
                                         style="{{ $isMine ? 'background:var(--sc-brand); color:var(--sc-brand-on);' : 'background:var(--sc-surface); color:var(--sc-ink); border-color:var(--sc-border);' }}">
                                        <p class="text-base font-normal leading-relaxed whitespace-pre-wrap break-words">{{ $displayMessage }}</p>
                                        <p class="mt-1.5 text-xs font-semibold sc-num {{ $isMine ? 'text-right' : '' }}"
                                           style="{{ $isMine ? 'color:var(--sc-brand-tint); opacity:0.85;' : 'color:var(--sc-ink-muted);' }}">
                                            {{ $message->created_at->format('g:i A') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @empty
                            {{-- Empty Chat State --}}
                            <div class="h-full flex flex-col items-center justify-center text-center p-8">
                                <div class="sc-plate mb-3">
                                    <x-lucide-message-circle class="sc-i w-6 h-6" aria-hidden="true" />
                                </div>
                                <h3 class="text-lg font-bold text-[var(--sc-ink)] mb-1">{{ __('Start a conversation') }}</h3>
                                <p class="text-sm text-[var(--sc-ink-muted)] max-w-sm">
                                    {{ __("Send a message to your caregiver — they'd love to hear from you!") }}
                                </p>
                            </div>
                        @endforelse
                    </div>

                    {{-- Sticky Input Bar --}}
                    <div class="flex items-end gap-3 mt-4 pt-4 border-t border-[var(--sc-border)]">
                        <div class="flex-1 relative">
                            <label for="chat-message-input" class="sr-only">{{ __('Type a message') }}</label>
                            <textarea
                                id="chat-message-input"
                                x-ref="messageInput"
                                x-model="messageText"
                                @keydown.enter.prevent="if (!$event.shiftKey) sendMessage()"
                                @input="autoGrow($event)"
                                maxlength="1200"
                                rows="1"
                                class="sc-textarea !rounded-2xl !py-3 !px-4 text-base resize-none"
                                placeholder="{{ __('Type a message…') }}"
                                style="max-height: 120px; overflow-y: auto;"
                            ></textarea>
                            {{-- Character Counter --}}
                            <span class="absolute bottom-2 right-3 text-xs text-[var(--sc-ink-muted)] font-semibold pointer-events-none sc-num"
                                  x-show="messageText.length > 100"
                                  x-text="messageText.length + '/1200'"
                                  x-transition></span>
                        </div>
                        {{-- Send Button --}}
                        <button
                            type="button"
                            class="sc-btn sc-btn-primary !p-3 !rounded-2xl flex-shrink-0 disabled:opacity-40"
                            @click="sendMessage()"
                            :disabled="sending || messageText.trim().length === 0"
                            aria-label="{{ __('Send message') }}"
                        >
                            <template x-if="!sending">
                                <x-lucide-send class="sc-i w-5 h-5" aria-hidden="true" />
                            </template>
                            <template x-if="sending">
                                <svg class="sc-i w-5 h-5 animate-spin" aria-hidden="true" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </template>
                        </button>
                    </div>

                </section>
            @endif
        </div>
    </main>

    @push('scripts')
    <script>
        function chatApp() {
            return {
                messageText: '',
                sending: false,

                scrollToBottom() {
                    this.$nextTick(() => {
                        const area = this.$refs.chatArea;
                        if (area) {
                            area.scrollTop = area.scrollHeight;
                        }
                    });
                },

                autoGrow(event) {
                    const el = event.target;
                    el.style.height = 'auto';
                    el.style.height = Math.min(el.scrollHeight, 120) + 'px';
                },

                async sendMessage() {
                    const text = this.messageText.trim();
                    if (!text || this.sending) return;

                    this.sending = true;

                    // Optimistic UI: append outgoing bubble immediately
                    const area = this.$refs.chatArea;
                    const now = new Date();
                    const timeStr = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });

                    const bubbleWrapper = document.createElement('div');
                    bubbleWrapper.className = 'flex justify-end mb-2';
                    bubbleWrapper.innerHTML = `
                        <div class="max-w-[85%] sm:max-w-[75%]">
                            <div class="px-4 py-3 rounded-2xl rounded-br-sm shadow-xs" style="background:var(--sc-brand); color:var(--sc-brand-on);">
                                <p class="text-base font-normal leading-relaxed whitespace-pre-wrap break-words">${this.escapeHtml(text)}</p>
                                <p class="mt-1.5 text-xs font-semibold sc-num text-right" style="color:var(--sc-brand-tint); opacity:0.85;">${timeStr}</p>
                            </div>
                        </div>
                    `;
                    area.appendChild(bubbleWrapper);
                    this.scrollToBottom();

                    // Clear input
                    this.messageText = '';
                    const input = this.$refs.messageInput;
                    if (input) {
                        input.style.height = 'auto';
                    }

                    try {
                        const response = await fetch('{{ route("elderly.messages.store") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ message: text }),
                        });

                        if (!response.ok) {
                            // Remove optimistic bubble on failure
                            bubbleWrapper.remove();
                            this.messageText = text;

                            if (Alpine.store('toast')) {
                                Alpine.store('toast').error('Failed to send message. Please try again.');
                            }
                        }
                    } catch (e) {
                        bubbleWrapper.remove();
                        this.messageText = text;

                        if (Alpine.store('toast')) {
                            Alpine.store('toast').error('Connection error. Please check your internet.');
                        }
                    } finally {
                        this.sending = false;
                        this.$refs.messageInput?.focus();
                    }
                },

                escapeHtml(text) {
                    const div = document.createElement('div');
                    div.textContent = text;
                    return div.innerHTML;
                }
            };
        }
    </script>
    @endpush

</x-dashboard-layout>
