{{-- ============================================================
     ElderlyMoodTracker — Mood slider with Alpine auto-save.
     Uses Alpine.data('moodTracker').
     ============================================================ --}}

@props(['initialMood' => 3])

<div
    x-data="moodTracker({{ $initialMood }})"
    class="bg-gradient-to-br from-amber-50 to-orange-100 rounded-card p-6 shadow-md border border-amber-200"
    role="region"
    aria-label="Mood tracker"
>
    <div class="flex flex-col sm:flex-row items-center gap-5">
        {{-- Emoji display --}}
        <div class="flex flex-col items-center justify-center w-28 flex-shrink-0">
            <div
                class="text-5xl transition-transform duration-300"
                x-text="emoji"
                :style="`transform: scale(${saved ? 1.1 : 1})`"
                aria-hidden="true"
            ></div>
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
                    class="badge badge-success text-xs"
                    role="status"
                >
                    ✓ Saved
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
