{{-- ============================================================
     VitalRecordModal — Server-rendered modal for recording vitals.
     Replaces 150+ lines of JS document.createElement.
     Uses Alpine.data('vitalRecorder') for state.
     ============================================================ --}}

<div x-data="vitalRecorder()" @open-vital-modal.window="openModal($event.detail.type)">
    {{-- Modal backdrop + dialog --}}
    <div
        x-show="open"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        role="dialog"
        aria-modal="true"
        :aria-label="config ? 'Record ' + config.name : 'Record vital'"
    >
        {{-- Backdrop --}}
        <div
            class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity duration-300"
            @click="closeModal()"
            x-show="open"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        ></div>

        {{-- Dialog --}}
        <div class="flex min-h-full items-center justify-center p-4">
            <div
                x-show="open"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                @keydown.escape.window="closeModal()"
                x-ref="modalContent"
                class="bg-white rounded-card-lg shadow-2xl max-w-md w-full p-8 relative z-10"
            >
                {{-- Header --}}
                <div class="flex items-center gap-4 mb-6" x-show="config">
                    <div
                        class="w-16 h-16 rounded-2xl flex items-center justify-center text-3xl"
                        :class="`bg-${config?.color}-50`"
                    >
                        <span x-text="config?.icon"></span>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-xl text-gray-900" x-text="config?.name" id="vital-modal-title"></h3>
                        <p class="text-sm text-gray-500">Record your measurement</p>
                    </div>
                </div>

                {{-- Form --}}
                <form @submit.prevent="submit()">
                    {{-- Blood Pressure (two inputs) --}}
                    <template x-if="config?.isBP">
                        <div class="mb-4">
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <label class="block text-sm font-bold text-gray-700">
                                    Value <span class="text-gray-400" x-text="'(' + config.unit + ')'"></span>
                                </label>
                                <button
                                    type="button"
                                    x-show="voiceSupported"
                                    @click="startVoiceCapture()"
                                    class="rounded-lg border border-gray-200 bg-white px-2.5 py-1 text-xs font-bold text-gray-600 hover:border-gray-300"
                                    x-text="voiceListening ? 'Stop Voice' : 'Use Voice'"
                                ></button>
                            </div>
                            <div class="flex gap-3 items-center">
                                <div class="flex-1">
                                    <input
                                        type="number"
                                        x-model="systolic"
                                        placeholder="120"
                                        min="60" max="250"
                                        class="w-full px-4 py-4 text-2xl font-bold text-center border-2 border-gray-200 rounded-xl focus:ring-4 transition-all outline-none"
                                        required
                                        aria-label="Systolic pressure"
                                    >
                                    <p class="text-xs text-gray-400 mt-1 text-center">Systolic</p>
                                </div>
                                <span class="text-3xl text-gray-300 font-bold" aria-hidden="true">/</span>
                                <div class="flex-1">
                                    <input
                                        type="number"
                                        x-model="diastolic"
                                        placeholder="80"
                                        min="40" max="150"
                                        class="w-full px-4 py-4 text-2xl font-bold text-center border-2 border-gray-200 rounded-xl focus:ring-4 transition-all outline-none"
                                        required
                                        aria-label="Diastolic pressure"
                                    >
                                    <p class="text-xs text-gray-400 mt-1 text-center">Diastolic</p>
                                </div>
                                <span class="text-gray-400 font-bold self-start pt-4" x-text="config.unit"></span>
                            </div>
                            <p x-show="voiceListening" class="text-xs text-emerald-600 font-semibold mt-2" x-cloak>
                                Listening... Try saying "120 over 80".
                            </p>
                            <p class="text-xs text-gray-400 mt-2" x-text="config.hint"></p>
                        </div>
                    </template>

                    {{-- Single value (sugar, temp, heart rate) --}}
                    <template x-if="config && !config.isBP">
                        <div class="mb-4">
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <label class="block text-sm font-bold text-gray-700">
                                    Value <span class="text-gray-400" x-text="'(' + config.unit + ')'"></span>
                                </label>
                                <button
                                    type="button"
                                    x-show="voiceSupported"
                                    @click="startVoiceCapture()"
                                    class="rounded-lg border border-gray-200 bg-white px-2.5 py-1 text-xs font-bold text-gray-600 hover:border-gray-300"
                                    x-text="voiceListening ? 'Stop Voice' : 'Use Voice'"
                                ></button>
                            </div>
                            <div class="relative">
                                <input
                                    type="number"
                                    x-model="value"
                                    :placeholder="config.placeholder"
                                    :min="config.min"
                                    :max="config.max"
                                    :step="config.step"
                                    class="w-full px-4 py-4 text-2xl font-bold text-center border-2 border-gray-200 rounded-xl focus:ring-4 transition-all outline-none"
                                    required
                                    :aria-label="config.name + ' value'"
                                >
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold" x-text="config.unit"></span>
                            </div>
                            <p x-show="voiceListening" class="text-xs text-emerald-600 font-semibold mt-2" x-cloak>
                                Listening... Say the value now.
                            </p>
                            <p class="text-xs text-gray-400 mt-2" x-text="config.hint"></p>
                        </div>
                    </template>

                    {{-- Notes --}}
                    <div class="mb-6">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Notes (optional)</label>
                        <textarea
                            x-model="notes"
                            placeholder="Any additional notes..."
                            rows="2"
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-4 transition-all outline-none resize-none text-sm"
                            aria-label="Notes about this measurement"
                        ></textarea>
                    </div>

                    {{-- Actions --}}
                    <div class="flex gap-3">
                        <button
                            type="button"
                            @click="closeModal()"
                            class="flex-1 py-3 px-4 bg-gray-100 text-gray-700 font-bold rounded-xl hover:bg-gray-200 transition-colors min-h-touch"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="submitting"
                            class="flex-1 py-3 px-4 bg-navy text-white font-bold rounded-xl hover:bg-navy-700 transition-colors min-h-touch flex items-center justify-center gap-2"
                        >
                            <span x-show="!submitting">Save</span>
                            <span x-show="submitting">Saving...</span>
                            <svg x-show="!submitting" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
