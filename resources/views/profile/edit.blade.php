<x-dashboard-layout>
    <x-slot:title>My Profile - SilverCare</x-slot:title>

    @php
        $dashboardRoute = $profile->isCaregiver() ? 'caregiver.dashboard' : 'dashboard';
        $profileErrorFields = [
            'name',
            'email',
            'phone_number',
            'relationship',
            'medical_conditions',
            'medications',
            'allergies',
            'emergency_name',
            'emergency_phone',
            'emergency_relationship',
        ];
        $hasProfileValidationErrors = $errors->hasAny($profileErrorFields);

        // Helper for consistent string conversion
        $safeImplode = function ($value) {
            if (is_array($value)) return implode(', ', $value);
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                return (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? implode(', ', $decoded) : $value;
            }
            return '';
        };

        // Initialize variables early to avoid undefined variable errors
        $legacyMedical = $profile->medical_info ?? [];
        $conditionsVal = $safeImplode($profile->medical_conditions) ?: $safeImplode($legacyMedical['conditions'] ?? []);
        $medsVal = $safeImplode($profile->medications) ?: $safeImplode($legacyMedical['medications'] ?? []);
        $allergiesVal = $safeImplode($profile->allergies) ?: $safeImplode($legacyMedical['allergies'] ?? []);

        $legacyEmergency = $profile->emergency_contact ?? [];
        $caregiver = $profile->caregiver?->user ?? null;
        $caregiverProfile = $profile->caregiver ?? null;

        $emergencyName = $profile->emergency_name ?: ($legacyEmergency['name'] ?? null) ?: ($caregiver?->name ?? '');
        $emergencyPhone = $profile->emergency_phone ?: ($legacyEmergency['phone'] ?? null) ?: ($caregiverProfile?->phone_number ?? '');
        $emergencyRelationship = $profile->emergency_relationship ?: ($legacyEmergency['relationship'] ?? null) ?: ($caregiverProfile?->relationship ?? '');
    @endphp

    <x-dashboard-nav
        title="My Profile"
        subtitle="Your personal information"
        role="{{ $profile->isCaregiver() ? 'caregiver' : 'elderly' }}"
        :unread-notifications="$unreadNotifications ?? 0"
    />

    <div class="min-h-screen bg-slate-50 py-8" x-data="{ editMode: {{ $hasProfileValidationErrors ? 'true' : 'false' }}, showLogoutConfirm: false }">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Back Navigation --}}
            <div class="mb-6 flex justify-end">
                <a href="{{ route($dashboardRoute) }}" class="back-nav-pill">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Back to Home
                </a>
            </div>

            {{-- Header Section --}}
            <div class="mb-8 flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <h2 class="text-3xl font-black text-slate-900">My Profile</h2>
                    </div>
                    <p class="text-base text-slate-500 font-medium">View and update your personal information</p>
                </div>

                <div class="flex items-center gap-4 mt-2 md:mt-0">
                    {{-- Success Message --}}
                    @if (session('status') === 'profile-updated')
                        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                            class="flex items-center bg-emerald-500 text-white px-6 py-3 rounded-2xl shadow-lg transition-all">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                            <span class="font-bold">Saved!</span>
                        </div>
                    @endif

                    {{-- Edit Button --}}
                    <button x-show="!editMode" @click="editMode = true" type="button"
                        class="flex items-center gap-2 bg-navy-500 text-white px-6 py-3 rounded-2xl font-bold shadow-glow-brand hover:-translate-y-0.5 transition-all min-h-touch">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        Edit Profile
                    </button>
                </div>
            </div>

            @if($hasProfileValidationErrors)
                <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-rose-800 shadow-sm" role="alert" aria-live="assertive">
                    <div class="flex items-start gap-3">
                        <svg class="mt-0.5 h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v4m0 4h.01M4.93 19h14.14c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.2 16c-.77 1.33.19 3 1.73 3z"></path>
                        </svg>
                        <div>
                            <p class="font-extrabold">Changes could not be saved.</p>
                            <p class="mt-1 text-sm font-semibold text-rose-700">Please fix the highlighted fields below and try again.</p>
                        </div>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PATCH')

                <div class="space-y-6">

                    {{-- CARD 1: Personal Details --}}
                    <div class="bg-white rounded-2xl p-6 md:p-8 shadow-card mb-6">
                        <div>
                            <div class="flex items-center gap-3 mb-8 pb-4 border-b border-slate-100">
                                <h3 class="font-extrabold text-xl text-slate-900">Personal Details</h3>
                            </div>

                            {{-- Profile Photo Section --}}
                            <div class="flex flex-col sm:flex-row items-center gap-6 mb-8 pb-8 border-b border-slate-100">
                                <div class="relative group">
                                    <div class="w-24 h-24 rounded-full overflow-hidden bg-gradient-to-br from-navy-500 to-navy-400 shadow-glow-brand flex items-center justify-center">
                                        @if($profile->profile_photo)
                                            <img id="profile-photo-preview" src="{{ Storage::url($profile->profile_photo) }}" alt="Profile Photo" class="w-full h-full object-cover">
                                        @else
                                            <span id="profile-photo-initial" class="text-white text-3xl font-bold">{{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}</span>
                                        @endif
                                    </div>

                                    <template x-if="editMode">
                                        <label for="photo-upload" class="absolute inset-0 bg-black/50 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
                                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0118.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                        </label>
                                    </template>
                                </div>

                                <div class="flex flex-col gap-2">
                                    <p class="text-base font-bold text-slate-900">Profile Photo</p>

                                    <template x-if="!editMode">
                                        <p class="text-sm text-slate-500">{{ $profile->profile_photo ? 'Photo uploaded' : 'No photo uploaded' }}</p>
                                    </template>

                                    <template x-if="editMode">
                                        <div class="flex flex-col gap-2">
                                            <input type="file" id="photo-upload" name="profile_photo" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden" @change="openCropModal($event)">

                                            <div class="flex items-center gap-2">
                                                <label for="photo-upload" class="flex items-center gap-1.5 bg-navy-500 text-white px-4 py-2 rounded-lg font-semibold text-sm shadow hover:-translate-y-0.5 transition-all cursor-pointer min-h-touch">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                    {{ $profile->profile_photo ? 'Change' : 'Upload' }}
                                                </label>

                                                @if($profile->profile_photo)
                                                {{-- C4 FIX: scConfirm guard before removing profile photo --}}
                                                <button type="button"
                                                    @click="window.scConfirm({ icon: 'warning', elderly: true, title: 'Remove Photo?', text: 'Are you sure you want to remove your profile photo?', confirmButtonText: 'Yes, Remove It', cancelButtonText: 'Keep Photo' }).then(ok => { if(ok) removeProfilePhoto(); })"
                                                    class="flex items-center gap-1.5 bg-rose-500 text-white px-4 py-2 rounded-lg font-semibold text-sm shadow hover:-translate-y-0.5 transition-all min-h-touch">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                    Remove
                                                </button>
                                                @endif
                                            </div>

                                            <p class="text-xs text-slate-400">JPG, PNG, GIF or WebP. Max 5MB. You'll be able to crop your photo before uploading.</p>
                                            <div id="photo-status"></div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- CROP MODAL --}}
                            <div id="profile-photo-crop-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
                                <div class="w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-2xl">
                                    <div class="flex items-center justify-between border-b border-slate-100 p-6">
                                        <h3 class="text-xl font-bold text-slate-900">Crop Your Photo</h3>
                                        <button type="button" onclick="cancelCrop()" class="text-slate-400 hover:text-slate-600">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                    </div>

                                    <div class="p-6">
                                        <div class="mb-6 flex justify-center rounded-lg bg-slate-100 p-4">
                                            <img id="crop-image" alt="Crop preview" class="max-h-96 max-w-full" style="max-width: 100%;">
                                        </div>

                                        <p class="mb-4 text-sm text-slate-500">Click and drag to reposition. Use the handles to resize the crop area. Aim for a square crop for best profile picture results.</p>

                                        <div class="flex justify-end gap-2">
                                            <button type="button" onclick="cancelCrop()" class="rounded-lg bg-slate-100 px-4 py-2 font-semibold text-slate-700 transition-all hover:bg-slate-200">
                                                Cancel
                                            </button>
                                            <button id="crop-upload-button" type="button" onclick="applyCrop()" class="rounded-lg bg-navy-500 px-4 py-2 font-semibold text-white transition-all hover:bg-navy-600">
                                                ✓ Crop & Upload
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-y-8 gap-x-6 md:grid-cols-2 lg:grid-cols-3">
                                {{-- Full Name --}}
                                <div class="md:col-span-2 lg:col-span-1">
                                    <label class="profile-field-label">Full Name</label>
                                    <template x-if="!editMode">
                                        <p class="profile-field-value">{{ $user->name ?: '—' }}</p>
                                    </template>
                                    <template x-if="editMode">
                                        <div>
                                            <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                                                class="profile-field-input @error('name') !border-rose-500 !bg-rose-50 focus:!border-rose-500 @enderror">
                                            @error('name')
                                                <p class="mt-2 text-sm font-bold text-rose-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </template>
                                </div>

                                {{-- Email --}}
                                <div>
                                    <label class="profile-field-label">Email</label>
                                    <p class="profile-field-value">{{ $user->email ?: '—' }}</p>
                                </div>

                                {{-- Phone --}}
                                <div>
                                    <label class="profile-field-label">Phone Number</label>
                                    <template x-if="!editMode">
                                        <p class="profile-field-value">{{ $profile->phone_number ?: '—' }}</p>
                                    </template>
                                    <template x-if="editMode">
                                        <div>
                                            <input type="text" name="phone_number" value="{{ old('phone_number', $profile->phone_number) }}"
                                                class="profile-field-input @error('phone_number') !border-rose-500 !bg-rose-50 focus:!border-rose-500 @enderror">
                                            @error('phone_number')
                                                <p class="mt-2 text-sm font-bold text-rose-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </template>
                                </div>

                                @if($profile->isCaregiver())
                                {{-- Relationship (Caregiver Only) --}}
                                <div>
                                    <label class="profile-field-label">Relationship to Elder</label>
                                    <template x-if="!editMode">
                                        <p class="profile-field-value">{{ $profile->relationship ?: '—' }}</p>
                                    </template>
                                    <template x-if="editMode">
                                        <div>
                                            <input type="text" name="relationship" value="{{ old('relationship', $profile->relationship) }}" placeholder="e.g. Daughter, Son, Professional"
                                                class="profile-field-input @error('relationship') !border-rose-500 !bg-rose-50 focus:!border-rose-500 @enderror">
                                            @error('relationship')
                                                <p class="mt-2 text-sm font-bold text-rose-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </template>
                                </div>
                                @endif

                                @if($profile->isElderly())
                                {{-- Medical Conditions (Elderly Only) --}}
                                <div>
                                    <label class="profile-field-label !text-rose-500">Medical Conditions</label>
                                    <template x-if="!editMode">
                                        <p class="profile-field-value">{{ $conditionsVal ?: 'None specified' }}</p>
                                    </template>
                                    <template x-if="editMode">
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                <span class="text-rose-300">📋</span>
                                            </div>
                                            <input type="text" name="medical_conditions" value="{{ old('medical_conditions', $conditionsVal) }}" placeholder="e.g. Diabetes, Hypertension"
                                                class="profile-field-input pl-12 !border-rose-100 !bg-rose-50/30 placeholder-rose-300 focus:!border-rose-400 @error('medical_conditions') !border-rose-500 !bg-rose-50 focus:!border-rose-500 @enderror">
                                            @error('medical_conditions')
                                                <p class="mt-2 text-sm font-bold text-rose-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </template>
                                </div>

                                {{-- Medications --}}
                                <div>
                                    <label class="profile-field-label !text-navy-400">Medications</label>
                                    <template x-if="!editMode">
                                        <div class="px-4 py-3">
                                            @if($medsVal)
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach(explode(', ', $medsVal) as $med)
                                                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold bg-navy-50 text-navy-700 border border-navy-100">
                                                            💊 {{ trim($med) }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <p class="text-lg font-bold text-slate-400">None specified</p>
                                            @endif
                                        </div>
                                    </template>
                                    <template x-if="editMode">
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                <span class="text-navy-300">💊</span>
                                            </div>
                                            <input type="text" name="medications" value="{{ old('medications', $medsVal) }}" placeholder="e.g. Metformin, Aspirin"
                                                class="profile-field-input pl-12 !border-navy-100 !bg-navy-50/30 placeholder-navy-200 focus:!border-navy-400 @error('medications') !border-rose-500 !bg-rose-50 focus:!border-rose-500 @enderror">
                                            @error('medications')
                                                <p class="mt-2 text-sm font-bold text-rose-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </template>
                                </div>

                                {{-- Allergies --}}
                                <div>
                                    <label class="profile-field-label !text-amber-500">Allergies</label>
                                    <template x-if="!editMode">
                                        <div class="px-4 py-3">
                                            @if($allergiesVal)
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach(explode(', ', $allergiesVal) as $allergy)
                                                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold bg-amber-50 text-amber-700 border border-amber-100">
                                                            ⚠️ {{ trim($allergy) }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <p class="text-lg font-bold text-slate-400">None specified</p>
                                            @endif
                                        </div>
                                    </template>
                                    <template x-if="editMode">
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                <span class="text-amber-300">⚠️</span>
                                            </div>
                                            <input type="text" name="allergies" value="{{ old('allergies', $allergiesVal) }}" placeholder="e.g. Peanuts, Penicillin"
                                                class="profile-field-input pl-12 !border-amber-100 !bg-amber-50/30 placeholder-amber-300 focus:!border-amber-400 @error('allergies') !border-rose-500 !bg-rose-50 focus:!border-rose-500 @enderror">
                                            @error('allergies')
                                                <p class="mt-2 text-sm font-bold text-rose-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </template>
                                </div>
                                @endif

                    {{-- CARD 3: Emergency Contact (Elderly Only) --}}
                    @if($profile->isElderly())
                    <div class="bg-white rounded-2xl p-6 md:p-8 shadow-card mb-6">
                        <div>
                            <div class="flex items-center gap-3 mb-8 pb-4 border-b border-slate-100">
                                <h3 class="font-extrabold text-xl text-slate-900">Emergency Contact</h3>
                            </div>

                            <div class="grid grid-cols-1 gap-y-8 gap-x-6 md:grid-cols-3">
                                {{-- Contact Name --}}
                                <div>
                                    <label class="profile-field-label">Contact Name</label>
                                    <template x-if="!editMode">
                                        <p class="profile-field-value">{{ $emergencyName ?: '—' }}</p>
                                    </template>
                                    <template x-if="editMode">
                                        <div>
                                            <input type="text" name="emergency_name" value="{{ old('emergency_name', $emergencyName) }}" placeholder="Contact Name"
                                                class="profile-field-input @error('emergency_name') !border-rose-500 !bg-rose-50 focus:!border-rose-500 @enderror">
                                            @error('emergency_name')
                                                <p class="mt-2 text-sm font-bold text-rose-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </template>
                                </div>

                                {{-- Phone Number --}}
                                <div>
                                    <label class="profile-field-label">Phone Number</label>
                                    <template x-if="!editMode">
                                        <p class="profile-field-value">{{ $emergencyPhone ?: '—' }}</p>
                                    </template>
                                    <template x-if="editMode">
                                        <div>
                                            <input type="text" name="emergency_phone" value="{{ old('emergency_phone', $emergencyPhone) }}" placeholder="Phone Number"
                                                class="profile-field-input @error('emergency_phone') !border-rose-500 !bg-rose-50 focus:!border-rose-500 @enderror">
                                            @error('emergency_phone')
                                                <p class="mt-2 text-sm font-bold text-rose-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </template>
                                </div>

                                {{-- Relationship --}}
                                <div>
                                    <label class="profile-field-label">Relationship</label>
                                    <template x-if="!editMode">
                                        <p class="profile-field-value">{{ $emergencyRelationship ?: '—' }}</p>
                                    </template>
                                    <template x-if="editMode">
                                        <div>
                                            <input type="text" name="emergency_relationship" value="{{ old('emergency_relationship', $emergencyRelationship) }}" placeholder="Relationship"
                                                class="profile-field-input @error('emergency_relationship') !border-rose-500 !bg-rose-50 focus:!border-rose-500 @enderror">
                                            @error('emergency_relationship')
                                                <p class="mt-2 text-sm font-bold text-rose-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- ACTION BUTTONS (only show in edit mode) --}}
                    <div x-show="editMode" class="flex justify-end gap-4">
                        <button type="button" @click="editMode = false"
                            class="px-8 py-4 rounded-2xl font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-all min-h-touch">
                            Cancel
                        </button>

                        <button type="submit"
                            class="group relative overflow-hidden rounded-2xl bg-gradient-to-r from-navy-500 to-navy-600 py-4 px-8 text-lg font-bold text-white shadow-glow-brand transition-all hover:-translate-y-1 hover:shadow-xl active:scale-95 min-h-touch">
                            <div class="relative z-10 flex items-center justify-center gap-2">
                                <span>SAVE CHANGES</span>
                                <svg class="h-5 w-5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <div class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/20 to-transparent transition-transform duration-1000 group-hover:translate-x-full"></div>
                        </button>
                    </div>

                </div>
            </form>

            {{-- CARD 4: Care Connection (Elderly Only) --}}
            @if($profile->isElderly())
                <div class="bg-white rounded-2xl p-6 md:p-8 shadow-card mb-6"
                     x-data="{
                        pin: @js(session('prefill_link_code', '')),
                        step: 'enter',
                        loading: false,
                        error: '',
                        caregiver: null,
                        init() {
                            if (this.pin && this.pin.length === 6) {
                                this.$nextTick(() => this.validatePin());
                            }
                        },
                        async validatePin() {
                            if (this.pin.length !== 6) {
                                window.scAlert({ icon: 'warning', title: 'Invalid PIN', text: 'Please enter all 6 digits.', elderly: true });
                                return;
                            }
                            this.loading = true;
                            this.error = '';
                            try {
                                const res = await fetch('{{ route('elderly.validate-link-code') }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                        'Accept': 'application/json',
                                    },
                                    body: JSON.stringify({ code: this.pin }),
                                });
                                const data = await res.json();
                                if (data.valid) {
                                    this.caregiver = data;
                                    this.step = 'confirm';
                                } else {
                                    window.scAlert({ icon: 'error', title: 'Invalid PIN', text: data.message || 'The PIN you entered is invalid or expired.', elderly: true });
                                }
                            } catch (e) {
                                window.scAlert({ icon: 'error', title: 'Error', text: 'Something went wrong. Please check your connection and try again.', elderly: true });
                            } finally {
                                this.loading = false;
                            }
                        },
                        async confirmLink(forceSwitch = false) {
                            // C1 + C7 FIX: Backend now always returns JSON.
                            // If the user is already linked to another caregiver,
                            // a 409 'switch_required' response triggers a SweetAlert2
                            // confirmation before re-sending with force_switch=true.
                            this.loading = true;
                            try {
                                const res = await fetch('{{ route('elderly.confirm-link') }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                        'Accept': 'application/json',
                                    },
                                    body: JSON.stringify({ code: this.caregiver.code, force_switch: forceSwitch }),
                                });
                                const data = await res.json();

                                // C7: Server signals an existing link must be confirmed
                                if (res.status === 409 && data.switch_required) {
                                    this.loading = false;
                                    const confirmed = await window.scConfirm({
                                        icon: 'warning',
                                        elderly: true,
                                        title: 'Switch Caregiver?',
                                        html: `<p class="text-lg text-slate-600 mt-2">You are currently connected to <strong>${data.existing_name}</strong>.<br>Do you want to switch to <strong>${data.new_name}</strong>?<br><br>Your current caregiver will lose access.</p>`,
                                        confirmButtonText: 'Yes, Switch Caregiver',
                                        cancelButtonText: 'Keep Current Caregiver',
                                    });
                                    if (confirmed) {
                                        await this.confirmLink(true);
                                    }
                                    return;
                                }

                                if (!res.ok || !data.success) {
                                    throw new Error(data.message || 'Failed to connect. Please try again.');
                                }

                                // Success
                                await window.scAlert({
                                    icon: 'success',
                                    elderly: true,
                                    title: 'Connected! 🎉',
                                    text: data.message || 'You have successfully linked with your caregiver.',
                                    timer: 2000,
                                    timerProgressBar: true,
                                    showConfirmButton: false,
                                    allowOutsideClick: false,
                                });
                                window.location.reload();
                            } catch (e) {
                                window.scAlert({ icon: 'error', elderly: true, title: 'Connection Failed', text: e.message || 'Please try again.' });
                            } finally {
                                this.loading = false;
                            }
                        },
                        reset() { this.step = 'enter'; this.pin = ''; this.error = ''; this.caregiver = null; }
                    }">
                    <div class="flex items-center gap-3 mb-8 pb-4 border-b border-slate-100">
                        <h3 class="font-extrabold text-xl text-slate-900">Care Connection</h3>
                    </div>

                    @if($profile->caregiver)
                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-4">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                <div>
                                    <p class="text-base font-extrabold text-slate-900">Connected to {{ $profile->caregiver->user?->name ?? $profile->caregiver->username ?? 'Your Caregiver' }}</p>
                                    <p class="text-sm text-slate-500 mt-1">Manage unlinking here with password confirmation for account safety.</p>
                                </div>

                                <div x-data="{ showUnlink: false }" class="w-full md:w-auto">
                                    <button x-show="!showUnlink"
                                            @click="showUnlink = true"
                                            class="w-full md:w-auto rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-bold text-rose-700 hover:bg-rose-100 transition-colors min-h-touch">
                                        Unlink Caregiver
                                    </button>

                                    <div x-show="showUnlink" x-cloak class="rounded-xl border border-rose-200 bg-white p-3">
                                        <p class="text-sm font-semibold text-slate-600 mb-2">Confirm unlink by entering your password.</p>
                                        {{-- C3 FIX: data-confirm pipes this destructive form through scConfirm SweetAlert2 --}}
                                        <form
                                            method="POST"
                                            action="{{ route('elderly.unlink-caregiver') }}"
                                            class="space-y-2"
                                            data-confirm="Are you sure you want to unlink your caregiver?"
                                            data-confirm-title="Unlink Caregiver?"
                                            data-confirm-icon="warning"
                                            data-confirm-confirm-text="Yes, Unlink"
                                            data-confirm-cancel-text="Keep My Caregiver"
                                            data-confirm-elderly="true"
                                        >
                                            @csrf
                                            <input type="password"
                                                   name="password"
                                                   required
                                                   autocomplete="current-password"
                                                   placeholder="Current password"
                                                   class="w-full rounded-lg border border-slate-200 px-3 py-2 text-base focus:border-rose-400 focus:ring-2 focus:ring-rose-200 min-h-touch">
                                            @error('password')
                                                <p class="text-sm font-semibold text-rose-600">{{ $message }}</p>
                                            @enderror
                                            <div class="flex items-center gap-2">
                                                <button type="submit" class="rounded-lg bg-rose-600 px-3 py-2 text-sm font-bold text-white hover:bg-rose-700 min-h-touch">Confirm Unlink</button>
                                                <button type="button" @click="showUnlink = false" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-bold text-slate-600 min-h-touch">Cancel</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="rounded-2xl border border-navy-200 bg-navy-50/50 p-4">
                            <p class="text-base font-extrabold text-slate-900">Link a caregiver</p>
                            <p class="text-sm text-slate-500 mt-1">Enter your caregiver's 6-digit PIN and confirm the profile before connecting.</p>

                            <div class="mt-3 flex flex-col sm:flex-row sm:items-center gap-2">
                                <input type="text"
                                       x-model="pin"
                                       inputmode="numeric"
                                       maxlength="6"
                                       pattern="[0-9]{6}"
                                       placeholder="000000"
                                       @keyup.enter="validatePin()"
                                       class="w-36 rounded-xl border border-navy-200 bg-white px-3 py-2 text-center text-lg font-black tracking-[0.2em] text-slate-900 focus:border-navy-500 focus:ring-2 focus:ring-navy-200 min-h-touch">
                                <button @click="validatePin()"
                                        :disabled="loading || pin.length !== 6"
                                        class="rounded-xl bg-navy-500 px-4 py-2.5 text-base font-bold text-white hover:bg-navy-600 transition-colors disabled:opacity-50 min-h-touch">
                                    <span x-show="!loading">Verify PIN</span>
                                    <span x-show="loading">Checking...</span>
                                </button>
                            </div>

                            <p x-show="error" x-text="error" class="mt-2 text-sm font-semibold text-rose-600"></p>

                            <div x-show="step === 'confirm'" x-cloak class="mt-3 rounded-xl border border-navy-200 bg-white p-4">
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-400 mb-2">Confirm connection with:</p>
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-11 h-11 rounded-full bg-navy-100 flex items-center justify-center text-navy-600 font-black text-lg">
                                        <span x-text="caregiver?.caregiver_name?.[0] ?? '?'" aria-hidden="true"></span>
                                    </div>
                                    <div>
                                        <p class="font-extrabold text-slate-900" x-text="caregiver?.caregiver_name ?? ''"></p>
                                        <p class="text-sm text-slate-500" x-text="caregiver?.caregiver_role ?? 'Caregiver'"></p>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button type="button" @click="confirmLink()" :disabled="loading" class="flex-1 w-full rounded-xl bg-navy-500 px-4 py-2.5 text-base font-bold text-white hover:bg-navy-600 transition-colors min-h-touch disabled:opacity-50">
                                        <span x-show="!loading">✓ Link Caregiver</span>
                                        <span x-show="loading">Linking...</span>
                                    </button>
                                    <button type="button" @click="reset()" class="rounded-xl border border-slate-200 px-4 py-2.5 text-base font-bold text-slate-600 min-h-touch">Cancel</button>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- CARD 5: Account Session --}}
            <div class="bg-white rounded-2xl p-6 md:p-8 shadow-card mb-6">
                <div class="flex items-center justify-between gap-8 flex-col sm:flex-row">
                    <div>
                        <h3 class="font-extrabold text-xl text-slate-900">Account Session</h3>
                        <p class="text-base text-slate-500 font-medium">Sign out safely from your profile page.</p>
                    </div>
                    <button
                        type="button"
                        @click="showLogoutConfirm = true"
                        class="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-5 py-3 text-base font-bold text-rose-700 hover:bg-rose-100 transition-colors min-h-touch"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        Log Out
                    </button>
                </div>
            </div>

            {{-- Logout Confirmation Modal --}}
            <div
                x-show="showLogoutConfirm"
                x-cloak
                x-transition.opacity
                class="fixed inset-0 z-[80] flex items-center justify-center px-4"
                role="dialog"
                aria-modal="true"
                aria-labelledby="logout-confirm-title"
            >
                <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="showLogoutConfirm = false"></div>

                <div class="relative w-full max-w-md rounded-2xl border border-white/70 bg-white p-6 shadow-elevated">
                    <h4 id="logout-confirm-title" class="text-xl font-black text-slate-900">Confirm logout</h4>
                    <p class="mt-2 text-base text-slate-600 font-medium">Are you sure you want to log out now?</p>

                    <div class="mt-5 flex items-center justify-end gap-2">
                        <button
                            type="button"
                            @click="showLogoutConfirm = false"
                            class="rounded-xl border border-slate-200 px-4 py-2.5 text-base font-bold text-slate-600 hover:bg-slate-50 min-h-touch"
                        >
                            Cancel
                        </button>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button
                                type="submit"
                                class="rounded-xl bg-rose-600 px-4 py-2.5 text-base font-bold text-white hover:bg-rose-700 min-h-touch"
                            >
                                Yes, log out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

</x-dashboard-layout>
