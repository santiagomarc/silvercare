{{-- ============================================================
     ElderlyMoodTracker — five taps, no slider.

     Direct tap beats drag precision for this reader, so the buttons are
     the only interaction. Each one is a 76px target with its own face
     AND its own word, and selection is carried in the brand colour like
     every other choice in the app — the app does not grade your day by
     turning the button you pressed red.

     The face above the buttons keeps the mood scale (`--sc-mood-*`),
     because there it is describing, not judging.

     Uses Alpine.data('moodTracker') — logic untouched.
     ============================================================ --}}

@props(['initialMood' => 3])

<div
    x-data="moodTracker({{ $initialMood }})"
    class="sc-card p-6"
    role="region"
    aria-label="Mood tracker"
>
    <div class="flex flex-col sm:flex-row items-center gap-5">

        {{-- The face --}}
        <div class="flex flex-col items-center justify-center w-28 sm:w-32 flex-shrink-0">
            <div
                class="sc-card-quiet w-20 h-20 flex items-center justify-center transition-transform duration-300"
                :style="`transform: scale(${saved ? 1.1 : 1})`"
                aria-hidden="true"
            >
                <template x-if="isSelected(1)">
                    <x-lucide-frown class="sc-i w-12 h-12" x-bind:style="`color: ${color}`" />
                </template>
                <template x-if="isSelected(2)">
                    <x-lucide-frown class="sc-i w-11 h-11" aria-hidden="true" x-bind:style="`color: ${color}`" />
                </template>
                <template x-if="isSelected(3)">
                    <x-lucide-meh class="sc-i w-11 h-11" aria-hidden="true" x-bind:style="`color: ${color}`" />
                </template>
                <template x-if="isSelected(4)">
                    <x-lucide-smile class="sc-i w-11 h-11" aria-hidden="true" x-bind:style="`color: ${color}`" />
                </template>
                <template x-if="isSelected(5)">
                    <x-lucide-laugh class="sc-i w-11 h-11" aria-hidden="true" x-bind:style="`color: ${color}`" />
                </template>
            </div>
            <p
                class="sc-h3 mt-2 text-center transition-colors duration-300"
                x-text="label"
                :style="`color: ${color}`"
            ></p>
        </div>

        {{-- The choice --}}
        <div class="flex-1 w-full">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                <p id="sc-mood-question" class="sc-h3">
                    How are you feeling today?
                </p>
                <div class="flex items-center gap-2">
                    <span
                        x-show="saving"
                        x-cloak
                        class="sc-mark"
                        role="status"
                    >
                        <x-lucide-loader-circle class="sc-i w-4 h-4 animate-spin" aria-hidden="true" />
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
                        class="sc-mark sc-mark-ok"
                        role="status"
                    >
                        <x-lucide-check class="sc-i w-4 h-4" aria-hidden="true" />
                        <span>Saved</span>
                    </span>
                </div>
            </div>

            <div
                class="grid grid-cols-5 gap-2"
                role="radiogroup"
                aria-labelledby="sc-mood-question"
            >
                <button
                    type="button"
                    role="radio"
                    aria-checked="false"
                    :aria-checked="isSelected(1)"
                    @click="setMood(1)"
                    class="sc-pick"
                    :class="isSelected(1) && 'sc-pick-on'"
                    aria-label="Very Sad"
                >
                    <x-lucide-frown class="sc-i w-7 h-7" aria-hidden="true" />
                    <span class="text-sm leading-none">Very Sad</span>
                </button>

                <button
                    type="button"
                    role="radio"
                    aria-checked="false"
                    :aria-checked="isSelected(2)"
                    @click="setMood(2)"
                    class="sc-pick"
                    :class="isSelected(2) && 'sc-pick-on'"
                    aria-label="Sad"
                >
                    <x-lucide-frown class="sc-i w-7 h-7" aria-hidden="true" />
                    <span class="text-sm leading-none">Sad</span>
                </button>

                <button
                    type="button"
                    role="radio"
                    aria-checked="false"
                    :aria-checked="isSelected(3)"
                    @click="setMood(3)"
                    class="sc-pick"
                    :class="isSelected(3) && 'sc-pick-on'"
                    aria-label="Neutral"
                >
                    <x-lucide-meh class="sc-i w-7 h-7" aria-hidden="true" />
                    <span class="text-sm leading-none">Neutral</span>
                </button>

                <button
                    type="button"
                    role="radio"
                    aria-checked="false"
                    :aria-checked="isSelected(4)"
                    @click="setMood(4)"
                    class="sc-pick"
                    :class="isSelected(4) && 'sc-pick-on'"
                    aria-label="Happy"
                >
                    <x-lucide-smile class="sc-i w-7 h-7" aria-hidden="true" />
                    <span class="text-sm leading-none">Happy</span>
                </button>

                <button
                    type="button"
                    role="radio"
                    aria-checked="false"
                    :aria-checked="isSelected(5)"
                    @click="setMood(5)"
                    class="sc-pick"
                    :class="isSelected(5) && 'sc-pick-on'"
                    aria-label="Very Happy"
                >
                    <x-lucide-laugh class="sc-i w-7 h-7" aria-hidden="true" />
                    <span class="text-sm leading-none">Very Happy</span>
                </button>
            </div>

            <div class="flex justify-between mt-2 px-0.5">
                <span class="font-medium" style="color:var(--sc-muted)">&larr; Very Sad</span>
                <span class="font-medium" style="color:var(--sc-muted)">Very Happy &rarr;</span>
            </div>
        </div>

    </div>
</div>
