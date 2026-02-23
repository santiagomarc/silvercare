{{-- ============================================================
     SilverCare Caregiver AI Analyst Widget — Streaming + Markdown
     ============================================================ --}}

<style>
    .cg-ai-md p { margin-bottom: 0.4rem; }
    .cg-ai-md strong { font-weight: 700; }
    .cg-ai-md em { font-style: italic; }
    .cg-ai-md ul, .cg-ai-md ol { padding-left: 1.2rem; margin-bottom: 0.4rem; }
    .cg-ai-md ul { list-style: disc; }
    .cg-ai-md ol { list-style: decimal; }
    .cg-ai-md li { margin-bottom: 0.15rem; }
    .cg-ai-md h1,.cg-ai-md h2,.cg-ai-md h3 { font-weight: 700; margin-bottom: 0.3rem; }
    .cg-ai-md h1 { font-size: 1.1rem; }
    .cg-ai-md h2 { font-size: 1rem; }
    .cg-ai-md h3 { font-size: 0.95rem; }
    .cg-ai-md code { background: #f3f4f6; padding: 0.1rem 0.3rem; border-radius: 0.25rem; font-size: 0.85em; }
    .cg-ai-md a { color: #7c3aed; text-decoration: underline; }
    .cg-chat-fade-in { animation: cgChatFadeIn 0.3s ease-out; }
    @keyframes cgChatFadeIn { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }
    .cg-typing-cursor::after { content:'▊'; animation: cgBlink 0.7s infinite; color: #6b7280; }
    @keyframes cgBlink { 0%,100%{opacity:1} 50%{opacity:0} }
</style>

<div x-data="caregiverAiWidget()" class="fixed z-50" :class="isFullScreen ? 'inset-0' : 'bottom-6 right-6'">
    
    {{-- Floating Button --}}
    <button 
        x-show="!isOpen" 
        @click="openChat()"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-50"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-50"
        class="w-16 h-16 bg-gradient-to-r from-purple-700 to-purple-500 rounded-full shadow-2xl flex items-center justify-center text-white hover:scale-110 transition-transform border-4 border-white focus:outline-none"
        aria-label="Open AI Health Analyst"
    >
        <span class="text-3xl">📊</span>
    </button>

    {{-- Chat Window --}}
    <div 
        x-show="isOpen" 
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-10 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-10 scale-95"
        class="bg-white flex flex-col shadow-2xl overflow-hidden"
        :class="isFullScreen ? 'w-full h-full rounded-none' : 'w-80 sm:w-96 h-[32rem] rounded-2xl border border-gray-200'"
        style="display: none;"
    >
        {{-- Header --}}
        <div class="bg-gradient-to-r from-purple-700 to-purple-500 p-4 flex items-center justify-between text-white shrink-0">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center text-2xl">
                    📊
                </div>
                <div>
                    <h3 class="font-bold text-lg leading-tight">AI Health Analyst</h3>
                    <p class="text-xs text-purple-100" x-text="isStreaming ? 'Analyzing...' : 'Patient insights powered by AI'"></p>
                </div>
            </div>
            <div class="flex items-center space-x-1">
                {{-- New Chat --}}
                <button @click="startNewSession()" class="p-2 hover:bg-white/20 rounded-full transition-colors focus:outline-none" title="New Analysis">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </button>
                {{-- Fullscreen Toggle --}}
                <button @click="toggleFullScreen()" class="p-2 hover:bg-white/20 rounded-full transition-colors focus:outline-none" title="Toggle Fullscreen">
                    <svg x-show="!isFullScreen" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                    </svg>
                    <svg x-show="isFullScreen" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9V4.5M9 9H4.5M9 9L3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5l5.25 5.25" />
                    </svg>
                </button>
                {{-- Close --}}
                <button @click="closeChat()" class="p-2 hover:bg-white/20 rounded-full transition-colors focus:outline-none" title="Close">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Messages Area --}}
        <div class="flex-1 p-4 overflow-y-auto bg-gray-50 space-y-4" x-ref="messagesContainer">
            
            {{-- Loading History --}}
            <div x-show="isLoadingHistory" class="flex justify-center py-8" style="display: none;">
                <div class="flex flex-col items-center space-y-2">
                    <svg class="animate-spin h-8 w-8 text-purple-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span class="text-xs text-gray-500 font-medium">Loading analysis...</span>
                </div>
            </div>

            {{-- Welcome (no history) --}}
            <div x-show="!isLoadingHistory && messages.length === 0" class="cg-chat-fade-in" style="display: none;">
                <div class="flex items-start space-x-2">
                    <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center text-lg shrink-0">📊</div>
                    <div class="bg-white border border-gray-100 text-gray-800 p-3 rounded-2xl rounded-tl-none shadow-sm max-w-[85%]">
                        <p class="text-sm font-medium">Hello! 👋 I'm the SilverCare AI Health Analyst.</p>
                        <p class="text-sm text-gray-600 mt-1">I can analyze your patient's health data, medication adherence, and identify trends. Ask me anything about their well-being.</p>
                    </div>
                </div>

                {{-- Suggested Prompts --}}
                <div x-show="suggestedPrompts.length > 0" class="mt-4 space-y-2 px-2">
                    <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Try asking</p>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="(prompt, i) in suggestedPrompts" :key="i">
                            <button 
                                @click="sendSuggestedPrompt(prompt)"
                                class="text-xs bg-white border border-gray-200 hover:border-purple-500 hover:bg-purple-50 text-gray-700 hover:text-purple-700 px-3 py-1.5 rounded-full transition-all shadow-sm font-medium"
                                x-text="prompt"
                            ></button>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Messages --}}
            <template x-for="(msg, index) in messages" :key="index">
                <div class="flex items-start space-x-2 cg-chat-fade-in" :class="msg.role === 'user' ? 'flex-row-reverse space-x-reverse' : ''">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-lg shrink-0"
                         :class="msg.role === 'user' ? 'bg-purple-600 text-white' : 'bg-purple-100'">
                        <span x-text="msg.role === 'user' ? '👤' : '📊'"></span>
                    </div>
                    <div class="p-3 shadow-sm max-w-[85%]"
                         :class="msg.role === 'user' ? 'bg-purple-600 text-white rounded-2xl rounded-tr-none' : 'bg-white border border-gray-100 text-gray-800 rounded-2xl rounded-tl-none'">
                        <div x-show="msg.role === 'user'" class="text-sm whitespace-pre-wrap" x-text="msg.content"></div>
                        <div x-show="msg.role !== 'user'" class="text-sm cg-ai-md" :class="msg.streaming ? 'cg-typing-cursor' : ''" x-html="renderMarkdown(msg.content)"></div>
                    </div>
                </div>
            </template>

            {{-- Thinking Indicator --}}
            <div x-show="isLoading && !isStreaming" class="flex items-start space-x-2" style="display: none;">
                <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center text-lg shrink-0">📊</div>
                <div class="bg-white border border-gray-100 p-4 rounded-2xl rounded-tl-none shadow-sm flex space-x-1.5 items-center">
                    <div class="w-2 h-2 bg-purple-500 rounded-full animate-bounce"></div>
                    <div class="w-2 h-2 bg-purple-500 rounded-full animate-bounce" style="animation-delay: 0.15s"></div>
                    <div class="w-2 h-2 bg-purple-500 rounded-full animate-bounce" style="animation-delay: 0.3s"></div>
                </div>
            </div>
        </div>

        {{-- Input Area --}}
        <div class="p-3 bg-white border-t border-gray-200 shrink-0">
            <form @submit.prevent="sendMessage" class="flex items-center space-x-2">
                <input 
                    type="text" 
                    x-model="input" 
                    x-ref="chatInput"
                    @keydown.escape="closeChat()"
                    placeholder="Ask about your patient's health..." 
                    class="flex-1 border border-gray-300 rounded-full px-4 py-2.5 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 text-sm transition-all"
                    :disabled="isLoading"
                >
                <button 
                    type="submit" 
                    class="w-10 h-10 bg-purple-600 text-white rounded-full flex items-center justify-center hover:bg-purple-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none shrink-0"
                    :disabled="isLoading || input.trim() === ''"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transform rotate-90" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function cgSimpleMarkdown(text) {
        if (!text) return '';
        let html = text
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/^### (.+)$/gm, '<h3>$1</h3>')
            .replace(/^## (.+)$/gm, '<h2>$1</h2>')
            .replace(/^# (.+)$/gm, '<h1>$1</h1>')
            .replace(/\*\*\*(.+?)\*\*\*/g, '<strong><em>$1</em></strong>')
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.+?)\*/g, '<em>$1</em>')
            .replace(/`(.+?)`/g, '<code>$1</code>')
            .replace(/^[\-\*] (.+)$/gm, '<li>$1</li>')
            .replace(/^\d+\. (.+)$/gm, '<li>$1</li>')
            .replace(/\[(.+?)\]\((.+?)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>')
            .replace(/\n\n/g, '</p><p>')
            .replace(/\n/g, '<br>');
        html = html.replace(/(<li>.*?<\/li>)(?:\s*<br>)?/gs, '$1');
        html = html.replace(/((?:<li>.*?<\/li>\s*)+)/gs, '<ul>$1</ul>');
        return '<p>' + html + '</p>';
    }

    document.addEventListener('alpine:init', () => {
        Alpine.data('caregiverAiWidget', () => ({
            isOpen: false,
            isFullScreen: false,
            messages: [],
            input: '',
            isLoading: false,
            isStreaming: false,
            isLoadingHistory: false,
            sessionId: null,
            suggestedPrompts: [],
            historyLoaded: false,

            openChat() {
                this.isOpen = true;
                if (!this.historyLoaded) this.loadHistory();
                setTimeout(() => { this.$refs.chatInput?.focus(); }, 150);
            },

            closeChat() {
                this.isOpen = false;
                this.isFullScreen = false;
            },

            toggleFullScreen() {
                this.isFullScreen = !this.isFullScreen;
            },

            scrollToBottom() {
                setTimeout(() => {
                    const container = this.$refs.messagesContainer;
                    if (container) container.scrollTop = container.scrollHeight;
                }, 50);
            },

            renderMarkdown(text) {
                return cgSimpleMarkdown(text);
            },

            async loadHistory() {
                this.isLoadingHistory = true;
                try {
                    const url = new URL('{{ route("caregiver.ai-analyst.history") }}', window.location.origin);
                    if (this.sessionId) url.searchParams.set('session_id', this.sessionId);

                    const res = await fetch(url, {
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.sessionId = data.session_id;
                        this.messages = data.messages || [];
                        this.suggestedPrompts = data.suggested_prompts || [];
                        this.historyLoaded = true;
                    }
                } catch (e) {
                    console.error('Failed to load analyst history:', e);
                } finally {
                    this.isLoadingHistory = false;
                    this.scrollToBottom();
                }
            },

            async startNewSession() {
                try {
                    const res = await fetch('{{ route("caregiver.ai-analyst.new-session") }}', {
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
                    }
                } catch (e) {
                    console.error('Failed to create new session:', e);
                }
            },

            sendSuggestedPrompt(prompt) {
                this.input = prompt;
                this.sendMessage();
            },

            async sendMessage() {
                if (this.input.trim() === '' || this.isLoading) return;

                const userMessage = this.input.trim();
                this.messages.push({ role: 'user', content: userMessage });
                this.input = '';
                this.isLoading = true;
                this.scrollToBottom();

                try {
                    const response = await fetch('{{ route("caregiver.ai-analyst.stream") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'text/event-stream'
                        },
                        body: JSON.stringify({
                            message: userMessage,
                            session_id: this.sessionId
                        })
                    });

                    if (!response.ok) throw new Error('Stream request failed');

                    const reader = response.body.getReader();
                    const decoder = new TextDecoder();
                    let aiMessageIndex = null;
                    let buffer = '';

                    while (true) {
                        const { done, value } = await reader.read();
                        if (done) break;

                        buffer += decoder.decode(value, { stream: true });
                        const lines = buffer.split('\n');
                        buffer = lines.pop();

                        for (const line of lines) {
                            if (!line.startsWith('data: ')) continue;
                            try {
                                const payload = JSON.parse(line.substring(6));
                                if (payload.type === 'session') {
                                    this.sessionId = payload.session_id;
                                } else if (payload.type === 'chunk') {
                                    if (aiMessageIndex === null) {
                                        this.messages.push({ role: 'ai', content: '', streaming: true });
                                        aiMessageIndex = this.messages.length - 1;
                                        this.isStreaming = true;
                                    }
                                    this.messages[aiMessageIndex].content += payload.content;
                                    this.scrollToBottom();
                                } else if (payload.type === 'done') {
                                    if (aiMessageIndex !== null) this.messages[aiMessageIndex].streaming = false;
                                    this.isStreaming = false;
                                } else if (payload.type === 'error') {
                                    this.messages.push({ role: 'ai', content: payload.content });
                                    this.isStreaming = false;
                                }
                            } catch (e) {}
                        }
                    }
                } catch (error) {
                    console.error('Stream error:', error);
                    await this.sendMessageFallback(userMessage);
                } finally {
                    this.isLoading = false;
                    this.isStreaming = false;
                    this.scrollToBottom();
                    setTimeout(() => { this.$refs.chatInput?.focus(); }, 50);
                }
            },

            async sendMessageFallback(userMessage) {
                try {
                    const response = await fetch('{{ route("caregiver.ai-analyst.chat") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ message: userMessage, session_id: this.sessionId })
                    });
                    const data = await response.json();
                    if (data.success) {
                        this.sessionId = data.session_id;
                        this.messages.push({ role: 'ai', content: data.message });
                    } else {
                        this.messages.push({ role: 'ai', content: data.message || 'Analysis failed.' });
                    }
                } catch (e) {
                    this.messages.push({ role: 'ai', content: 'Network error. Please try again.' });
                }
            }
        }));
    });
</script>
