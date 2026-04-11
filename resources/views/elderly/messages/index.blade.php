<x-dashboard-layout>
    <x-slot:title>Care Messages - SilverCare</x-slot:title>

    <x-dashboard-nav
        title="Care Messages"
        role="elderly"
        :unread-notifications="$unreadNotifications"
        subtitle="Secure messages from your caregiver"
    />

    <main class="max-w-[800px] mx-auto px-4 sm:px-6 lg:px-12 py-6 space-y-5">
        <x-flash-messages />

        {{-- Back Navigation --}}
        <div>
            <a href="{{ route('dashboard') }}" class="back-nav-pill">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Back to Home
            </a>
        </div>

        @if(!$caregiver)
            {{-- No Caregiver Linked — Premium Empty State --}}
            <section class="empty-state rounded-3xl border border-amber-100 bg-gradient-to-br from-amber-50/80 to-orange-50/60 py-12">
                <div class="empty-state-icon !bg-gradient-to-br !from-amber-100 !to-orange-100">
                    <svg class="w-10 h-10 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
                <h3 class="!text-amber-900">No caregiver linked yet</h3>
                <p class="!text-amber-700">Link a caregiver first to start in‑app messaging. It only takes a minute!</p>
                <a href="{{ route('dashboard') }}#link-caregiver-card" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-amber-500 px-6 py-3 text-base font-bold text-white hover:bg-amber-600 transition-colors min-h-touch shadow-lg shadow-amber-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                    Link Caregiver
                </a>
            </section>
        @else
            {{-- Chat Container --}}
            <section class="chat-container" x-data="chatApp()" x-init="scrollToBottom()">

                {{-- Chat Header --}}
                <div class="chat-header">
                    <div class="w-11 h-11 rounded-full bg-navy-100 flex items-center justify-center text-navy-600 font-black text-lg flex-shrink-0">
                        {{ strtoupper(substr($caregiver->user?->name ?? 'C', 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-lg font-extrabold text-slate-900 truncate">{{ $caregiver->user?->name ?? 'Your Caregiver' }}</h3>
                        <p class="text-xs font-semibold text-slate-400">Secure SilverCare messaging</p>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        <span class="text-xs font-bold text-emerald-600">Active</span>
                    </div>
                </div>

                {{-- Scrollable Message Area --}}
                <div class="chat-scroll-area" id="chatScrollArea" x-ref="chatArea">
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
                            <div class="chat-date-separator">
                                <span>
                                    @if($message->created_at->isToday())
                                        Today
                                    @elseif($message->created_at->isYesterday())
                                        Yesterday
                                    @else
                                        {{ $message->created_at->format('M j, Y') }}
                                    @endif
                                </span>
                            </div>
                        @endif

                        {{-- Message Bubble --}}
                        <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }} mb-2">
                            <div class="chat-bubble {{ $isMine ? 'chat-bubble-outgoing' : 'chat-bubble-incoming' }}">
                                <p class="whitespace-pre-wrap">{{ $message->message }}</p>
                                <p class="mt-1.5 text-xs {{ $isMine ? 'text-navy-200' : 'text-slate-400' }} font-semibold">
                                    {{ $message->created_at->format('g:i A') }}
                                </p>
                            </div>
                        </div>
                    @empty
                        {{-- Empty Chat State --}}
                        <div class="flex flex-col items-center justify-center h-full text-center py-12">
                            <div class="empty-state-icon mb-4">
                                <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-extrabold text-slate-700 mb-1">Start a conversation</h3>
                            <p class="text-base text-slate-500 font-medium max-w-xs">Send a message to your caregiver — they'd love to hear from you! 💬</p>
                        </div>
                    @endforelse
                </div>

                {{-- Sticky Input Bar --}}
                <div class="chat-input-bar">
                    <div class="flex-1 relative">
                        <label for="chat-message-input" class="sr-only">Type a message</label>
                        <textarea
                            id="chat-message-input"
                            x-ref="messageInput"
                            x-model="messageText"
                            @keydown.enter.prevent="if (!$event.shiftKey) sendMessage()"
                            @input="autoGrow($event)"
                            maxlength="1200"
                            rows="1"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-base font-semibold text-slate-800 resize-none
                                   focus:border-navy-400 focus:bg-white focus:ring-2 focus:ring-navy-100 transition-all
                                   placeholder:text-slate-400"
                            placeholder="Type a message..."
                            style="max-height: 120px; overflow-y: auto;"
                        ></textarea>
                        {{-- Character Counter --}}
                        <span class="absolute bottom-1.5 right-3 text-xs text-slate-300 font-semibold pointer-events-none"
                              x-show="messageText.length > 100"
                              x-text="messageText.length + '/1200'"
                              x-transition></span>
                    </div>
                    {{-- Circular Send Button --}}
                    <button
                        type="button"
                        class="chat-send-btn"
                        @click="sendMessage()"
                        :disabled="sending || messageText.trim().length === 0"
                        aria-label="Send message"
                    >
                        <template x-if="!sending">
                            <svg class="w-5 h-5 -rotate-45" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"></path>
                            </svg>
                        </template>
                        <template x-if="sending">
                            <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </template>
                    </button>
                </div>

            </section>
        @endif
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
                    bubbleWrapper.className = 'flex justify-end mb-2 animate-fade-in';
                    bubbleWrapper.innerHTML = `
                        <div class="chat-bubble chat-bubble-outgoing">
                            <p class="whitespace-pre-wrap">${this.escapeHtml(text)}</p>
                            <p class="mt-1.5 text-xs text-navy-200 font-semibold">${timeStr}</p>
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
