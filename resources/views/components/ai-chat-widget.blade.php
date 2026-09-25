@props(['role' => 'elderly'])

{{-- ============================================================
     SilverCare AI companion — "Silvia" for the senior, the health
     analyst for the caregiver. Sits on every page that includes it.

     This was the last view still on the old visual language: a frosted
     glass panel over a drifting aurora, a breathing gradient orb to open
     it, three swappable colour themes, and a <style> block of its own.
     It is now built from silvercare-ui.css (.sc-fab, .sc-companion,
     .sc-bubble, .sc-md) like everything else, and follows the theme,
     dark mode and high contrast through the tokens.

     Two controls were removed rather than restyled:
     - "Switch style" cycled the panel through three accent colours.
       Colour as decoration is exactly what the design system rules out,
       and a button that repaints the screen is noise for Arthur.
     - "Voice mode (coming soon)" had no click handler: a button that
       does nothing. The senior dashboard already has a working voice
       button for readings.

     Everything the script does is unchanged: the routes, the stream and
     its fallback, session persistence, history, and the
     `ai-medication-logged` event three other components listen for.
     ============================================================ --}}

@php
    $isCaregiver = $role === 'caregiver';
    $companionName = $isCaregiver ? 'Health analyst' : 'Silvia';
@endphp

<div
    x-data="aiCompanion()"
    class="fixed inset-0 z-50 flex pointer-events-none"
    :class="isOpen ? 'pointer-events-auto' : ''"
    @keydown.escape.window="isOpen && closeChat()"
