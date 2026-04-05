/**
 * Alpine.data('moodTracker') — Mood slider with auto-save.
 *
 * Props (via x-data):
 *   initialMood: int (1-5, defaults to 3)
 */
import Alpine from 'alpinejs';
import { sendJsonRequest } from '../utils/offline-queue.js';

const MOODS = [
    { emoji: '😢', label: 'Very Sad',    color: '#EF4444' },
    { emoji: '☹️',  label: 'Sad',         color: '#F97316' },
    { emoji: '😐', label: 'Neutral',     color: '#6B7280' },
    { emoji: '🙂', label: 'Happy',       color: '#65A30D' },
    { emoji: '😄', label: 'Very Happy',  color: '#16A34A' },
];

export default function moodTracker(initialMood = 3) {
    return {
        value: initialMood,
        saved: false,
        _saveTimeout: null,

        get mood()  { return MOODS[this.value - 1]; },
        get emoji() { return this.mood.emoji; },
        get label() { return this.mood.label; },
        get color() { return this.mood.color; },

        onInput() {
            clearTimeout(this._saveTimeout);
            this._saveTimeout = setTimeout(() => this.save(), 1000);
        },

        async save() {
            try {
                const result = await sendJsonRequest('/my-mood', {
                    method: 'POST',
                    body: { value: this.value },
                });

                if (result.ok || result.queued) {
                    this.saved = true;
                    window.dispatchEvent(new CustomEvent('mood-logged', {
                        detail: { value: this.value },
                    }));
                    if (result.queued) {
                        Alpine.store('toast')?.info('Mood saved offline. It will sync when connection returns.');
                    }
                    setTimeout(() => { this.saved = false; }, 2000);
                    return;
                }

                Alpine.store('toast')?.error(result.data?.message || 'Failed to save mood');
            } catch (e) {
                console.error('Mood save failed:', e);
                Alpine.store('toast')?.error('Failed to save mood');
            }
        },
    };
}
