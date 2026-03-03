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
 */
export default function gardenWellness(checklists, meds, vitals) {
    return {
        checklists: { done: checklists.done, total: checklists.total },
        meds:       { done: meds.done,       total: meds.total       },
        vitals:     { done: vitals.done,      total: vitals.total     },

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
            if (p >= 100) return 4;
            if (p >= 75)  return 3;
            if (p >= 50)  return 2;
            if (p >= 25)  return 1;
            return 0;
        },

        get message() {
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
                if (d.checklists !== undefined) this.checklists.done = d.checklists;
                if (d.checklistTotal !== undefined) this.checklists.total = d.checklistTotal;
                if (d.medications !== undefined) this.meds.done = d.medications;
                if (d.medicationTotal !== undefined) this.meds.total = d.medicationTotal;
            });
        },
    };
}
