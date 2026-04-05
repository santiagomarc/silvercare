/**
 * Alpine.data('vitalRecorder') — Vital recording modal logic.
 *
 * Replaces 150+ lines of document.createElement JS with Alpine reactivity.
 * The modal Blade markup lives in <x-vital-record-modal>.
 */
import Alpine from 'alpinejs';
import { sendJsonRequest } from '../utils/offline-queue.js';

const VITAL_CONFIGS = {
    blood_pressure: {
        name: 'Blood Pressure', icon: '❤️', unit: 'mmHg', color: 'red',
        isBP: true, hint: 'Enter systolic and diastolic values',
    },
    sugar_level: {
        name: 'Sugar Level', icon: '🩸', unit: 'mg/dL', color: 'blue',
        placeholder: '100', min: 50, max: 500, step: 1,
        hint: 'Normal range: 70–100 mg/dL (fasting)',
    },
    temperature: {
        name: 'Temperature', icon: '🌡️', unit: '°C', color: 'orange',
        placeholder: '36.5', min: 35, max: 42, step: 0.1,
        hint: 'Normal range: 36.1–37.2 °C',
    },
    heart_rate: {
        name: 'Heart Rate', icon: '💓', unit: 'bpm', color: 'rose',
        placeholder: '72', min: 40, max: 200, step: 1,
        hint: 'Normal resting: 60–100 bpm',
    },
};

export default function vitalRecorder() {
    return {
        open: false,
        type: null,
        config: null,
        value: '',
        systolic: '',
        diastolic: '',
        notes: '',
        submitting: false,
        voiceSupported: false,
        voiceListening: false,
        recognition: null,

        init() {
            this.voiceSupported = Boolean(window.SpeechRecognition || window.webkitSpeechRecognition);
        },

        openModal(type) {
            this.type = type;
            this.config = VITAL_CONFIGS[type] || null;
            if (!this.config) return;
            this.value = '';
            this.systolic = '';
            this.diastolic = '';
            this.notes = '';
            this.submitting = false;
            this.open = true;

            // Focus first input on next tick
            this.$nextTick(() => {
                const input = this.$refs.modalContent?.querySelector('input');
                input?.focus();
            });
        },

        closeModal() {
            if (this.voiceListening) {
                this.stopVoiceCapture();
            }
            this.open = false;
        },

        startVoiceCapture() {
            const toast = Alpine.store('toast');

            if (!this.voiceSupported) {
                toast?.info('Voice input is not supported on this browser.');
                return;
            }

            if (this.voiceListening) {
                this.stopVoiceCapture();
                return;
            }

            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            this.recognition = new SpeechRecognition();
            this.recognition.lang = 'en-US';
            this.recognition.interimResults = false;
            this.recognition.maxAlternatives = 1;

            this.recognition.onstart = () => {
                this.voiceListening = true;
            };

            this.recognition.onresult = (event) => {
                const transcript = event.results?.[0]?.[0]?.transcript || '';
                this.applyVoiceTranscript(transcript);
            };

            this.recognition.onerror = () => {
                toast?.error('Could not capture voice input. Please try again.');
            };

            this.recognition.onend = () => {
                this.voiceListening = false;
            };

            this.recognition.start();
        },

        stopVoiceCapture() {
            if (this.recognition && this.voiceListening) {
                this.recognition.stop();
            }
            this.voiceListening = false;
        },

        applyVoiceTranscript(transcript) {
            const toast = Alpine.store('toast');
            const spoken = String(transcript || '').toLowerCase().trim();

            if (!spoken) {
                toast?.info('No voice input detected.');
                return;
            }

            if (this.config?.isBP) {
                const bpMatch = spoken.match(/(\d{2,3})\s*(?:over|\/|and)\s*(\d{2,3})/i);
                if (!bpMatch) {
                    toast?.info('Try saying blood pressure like "120 over 80".');
                    return;
                }

                this.systolic = bpMatch[1];
                this.diastolic = bpMatch[2];
                toast?.success('Blood pressure captured from voice.');
                return;
            }

            const numberMatch = spoken.match(/\d+(?:\.\d+)?/);
            if (!numberMatch) {
                toast?.info('Try saying a numeric value like "72" or "36.5".');
                return;
            }

            this.value = numberMatch[0];
            toast?.success(`${this.config?.name || 'Value'} captured from voice.`);
        },

        async submit() {
            const toast = Alpine.store('toast');
            if (!this.config) return;

            // Validate
            if (this.config.isBP) {
                if (!this.systolic || !this.diastolic) {
                    toast?.error('Please enter both systolic and diastolic values');
                    return;
                }
            } else if (!this.value) {
                toast?.error('Please enter a value');
                return;
            }

            this.submitting = true;

            try {
                const payload = this.config.isBP
                    ? { type: this.type, value_text: `${this.systolic}/${this.diastolic}`, notes: this.notes || null }
                    : { type: this.type, value: parseFloat(this.value), notes: this.notes || null };

                const result = await sendJsonRequest('/my-vitals', {
                    method: 'POST',
                    body: payload,
                });

                if (result.queued) {
                    this.closeModal();
                    toast?.info(`${this.config.name} saved offline. It will sync automatically.`);
                    return;
                }

                if (!result.ok) throw new Error(result.data?.message || 'Failed to save');

                this.closeModal();
                toast?.success(`${this.config.name} recorded!`);
                setTimeout(() => window.location.reload(), 600);

            } catch (err) {
                console.error('Vital save failed:', err);
                toast?.error(err.message);
            } finally {
                this.submitting = false;
            }
        },
    };
}
