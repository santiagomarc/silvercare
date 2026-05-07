<x-dashboard-layout>
    <x-slot:title>My Patients - SilverCare</x-slot:title>

    <x-dashboard-nav
        title="My Patients"
        subtitle="Manage your linked patients"
        role="caregiver"
        :show-back="true"
    />

    {{-- Alpine Store --}}
    {{-- ===== REMOVE PATIENT MODAL ===== --}}
    <div
        x-data
        x-show="$store.patientModal.removeOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center"
        @keydown.escape.window="$store.patientModal.closeRemove()"
    >
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="$store.patientModal.closeRemove()"></div>
        <div
            class="relative bg-white rounded-3xl shadow-2xl p-8 w-full max-w-sm mx-4 flex flex-col items-center text-center"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
        >
            <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
            <h2 class="text-xl font-[900] text-gray-900 mb-2">Remove Patient</h2>
            <p class="text-gray-500 text-sm leading-relaxed mb-6">
                Are you sure you want to remove this patient? This will unlink their profile and they will no longer be in your active list.
            </p>
            <div class="flex items-center gap-3 w-full">
                <button
                    @click="$store.patientModal.closeRemove()"
                    class="flex-1 rounded-2xl border border-gray-200 bg-gray-100 px-4 py-3 text-sm font-bold text-gray-700 hover:bg-gray-200 transition-colors"
                >Cancel</button>
                <form method="POST" :action="$store.patientModal.removeAction" class="flex-1">
                    @csrf
                    <button type="submit" class="w-full rounded-2xl bg-red-600 px-4 py-3 text-sm font-bold text-white hover:bg-red-700 transition-colors">
                        Remove Patient
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ===== RESTORE PATIENT MODAL ===== --}}
    <div
        x-data
        x-show="$store.patientModal.restoreOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center"
        @keydown.escape.window="$store.patientModal.closeRestore()"
    >
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="$store.patientModal.closeRestore()"></div>
        <div
            class="relative bg-white rounded-3xl shadow-2xl p-8 w-full max-w-sm mx-4 flex flex-col items-center text-center"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
        >
            <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-[#000080]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </div>
            <h2 class="text-xl font-[900] text-gray-900 mb-2">Restore Patient</h2>
            <p class="text-gray-500 text-sm leading-relaxed mb-6">
                Are you sure you want to restore? This will reactivate their profile and make them available again.
            </p>
            <div class="flex items-center gap-3 w-full">
                <button
                    @click="$store.patientModal.closeRestore()"
                    class="flex-1 rounded-2xl border border-gray-200 bg-gray-100 px-4 py-3 text-sm font-bold text-gray-700 hover:bg-gray-200 transition-colors"
                >Cancel</button>
                <form method="POST" :action="$store.patientModal.restoreAction" class="flex-1">
                    @csrf
                    <button type="submit" class="w-full rounded-2xl bg-[#000080] px-4 py-3 text-sm font-bold text-white hover:bg-blue-900 transition-colors">
                        Restore Patient
                    </button>
                </form>
            </div>
        </div>
    </div>

    <main x-data class="max-w-[1200px] mx-auto px-6 lg:px-12 py-6 space-y-8">
        <x-flash-messages />

        {{-- ACTIVE PATIENTS --}}
        <section>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-[900] text-gray-900">Active Patients <span class="text-base font-bold text-gray-400">({{ $activePatients->count() }})</span></h2>
            </div>

            @if($activePatients->isEmpty())
                <div class="rounded-2xl border border-gray-100 bg-white p-8 text-center shadow-sm">
                    <div class="text-4xl mb-3">👥</div>
                    <p class="font-bold text-gray-500">No active patients yet.</p>
                    <p class="text-sm text-gray-400 mt-1">Go to your dashboard to generate a linking PIN.</p>
                    <a href="{{ route('caregiver.dashboard') }}" class="inline-flex mt-4 items-center gap-2 rounded-xl bg-[#000080] px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-900 transition-colors">
                        Go to Dashboard
                    </a>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($activePatients as $data)
                        @php $patient = $data['profile']; $user = $data['user']; @endphp
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col gap-4">
                            {{-- Patient Header --}}
                            <div class="flex items-center gap-4">
                                <div class="w-14 h-14 rounded-full bg-teal-100 flex items-center justify-center text-teal-700 font-[900] text-xl overflow-hidden flex-shrink-0">
                                    @if($patient->profile_photo)
                                        <img src="{{ Storage::url($patient->profile_photo) }}" class="w-full h-full object-cover">
                                    @else
                                        {{ strtoupper(substr($user?->name ?? 'P', 0, 1)) }}
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-[900] text-gray-900 text-lg truncate">{{ $user?->name ?? 'Unknown' }}</p>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        @if($patient->age)
                                            <span class="text-xs text-gray-500 font-medium">{{ $patient->age }} yrs</span>
                                        @endif
                                        @if($patient->sex)
                                            <span class="text-xs text-gray-500 font-medium">· {{ $patient->sex }}</span>
                                        @endif
                                        <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {{-- Stats --}}
                            <div class="grid grid-cols-2 gap-3">
                                <div class="rounded-xl bg-gray-50 p-3">
                                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wide">Medication Today</p>
                                    <p class="text-lg font-[900] text-gray-900 mt-0.5">
                                        @if($data['medication_adherence'] !== null)
                                            {{ $data['medication_adherence'] }}%
                                        @else
                                            <span class="text-gray-300">N/A</span>
                                        @endif
                                    </p>
                                </div>
                                <div class="rounded-xl bg-gray-50 p-3">
                                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wide">Last Active</p>
                                    <p class="text-sm font-bold text-gray-900 mt-0.5">
                                        {{ $data['last_active'] ? \Carbon\Carbon::parse($data['last_active'])->diffForHumans() : 'No data' }}
                                    </p>
                                </div>
                            </div>

                            {{-- Actions --}}
                            <div class="flex items-center gap-2 pt-1 border-t border-gray-100">
                                <a href="{{ route('caregiver.dashboard', ['elderly' => $patient->id]) }}"
                                    class="flex-1 text-center rounded-xl bg-[#000080] px-3 py-2 text-sm font-bold text-white hover:bg-blue-900 transition-colors">
                                    View Dashboard
                                </a>
                                <button
                                    @click="$store.patientModal.openRemove('{{ route('caregiver.patients.remove', $patient->id) }}')"
                                    class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-bold text-rose-700 hover:bg-rose-100 transition-colors">
                                    Remove
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- ARCHIVED PATIENTS --}}
        @if($archivedPatients->isNotEmpty())
        <section>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-[900] text-gray-900">Archived Patients <span class="text-base font-bold text-gray-400">({{ $archivedPatients->count() }})</span></h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($archivedPatients as $data)
                    @php $patient = $data['profile']; $user = $data['user']; @endphp
                    <div class="bg-gray-50 rounded-2xl border border-gray-200 shadow-sm p-5 flex flex-col gap-4 opacity-75">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 font-[900] text-xl overflow-hidden flex-shrink-0">
                                @if($patient->profile_photo)
                                    <img src="{{ Storage::url($patient->profile_photo) }}" class="w-full h-full object-cover grayscale">
                                @else
                                    {{ strtoupper(substr($user?->name ?? 'P', 0, 1)) }}
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-[900] text-gray-700 text-lg truncate">{{ $user?->name ?? 'Unknown' }}</p>
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center gap-1 text-xs font-bold text-gray-500 bg-gray-200 px-2 py-0.5 rounded-full">
                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Archived
                                    </span>
                                    @if($data['archived_at'])
                                        <span class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($data['archived_at'])->format('M d, Y') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 pt-1 border-t border-gray-200">
                            <button
                                @click="$store.patientModal.openRestore('{{ route('caregiver.patients.restore', $patient->id) }}')"
                                class="flex-1 w-full rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-bold text-[#000080] hover:bg-blue-100 transition-colors">
                                Restore Patient
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
        @endif

    </main>
</x-dashboard-layout>