/**
 * SilverCare Senior Voice Vital & Action Capture
 * Accessible Web Speech API voice capture assistant for seniors.
 */

window.SilverCareVoice = (function () {
    let recognition = null;
    let isListening = false;

    function isSupported() {
        return 'webkitSpeechRecognition' in window || 'SpeechRecognition' in window;
    }

    function init() {
        if (!isSupported()) {
            console.warn('Web Speech API is not supported in this browser.');
            return null;
        }

        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        recognition = new SpeechRecognition();
        recognition.continuous = false;
        recognition.interimResults = false;
        recognition.lang = 'en-US'; // Supports standard English and English-Tagalog numeric speech

        recognition.onstart = function () {
            isListening = true;
            updateUiListening(true);
        };

        recognition.onresult = function (event) {
            const transcript = event.results[0][0].transcript;
            console.log('Recognized speech:', transcript);
            handleTranscript(transcript);
        };

        recognition.onerror = function (event) {
            console.error('Speech recognition error:', event.error);
            isListening = false;
            updateUiListening(false);
            showFeedback('Sorry, could not hear clearly. Please try speaking again.', 'error');
        };

        recognition.onend = function () {
            isListening = false;
            updateUiListening(false);
        };

        return recognition;
    }

    function startListening() {
        if (!recognition) {
            init();
        }
        if (!recognition) {
            showFeedback('Voice input is not supported on this device. Please use standard buttons.', 'error');
            return;
        }
        try {
            recognition.start();
        } catch (e) {
            console.warn('Speech recognition already active or starting:', e);
        }
    }

    function stopListening() {
        if (recognition && isListening) {
            recognition.stop();
        }
    }

    function handleTranscript(transcript) {
        showFeedback(`Heard: "${transcript}". Analyzing...`, 'info');

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        fetch('/api/voice/parse', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ transcript: transcript })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.parsed) {
                renderCandidateModal(data.parsed);
            } else {
                showFeedback('Could not recognize the vital. Please try saying e.g. "My blood pressure is 120 over 80"', 'error');
            }
        })
        .catch(err => {
            console.error('Error parsing voice transcript:', err);
            showFeedback('Error analyzing speech. Please check your connection.', 'error');
        });
    }

    function renderCandidateModal(parsed) {
        if (parsed.intent === 'unknown') {
            showFeedback(`Could not recognize vital from "${parsed.raw_transcript}". Please try again.`, 'error');
            return;
        }

        // Show confirmation dialog / banner
        const modalHtml = `
            <div id="voice-confirmation-overlay" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 animate-fade-in">
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl border border-slate-100 dark:border-slate-800 text-center">
                    <div class="w-16 h-16 rounded-2xl bg-blue-100 dark:bg-blue-950 flex items-center justify-center text-3xl mx-auto mb-4">
                        🎙️
                    </div>
                    <h3 class="text-xl font-black text-slate-900 dark:text-white mb-1">Confirm Voice Entry</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Heard: "${parsed.raw_transcript}"</p>
                    
                    <div class="bg-blue-50/80 dark:bg-blue-950/40 rounded-2xl p-4 border border-blue-200 dark:border-blue-800 mb-6 text-left">
                        <p class="text-sm font-extrabold text-blue-950 dark:text-blue-200">${parsed.summary}</p>
                        <p class="text-xs text-blue-700 dark:text-blue-300 mt-1">Confidence: ${Math.round(parsed.confidence * 100)}%</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="button" onclick="document.getElementById('voice-confirmation-overlay').remove()" class="flex-1 py-3 px-4 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-sm transition-colors">
                            Cancel
                        </button>
                        <button type="button" id="voice-confirm-btn" class="flex-1 py-3 px-4 rounded-xl bg-[#000080] hover:bg-blue-900 text-white font-extrabold text-sm shadow-md transition-transform active:scale-95">
                            Confirm & Save
                        </button>
                    </div>
                </div>
            </div>
        `;

        const existing = document.getElementById('voice-confirmation-overlay');
        if (existing) existing.remove();

        document.body.insertAdjacentHTML('beforeend', modalHtml);

        document.getElementById('voice-confirm-btn').addEventListener('click', function () {
            confirmParsed(parsed);
        });
    }

    function confirmParsed(parsed) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        fetch('/api/voice/confirm', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                intent: parsed.intent,
                payload: parsed,
            })
        })
        .then(r => r.json())
        .then(data => {
            const overlay = document.getElementById('voice-confirmation-overlay');
            if (overlay) overlay.remove();

            if (data.success) {
                showFeedback(data.message || 'Saved successfully!', 'success');
                setTimeout(() => window.location.reload(), 1200);
            } else {
                showFeedback(data.message || 'Could not save reading.', 'error');
            }
        })
        .catch(err => {
            console.error('Error confirming voice reading:', err);
            showFeedback('Error saving reading. Please try again.', 'error');
        });
    }

    function updateUiListening(listening) {
        const btn = document.getElementById('senior-voice-mic-btn');
        if (!btn) return;

        if (listening) {
            btn.classList.add('bg-red-500', 'animate-pulse', 'ring-4', 'ring-red-300');
            btn.classList.remove('bg-[#000080]');
        } else {
            btn.classList.remove('bg-red-500', 'animate-pulse', 'ring-4', 'ring-red-300');
            btn.classList.add('bg-[#000080]');
        }
    }

    function showFeedback(message, type) {
        console.log(`[Voice ${type}]`, message);
        let toast = document.getElementById('voice-feedback-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'voice-feedback-toast';
            toast.className = 'fixed bottom-6 right-6 z-50 px-5 py-3 rounded-2xl shadow-xl font-bold text-sm transition-all max-w-sm';
            document.body.appendChild(toast);
        }

        toast.textContent = message;
        if (type === 'error') {
            toast.className = 'fixed bottom-6 right-6 z-50 px-5 py-3 rounded-2xl shadow-xl font-bold text-sm bg-red-600 text-white animate-bounce max-w-sm';
        } else if (type === 'success') {
            toast.className = 'fixed bottom-6 right-6 z-50 px-5 py-3 rounded-2xl shadow-xl font-bold text-sm bg-emerald-600 text-white max-w-sm';
        } else {
            toast.className = 'fixed bottom-6 right-6 z-50 px-5 py-3 rounded-2xl shadow-xl font-bold text-sm bg-slate-900 text-white max-w-sm';
        }

        setTimeout(() => {
            if (toast) toast.remove();
        }, 4000);
    }

    return {
        init: init,
        start: startListening,
        stop: stopListening,
        isSupported: isSupported,
    };
})();
