<x-dashboard-layout sc>
    <x-slot:title>My Patients - SilverCare</x-slot:title>

    <x-dashboard-nav
        title="My Patients"
        subtitle="Manage your linked patients"
        role="caregiver"
        :show-back="true"
    />

    {{-- ===== REMOVE PATIENT MODAL ===== --}}
    <div
        id="remove-patient-modal"
        x-data
        x-show="$store.patientModal.removeOpen"
        x-cloak
        class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50 flex items-center justify-center"
        @keydown.escape.window="$store.patientModal.closeRemove()"
    >
        <div
            x-show="$store.patientModal.removeOpen"
            class="sc-scrim"
            @click="$store.patientModal.closeRemove()"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        ></div>

        <div
            x-show="$store.patientModal.removeOpen"
            role="dialog"
            aria-modal="true"
            aria-labelledby="remove-patient-title"
            class="sc-dialog relative z-[70] w-[90%] max-w-md text-center flex flex-col items-center"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        >
            <span class="sc-plate sc-plate-alert mb-5">
                <x-lucide-user-minus class="sc-i w-7 h-7" aria-hidden="true" />
            </span>
            <h2 id="remove-patient-title" class="sc-dialog-title mb-2">Remove Patient</h2>
            <p class="text-sm leading-relaxed mb-6 text-[var(--sc-body)]">
                Are you sure you want to remove this patient? This will unlink their profile and they will no longer be in your active list.
            </p>
            <div class="flex flex-col-reverse sm:flex-row items-center gap-3 w-full">
                <button
                    type="button"
                    @click="$store.patientModal.closeRemove()"
                    class="sc-btn sc-btn-ghost flex-1 w-full"
                >Cancel</button>
                <form method="POST" :action="$store.patientModal.removeAction" class="flex-1 w-full m-0">
                    @csrf
                    <button type="submit" class="sc-btn sc-btn-danger w-full">
                        Remove Patient
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ===== RESTORE PATIENT MODAL ===== --}}
    <div
        id="restore-patient-modal"
        x-data
        x-show="$store.patientModal.restoreOpen"
        x-cloak
        class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50 flex items-center justify-center"
        @keydown.escape.window="$store.patientModal.closeRestore()"
    >
        <div
            x-show="$store.patientModal.restoreOpen"
            class="sc-scrim"
            @click="$store.patientModal.closeRestore()"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        ></div>

        <div
            x-show="$store.patientModal.restoreOpen"
            role="dialog"
            aria-modal="true"
            aria-labelledby="restore-patient-title"
            class="sc-dialog relative z-[70] w-[90%] max-w-md text-center flex flex-col items-center"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        >
            <span class="sc-plate sc-plate-brand mb-5">
                <x-lucide-rotate-ccw class="sc-i w-7 h-7" aria-hidden="true" />
            </span>
            <h2 id="restore-patient-title" class="sc-dialog-title mb-2">Restore Patient</h2>
            <p class="text-sm leading-relaxed mb-6 text-[var(--sc-body)]">
                Are you sure you want to restore? This will reactivate their profile and make them available again.
            </p>
            <div class="flex flex-col-reverse sm:flex-row items-center gap-3 w-full">
                <button
                    type="button"
                    @click="$store.patientModal.closeRestore()"
                    class="sc-btn sc-btn-ghost flex-1 w-full"
                >Cancel</button>
                <form method="POST" :action="$store.patientModal.restoreAction" class="flex-1 w-full m-0">
                    @csrf
                    <button type="submit" class="sc-btn sc-btn-primary w-full">
                        Restore Patient
                    </button>
                </form>
            </div>
        </div>
    </div>

    <main id="main-content" x-data class="sc-app-main">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <x-flash-messages />

            {{-- ACTIVE PATIENTS --}}
            <section>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="sc-h3 text-[var(--sc-ink)]">Active Patients <span class="sc-num text-sm font-semibold text-[var(--sc-muted)]">({{ $activePatients->count() }})</span></h2>
                </div>

                @if($activePatients->isEmpty())
                    <div class="sc-card p-8 sm:p-10 text-center">
                        <div class="sc-plate sc-plate-brand mx-auto mb-4">
                            <x-lucide-users class="sc-i w-6 h-6" aria-hidden="true" />
                        </div>
                        <p class="font-bold text-base text-[var(--sc-ink)]">No active patients yet.</p>
                        <p class="text-sm text-[var(--sc-muted)] mt-1">Go to your dashboard to generate a linking PIN.</p>
                        <a href="{{ route('caregiver.dashboard') }}" class="sc-btn sc-btn-primary inline-flex mt-5">
                            <span>Go to Dashboard</span>
                        </a>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        @foreach($activePatients as $data)
                            @php $patient = $data['profile']; $user = $data['user']; @endphp
                            <div class="sc-card sc-lift p-5 sm:p-6 flex flex-col justify-between gap-5">
                                {{-- Patient Header --}}
                                <div class="flex items-center gap-4">
                                    <div class="w-14 h-14 rounded-full bg-[var(--sc-surface-3)] border border-[var(--sc-line)] flex items-center justify-center text-[var(--sc-ink)] font-bold text-xl overflow-hidden flex-shrink-0">
                                        @if($patient->profile_photo)
                                            <img src="{{ Storage::url($patient->profile_photo) }}" alt="{{ $user?->name ?? 'Patient' }}" class="w-full h-full object-cover">
                                        @else
                                            <span>{{ strtoupper(substr($user?->name ?? 'P', 0, 1)) }}</span>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-bold text-lg text-[var(--sc-ink)] truncate">{{ $user?->name ?? 'Unknown' }}</p>
                                        <div class="flex items-center gap-2 flex-wrap mt-0.5">
                                            @if($patient->age)
                                                <span class="text-xs text-[var(--sc-muted)] font-medium sc-num">{{ $patient->age }} yrs</span>
                                            @endif
                                            @if($patient->sex)
                                                <span class="text-xs text-[var(--sc-muted)] font-medium">· {{ $patient->sex }}</span>
                                            @endif
                                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-[var(--sc-body)]">
                                                <span class="sc-mark sc-mark-ok" aria-hidden="true"></span> Active
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Stats --}}
                                <div class="grid grid-cols-2 gap-2.5 sm:gap-3">
                                    <div class="sc-card-quiet p-3 sm:p-3.5">
                                        <p class="sc-eyebrow text-[10px] sm:text-xs tracking-normal sm:tracking-wider truncate">Medication Today</p>
                                        <p class="text-lg font-bold sc-num text-[var(--sc-ink)] mt-1">
                                            @if($data['medication_adherence'] !== null)
                                                {{ $data['medication_adherence'] }}%
                                            @else
                                                <span class="text-[var(--sc-muted)] font-normal text-sm">N/A</span>
                                            @endif
                                        </p>
                                    </div>
                                    <div class="sc-card-quiet p-3 sm:p-3.5">
                                        <p class="sc-eyebrow text-[10px] sm:text-xs tracking-normal sm:tracking-wider truncate">Last Active</p>
                                        <p class="text-sm font-semibold text-[var(--sc-ink)] mt-1 truncate">
                                            {{ $data['last_active'] ? \Carbon\Carbon::parse($data['last_active'])->diffForHumans() : 'No data' }}
                                        </p>
                                    </div>
                                </div>

                                {{-- Actions --}}
                                <div class="flex items-center gap-2.5 pt-3 border-t border-[var(--sc-line)]">
                                    <a href="{{ route('caregiver.dashboard', ['elderly' => $patient->id]) }}"
                                        class="flex-1 sc-btn sc-btn-primary sc-btn-sm text-center">
                                        <span>View Dashboard</span>
                                    </a>
                                    <button
                                        type="button"
                                        @click="$store.patientModal.openRemove('{{ route('caregiver.patients.remove', $patient->id) }}')"
                                        class="sc-btn sc-btn-ghost sc-btn-sm text-[var(--sc-alert)] hover:bg-[var(--sc-alert-tint)]">
                                        <span>Remove</span>
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
                    <h2 class="sc-h3 text-[var(--sc-ink)]">Archived Patients <span class="sc-num text-sm font-semibold text-[var(--sc-muted)]">({{ $archivedPatients->count() }})</span></h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    @foreach($archivedPatients as $data)
                        @php $patient = $data['profile']; $user = $data['user']; @endphp
                        <div class="sc-card sc-card-quiet p-5 sm:p-6 flex flex-col justify-between gap-5 opacity-80">
                            <div class="flex items-center gap-4">
                                <div class="w-14 h-14 rounded-full bg-[var(--sc-surface-3)] border border-[var(--sc-line)] flex items-center justify-center text-[var(--sc-muted)] font-bold text-xl overflow-hidden flex-shrink-0">
                                    @if($patient->profile_photo)
                                        <img src="{{ Storage::url($patient->profile_photo) }}" alt="{{ $user?->name ?? 'Patient' }}" class="w-full h-full object-cover grayscale">
                                    @else
                                        <span>{{ strtoupper(substr($user?->name ?? 'P', 0, 1)) }}</span>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-bold text-lg text-[var(--sc-body)] truncate">{{ $user?->name ?? 'Unknown' }}</p>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="inline-flex items-center gap-1.5 text-xs font-medium text-[var(--sc-muted)]">
                                            <span class="sc-mark" aria-hidden="true"></span> Archived
                                        </span>
                                        @if($data['archived_at'])
                                            <span class="text-xs text-[var(--sc-muted)] sc-num">· {{ \Carbon\Carbon::parse($data['archived_at'])->format('M d, Y') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2.5 pt-3 border-t border-[var(--sc-line)]">
                                <button
                                    type="button"
                                    @click="$store.patientModal.openRestore('{{ route('caregiver.patients.restore', $patient->id) }}')"
                                    class="flex-1 sc-btn sc-btn-ghost sc-btn-sm text-center">
                                    <x-lucide-rotate-ccw class="sc-i w-4 h-4" aria-hidden="true" />
                                    <span>Restore Patient</span>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
            @endif

        </div>
    </main>
</x-dashboard-layout>