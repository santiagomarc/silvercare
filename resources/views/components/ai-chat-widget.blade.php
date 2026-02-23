<div x-data="aiChatWidget()" class="fixed z-50" :class="isFullScreen ? 'inset-0' : 'bottom-6 right-6'">
    
    <!-- Floating Button (when closed) -->
    <button 
        x-show="!isOpen" 
        @click="openChat()"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-50"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-50"
        class="w-16 h-16 bg-gradient-to-r from-[#000080] to-blue-600 rounded-full shadow-2xl flex items-center justify-center text-white hover:scale-110 transition-transform border-4 border-white focus:outline-none"
        aria-label="Open AI Assistant"
    >
        <span class="text-3xl">🤖</span>
    </button>

    <!-- Chat Window -->
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
        <!-- Header -->
        <div class="bg-gradient-to-r from-[#000080] to-blue-600 p-4 flex items-center justify-between text-white shrink-0">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center text-2xl">
                    🤖
                </div>
                <div>
                    <h3 class="font-bold text-lg leading-tight">SilverCare AI</h3>
                    <p class="text-xs text-blue-100">Always here to help</p>
                </div>
            </div>
            <div class="flex items-center space-x-1">
                <!-- Fullscreen Toggle -->
                <button @click="toggleFullScreen()" class="p-2 hover:bg-white/20 rounded-full transition-colors focus:outline-none" title="Toggle Fullscreen">
                    <svg x-show="!isFullScreen" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                    </svg>
                    <svg x-show="isFullScreen" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9V4.5M9 9H4.5M9 9L3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5l5.25 5.25" />
                    </svg>
                </button>
                <!-- Close Button -->
                <button @click="closeChat()" class="p-2 hover:bg-white/20 rounded-full transition-colors focus:outline-none" title="Close Chat">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Messages Area -->
        <div class="flex-1 p-4 overflow-y-auto bg-gray-50 space-y-4" id="ai-chat-messages">
            <!-- Welcome Message -->
            <div class="flex items-start space-x-2">
                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center text-lg shrink-0">
                    🤖
                </div>
                <div class="bg-white border border-gray-100 text-gray-800 p-3 rounded-2xl rounded-tl-none shadow-sm max-w-[85%]">
                    <p class="text-sm">Hello! I'm your SilverCare AI Assistant. How can I help you today?</p>
                </div>
            </div>

            <!-- Dynamic Messages -->
            <template x-for="(msg, index) in messages" :key="index">
                <div class="flex items-start space-x-2" :class="msg.role === 'user' ? 'flex-row-reverse space-x-reverse' : ''">
                    <!-- Avatar -->
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-lg shrink-0"
                         :class="msg.role === 'user' ? 'bg-[#000080] text-white' : 'bg-blue-100'">
                        <span x-text="msg.role === 'user' ? '👤' : '🤖'"></span>
                    </div>
                    <!-- Bubble -->
                    <div class="p-3 shadow-sm max-w-[85%]"
                         :class="msg.role === 'user' ? 'bg-[#000080] text-white rounded-2xl rounded-tr-none' : 'bg-white border border-gray-100 text-gray-800 rounded-2xl rounded-tl-none'">
                        <p class="text-sm whitespace-pre-wrap" x-text="msg.content"></p>
                    </div>
                </div>
            </template>

            <!-- Loading Indicator -->
            <div x-show="isLoading" class="flex items-start space-x-2" style="display: none;">
                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center text-lg shrink-0">
                    🤖
                </div>
                <div class="bg-white border border-gray-100 p-4 rounded-2xl rounded-tl-none shadow-sm flex space-x-1 items-center">
                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.4s"></div>
                </div>
            </div>
        </div>

        <!-- Input Area -->
        <div class="p-3 bg-white border-t border-gray-200 shrink-0">
            <form @submit.prevent="sendMessage" class="flex items-center space-x-2">
                <input 
                    type="text" 
                    x-model="input" 
                    x-ref="chatInput"
                    placeholder="Type your message..." 
                    class="flex-1 border border-gray-300 rounded-full px-4 py-2 focus:outline-none focus:border-[#000080] focus:ring-1 focus:ring-[#000080] text-sm"
                    :disabled="isLoading"
                >
                <button 
                    type="submit" 
                    class="w-10 h-10 bg-[#000080] text-white rounded-full flex items-center justify-center hover:bg-blue-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none shrink-0"
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
    document.addEventListener('alpine:init', () => {
        Alpine.data('aiChatWidget', () => ({
            isOpen: false,
            isFullScreen: false,
            messages: [],
            input: '',
            isLoading: false,

            openChat() {
                this.isOpen = true;
                setTimeout(() => {
                    this.$refs.chatInput.focus();
                }, 100);
            },

            closeChat() {
                this.isOpen = false;
                this.isFullScreen = false;
                // Clear history when closed
                setTimeout(() => {
                    this.messages = [];
                    this.input = '';
                }, 300); // Wait for transition to finish
            },

            toggleFullScreen() {
                this.isFullScreen = !this.isFullScreen;
            },

            scrollToBottom() {
                setTimeout(() => {
                    const container = document.getElementById('ai-chat-messages');
                    if (container) {
                        container.scrollTop = container.scrollHeight;
                    }
                }, 50);
            },

            async sendMessage() {
                if (this.input.trim() === '' || this.isLoading) return;

                const userMessage = this.input.trim();
                this.messages.push({ role: 'user', content: userMessage });
                this.input = '';
                this.isLoading = true;
                this.scrollToBottom();

                try {
                    const response = await fetch('{{ route('elderly.ai-assistant.chat') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ message: userMessage })
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.messages.push({ role: 'ai', content: data.message });
                    } else {
                        this.messages.push({ role: 'ai', content: data.message || 'Sorry, an error occurred.' });
                    }
                } catch (error) {
                    this.messages.push({ role: 'ai', content: 'Network error. Please check your connection and try again.' });
                } finally {
                    this.isLoading = false;
                    this.scrollToBottom();
                    setTimeout(() => {
                        this.$refs.chatInput.focus();
                    }, 50);
                }
            }
        }));
    });
</script>
