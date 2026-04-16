/**
 * Alpine.data('moodTracker') — Mood slider with auto-save.
 *
 * Props (via x-data):
 *   initialMood: int (1-5, defaults to 3)
 */
import Alpine from 'alpinejs';
import { sendJsonRequest } from '../utils/offline-queue.js';

const MOODS = [
    { value: 1, label: 'Very Sad', color: '#EF4444' },
    { value: 2, label: 'Sad', color: '#F97316' },
    { value: 3, label: 'Neutral', color: '#6B7280' },
    { value: 4, label: 'Happy', color: '#65A30D' },
    { value: 5, label: 'Very Happy', color: '#16A34A' },
];

const DEFAULT_MOOD = 3;

function normalizeMood(value) {
    const parsed = Number.parseInt(value, 10);

    if (Number.isNaN(parsed)) {
        return DEFAULT_MOOD;
    }

    return Math.min(5, Math.max(1, parsed));
}

export default function moodTracker(initialMood = 3) {
    return {
        value: normalizeMood(initialMood),
        saved: false,
        saving: false,
        _saveTimeout: null,
        _savedStateTimeout: null,

        init() {
            this.value = normalizeMood(this.value);
        },

        get moods() {
            return MOODS;
        },

        get mood() {
            return MOODS[normalizeMood(this.value) - 1] ?? MOODS[DEFAULT_MOOD - 1];
        },

        get label() {
            return this.mood.label;
        },

        get color() {
            return this.mood.color;
        },

        isSelected(moodValue) {
            return normalizeMood(this.value) === moodValue;
        },

        setMood(nextMood) {
            const normalized = normalizeMood(nextMood);

            if (normalized === this.value) {
                return;
            }

            this.value = normalized;
            this.onInput();

            if (this.$refs.moodSlider) {
                this.$refs.moodSlider.focus({ preventScroll: true });
            }
        },

        onInput() {
            this.value = normalizeMood(this.value);
            this.saved = false;
            clearTimeout(this._saveTimeout);
            this._saveTimeout = setTimeout(() => this.save(), 1000);
        },

        async save() {
            const moodToSave = normalizeMood(this.value);
            this.saving = true;

            try {
                const result = await sendJsonRequest('/my-mood', {
                    method: 'POST',
                    body: { value: moodToSave },
                });

                if (result.ok || result.queued) {
                    if (moodToSave === normalizeMood(this.value)) {
                        this.saved = true;
                        clearTimeout(this._savedStateTimeout);
                        this._savedStateTimeout = setTimeout(() => { this.saved = false; }, 2000);
                    }

                    window.dispatchEvent(new CustomEvent('mood-logged', {
                        detail: { value: moodToSave },
                    }));

                    if (result.queued) {
                        Alpine.store('toast')?.info('Mood saved offline. It will sync when connection returns.');
                    }

                    return;
                }

                Alpine.store('toast')?.error(result.data?.message || 'Failed to save mood');
            } catch (e) {
                console.error('Mood save failed:', e);
                Alpine.store('toast')?.error('Failed to save mood');
            } finally {
                this.saving = false;
            }
        },
    };
}
