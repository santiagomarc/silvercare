/**
 * Alpine.data('vitalRecorder') — Vital recording modal logic.
 *
 * Replaces 150+ lines of document.createElement JS with Alpine reactivity.
 * The modal Blade markup lives in <x-vital-record-modal>.
 */
import Alpine from 'alpinejs';

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
            this.open = false;
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

                const resp = await fetch('/my-vitals', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(payload),
                });

                const data = await resp.json();
                if (!resp.ok) throw new Error(data.message || 'Failed to save');

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
