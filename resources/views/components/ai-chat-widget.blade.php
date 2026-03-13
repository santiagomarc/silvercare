{{-- ============================================================
     SilverCare Ambient AI Companion
     ============================================================ --}}

<style>
    .ai-companion-root {
        --ai-accent: #0f766e;
        --ai-accent-soft: #ccfbf1;
        --ai-highlight: #0369a1;
        --ai-surface: rgba(255, 255, 255, 0.8);
        --ai-surface-strong: rgba(255, 255, 255, 0.92);
        --ai-border: rgba(255, 255, 255, 0.65);
        --ai-ink: #0f172a;
        --ai-muted: #475569;
    }

    .ai-theme-coast {
        --ai-accent: #0f766e;
        --ai-accent-soft: #ccfbf1;
        --ai-highlight: #0284c7;
    }

    .ai-theme-sunrise {
        --ai-accent: #b45309;
        --ai-accent-soft: #fef3c7;
        --ai-highlight: #ea580c;
    }

    .ai-theme-grove {
        --ai-accent: #166534;
        --ai-accent-soft: #dcfce7;
        --ai-highlight: #15803d;
    }

    .ai-glass-shell {
        background: linear-gradient(140deg, var(--ai-surface-strong), var(--ai-surface));
        border: 1px solid var(--ai-border);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        box-shadow: 0 28px 80px rgba(15, 23, 42, 0.2);
    }

    .ai-noise {
        position: absolute;
        inset: 0;
        pointer-events: none;
        opacity: 0.05;
        background-image: radial-gradient(circle at 2px 2px, #0f172a 0.8px, transparent 0.8px);
        background-size: 6px 6px;
    }

    .ai-aurora {
        position: absolute;
        inset: -25%;
        background:
            radial-gradient(circle at 15% 25%, color-mix(in srgb, var(--ai-accent-soft) 95%, #ffffff 5%) 0%, transparent 45%),
            radial-gradient(circle at 80% 15%, color-mix(in srgb, var(--ai-highlight) 25%, #ffffff 75%) 0%, transparent 42%),
            radial-gradient(circle at 50% 88%, color-mix(in srgb, var(--ai-accent) 20%, #ffffff 80%) 0%, transparent 45%);
        animation: auroraDrift 11s ease-in-out infinite alternate;
        pointer-events: none;
    }

    .ai-orb {
        animation: aiOrbBreath 3s ease-in-out infinite;
    }

    .ai-orb-ring {
        animation: aiRingRipple 2.4s ease-out infinite;
    }

    .ai-stream-line {
        background: linear-gradient(90deg, var(--ai-accent), var(--ai-highlight), var(--ai-accent));
        background-size: 210% 100%;
        animation: aiStream 2s linear infinite;
    }

    .ai-message-in {
        animation: aiMessageIn 0.42s cubic-bezier(0.19, 1, 0.22, 1) both;
    }

    .ai-md {
        color: var(--ai-ink);
    }

    .ai-md p { margin-bottom: 0.8rem; line-height: 1.65; }
    .ai-md strong { font-weight: 700; color: var(--ai-accent); }
    .ai-md em { font-style: italic; }
    .ai-md ul, .ai-md ol { padding-left: 1.2rem; margin-bottom: 0.9rem; }
    .ai-md ul { list-style: none; }
    .ai-md ul li::before {
        content: "•";
        color: var(--ai-highlight);
        font-weight: 700;
        display: inline-block;
        width: 1em;
        margin-left: -1em;
    }
    .ai-md ol { list-style: decimal; }
    .ai-md li { margin-bottom: 0.35rem; }
    .ai-md h1, .ai-md h2, .ai-md h3 {
        font-weight: 800;
        margin-bottom: 0.55rem;
        line-height: 1.25;
        letter-spacing: -0.02em;
        color: #0b1324;
    }
    .ai-md h1 { font-size: 1.3rem; }
    .ai-md h2 { font-size: 1.15rem; }
    .ai-md h3 { font-size: 1.05rem; }
    .ai-md a {
        color: var(--ai-highlight);
        font-weight: 700;
        text-decoration: none;
        border-bottom: 2px solid color-mix(in srgb, var(--ai-highlight) 30%, #ffffff 70%);
    }

    .ai-scroll::-webkit-scrollbar { width: 10px; }
    .ai-scroll::-webkit-scrollbar-track { background: transparent; }
    .ai-scroll::-webkit-scrollbar-thumb {
        background: color-mix(in srgb, var(--ai-highlight) 25%, #94a3b8 75%);
        border-radius: 999px;
    }

    @keyframes aiMessageIn {
        from { opacity: 0; transform: translateY(12px) scale(0.985); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    @keyframes aiOrbBreath {
        0%, 100% { transform: scale(1); opacity: 0.88; }
        50% { transform: scale(1.08); opacity: 1; }
    }

    @keyframes aiRingRipple {
        from { transform: scale(0.92); opacity: 0.45; }
        to { transform: scale(1.25); opacity: 0; }
    }

    @keyframes aiStream {
        0% { background-position: 0% 50%; }
        100% { background-position: 210% 50%; }
    }

    @keyframes auroraDrift {
        0% { transform: translate(-2%, 0%) rotate(0deg); }
        100% { transform: translate(2%, -3%) rotate(5deg); }
    }
</style>

<div
    x-data="aiCompanion()"
    class="ai-companion-root fixed inset-0 z-50 flex pointer-events-none"
    :class="isOpen ? 'pointer-events-auto ' + themeClass() : themeClass()"
>
    {{-- Trigger Orb --}}
    <div
        x-show="!isOpen"
        class="absolute bottom-6 right-6 sm:bottom-10 sm:right-10 pointer-events-auto"
        x-transition:enter="transition ease-out duration-500"
        x-transition:enter-start="opacity-0 translate-y-8 scale-75"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-250"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-70"
    >
        <button
            @click="openChat()"
            class="group relative h-20 w-20 sm:h-24 sm:w-24 rounded-full focus:outline-none focus:ring-4"
            style="background: linear-gradient(145deg, var(--ai-accent-soft), color-mix(in srgb, var(--ai-accent) 22%, #ffffff 78%)); color: var(--ai-accent);"
            aria-label="Open AI Companion"
        >
            <span class="ai-orb-ring absolute inset-0 rounded-full border-2" style="border-color: color-mix(in srgb, var(--ai-accent) 40%, transparent 60%);"></span>
            <span class="ai-orb absolute inset-2 rounded-full shadow-xl" style="background: radial-gradient(circle at 30% 20%, #ffffff 0%, color-mix(in srgb, var(--ai-highlight) 30%, #ffffff 70%) 42%, color-mix(in srgb, var(--ai-accent) 48%, #ffffff 52%) 100%);"></span>
            <span class="relative z-10 flex h-full w-full items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-9 w-9 drop-shadow" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M11.25 3.75a8.25 8.25 0 108.25 8.25M11.25 3.75A8.25 8.25 0 0120.4 9M11.25 3.75c2.4 1.9 3.9 4.6 4.2 7.65m-8.7-2.4c.95 1.15 2.35 1.8 3.85 1.8M5.25 12.75h1.5m8.25 0h3" />
                </svg>
            </span>
            <span class="pointer-events-none absolute -left-44 top-1/2 -translate-y-1/2 rounded-2xl border px-5 py-3 text-left text-sm font-semibold opacity-0 transition-all duration-300 group-hover:translate-x-0 group-hover:opacity-100"
                  style="background: rgba(255,255,255,0.9); border-color: rgba(255,255,255,0.7); color: #334155; transform: translate(12px, -50%);">
                Ask your companion
            </span>
        </button>
    </div>

    {{-- Backdrop --}}
    <div
        x-show="isOpen"
        class="absolute inset-0 bg-slate-900/28 backdrop-blur-[3px]"
        x-transition:enter="transition ease-out duration-350"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-250"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="closeChat()"
        style="display: none;"
    ></div>

    {{-- Companion Panel --}}
    <section
        x-show="isOpen"
        class="ai-glass-shell pointer-events-auto absolute z-50 flex flex-col overflow-hidden transition-all duration-500 ease-in-out transform"
        :class="isExpanded
            ? 'inset-2 sm:inset-6 md:inset-8 lg:inset-y-8 lg:inset-x-4 lg:mx-auto lg:w-full lg:max-w-5xl rounded-2xl sm:rounded-[2.2rem]'
            : 'inset-0 sm:inset-8 sm:rounded-[2.2rem] md:inset-x-16 md:inset-y-10 lg:inset-y-8 lg:right-10 lg:left-auto lg:w-[35rem] lg:mx-0 rounded-none sm:rounded-[2.2rem]'"
        x-transition:enter="transition ease-out duration-450"
        x-transition:enter-start="opacity-0 translate-y-8 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-250"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-8 scale-95"
        style="display: none;"
    >
        <div class="ai-noise"></div>
        <div class="ai-aurora"></div>

        {{-- Header --}}
        <header class="relative z-10 px-6 pt-6 pb-4 sm:px-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="relative h-12 w-12 rounded-2xl border bg-white/80 p-2 shadow-sm" style="border-color: color-mix(in srgb, var(--ai-accent) 20%, #ffffff 80%);">
                        <div class="ai-orb h-full w-full rounded-xl" style="background: radial-gradient(circle at 35% 25%, #ffffff 0%, var(--ai-accent-soft) 45%, color-mix(in srgb, var(--ai-highlight) 20%, #ffffff 80%) 100%);"></div>
                    </div>
                    <div class="flex flex-col justify-center">
                        <div class="flex items-center gap-2">
                            <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">Silvia</h2>
                            <span x-show="isStreaming" class="text-xs font-bold uppercase tracking-wider" style="color: var(--ai-accent);">Composing...</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        @click="cycleTheme()"
                        class="rounded-full border bg-white/85 p-3 text-slate-600 transition hover:-translate-y-0.5 hover:bg-white hover:shadow-md"
                        style="border-color: color-mix(in srgb, var(--ai-highlight) 18%, #e2e8f0 82%);"
                        title="Switch style"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M7 12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                        </svg>
                    </button>
                    <button @click="isExpanded = !isExpanded" class="hidden sm:block rounded-full border bg-white/85 p-3 text-slate-600 transition hover:-translate-y-0.5 hover:bg-white hover:shadow-md" style="border-color: #e2e8f0;" :title="isExpanded ? 'Shrink panel' : 'Expand panel'">
                        <svg x-show="!isExpanded" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                        </svg>
                        <svg x-show="isExpanded" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display:none;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 9V4.5M9 9H4.5M9 9L3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5l5.25 5.25" />
                        </svg>
                    </button>
                    <button @click="startNewSession()" class="rounded-full border bg-white/85 p-3 text-slate-600 transition hover:-translate-y-0.5 hover:bg-white hover:shadow-md" style="border-color: #e2e8f0;" aria-label="Start new session">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>
                    <button @click="closeChat()" class="rounded-full border bg-white/85 p-3 text-slate-600 transition hover:-translate-y-0.5 hover:bg-white hover:shadow-md" style="border-color: #e2e8f0;" aria-label="Close panel">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </header>

        {{-- Messages --}}
        <div x-ref="messagesContainer" class="ai-scroll relative z-10 flex-1 overflow-y-auto px-5 pb-4 sm:px-7">
            <div x-show="isLoadingHistory" class="flex h-full flex-col items-center justify-center gap-4" style="display:none;">
                <div class="h-14 w-14 rounded-2xl ai-orb" style="background: radial-gradient(circle at 30% 20%, #ffffff 0%, var(--ai-accent-soft) 45%, color-mix(in srgb, var(--ai-accent) 30%, #ffffff 70%) 100%);"></div>
                <p class="text-base font-semibold text-slate-500">Restoring your conversation...</p>
            </div>

            <div x-show="!isLoadingHistory && messages.length === 0" class="ai-message-in pb-8" style="display:none;">
                <div class="rounded-[2rem] border bg-white/85 p-6 shadow-sm" style="border-color: rgba(255,255,255,0.75);">
                    <h3 class="text-3xl font-extrabold tracking-tight text-slate-900">Let us plan today together.</h3>
                    <p class="mt-3 text-lg text-slate-600">I can break down medications, summarize health trends, and help you finish tasks with less stress.</p>
                </div>

                <div class="mt-5 space-y-3">
                    <template x-for="(prompt, index) in suggestedPrompts" :key="index">
                        <button
                            @click="sendSuggestedPrompt(prompt)"
                            class="group w-full rounded-[1.6rem] border bg-white/90 px-5 py-4 text-left shadow-sm transition hover:-translate-y-[1px] hover:bg-white"
                            style="border-color: color-mix(in srgb, var(--ai-accent) 18%, #ffffff 82%);"
                        >
                            <p class="text-base font-semibold text-slate-700 group-hover:text-slate-900" x-text="prompt"></p>
                        </button>
                    </template>
                </div>
            </div>

            <div class="space-y-4 pb-3">
                <template x-for="(msg, index) in messages" :key="index">
                    <div class="ai-message-in flex" :class="msg.role === 'user' ? 'justify-end' : 'justify-start'">
                        <div x-show="msg.role === 'user'" class="max-w-[88%] rounded-[1.5rem] rounded-tr-lg px-5 py-4 text-[1.04rem] font-semibold leading-relaxed text-white shadow-lg"
                             style="background: linear-gradient(132deg, color-mix(in srgb, var(--ai-accent) 88%, #0f172a 12%), color-mix(in srgb, var(--ai-highlight) 72%, #0f172a 28%));">
                            <span x-text="msg.content"></span>
                        </div>

                        <article x-show="msg.role !== 'user'" class="max-w-[95%] rounded-[1.7rem] rounded-tl-lg border bg-white/95 p-5 text-[1.03rem] leading-relaxed shadow-md sm:p-6"
                                 style="border-color: color-mix(in srgb, var(--ai-accent) 14%, #ffffff 86%); color: #1f2937;">
                            <div x-show="msg.streaming" class="ai-stream-line -mx-5 -mt-5 mb-4 h-1.5 rounded-t-[1.7rem] sm:-mx-6 sm:-mt-6"></div>
                            <div class="ai-md" x-html="renderMarkdown(msg.content)"></div>

                            <div x-show="msg.streaming" class="mt-4 flex items-center gap-2 opacity-55">
                                <span class="h-2.5 w-2.5 animate-bounce rounded-full" style="background: var(--ai-accent);"></span>
                                <span class="h-2.5 w-2.5 animate-bounce rounded-full" style="background: var(--ai-accent); animation-delay: .12s;"></span>
                                <span class="h-2.5 w-2.5 animate-bounce rounded-full" style="background: var(--ai-accent); animation-delay: .24s;"></span>
                            </div>
                        </article>
                    </div>
                </template>
            </div>
        </div>

        {{-- Input --}}
        <footer class="relative z-10 border-t border-white/70 bg-white/70 px-5 py-5 sm:px-7">
            <form @submit.prevent="sendMessage" class="relative">
                <input
                    type="text"
                    x-model="input"
                    x-ref="chatInput"
                    @keydown.escape="closeChat()"
                    placeholder="Tell me what you need help with"
                    class="w-full rounded-full border-2 bg-white/95 py-4 pl-6 pr-24 text-lg font-medium text-slate-800 shadow-inner transition placeholder:text-slate-400 focus:outline-none"
                    style="border-color: color-mix(in srgb, var(--ai-highlight) 18%, #dbeafe 82%);"
                    :disabled="isLoading"
                >

                <div class="absolute right-2 top-1/2 flex -translate-y-1/2 items-center gap-2">
                    <button
                        type="button"
                        class="relative flex h-11 w-11 items-center justify-center rounded-full transition"
                        :class="input.trim() === '' ? 'scale-105 shadow-md' : ''"
                        :style="input.trim() === '' ? 'background: var(--ai-accent-soft); color: var(--ai-accent);' : 'background: transparent; color: #94a3b8;'"
                        title="Voice mode (coming soon)"
                    >
                        <span x-show="input.trim() === ''" class="ai-orb-ring absolute inset-0 rounded-full border" style="border-color: color-mix(in srgb, var(--ai-accent) 40%, transparent 60%);"></span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.3" d="M12 18.75a6.75 6.75 0 006.75-6.75m-13.5 0A6.75 6.75 0 0012 18.75m0 0V22.5m0-3.75H9m3 0h3m-3-6.75a2.25 2.25 0 01-2.25-2.25V6.75a2.25 2.25 0 114.5 0v2.25A2.25 2.25 0 0112 12z" />
                        </svg>
                    </button>

                    <button
                        type="submit"
                        class="flex h-11 w-11 items-center justify-center rounded-full text-white transition disabled:cursor-not-allowed disabled:opacity-50"
                        :style="'background: linear-gradient(130deg, var(--ai-accent), var(--ai-highlight)); box-shadow: 0 8px 18px color-mix(in srgb, var(--ai-highlight) 45%, transparent 55%);'"
                        :disabled="isLoading || input.trim() === ''"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 translate-x-[1px] rotate-90" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                        </svg>
                    </button>
                </div>
            </form>
        </footer>
    </section>
</div>

<script>
    function parseAiMarkdown(text) {
        if (!text) return '';

        let html = text
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/^### (.+)$/gm, '<h3>$1</h3>')
            .replace(/^## (.+)$/gm, '<h2>$1</h2>')
            .replace(/^# (.+)$/gm, '<h1>$1</h1>')
            .replace(/\*\*\*(.+?)\*\*\*/g, '<strong><em>$1</em></strong>')
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.+?)\*/g, '<em>$1</em>')
            .replace(/`([^`]+)`/g, '<code class="rounded-md bg-slate-100 px-2 py-0.5 text-[0.9em] font-mono text-slate-700">$1</code>')
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
            themeIndex: 0,
            themes: [
                { key: 'ai-theme-coast', label: 'Coast' },
                { key: 'ai-theme-sunrise', label: 'Sunrise' },
                { key: 'ai-theme-grove', label: 'Grove' },
            ],

            get themeLabel() {
                return this.themes[this.themeIndex].label;
            },

            themeClass() {
                return this.themes[this.themeIndex].key;
            },

            init() {
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

            cycleTheme() {
                this.themeIndex = (this.themeIndex + 1) % this.themes.length;
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

            async loadHistory() {
                this.isLoadingHistory = true;

                try {
                    const url = new URL('{{ route("elderly.ai-assistant.history") }}', window.location.origin);
                    if (this.sessionId) {
                        url.searchParams.set('session_id', this.sessionId);
                    }

                    const res = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    });

                    const data = await res.json();
                    if (data.success) {
                        this.sessionId = data.session_id;
                        this.messages = data.messages || [];
                        this.suggestedPrompts = data.suggested_prompts || [];

                        if (this.suggestedPrompts.length === 0) {
                            this.suggestedPrompts = [
                                'What medications should I prioritize today?',
                                'Summarize my progress in simple steps.',
                                'Help me review my vitals and next actions.'
                            ];
                        }

                        this.historyLoaded = true;
                    }
                } catch (e) {
                    console.error('History load failed:', e);
                } finally {
                    this.isLoadingHistory = false;
                    this.scrollToBottom();
                }
            },

            async startNewSession() {
                try {
                    const res = await fetch('{{ route("elderly.ai-assistant.new-session") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });

                    const data = await res.json();
                    if (data.success) {
                        this.sessionId = data.session_id;
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
                    const res = await fetch('{{ route("elderly.ai-assistant.stream") }}', {
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
                                    this.sessionId = payload.session_id;
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
                    const res = await fetch('{{ route("elderly.ai-assistant.chat") }}', {
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
                        this.sessionId = data.session_id;
                        this.messages.push({ role: 'ai', content: data.message });
                    } else {
                        this.messages.push({ role: 'ai', content: 'Connection issue.' });
                    }
                } catch (_e) {
                    this.messages.push({ role: 'ai', content: 'Connection issue.' });
                }
            }
        }));
    });
</script>
