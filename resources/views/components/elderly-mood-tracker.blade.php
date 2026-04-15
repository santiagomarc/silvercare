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
        <div class="flex flex-col items-center justify-center w-28 flex-shrink-0">
            <div
                class="w-14 h-14 rounded-2xl bg-white/70 border border-white/80 flex items-center justify-center transition-transform duration-300"
                :style="`transform: scale(${saved ? 1.1 : 1})`"
                aria-hidden="true"
            >
                <x-lucide-frown x-show="value === 1" class="w-10 h-10" x-bind:style="`color: ${color}`" />
                <x-lucide-frown x-show="value === 2" class="w-9 h-9" x-bind:style="`color: ${color}`" />
                <x-lucide-meh x-show="value === 3" class="w-9 h-9" x-bind:style="`color: ${color}`" />
                <x-lucide-smile x-show="value === 4" class="w-9 h-9" x-bind:style="`color: ${color}`" />
                <x-lucide-party-popper x-show="value === 5" class="w-9 h-9" x-bind:style="`color: ${color}`" />
            </div>
            <p
                class="font-extrabold text-lg mt-1 transition-colors duration-300 text-center"
                x-text="label"
                :style="`color: ${color}`"
            ></p>
        </div>

        {{-- Slider --}}
        <div class="flex-1 w-full">
            <div class="flex items-center justify-between mb-2">
                <label for="mood-slider" class="font-bold text-base text-gray-800">
                    How are you feeling today?
                </label>
                <span
                    x-show="saved"
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

            <input
                id="mood-slider"
                type="range"
                min="1"
                max="5"
                x-model.number="value"
                @input="onInput()"
                :style="`accent-color: ${color}`"
                class="w-full h-3 rounded-full appearance-none cursor-pointer bg-gray-200"
                aria-label="Mood level"
                aria-valuemin="1"
                aria-valuemax="5"
                :aria-valuenow="value"
                :aria-valuetext="label"
            >

            <div class="flex justify-between mt-1.5 px-0.5">
                <span class="text-xs font-bold text-gray-400">Very Sad</span>
                <span class="text-xs font-bold text-gray-400">Very Happy</span>
            </div>
        </div>
    </div>
</div>
