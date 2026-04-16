{{-- ============================================================
     ElderlyMoodTracker — Mood slider with Alpine auto-save.
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
                class="w-16 h-16 rounded-2xl bg-white/80 border border-white/90 flex items-center justify-center transition-transform duration-300 shadow-sm"
                :style="`transform: scale(${saved ? 1.1 : 1})`"
                aria-hidden="true"
            >
                <template x-if="isSelected(1)">
                    <x-lucide-angry class="w-10 h-10" x-bind:style="`color: ${color}`" />
                </template>
                <template x-if="isSelected(2)">
                    <x-lucide-frown class="w-9 h-9" x-bind:style="`color: ${color}`" />
                </template>
                <template x-if="isSelected(3)">
                    <x-lucide-meh class="w-9 h-9" x-bind:style="`color: ${color}`" />
                </template>
                <template x-if="isSelected(4)">
                    <x-lucide-smile class="w-9 h-9" x-bind:style="`color: ${color}`" />
                </template>
                <template x-if="isSelected(5)">
                    <x-lucide-laugh class="w-9 h-9" x-bind:style="`color: ${color}`" />
                </template>
            </div>
            <p
                class="font-extrabold text-lg mt-1 transition-colors duration-300 text-center"
                x-text="label"
                :style="`color: ${color}`"
            ></p>
            <p class="text-[11px] font-bold text-slate-400 text-center">Tap a face below</p>
        </div>

        {{-- Slider --}}
        <div class="flex-1 w-full">
            <div class="flex items-center justify-between mb-2 gap-2">
                <label for="mood-slider" class="font-bold text-base text-gray-800">
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

            <input
                id="mood-slider"
                x-ref="moodSlider"
                type="range"
                min="1"
                max="5"
                x-model.number="value"
                @input="onInput()"
                @change="onInput()"
                :style="`accent-color: ${color}`"
                class="w-full h-3 rounded-full appearance-none cursor-pointer bg-gray-200"
                aria-label="Mood level"
                aria-valuemin="1"
                aria-valuemax="5"
                :aria-valuenow="value"
                :aria-valuetext="label"
            >

            <div class="relative mt-3">
                <div class="absolute left-3 right-3 top-1/2 -translate-y-1/2 h-0.5 bg-slate-200" aria-hidden="true"></div>
                <div class="relative grid grid-cols-5 gap-2" role="radiogroup" aria-label="Choose your mood">
                    <button
                        type="button"
                        role="radio"
                        :aria-checked="isSelected(1)"
                        @click="setMood(1)"
                        class="h-10 rounded-full border transition-all duration-200 flex items-center justify-center"
                        :class="isSelected(1) ? 'bg-rose-50 border-rose-300 text-rose-600 shadow-sm scale-105' : 'bg-white/85 border-slate-200 text-slate-400 hover:text-slate-600'"
                        aria-label="Very Sad"
                    >
                        <x-lucide-angry class="w-4 h-4" aria-hidden="true" />
                    </button>

                    <button
                        type="button"
                        role="radio"
                        :aria-checked="isSelected(2)"
                        @click="setMood(2)"
                        class="h-10 rounded-full border transition-all duration-200 flex items-center justify-center"
                        :class="isSelected(2) ? 'bg-orange-50 border-orange-300 text-orange-600 shadow-sm scale-105' : 'bg-white/85 border-slate-200 text-slate-400 hover:text-slate-600'"
                        aria-label="Sad"
                    >
                        <x-lucide-frown class="w-4 h-4" aria-hidden="true" />
                    </button>

                    <button
                        type="button"
                        role="radio"
                        :aria-checked="isSelected(3)"
                        @click="setMood(3)"
                        class="h-10 rounded-full border transition-all duration-200 flex items-center justify-center"
                        :class="isSelected(3) ? 'bg-slate-100 border-slate-300 text-slate-600 shadow-sm scale-105' : 'bg-white/85 border-slate-200 text-slate-400 hover:text-slate-600'"
                        aria-label="Neutral"
                    >
                        <x-lucide-meh class="w-4 h-4" aria-hidden="true" />
                    </button>

                    <button
                        type="button"
                        role="radio"
                        :aria-checked="isSelected(4)"
                        @click="setMood(4)"
                        class="h-10 rounded-full border transition-all duration-200 flex items-center justify-center"
                        :class="isSelected(4) ? 'bg-lime-50 border-lime-300 text-lime-600 shadow-sm scale-105' : 'bg-white/85 border-slate-200 text-slate-400 hover:text-slate-600'"
                        aria-label="Happy"
                    >
                        <x-lucide-smile class="w-4 h-4" aria-hidden="true" />
                    </button>

                    <button
                        type="button"
                        role="radio"
                        :aria-checked="isSelected(5)"
                        @click="setMood(5)"
                        class="h-10 rounded-full border transition-all duration-200 flex items-center justify-center"
                        :class="isSelected(5) ? 'bg-emerald-50 border-emerald-300 text-emerald-600 shadow-sm scale-105' : 'bg-white/85 border-slate-200 text-slate-400 hover:text-slate-600'"
                        aria-label="Very Happy"
                    >
                        <x-lucide-laugh class="w-4 h-4" aria-hidden="true" />
                    </button>
                </div>
            </div>

            <div class="flex justify-between mt-1.5 px-0.5">
                <span class="text-xs font-bold text-gray-400">Very Sad</span>
                <span class="text-xs font-bold text-gray-400">Very Happy</span>
            </div>
        </div>
    </div>
</div>
