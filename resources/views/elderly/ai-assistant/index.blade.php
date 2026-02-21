<x-app-layout>
    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden flex flex-col h-[80vh]">
            <!-- Header -->
            <div class="bg-gradient-to-r from-[#000080] to-blue-600 p-6 text-white flex items-center gap-4">
                <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center text-2xl">
                    🤖
                </div>
                <div>
                    <h2 class="text-2xl font-[900]">SilverCare AI Assistant</h2>
                    <p class="text-blue-100 text-sm font-medium">I'm here to help you with your daily tasks and health questions.</p>
                </div>
            </div>

            <!-- Chat Area -->
            <div id="chat-container" class="flex-1 p-6 overflow-y-auto bg-gray-50 space-y-4">
                <!-- Welcome Message -->
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 bg-[#000080] rounded-full flex items-center justify-center text-white text-sm flex-shrink-0">
                        🤖
                    </div>
                    <div class="bg-white p-4 rounded-2xl rounded-tl-none shadow-sm border border-gray-100 max-w-[80%]">
                        <p class="text-gray-800 text-sm">Hello {{ Auth::user()->name }}! How can I assist you today?</p>
                    </div>
                </div>
            </div>

            <!-- Input Area -->
            <div class="p-4 bg-white border-t border-gray-200">
                <form id="chat-form" class="flex gap-3">
                    <input type="text" id="chat-input" class="flex-1 rounded-xl border-gray-300 focus:border-[#000080] focus:ring focus:ring-[#000080] focus:ring-opacity-50 shadow-sm px-4 py-3 text-sm" placeholder="Type your message here..." required>
                    <button type="submit" class="bg-[#000080] hover:bg-blue-800 text-white px-6 py-3 rounded-xl font-bold transition-colors flex items-center gap-2">
                        <span>Send</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('chat-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const input = document.getElementById('chat-input');
            const message = input.value.trim();
            if (!message) return;
            
            const container = document.getElementById('chat-container');
            
            // Add user message
            container.insertAdjacentHTML('beforeend', `
                <div class="flex items-start gap-3 justify-end">
                    <div class="bg-[#000080] text-white p-4 rounded-2xl rounded-tr-none shadow-sm max-w-[80%]">
                        <p class="text-sm">${message}</p>
                    </div>
                    <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center text-gray-600 text-sm font-bold flex-shrink-0">
                        {{ substr(Auth::user()->name, 0, 1) }}
                    </div>
                </div>
            `);
            
            input.value = '';
            container.scrollTop = container.scrollHeight;
            
            // Add loading indicator
            const loadingId = 'loading-' + Date.now();
            container.insertAdjacentHTML('beforeend', `
                <div id="${loadingId}" class="flex items-start gap-3">
                    <div class="w-8 h-8 bg-[#000080] rounded-full flex items-center justify-center text-white text-sm flex-shrink-0">
                        🤖
                    </div>
                    <div class="bg-white p-4 rounded-2xl rounded-tl-none shadow-sm border border-gray-100">
                        <div class="flex gap-1">
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.4s"></div>
                        </div>
                    </div>
                </div>
            `);
            container.scrollTop = container.scrollHeight;
            
            try {
                const response = await fetch('{{ route('elderly.ai-assistant.chat') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ message })
                });
                
                const data = await response.json();
                
                // Remove loading indicator
                document.getElementById(loadingId).remove();
                
                // Add AI response
                container.insertAdjacentHTML('beforeend', `
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-[#000080] rounded-full flex items-center justify-center text-white text-sm flex-shrink-0">
                            🤖
                        </div>
                        <div class="bg-white p-4 rounded-2xl rounded-tl-none shadow-sm border border-gray-100 max-w-[80%]">
                            <p class="text-gray-800 text-sm">${data.message}</p>
                        </div>
                    </div>
                `);
                
                container.scrollTop = container.scrollHeight;
            } catch (error) {
                console.error('Error:', error);
                document.getElementById(loadingId).remove();
                // Show error message
            }
        });
    </script>
</x-app-layout>