>
    {{-- Open button. A visible label, not a hover tooltip: hover does not
         exist on a phone, and an unlabelled orb says nothing about itself. --}}
    <div
        x-show="!isOpen"
        class="absolute bottom-6 right-5 sm:bottom-8 sm:right-8 pointer-events-auto"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <button type="button" @click="openChat()" class="sc-fab" aria-haspopup="dialog">
            @if($isCaregiver)
                <x-lucide-chart-column class="sc-i w-6 h-6" aria-hidden="true" />
                <span>AI health analyst</span>
            @else
                <x-lucide-message-circle class="sc-i w-6 h-6" aria-hidden="true" />
                <span>Ask Silvia</span>
            @endif
        </button>
    </div>

    {{-- Backdrop --}}
    <div
        x-show="isOpen"
        class="sc-scrim"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="closeChat()"
        style="display: none;"
    ></div>

    {{-- Panel. No transition-all here: it animated inset and width when the
         panel expanded, which breaks the transform/opacity-only rule, and it
         also faded the background for half a second on every theme change —
         a light panel behind a white title. Expand now simply resizes. --}}
    <section
        x-show="isOpen"
        role="dialog"
        aria-modal="true"
        aria-labelledby="sc-companion-title"
        class="sc-companion pointer-events-auto absolute z-[70] flex flex-col overflow-hidden"
        style="display: none;"
        :class="isExpanded
            ? 'inset-2 sm:inset-6 md:inset-8 lg:top-8 lg:bottom-8 lg:right-10 lg:left-auto lg:w-[calc(100vw-5rem)] lg:max-w-[80rem] rounded-2xl sm:rounded-[2rem]'
            : 'inset-0 sm:inset-8 sm:rounded-[2rem] md:inset-x-16 md:inset-y-10 lg:top-8 lg:bottom-8 lg:right-10 lg:left-auto lg:w-[35rem] lg:max-w-none rounded-none sm:rounded-[2rem]'"
        x-transition:enter="transition duration-300"
        x-transition:enter-start="opacity-0 translate-y-6"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-6"
    >
        {{-- Header --}}
        <header class="flex items-center justify-between gap-3 px-5 py-4 sm:px-7" style="border-bottom: 1px solid var(--sc-line)">
            <div class="flex items-center gap-3 min-w-0">
                <span class="sc-plate sc-plate-sm flex-shrink-0">
                    @if($isCaregiver)
                        <x-lucide-chart-column class="sc-i w-5 h-5" aria-hidden="true" />
                    @else
                        <x-lucide-message-circle class="sc-i w-5 h-5" aria-hidden="true" />
                    @endif
                </span>
                <div class="min-w-0">
                    <h2 id="sc-companion-title" class="sc-dialog-title">{{ $companionName }}</h2>
                    <span x-show="isStreaming" x-cloak class="sc-mark sc-mark-brand"><i></i>Writing a reply…</span>
                </div>
            </div>

            <div class="flex items-center gap-1 flex-shrink-0">
                <button type="button" @click="isExpanded = !isExpanded" class="sc-icon-btn hidden sm:inline-flex"
                        aria-pressed="false" :aria-pressed="isExpanded ? 'true' : 'false'">
                    <x-lucide-maximize-2 x-show="!isExpanded" class="sc-i w-5 h-5" aria-hidden="true" />
                    <x-lucide-minimize-2 x-show="isExpanded" x-cloak class="sc-i w-5 h-5" aria-hidden="true" />
                    <span class="sr-only">Wider panel</span>
                </button>
                <button type="button" @click="startNewSession()" class="sc-icon-btn">
                    <x-lucide-rotate-ccw class="sc-i w-5 h-5" aria-hidden="true" />
                    <span class="sr-only">Start a new conversation</span>
                </button>
                <button type="button" @click="closeChat()" class="sc-icon-btn">
                    <x-lucide-x class="sc-i w-5 h-5" aria-hidden="true" />
                    <span class="sr-only">Close</span>
                </button>
            </div>
        </header>

        {{-- Messages --}}
        <div x-ref="messagesContainer" class="relative flex-1 overflow-y-auto px-5 py-5 sm:px-7">
            <div x-show="isLoadingHistory" class="flex h-full flex-col items-center justify-center gap-3" style="display:none;">
                <x-lucide-loader-circle class="sc-i w-8 h-8 animate-spin" style="color: var(--sc-brand-text)" aria-hidden="true" />
                <p class="font-medium" style="color: var(--sc-muted)">Restoring your conversation…</p>
            </div>

            <div x-show="!isLoadingHistory && messages.length === 0" class="sc-msg-in" style="display:none;">
                <div class="sc-card-quiet p-6">
                    <h3 class="sc-h3">
                        @if($isCaregiver) Ask about your patient @else Let us plan today together. @endif
                    </h3>
                    <p class="mt-2" style="color: var(--sc-body)">
                        @if($isCaregiver)
                            I can analyse your patient's health data and medication adherence, and point out trends. Ask me anything about how they are doing.
                        @else
                            I can explain your medications, sum up your health trends, and help you get through today's tasks with less stress.
                        @endif
                    </p>
                </div>

                <ul class="mt-4 space-y-3">
                    <template x-for="(prompt, index) in suggestedPrompts" :key="index">
                        <li>
                            <button type="button" @click="sendSuggestedPrompt(prompt)"
                                    class="sc-card sc-lift w-full px-5 py-4 text-left font-semibold"
                                    style="color: var(--sc-ink)" x-text="prompt"></button>
                        </li>
                    </template>
                </ul>
            </div>

            <div class="space-y-4">
                <template x-for="(msg, index) in messages" :key="index">
                    <div class="sc-msg-in flex" :class="msg.role === 'user' ? 'justify-end' : 'justify-start'">
                        <div x-show="msg.role === 'user'" class="sc-bubble sc-bubble-me">
                            <span class="sr-only">You said: </span><span x-text="msg.content"></span>
                        </div>

                        <article x-show="msg.role !== 'user'" class="sc-bubble sc-bubble-them">
                            <div class="sc-md" x-html="renderMarkdown(msg.content)"></div>
                            <div x-show="msg.streaming" class="mt-3 flex items-center gap-1.5" aria-hidden="true">
                                <span class="h-2 w-2 animate-bounce rounded-full" style="background: var(--sc-brand-text);"></span>
                                <span class="h-2 w-2 animate-bounce rounded-full" style="background: var(--sc-brand-text); animation-delay: .12s;"></span>
                                <span class="h-2 w-2 animate-bounce rounded-full" style="background: var(--sc-brand-text); animation-delay: .24s;"></span>
                            </div>
                        </article>
                    </div>
                </template>
            </div>

            {{-- Announces that a reply is coming, once — not every streamed word. --}}
            <p class="sr-only" aria-live="polite" x-text="isStreaming ? '{{ $companionName }} is writing a reply' : ''"></p>
        </div>

        {{-- Composer --}}
        <footer class="px-5 py-4 sm:px-7" style="border-top: 1px solid var(--sc-line)">
            <form @submit.prevent="sendMessage" class="flex items-center gap-2">
                <label for="sc-companion-input" class="sr-only">
                    @if($isCaregiver) Ask about your patient's health @else Ask {{ $companionName }} a question @endif
                </label>
                <input
                    id="sc-companion-input"
                    type="text"
                    x-model="input"
                    x-ref="chatInput"
                    @keydown.escape="closeChat()"
                    placeholder="@if($isCaregiver) Ask about your patient's health… @else Tell me what you need help with @endif"
                    class="sc-input flex-1 min-w-0"
                    autocomplete="off"
                    :disabled="isLoading"
                >
                <button type="submit" class="sc-btn sc-btn-primary flex-shrink-0" :disabled="isLoading || input.trim() === ''">
                    <x-lucide-send class="sc-i w-5 h-5" aria-hidden="true" />
                    <span class="sr-only sm:not-sr-only">Send</span>
                </button>
            </form>
        </footer>
    </section>
