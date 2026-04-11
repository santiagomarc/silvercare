<x-dashboard-layout>
    <x-slot:title>My Profile - SilverCare</x-slot:title>

    <x-dashboard-nav
        title="My Profile"
        subtitle="Your personal information"
        role="{{ $profile->isCaregiver() ? 'caregiver' : 'elderly' }}"
        :unread-notifications="$unreadNotifications ?? 0"
    />

    <div class="min-h-screen bg-slate-50 py-8" x-data="{ editMode: false, showLogoutConfirm: false }">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Back Navigation --}}
            <div class="mb-6">
                <a href="{{ route('dashboard') }}" class="back-nav-pill">
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
                            <div class="flex flex-col sm:flex-row items-center gap-6 mb-8 pb-8 border-b border-slate-100" x-data="{ photoUploading: false, photoError: null }">
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
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
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
                                            <input type="file" id="photo-upload" name="profile_photo" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden" onchange="uploadProfilePhoto(this)">

                                            <div class="flex items-center gap-2">
                                                <label for="photo-upload" class="flex items-center gap-1.5 bg-navy-500 text-white px-4 py-2 rounded-lg font-semibold text-sm shadow hover:-translate-y-0.5 transition-all cursor-pointer min-h-touch">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                    {{ $profile->profile_photo ? 'Change' : 'Upload' }}
                                                </label>

                                                @if($profile->profile_photo)
                                                <button type="button" onclick="removeProfilePhoto()" class="flex items-center gap-1.5 bg-rose-500 text-white px-4 py-2 rounded-lg font-semibold text-sm shadow hover:-translate-y-0.5 transition-all min-h-touch">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                    Remove
                                                </button>
                                                @endif
                                            </div>

                                            <p class="text-xs text-slate-400">JPG, PNG, GIF or WebP. Max 5MB.</p>
                                            <div id="photo-status"></div>
                                        </div>
                                    </template>
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
                                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="profile-field-input">
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
                                        <input type="text" name="phone_number" value="{{ old('phone_number', $profile->phone_number) }}" class="profile-field-input">
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
                                        <div class="relative">
                                            <select name="relationship" class="profile-field-input appearance-none">
                                                <option value="">Select...</option>
                                                <option value="Son" {{ (old('relationship', $profile->relationship) == 'Son') ? 'selected' : '' }}>Son</option>
                                                <option value="Daughter" {{ (old('relationship', $profile->relationship) == 'Daughter') ? 'selected' : '' }}>Daughter</option>
                                                <option value="Spouse" {{ (old('relationship', $profile->relationship) == 'Spouse') ? 'selected' : '' }}>Spouse</option>
                                                <option value="Sibling" {{ (old('relationship', $profile->relationship) == 'Sibling') ? 'selected' : '' }}>Sibling</option>
                                                <option value="Friend" {{ (old('relationship', $profile->relationship) == 'Friend') ? 'selected' : '' }}>Friend</option>
                                                <option value="Professional Caregiver" {{ (old('relationship', $profile->relationship) == 'Professional Caregiver') ? 'selected' : '' }}>Professional Caregiver</option>
                                                <option value="Other" {{ (old('relationship', $profile->relationship) == 'Other') ? 'selected' : '' }}>Other</option>
                                            </select>
                                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-500">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                @endif

                                @if($profile->isCaregiver())
                                {{-- Age (Caregiver) --}}
                                <div>
                                    <label class="profile-field-label">Age</label>
                                    <template x-if="!editMode">
                                        <p class="profile-field-value">{{ $profile->age ?: '—' }}</p>
                                    </template>
                                    <template x-if="editMode">
                                        <input type="number" name="age" value="{{ old('age', $profile->age) }}" class="profile-field-input">
                                    </template>
                                </div>
                                @endif

                                @if($profile->isElderly())
                                {{-- Age (Elderly) --}}
                                <div>
                                    <label class="profile-field-label">Age</label>
                                    <template x-if="!editMode">
                                        <p class="profile-field-value">{{ $profile->age ?: '—' }}</p>
                                    </template>
                                    <template x-if="editMode">
                                        <input type="number" name="age" value="{{ old('age', $profile->age) }}" class="profile-field-input">
                                    </template>
                                </div>
                                <div>
                                    <label class="profile-field-label">Sex</label>
                                    <template x-if="!editMode">
                                        <p class="profile-field-value">{{ $profile->sex ?: '—' }}</p>
                                    </template>
                                    <template x-if="editMode">
                                        <div class="relative">
                                            <select name="sex" class="profile-field-input appearance-none">
                                                <option value="">Select...</option>
                                                <option value="Male" {{ (old('sex', $profile->sex) == 'Male') ? 'selected' : '' }}>Male</option>
                                                <option value="Female" {{ (old('sex', $profile->sex) == 'Female') ? 'selected' : '' }}>Female</option>
                                            </select>
                                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-500">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                {{-- Height --}}
                                <div>
                                    <label class="profile-field-label">Height (cm)</label>
                                    <template x-if="!editMode">
                                        <p class="profile-field-value">{{ $profile->height ?: '—' }}</p>
                                    </template>
                                    <template x-if="editMode">
                                        <input type="number" step="0.01" name="height" value="{{ old('height', $profile->height) }}" class="profile-field-input">
                                    </template>
                                </div>

                                {{-- Weight --}}
                                <div>
                                    <label class="profile-field-label">Weight (kg)</label>
                                    <template x-if="!editMode">
                                        <p class="profile-field-value">{{ $profile->weight ?: '—' }}</p>
                                    </template>
                                    <template x-if="editMode">
                                        <input type="number" step="0.01" name="weight" value="{{ old('weight', $profile->weight) }}" class="profile-field-input">
                                    </template>
                                </div>
                                @endif

                                {{-- Address --}}
                                <div class="md:col-span-2 lg:col-span-3">
                                    <label class="profile-field-label">Address</label>
                                    <template x-if="!editMode">
                                        <p class="profile-field-value">{{ $profile->address ?: '—' }}</p>
                                    </template>
                                    <template x-if="editMode">
                                        <textarea name="address" rows="2" class="profile-field-input resize-none">{{ old('address', $profile->address) }}</textarea>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- CARD 2: Medical Information (Elderly Only) --}}
                    @if($profile->isElderly())
                    @php
                        // Helper to safely implode arrays or JSON strings
                        function safeImplode($value) {
                            if (is_array($value)) return implode(', ', $value);
                            if (is_string($value)) {
                                $decoded = json_decode($value, true);
                                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) return implode(', ', $decoded);
                                return $value;
                            }
                            return '';
                        }

                        // Try individual columns first, fallback to legacy medical_info JSON
                        $legacyMedical = $profile->medical_info ?? [];

                        $conditionsVal = safeImplode($profile->medical_conditions)
                            ?: safeImplode($legacyMedical['conditions'] ?? []);
                        $medsVal = safeImplode($profile->medications)
                            ?: safeImplode($legacyMedical['medications'] ?? []);
                        $allergiesVal = safeImplode($profile->allergies)
                            ?: safeImplode($legacyMedical['allergies'] ?? []);

                        // Emergency Contact
                        $legacyEmergency = $profile->emergency_contact ?? [];
                        $caregiver = $profile->caregiver?->user ?? null;
                        $caregiverProfile = $profile->caregiver ?? null;

                        $emergencyName = $profile->emergency_name
                            ?: ($legacyEmergency['name'] ?? null)
                            ?: ($caregiver?->name ?? '');
                        $emergencyPhone = $profile->emergency_phone
                            ?: ($legacyEmergency['phone'] ?? null)
                            ?: ($caregiverProfile?->phone_number ?? '');
                        $emergencyRelationship = $profile->emergency_relationship
                            ?: ($legacyEmergency['relationship'] ?? null)
                            ?: ($caregiverProfile?->relationship ?? '');
                    @endphp

                    <div class="bg-white rounded-2xl p-6 md:p-8 shadow-card mb-6">
                        <div>
                            <div class="flex items-center gap-3 mb-8 pb-4 border-b border-slate-100">
                                <h3 class="font-extrabold text-xl text-slate-900">Medical Information</h3>
                            </div>

                            <div class="grid grid-cols-1 gap-y-8 gap-x-6 md:grid-cols-3">
                                {{-- Medical Conditions --}}
                                <div>
                                    <label class="profile-field-label !text-rose-400">Medical Conditions</label>
                                    <template x-if="!editMode">
                                        <div class="px-4 py-3">
                                            @if($conditionsVal)
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach(explode(', ', $conditionsVal) as $condition)
                                                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold bg-rose-50 text-rose-700 border border-rose-100">
                                                            ❤️ {{ trim($condition) }}
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
                                                <span class="text-rose-300">❤️</span>
                                            </div>
                                            <input type="text" name="medical_conditions" value="{{ old('medical_conditions', $conditionsVal) }}" placeholder="e.g. Diabetes, Hypertension"
                                                class="profile-field-input pl-12 !border-rose-100 !bg-rose-50/30 placeholder-rose-300 focus:!border-rose-400">
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
                                                class="profile-field-input pl-12 !border-navy-100 !bg-navy-50/30 placeholder-navy-200 focus:!border-navy-400">
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
                                                class="profile-field-input pl-12 !border-amber-100 !bg-amber-50/30 placeholder-amber-300 focus:!border-amber-400">
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
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
                                        <input type="text" name="emergency_name" value="{{ old('emergency_name', $emergencyName) }}" placeholder="Contact Name"
                                            class="profile-field-input">
                                    </template>
                                </div>

                                {{-- Phone Number --}}
                                <div>
                                    <label class="profile-field-label">Phone Number</label>
                                    <template x-if="!editMode">
                                        <p class="profile-field-value">{{ $emergencyPhone ?: '—' }}</p>
                                    </template>
                                    <template x-if="editMode">
                                        <input type="text" name="emergency_phone" value="{{ old('emergency_phone', $emergencyPhone) }}" placeholder="Phone Number"
                                            class="profile-field-input">
                                    </template>
                                </div>

                                {{-- Relationship --}}
                                <div>
                                    <label class="profile-field-label">Relationship</label>
                                    <template x-if="!editMode">
                                        <p class="profile-field-value">{{ $emergencyRelationship ?: '—' }}</p>
                                    </template>
                                    <template x-if="editMode">
                                        <input type="text" name="emergency_relationship" value="{{ old('emergency_relationship', $emergencyRelationship) }}" placeholder="Relationship"
                                            class="profile-field-input">
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
                            if (this.pin.length !== 6) { this.error = 'Please enter all 6 digits.'; return; }
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
                                    this.error = data.message || 'Invalid PIN.';
                                }
                            } catch (e) {
                                this.error = 'Something went wrong. Please try again.';
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
                                        <form method="POST" action="{{ route('elderly.unlink-caregiver') }}" class="space-y-2">
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
                                    <form method="POST" action="{{ route('elderly.confirm-link') }}" class="flex-1">
                                        @csrf
                                        <input type="hidden" name="code" :value="caregiver?.code">
                                        <button type="submit" class="w-full rounded-xl bg-navy-500 px-4 py-2.5 text-base font-bold text-white hover:bg-navy-600 transition-colors min-h-touch">✓ Link Caregiver</button>
                                    </form>
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

    <script>
        function uploadProfilePhoto(input) {
            if (!input.files || !input.files[0]) return;

            const file = input.files[0];
            const statusDiv = document.getElementById('photo-status');

            // Validate file type
            const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!validTypes.includes(file.type)) {
                statusDiv.innerHTML = '<span class="text-rose-500 text-sm">Please select a valid image (JPG, PNG, GIF, WebP)</span>';
                return;
            }

            // Validate file size (5MB max)
            if (file.size > 5 * 1024 * 1024) {
                statusDiv.innerHTML = '<span class="text-rose-500 text-sm">Image must be less than 5MB</span>';
                return;
            }

            // Show uploading status
            statusDiv.innerHTML = '<span class="text-navy-500 text-sm flex items-center gap-1"><svg class="animate-spin h-3 w-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Uploading...</span>';

            const formData = new FormData();
            formData.append('profile_photo', file);

            fetch('{{ route("profile.photo.upload") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: formData
            })
            .then(response => {
                if (response.ok) {
                    statusDiv.innerHTML = '<span class="text-emerald-600 text-sm flex items-center gap-1"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Photo updated!</span>';
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    statusDiv.innerHTML = '<span class="text-rose-500 text-sm">Failed to upload. Please try again.</span>';
                }
            })
            .catch(() => {
                statusDiv.innerHTML = '<span class="text-rose-500 text-sm">An error occurred. Please try again.</span>';
            });
        }

        function removeProfilePhoto() {
            if (!confirm('Are you sure you want to remove your profile photo?')) return;

            const statusDiv = document.getElementById('photo-status');
            statusDiv.innerHTML = '<span class="text-navy-500 text-sm">Removing...</span>';

            fetch('{{ route("profile.photo.remove") }}', {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                }
            })
            .then(response => {
                if (response.ok) {
                    statusDiv.innerHTML = '<span class="text-emerald-600 text-sm">Photo removed!</span>';
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    statusDiv.innerHTML = '<span class="text-rose-500 text-sm">Failed to remove. Please try again.</span>';
                }
            })
            .catch(() => {
                statusDiv.innerHTML = '<span class="text-rose-500 text-sm">An error occurred. Please try again.</span>';
            });
        }
    </script>
</x-dashboard-layout>