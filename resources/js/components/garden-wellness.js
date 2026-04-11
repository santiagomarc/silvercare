/**
 * Alpine.data('gardenWellness') — Garden of Wellness state machine.
 *
 * Listens to 'progress-updated' events from checklist & medication trackers
 * and recomputes the overall daily goal progress.
 *
 * Props:
 *   initialChecklist:  { done: int, total: int }
 *   initialMeds:       { done: int, total: int }
 *   initialVitals:     { done: int, total: int }
 *   meta:              { streakDays: int, isWilting: bool, missedCount: int }
 */
export default function gardenWellness(checklists, meds, vitals, meta = {}) {
    return {
        checklists: { done: checklists.done, total: checklists.total },
        meds:       { done: meds.done,       total: meds.total       },
        vitals:     { done: vitals.done,      total: vitals.total     },
        streakDays: Number(meta.streakDays || 0),
        isWilting: Boolean(meta.isWilting || false),
        missedCount: Number(meta.missedCount || 0),

        get overallProgress() {
            let weight = 0, weighted = 0;
            if (this.checklists.total > 0) {
                const p = Math.round((this.checklists.done / this.checklists.total) * 100);
                weight += 40; weighted += p * 40;
            }
            if (this.meds.total > 0) {
                const p = Math.round((this.meds.done / this.meds.total) * 100);
                weight += 40; weighted += p * 40;
            }
            if (this.vitals.total > 0) {
                const p = Math.round((this.vitals.done / this.vitals.total) * 100);
                weight += 20; weighted += p * 20;
            }
            return weight > 0 ? Math.round(weighted / weight) : 0;
        },

        get stage() {
            const p = this.overallProgress;

            // H5 FIX: Only show wilting when the user is genuinely struggling
            // (< 50% overall progress). Previously triggered at any % if any
            // item was missed — psychologically punishing 90%+ completion.
            if (this.isWilting && p < 50) return -1;

            if (p >= 100) return 4;
            if (p >= 75)  return 3;
            if (p >= 50)  return 2;
            if (p >= 25)  return 1;
            return 0;
        },

        get streakLabel() {
            if (this.streakDays <= 0) return 'No streak';
            if (this.streakDays === 1) return '1-day streak';
            return `${this.streakDays}-day streak`;
        },

        get streakDetail() {
            if (this.isWilting) {
                return this.missedCount > 0
                    ? `${this.missedCount} missed item(s) today. Recover your streak by finishing upcoming steps.`
                    : 'Your garden is wilting. Complete your next actions to recover it.';
            }

            if (this.streakDays <= 0) {
                return 'Complete today\'s goals to start a new streak.';
            }

            return `Great momentum: ${this.streakLabel}!`;
        },

        get stageIcon() {
            if (this.stage === -1) return '🥀';
            if (this.stage === 0) return '🌰';
            if (this.stage === 1) return '🌱';
            if (this.stage === 2) return '🌿';
            if (this.stage === 3) return '🌷';
            return '🌸';
        },

        get message() {
            if (this.isWilting) {
                return "Your plant is wilting. Let's recover it with one action at a time. 🌾";
            }

            const msgs = [
                "Your plant needs water. Let's do some tasks! 💧",
                "It's sprouting! Good start! 🌿",
                "Look at it grow! Keep it up! 🌱",
                "Almost there! About to bloom! 🌷",
                "Amazing! Your garden is in full bloom! 🌸",
            ];
            return msgs[this.stage];
        },

        init() {
            // Listen for progress updates from other Alpine components
            window.addEventListener('progress-updated', (e) => {
                const d = e.detail;
                if (d.checklists  !== undefined) this.checklists.done  = d.checklists;
                if (d.checklistTotal !== undefined) this.checklists.total = d.checklistTotal;
                if (d.medications !== undefined) this.meds.done = d.medications;
                if (d.medicationTotal !== undefined) this.meds.total = d.medicationTotal;
                // H7 FIX: Update vitals count in real-time when a vital is recorded.
                if (d.vitals      !== undefined) this.vitals.done  = d.vitals;
                if (d.vitalTotal  !== undefined) this.vitals.total = d.vitalTotal;
            });

            window.addEventListener('garden-meta-updated', (e) => {
                const d = e.detail || {};
                if (d.streakDays !== undefined) this.streakDays = Number(d.streakDays || 0);
                if (d.isWilting !== undefined) this.isWilting = Boolean(d.isWilting);
                if (d.missedCount !== undefined) this.missedCount = Number(d.missedCount || 0);
            });
        },
    };
}
