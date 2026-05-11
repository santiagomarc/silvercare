{{-- ============================================================
     ElderlyMoodTracker — Mood buttons with Alpine auto-save.
     Slider removed: buttons are now the sole interaction method
     for better elderly UX (direct tap > drag precision).
     Uses Alpine.data('moodTracker').
     ============================================================ --}}
 
@props(['initialMood' => 3])
 
<div
    x-data="moodTracker({{ $initialMood }})"
    class="surface-warm relative overflow-hidden p-6"
    role="region"
    aria-label="Mood tracker"
>
    <div class="ambient-orb -right-8 top-0 h-28 w-28 bg-amber-200/45"></div>
    <div class="ambient-orb -bottom-8 left-8 h-24 w-24 bg-rose-200/30 blur-2xl"></div>
 
    <div class="flex flex-col sm:flex-row items-center gap-5">
 
        {{-- Mood icon display --}}
        <div class="flex flex-col items-center justify-center w-28 sm:w-32 flex-shrink-0">
            <div
                class="w-20 h-20 rounded-2xl bg-white/80 border border-white/90 flex items-center justify-center transition-transform duration-300 shadow-sm"
                :style="`transform: scale(${saved ? 1.1 : 1})`"
                aria-hidden="true"
            >
                <template x-if="isSelected(1)">
                    <x-lucide-frown class="w-12 h-12" x-bind:style="`color: ${color}`" />
                </template>
                <template x-if="isSelected(2)">
                    <x-lucide-frown class="w-11 h-11" x-bind:style="`color: ${color}`" />
                </template>
                <template x-if="isSelected(3)">
                    <x-lucide-meh class="w-11 h-11" x-bind:style="`color: ${color}`" />
                </template>
                <template x-if="isSelected(4)">
                    <x-lucide-smile class="w-11 h-11" x-bind:style="`color: ${color}`" />
                </template>
                <template x-if="isSelected(5)">
                    <x-lucide-laugh class="w-11 h-11" x-bind:style="`color: ${color}`" />
                </template>
            </div>
            <p
                class="font-extrabold text-xl mt-2 transition-colors duration-300 text-center"
                x-text="label"
                :style="`color: ${color}`"
            ></p>
        </div>
 
        {{-- Mood buttons --}}
        <div class="flex-1 w-full">
            <div class="flex items-center justify-between mb-4 gap-2">
                <label class="font-bold text-lg text-gray-900">
                    How are you feeling today?
                </label>
                <div class="flex items-center gap-2">
                    <span
                        x-show="saving"
                        x-cloak
                        class="badge text-xs inline-flex items-center gap-1 border border-navy-200 bg-white/85 text-navy-700"
                        role="status"
                    >
                        <x-lucide-loader-circle class="w-3.5 h-3.5 animate-spin" aria-hidden="true" />
                        <span>Saving</span>
                    </span>
                    <span
                        x-show="saved && !saving"
                        x-cloak
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="badge badge-success text-xs inline-flex items-center gap-1"
                        role="status"
                    >
                        <x-lucide-check class="w-3.5 h-3.5" aria-hidden="true" />
                        <span>Saved</span>
                    </span>
                </div>
            </div>
 
            {{--
                Mood buttons — the ONLY interaction method.
                Slider removed: elderly users perform better with
                direct tap targets than drag interactions.
                Changes from original:
                - h-10 → h-14: larger touch target (56px) for reduced dexterity
                - w-4 h-4 → w-7 h-7: icons 16px → 28px for low-vision users
                - Added short text label under each icon (co-located label)
                - End labels darkened from gray-400 to slate-600 for WCAG AA contrast
            --}}
            <div
                class="grid grid-cols-5 gap-2"
                role="radiogroup"
                aria-label="Choose your mood"
            >
                <button
                    type="button"
                    role="radio"
                    :aria-checked="isSelected(1)"
                    @click="setMood(1)"
                    class="h-14 rounded-full border-2 transition-all duration-200 flex flex-col items-center justify-center gap-0.5"
                    :class="isSelected(1)
                        ? 'bg-rose-50 border-rose-400 text-rose-600 shadow-sm scale-105'
                        : 'bg-white/85 border-slate-200 text-slate-500 hover:border-slate-400 hover:text-slate-700'"
                    aria-label="Very Sad"
                >
                    <x-lucide-frown class="w-7 h-7" aria-hidden="true" />
                    <span class="text-[10px] font-bold leading-none">Very Sad</span>
                </button>
 
                <button
                    type="button"
                    role="radio"
                    :aria-checked="isSelected(2)"
                    @click="setMood(2)"
                    class="h-14 rounded-full border-2 transition-all duration-200 flex flex-col items-center justify-center gap-0.5"
                    :class="isSelected(2)
                        ? 'bg-orange-50 border-orange-400 text-orange-600 shadow-sm scale-105'
                        : 'bg-white/85 border-slate-200 text-slate-500 hover:border-slate-400 hover:text-slate-700'"
                    aria-label="Sad"
                >
                    <x-lucide-frown class="w-7 h-7" aria-hidden="true" />
                    <span class="text-[10px] font-bold leading-none">Sad</span>
                </button>
 
                <button
                    type="button"
                    role="radio"
                    :aria-checked="isSelected(3)"
                    @click="setMood(3)"
                    class="h-14 rounded-full border-2 transition-all duration-200 flex flex-col items-center justify-center gap-0.5"
                    :class="isSelected(3)
                        ? 'bg-slate-100 border-slate-400 text-slate-700 shadow-sm scale-105'
                        : 'bg-white/85 border-slate-200 text-slate-500 hover:border-slate-400 hover:text-slate-700'"
                    aria-label="Neutral"
                >
                    <x-lucide-meh class="w-7 h-7" aria-hidden="true" />
                    <span class="text-[10px] font-bold leading-none">Neutral</span>
                </button>
 
                <button
                    type="button"
                    role="radio"
                    :aria-checked="isSelected(4)"
                    @click="setMood(4)"
                    class="h-14 rounded-full border-2 transition-all duration-200 flex flex-col items-center justify-center gap-0.5"
                    :class="isSelected(4)
                        ? 'bg-lime-50 border-lime-400 text-lime-700 shadow-sm scale-105'
                        : 'bg-white/85 border-slate-200 text-slate-500 hover:border-slate-400 hover:text-slate-700'"
                    aria-label="Happy"
                >
                    <x-lucide-smile class="w-7 h-7" aria-hidden="true" />
                    <span class="text-[10px] font-bold leading-none">Happy</span>
                </button>
 
                <button
                    type="button"
                    role="radio"
                    :aria-checked="isSelected(5)"
                    @click="setMood(5)"
                    class="h-14 rounded-full border-2 transition-all duration-200 flex flex-col items-center justify-center gap-0.5"
                    :class="isSelected(5)
                        ? 'bg-emerald-50 border-emerald-400 text-emerald-700 shadow-sm scale-105'
                        : 'bg-white/85 border-slate-200 text-slate-500 hover:border-slate-400 hover:text-slate-700'"
                    aria-label="Very Happy"
                >
                    <x-lucide-laugh class="w-7 h-7" aria-hidden="true" />
                    <span class="text-[10px] font-bold leading-none">Very Happy</span>
                </button>
            </div>
 
            {{-- End labels — darkened for WCAG AA contrast on cream background --}}
            <div class="flex justify-between mt-2 px-0.5">
                <span class="text-sm font-bold text-slate-600">← Very Sad</span>
                <span class="text-sm font-bold text-slate-600">Very Happy →</span>
            </div>
        </div>
 
    </div>
</div>