</div>

<script>
    function parseAiMarkdown(text) {
        if (!text) return '';

        // Headings drop two levels (# -> h3): the panel title is the h2, and a
        // reply must never add a second <h1> to the page underneath. `code`
        // takes its look from .sc-md code rather than inline colour classes.
        let html = text
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/^### (.+)$/gm, '<h4>$1</h4>')
            .replace(/^## (.+)$/gm, '<h3>$1</h3>')
            .replace(/^# (.+)$/gm, '<h3>$1</h3>')
            .replace(/\*\*\*(.+?)\*\*\*/g, '<strong><em>$1</em></strong>')
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.+?)\*/g, '<em>$1</em>')
            .replace(/`([^`]+)`/g, '<code>$1</code>')
            .replace(/^[\-\*]\s+(.+)$/gm, '<li>$1</li>')
            .replace(/^\d+\.\s+(.+)$/gm, '<li class="list-decimal">$1</li>')
            .replace(/\[(.+?)\]\((.+?)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>')
            .replace(/\n\n/g, '</p><p>')
            .replace(/\n/g, '<br>');

        html = html.replace(/(<li>.*?<\/li>)(?:\s*<br>)?/gs, '$1');
        html = html.replace(/((?:<li(?: class="list-decimal")?>.*?<\/li>\s*)+)/gs, function(match, p1) {
            return p1.includes('class="list-decimal"') ? '<ol>' + p1 + '</ol>' : '<ul>' + p1 + '</ul>';
        });

        return '<p>' + html + '</p>';
    }

    document.addEventListener('alpine:init', () => {
        Alpine.data('aiCompanion', () => ({
            role: '{{ $role }}',
            historyRoute: '{{ $role === "caregiver" ? route("caregiver.ai-analyst.history") : route("elderly.ai-assistant.history") }}',
            newSessionRoute: '{{ $role === "caregiver" ? route("caregiver.ai-analyst.new-session") : route("elderly.ai-assistant.new-session") }}',
            streamRoute: '{{ $role === "caregiver" ? route("caregiver.ai-analyst.stream") : route("elderly.ai-assistant.stream") }}',
            chatRoute: '{{ $role === "caregiver" ? route("caregiver.ai-analyst.chat") : route("elderly.ai-assistant.chat") }}',
            sessionStorageKey: '{{ $role === "caregiver" ? "silvercare.caregiver.ai.session_id" : "silvercare.elderly.ai.session_id" }}',

            isOpen: false,
            isExpanded: false,
            messages: [],
            input: '',
            isLoading: false,
            isStreaming: false,
            isLoadingHistory: false,
            sessionId: null,
            suggestedPrompts: [],
            historyLoaded: false,

            init() {
                this.restoreSessionId();

                this.$watch('isOpen', value => {
                    if (value && !this.historyLoaded) {
                        this.loadHistory();
                    }

                    if (value) {
                        setTimeout(() => this.$refs.chatInput?.focus(), 320);
                        document.body.style.overflow = 'hidden';
                    } else {
                        document.body.style.overflow = '';
                    }
                });
            },

            openChat() {
                this.isOpen = true;
            },

            closeChat() {
                this.isOpen = false;
                this.isExpanded = false;
            },

            scrollToBottom() {
                setTimeout(() => {
                    const c = this.$refs.messagesContainer;
                    if (c) c.scrollTo({ top: c.scrollHeight, behavior: 'smooth' });
                }, 90);
            },

            renderMarkdown(text) {
                return parseAiMarkdown(text);
            },

            restoreSessionId() {
                try {
                    const stored = window.localStorage.getItem(this.sessionStorageKey);
                    if (!stored) {
                        return;
                    }

                    const parsed = Number.parseInt(stored, 10);
                    if (Number.isInteger(parsed) && parsed > 0) {
                        this.sessionId = parsed;
                    }
                } catch (_e) {
                    // Ignore storage errors in private browsing contexts.
                }
            },

            persistSessionId(sessionId) {
                if (!Number.isInteger(sessionId) || sessionId <= 0) {
                    return;
                }

                this.sessionId = sessionId;

                try {
                    window.localStorage.setItem(this.sessionStorageKey, String(sessionId));
                } catch (_e) {
                    // Ignore storage errors in private browsing contexts.
                }
            },

            async loadHistory() {
                this.isLoadingHistory = true;

                try {
                    const url = new URL(this.historyRoute, window.location.origin);
                    if (this.sessionId) {
                        url.searchParams.set('session_id', this.sessionId);
                    }

                    const res = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    });

                    if (!res.ok) {
                        throw new Error(`Server returned ${res.status}`);
                    }

                    const data = await res.json();
                    if (data.success) {
                        this.persistSessionId(data.session_id);
                        this.messages = data.messages || [];
                        this.suggestedPrompts = data.suggested_prompts || [];

                        if (this.suggestedPrompts.length === 0) {
                            if (this.role === 'caregiver') {
                                this.suggestedPrompts = [
                                    'What is my patient\'s medication adherence?',
                                    'Are there any worrying vitals?',
                                    'Summarize the patient\'s health status.'
                                ];
                            } else {
                                this.suggestedPrompts = [
                                    'What medications should I prioritize today?',
                                    'Summarize my progress in simple steps.',
                                    'Help me review my vitals and next actions.'
                                ];
                            }
                        }

                        this.historyLoaded = true;
                    }
                } catch (e) {
                    console.error('History load failed:', e);
                    window.Swal?.fire({
                        icon: 'error',
                        title: 'Oops!',
                        text: 'Failed to restore conversation. Check connection.',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                } finally {
                    this.isLoadingHistory = false;
                    this.scrollToBottom();
                }
            },

            async startNewSession() {
                try {
                    const res = await fetch(this.newSessionRoute, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });

                    const data = await res.json();
                    if (data.success) {
                        this.persistSessionId(data.session_id);
                        this.messages = [];
                        this.loadHistory();
                    }
                } catch (e) {
                    console.error('Start session failed:', e);
                }
            },

            sendSuggestedPrompt(prompt) {
                this.input = prompt;
                this.sendMessage();
            },

            async sendMessage() {
                if (this.input.trim() === '' || this.isLoading) {
                    return;
                }

                const userMsg = this.input.trim();
                this.messages.push({ role: 'user', content: userMsg });
                this.input = '';
                this.isLoading = true;
                this.scrollToBottom();

                try {
                    const res = await fetch(this.streamRoute, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'text/event-stream'
                        },
                        body: JSON.stringify({ message: userMsg, session_id: this.sessionId })
                    });

                    if (!res.ok) {
                        throw new Error('Stream request failed');
                    }

                    const reader = res.body.getReader();
                    const decoder = new TextDecoder();
                    let aiIdx = null;
                    let buffer = '';

                    while (true) {
                        const { done, value } = await reader.read();
                        if (done) break;

                        buffer += decoder.decode(value, { stream: true });
                        const lines = buffer.split('\n');
                        buffer = lines.pop();

                        for (const line of lines) {
                            if (!line.startsWith('data: ')) {
                                continue;
                            }

                            try {
                                const payload = JSON.parse(line.substring(6));
                                if (payload.type === 'session') {
                                    this.persistSessionId(payload.session_id);
                                } else if (payload.type === 'chunk') {
                                    if (aiIdx === null) {
                                        this.messages.push({ role: 'ai', content: '', streaming: true });
                                        aiIdx = this.messages.length - 1;
                                        this.isStreaming = true;
                                    }

                                    this.messages[aiIdx].content += payload.content;
                                    this.scrollToBottom();
                                } else if (payload.type === 'done') {
                                    if (aiIdx !== null) {
                                        this.messages[aiIdx].streaming = false;
                                    }
                                    this.isStreaming = false;
                                } else if (payload.type === 'action') {
                                    this.handleAction(payload.action);
                                } else if (payload.type === 'error') {
                                    this.messages.push({ role: 'ai', content: payload.content });
                                    this.isStreaming = false;
                                }
                            } catch (_e) {
                                // Ignore malformed chunk lines.
                            }
                        }
                    }
                } catch (err) {
                    console.error('Stream failure:', err);
                    await this.sendMessageFallback(userMsg);
                } finally {
                    this.isLoading = false;
                    this.isStreaming = false;
                    this.scrollToBottom();
                    setTimeout(() => this.$refs.chatInput?.focus(), 120);
                }
            },

            async sendMessageFallback(msg) {
                try {
                    const res = await fetch(this.chatRoute, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ message: msg, session_id: this.sessionId })
                    });

                    const data = await res.json();
                    if (data.success) {
                        this.persistSessionId(data.session_id);
                        this.messages.push({ role: 'ai', content: data.message });

                        if (Array.isArray(data.actions)) {
                            data.actions.forEach(action => this.handleAction(action));
                        }
                    } else {
                        this.messages.push({ role: 'ai', content: 'Connection issue.' });
                    }
                } catch (_e) {
                    this.messages.push({ role: 'ai', content: 'Connection issue.' });
                }
            },

            handleAction(action) {
                if (!action || typeof action !== 'object') {
                    return;
                }

                if (action.type === 'medication_logged') {
                    window.dispatchEvent(new CustomEvent('ai-medication-logged', {
                        detail: action,
                    }));
                }
            }
        }));
    });
</script>
