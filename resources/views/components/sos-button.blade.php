{{-- ============================================================
     SOS Emergency Button — Persistent floating button
     Only visible when a caregiver is linked.
     Two-tap confirm: first tap shows "Are you sure?", second tap triggers.
     ============================================================ --}}

@if($linkedCaregiver ?? false)
<div x-data="{
    step: 'idle',
    sending: false,
    sent: false,
    cooldown: false,

    async triggerSos() {
        if (this.sending || this.cooldown) return;
        this.sending = true;

        try {
            const resp = await fetch('{{ route('elderly.sos') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                },
            });

            const data = await resp.json();
            if (data.success) {
                this.sent = true;
                this.step = 'sent';
                Alpine.store('toast')?.success('SOS sent to your caregiver!');

                // Haptic feedback
                if (navigator.vibrate) navigator.vibrate([200, 100, 200]);

                // Cooldown: prevent rapid re-triggering (60 seconds)
                this.cooldown = true;
                setTimeout(() => {
                    this.cooldown = false;
                    this.step = 'idle';
                    this.sent = false;
                }, 60000);
            } else {
                Alpine.store('toast')?.error(data.message || 'Failed to send SOS');
                this.step = 'idle';
            }
        } catch (e) {
            Alpine.store('toast')?.error('Failed to send SOS. Try calling your caregiver.');
            this.step = 'idle';
        } finally {
            this.sending = false;
        }
    },

    cancel() {
        this.step = 'idle';
    }
}" class="fixed bottom-6 left-6 z-40">

    {{-- IDLE STATE: Red pulsing SOS button --}}
    <button
        x-show="step === 'idle'"
        @click="step = 'confirm'"
        class="group relative h-16 w-16 rounded-full bg-gradient-to-br from-red-500 to-red-700
               text-white font-black text-xs shadow-[0_8px_30px_rgba(220,38,38,0.5)]
               hover:shadow-[0_12px_40px_rgba(220,38,38,0.7)]
               transition-all duration-300 hover:scale-110 active:scale-95
               flex items-center justify-center"
        aria-label="Emergency SOS"
        x-transition
    >
        {{-- Pulse ring --}}
        <span class="absolute inset-0 rounded-full bg-red-400 animate-ping opacity-30"></span>
        <span class="relative z-10 flex flex-col items-center leading-none">
            <svg class="w-6 h-6 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                      d="M20.618 5.984A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
            <span class="text-[10px] font-black tracking-wide">SOS</span>
        </span>
    </button>

    {{-- CONFIRM STATE: "Are you sure?" expanded panel --}}
    <div
        x-show="step === 'confirm'"
        x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-90"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-90"
        class="rounded-2xl bg-white border-2 border-red-200 shadow-[0_20px_50px_rgba(220,38,38,0.25)] p-4 min-w-[220px]"
    >
        <p class="text-sm font-extrabold text-red-700 mb-1">Send Emergency SOS?</p>
        <p class="text-xs text-gray-500 mb-3">Your caregiver will be alerted immediately via notification and email.</p>
        <div class="flex gap-2">
            <button
                @click="triggerSos()"
                :disabled="sending"
                class="flex-1 rounded-xl bg-red-600 hover:bg-red-700 text-white text-sm font-bold py-2.5 px-4
                       transition-colors disabled:opacity-60 flex items-center justify-center gap-1.5"
            >
                <span x-show="!sending">🚨 Yes, Send SOS</span>
                <span x-show="sending" class="flex items-center gap-1.5">
                    <svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    Sending...
                </span>
            </button>
            <button
                @click="cancel()"
                class="rounded-xl border-2 border-gray-200 text-gray-600 text-sm font-bold py-2.5 px-3
                       hover:border-gray-300 transition-colors"
            >
                Cancel
            </button>
        </div>
    </div>

    {{-- SENT STATE: Success confirmation --}}
    <div
        x-show="step === 'sent'"
        x-cloak
        x-transition
        class="rounded-2xl bg-green-50 border-2 border-green-200 shadow-lg p-4 min-w-[200px]"
    >
        <p class="text-sm font-extrabold text-green-700 flex items-center gap-2">
            ✅ SOS Sent!
        </p>
        <p class="text-xs text-gray-500 mt-1">Your caregiver has been notified.</p>
    </div>
</div>
@endif
