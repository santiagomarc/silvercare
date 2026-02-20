<x-app-layout>
    <head>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
        <style>
            body { font-family: 'Montserrat', sans-serif; }
        </style>
    </head>

    <div class="min-h-screen bg-[#EBEBEB] py-12 font-sans" x-data="{ editMode: false }">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- HEADER SECTION -->
            <div class="mb-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-[#000080] rounded-2xl flex items-center justify-center shadow-lg shadow-blue-900/20">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    </div>
                    <div>
                        <h2 class="text-3xl font-[900] text-gray-900 tracking-tight">MY PROFILE</h2>
                        <p class="text-gray-500 font-medium">Your personal information</p>
                    </div>
                </div>
                
                <div class="flex items-center gap-4">
                    <!-- Success Message -->
                    @if (session('status') === 'profile-updated')
                        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" 
                            class="flex items-center bg-green-500 text-white px-6 py-3 rounded-2xl shadow-lg shadow-green-200 transition-all">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                            <span class="font-[700]">Saved!</span>
                        </div>
                    @endif

                    <!-- Edit Button (only show when NOT in edit mode) -->
                    <button x-show="!editMode" @click="editMode = true" type="button"
                        class="flex items-center gap-2 bg-[#000080] text-white px-6 py-3 rounded-2xl font-[700] shadow-lg shadow-blue-900/20 hover:-translate-y-0.5 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        Edit Profile
                    </button>
                </div>
            </div>

            <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PATCH')

                <div class="space-y-8">
                    
                    <!-- CARD 1: Personal Details (includes contact & address) -->
                    <div class="relative overflow-hidden bg-white rounded-[24px] p-8 shadow-sm border border-gray-100">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-blue-50 rounded-bl-[100px] -mr-10 -mt-10"></div>
                        
                        <div class="relative z-10">
                            <div class="flex items-center gap-3 mb-8">
                                <span class="text-2xl">👤</span>
                                <h3 class="font-[800] text-xl text-gray-900">Personal Details</h3>
                            </div>

                            <!-- Profile Photo Section -->
                            <div class="flex flex-col sm:flex-row items-center gap-6 mb-8 pb-8 border-b border-gray-100" x-data="{ photoUploading: false, photoError: null }">
                                <!-- Photo Preview -->
                                <div class="relative group">
                                    <div class="w-24 h-24 rounded-full overflow-hidden bg-gradient-to-br from-[#000080] to-[#4040a0] shadow-lg shadow-blue-900/20 flex items-center justify-center">
                                        @if($profile->profile_photo)
                                            <img id="profile-photo-preview" src="{{ Storage::url($profile->profile_photo) }}" alt="Profile Photo" class="w-full h-full object-cover">
                                        @else
                                            <span id="profile-photo-initial" class="text-white text-3xl font-bold">{{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}</span>
                                        @endif
                                    </div>
                                    
                                    <!-- Camera overlay on hover (only in edit mode) -->
                                    <template x-if="editMode">
                                        <label for="photo-upload" class="absolute inset-0 bg-black/50 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
                                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                        </label>
                                    </template>
                                </div>
                                
                                <!-- Photo Info & Controls -->
                                <div class="flex flex-col gap-2">
                                    <p class="text-sm font-[700] text-gray-900">Profile Photo</p>
                                    
                                    <!-- View mode: just show status -->
                                    <template x-if="!editMode">
                                        <p class="text-xs text-gray-500">{{ $profile->profile_photo ? 'Photo uploaded' : 'No photo uploaded' }}</p>
                                    </template>
                                    
                                    <!-- Edit mode: show upload controls -->
                                    <template x-if="editMode">
                                        <div class="flex flex-col gap-2">
                                            <input type="file" id="photo-upload" name="profile_photo" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden" onchange="uploadProfilePhoto(this)">
                                            
                                            <div class="flex items-center gap-2">
                                                <label for="photo-upload" class="flex items-center gap-1.5 bg-[#000080] text-white px-4 py-2 rounded-lg font-[600] text-sm shadow hover:-translate-y-0.5 transition-all cursor-pointer">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                    {{ $profile->profile_photo ? 'Change' : 'Upload' }}
                                                </label>
                                                
                                                @if($profile->profile_photo)
                                                <button type="button" onclick="removeProfilePhoto()" class="flex items-center gap-1.5 bg-red-500 text-white px-4 py-2 rounded-lg font-[600] text-sm shadow hover:-translate-y-0.5 transition-all">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                    Remove
                                                </button>
                                                @endif
                                            </div>
                                            
                                            <p class="text-xs text-gray-400">JPG, PNG, GIF or WebP. Max 5MB.</p>
                                            <div id="photo-status"></div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                                <!-- Full Name -->
                                <div class="md:col-span-2 lg:col-span-1">
                                    <label class="mb-2 block text-xs font-[800] uppercase tracking-wider text-gray-400">Full Name</label>
                                    <template x-if="!editMode">
                                        <p class="px-5 py-4 font-[700] text-gray-900">{{ $user->name ?: '—' }}</p>
                                    </template>
                                    <template x-if="editMode">
                                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required 
                                            class="w-full rounded-xl border-2 border-gray-100 bg-gray-50 px-5 py-4 font-[700] text-gray-900 transition-all focus:border-blue-500 focus:bg-white focus:ring-0 outline-none">
                                    </template>
                                </div>

                                <!-- Email -->
                                <div>
                                    <label class="mb-2 block text-xs font-[800] uppercase tracking-wider text-gray-400">Email</label>
                                    <p class="px-5 py-4 font-[700] text-gray-900">{{ $user->email ?: '—' }}</p>
                                </div>

                                <!-- Phone -->
                                <div>
                                    <label class="mb-2 block text-xs font-[800] uppercase tracking-wider text-gray-400">Phone Number</label>
                                    <template x-if="!editMode">
                                        <p class="px-5 py-4 font-[700] text-gray-900">{{ $profile->phone_number ?: '—' }}</p>
                                    </template>
                                    <template x-if="editMode">
                                        <input type="text" name="phone_number" value="{{ old('phone_number', $profile->phone_number) }}" 
                                            class="w-full rounded-xl border-2 border-gray-100 bg-gray-50 px-5 py-4 font-[700] text-gray-900 transition-all focus:border-blue-500 focus:bg-white focus:ring-0 outline-none">
                                    </template>
                                </div>

                                @if($profile->isCaregiver())
                                <!-- Relationship (Caregiver Only) -->
                                <div>
                                    <label class="mb-2 block text-xs font-[800] uppercase tracking-wider text-gray-400">Relationship to Elder</label>
                                    <template x-if="!editMode">
                                        <p class="px-5 py-4 font-[700] text-gray-900">{{ $profile->relationship ?: '—' }}</p>
                                    </template>
                                    <template x-if="editMode">
                                        <div class="relative">
                                            <select name="relationship" class="w-full appearance-none rounded-xl border-2 border-gray-100 bg-gray-50 px-5 py-4 font-[700] text-gray-900 transition-all focus:border-blue-500 focus:bg-white focus:ring-0 outline-none">
                                                <option value="">Select...</option>
                                                <option value="Son" {{ (old('relationship', $profile->relationship) == 'Son') ? 'selected' : '' }}>Son</option>
                                                <option value="Daughter" {{ (old('relationship', $profile->relationship) == 'Daughter') ? 'selected' : '' }}>Daughter</option>
                                                <option value="Spouse" {{ (old('relationship', $profile->relationship) == 'Spouse') ? 'selected' : '' }}>Spouse</option>
                                                <option value="Sibling" {{ (old('relationship', $profile->relationship) == 'Sibling') ? 'selected' : '' }}>Sibling</option>
                                                <option value="Friend" {{ (old('relationship', $profile->relationship) == 'Friend') ? 'selected' : '' }}>Friend</option>
                                                <option value="Professional Caregiver" {{ (old('relationship', $profile->relationship) == 'Professional Caregiver') ? 'selected' : '' }}>Professional Caregiver</option>
                                                <option value="Other" {{ (old('relationship', $profile->relationship) == 'Other') ? 'selected' : '' }}>Other</option>
                                            </select>
                                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-500">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                @endif

                                @if($profile->isCaregiver())
                                <!-- Age (Caregiver) -->
                                <div>
                                    <label class="mb-2 block text-xs font-[800] uppercase tracking-wider text-gray-400">Age</label>
                                    <template x-if="!editMode">
                                        <p class="px-5 py-4 font-[700] text-gray-900">{{ $profile->age ?: '—' }}</p>
                                    </template>
                                    <template x-if="editMode">
                                        <input type="number" name="age" value="{{ old('age', $profile->age) }}" 
                                            class="w-full rounded-xl border-2 border-gray-100 bg-gray-50 px-5 py-4 font-[700] text-gray-900 transition-all focus:border-blue-500 focus:bg-white focus:ring-0 outline-none">
                                    </template>
                                </div>
                                @endif

                                @if($profile->isElderly())
                                <!-- Age (Elderly Only) -->
                                <div>
                                    <label class="mb-2 block text-xs font-[800] uppercase tracking-wider text-gray-400">Age</label>
                                    <template x-if="!editMode">
                                        <p class="px-5 py-4 font-[700] text-gray-900">{{ $profile->age ?: '—' }}</p>
                                    </template>
                                    <template x-if="editMode">
                                        <input type="number" name="age" value="{{ old('age', $profile->age) }}" 
                                            class="w-full rounded-xl border-2 border-gray-100 bg-gray-50 px-5 py-4 font-[700] text-gray-900 transition-all focus:border-blue-500 focus:bg-white focus:ring-0 outline-none">
                                    </template>
                                </div>
                                <div>
                                    <label class="mb-2 block text-xs font-[800] uppercase tracking-wider text-gray-400">Sex</label>
                                    <template x-if="!editMode">
                                        <p class="px-5 py-4 font-[700] text-gray-900">{{ $profile->sex ?: '—' }}</p>
                                    </template>
                                    <template x-if="editMode">
                                        <div class="relative">
                                            <select name="sex" class="w-full appearance-none rounded-xl border-2 border-gray-100 bg-gray-50 px-5 py-4 font-[700] text-gray-900 transition-all focus:border-blue-500 focus:bg-white focus:ring-0 outline-none">
                                                <option value="">Select...</option>
                                                <option value="Male" {{ (old('sex', $profile->sex) == 'Male') ? 'selected' : '' }}>Male</option>
                                                <option value="Female" {{ (old('sex', $profile->sex) == 'Female') ? 'selected' : '' }}>Female</option>
                                            </select>
                                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-500">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <!-- Height (Elderly Only) -->
                                <div>
                                    <label class="mb-2 block text-xs font-[800] uppercase tracking-wider text-gray-400">Height (cm)</label>
                                    <template x-if="!editMode">
                                        <p class="px-5 py-4 font-[700] text-gray-900">{{ $profile->height ?: '—' }}</p>
                                    </template>
                                    <template x-if="editMode">
                                        <input type="number" step="0.01" name="height" value="{{ old('height', $profile->height) }}" 
                                            class="w-full rounded-xl border-2 border-gray-100 bg-gray-50 px-5 py-4 font-[700] text-gray-900 transition-all focus:border-blue-500 focus:bg-white focus:ring-0 outline-none">
                                    </template>
                                </div>

                                <!-- Weight (Elderly Only) -->
                                <div>
                                    <label class="mb-2 block text-xs font-[800] uppercase tracking-wider text-gray-400">Weight (kg)</label>
                                    <template x-if="!editMode">
                                        <p class="px-5 py-4 font-[700] text-gray-900">{{ $profile->weight ?: '—' }}</p>
                                    </template>
                                    <template x-if="editMode">
                                        <input type="number" step="0.01" name="weight" value="{{ old('weight', $profile->weight) }}" 
                                            class="w-full rounded-xl border-2 border-gray-100 bg-gray-50 px-5 py-4 font-[700] text-gray-900 transition-all focus:border-blue-500 focus:bg-white focus:ring-0 outline-none">
                                    </template>
                                </div>
                                @endif

                                <!-- Address (full width) -->
                                <div class="md:col-span-2 lg:col-span-3">
                                    <label class="mb-2 block text-xs font-[800] uppercase tracking-wider text-gray-400">Address</label>
                                    <template x-if="!editMode">
                                        <p class="px-5 py-4 font-[700] text-gray-900">{{ $profile->address ?: '—' }}</p>
                                    </template>
                                    <template x-if="editMode">
                                        <textarea name="address" rows="2" class="w-full resize-none rounded-xl border-2 border-gray-100 bg-gray-50 px-5 py-4 font-[700] text-gray-900 transition-all focus:border-blue-500 focus:bg-white focus:ring-0 outline-none">{{ old('address', $profile->address) }}</textarea>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CARD 2: Medical Information (Elderly Only) -->
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
                        
                        // Emergency Contact: Try individual columns first, fallback to legacy JSON, then caregiver
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

                    <div class="relative overflow-hidden bg-white rounded-[24px] p-8 shadow-sm border border-gray-100">
                        <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-red-400 to-rose-500"></div>
                        
                        <div class="relative z-10 mt-2">
                            <div class="flex items-center gap-3 mb-8">
                                <span class="text-2xl">🏥</span>
                                <h3 class="font-[800] text-xl text-gray-900">Medical Information</h3>
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                                <!-- Medical Conditions -->
                                <div>
                                    <label class="mb-2 block text-xs font-[800] uppercase tracking-wider text-red-400">Medical Conditions</label>
                                    <template x-if="!editMode">
                                        <div class="px-5 py-4">
                                            @if($conditionsVal)
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach(explode(', ', $conditionsVal) as $condition)
                                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-[600] bg-red-50 text-red-700">
                                                            ❤️ {{ trim($condition) }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <p class="font-[700] text-gray-400">None specified</p>
                                            @endif
                                        </div>
                                    </template>
                                    <template x-if="editMode">
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                <span class="text-red-300">❤️</span>
                                            </div>
                                            <input type="text" name="medical_conditions" value="{{ old('medical_conditions', $conditionsVal) }}" placeholder="e.g. Diabetes, Hypertension"
                                                class="w-full rounded-2xl border-2 border-red-50 bg-red-50/30 pl-12 pr-5 py-4 font-[700] text-gray-800 placeholder-red-200 focus:border-red-400 focus:bg-white focus:ring-0 transition-all">
                                        </div>
                                    </template>
                                </div>

                                <!-- Medications -->
                                <div>
                                    <label class="mb-2 block text-xs font-[800] uppercase tracking-wider text-blue-400">Medications</label>
                                    <template x-if="!editMode">
                                        <div class="px-5 py-4">
                                            @if($medsVal)
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach(explode(', ', $medsVal) as $med)
                                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-[600] bg-blue-50 text-blue-700">
                                                            💊 {{ trim($med) }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <p class="font-[700] text-gray-400">None specified</p>
                                            @endif
                                        </div>
                                    </template>
                                    <template x-if="editMode">
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                <span class="text-blue-300">💊</span>
                                            </div>
                                            <input type="text" name="medications" value="{{ old('medications', $medsVal) }}" placeholder="e.g. Metformin, Aspirin"
                                                class="w-full rounded-2xl border-2 border-blue-50 bg-blue-50/30 pl-12 pr-5 py-4 font-[700] text-gray-800 placeholder-blue-200 focus:border-blue-400 focus:bg-white focus:ring-0 transition-all">
                                        </div>
                                    </template>
                                </div>

                                <!-- Allergies -->
                                <div>
                                    <label class="mb-2 block text-xs font-[800] uppercase tracking-wider text-amber-500">Allergies</label>
                                    <template x-if="!editMode">
                                        <div class="px-5 py-4">
                                            @if($allergiesVal)
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach(explode(', ', $allergiesVal) as $allergy)
                                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-[600] bg-amber-50 text-amber-700">
                                                            ⚠️ {{ trim($allergy) }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <p class="font-[700] text-gray-400">None specified</p>
                                            @endif
                                        </div>
                                    </template>
                                    <template x-if="editMode">
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                <span class="text-amber-300">⚠️</span>
                                            </div>
                                            <input type="text" name="allergies" value="{{ old('allergies', $allergiesVal) }}" placeholder="e.g. Peanuts, Penicillin"
                                                class="w-full rounded-2xl border-2 border-amber-50 bg-amber-50/30 pl-12 pr-5 py-4 font-[700] text-gray-800 placeholder-amber-200 focus:border-amber-400 focus:bg-white focus:ring-0 transition-all">
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- CARD 3: Emergency Contact (Elderly Only) -->
                    @if($profile->isElderly())
                    <div class="relative overflow-hidden rounded-[24px] bg-gradient-to-br from-gray-800 to-gray-900 p-8 text-white shadow-xl shadow-gray-900/20">
                        <div class="absolute top-0 right-0 -mr-8 -mt-8 h-32 w-32 rounded-full bg-white/5 blur-xl"></div>
                        <div class="absolute bottom-0 left-0 -ml-8 -mb-8 h-32 w-32 rounded-full bg-indigo-500/30 blur-xl"></div>

                        <div class="relative z-10">
                            <div class="mb-6 flex items-center gap-3">
                                <span class="text-2xl">🚨</span>
                                <h3 class="text-xl font-[800] tracking-wide">Emergency Contact</h3>
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                                <!-- Contact Name -->
                                <div>
                                    <label class="mb-2 block text-xs font-[800] uppercase tracking-wider text-gray-400">Contact Name</label>
                                    <template x-if="!editMode">
                                        <p class="px-5 py-4 font-[700] text-white">{{ $emergencyName ?: '—' }}</p>
                                    </template>
                                    <template x-if="editMode">
                                        <input type="text" name="emergency_name" value="{{ old('emergency_name', $emergencyName) }}" placeholder="Contact Name"
                                            class="w-full rounded-xl border-0 bg-white/10 px-5 py-3.5 text-white font-[600] placeholder-gray-400 backdrop-blur-sm transition-all focus:bg-white/20 focus:ring-2 focus:ring-yellow-400">
                                    </template>
                                </div>

                                <!-- Phone Number -->
                                <div>
                                    <label class="mb-2 block text-xs font-[800] uppercase tracking-wider text-gray-400">Phone Number</label>
                                    <template x-if="!editMode">
                                        <p class="px-5 py-4 font-[700] text-white">{{ $emergencyPhone ?: '—' }}</p>
                                    </template>
                                    <template x-if="editMode">
                                        <input type="text" name="emergency_phone" value="{{ old('emergency_phone', $emergencyPhone) }}" placeholder="Phone Number"
                                            class="w-full rounded-xl border-0 bg-white/10 px-5 py-3.5 text-white font-[600] placeholder-gray-400 backdrop-blur-sm transition-all focus:bg-white/20 focus:ring-2 focus:ring-yellow-400">
                                    </template>
                                </div>

                                <!-- Relationship -->
                                <div>
                                    <label class="mb-2 block text-xs font-[800] uppercase tracking-wider text-gray-400">Relationship</label>
                                    <template x-if="!editMode">
                                        <p class="px-5 py-4 font-[700] text-white">{{ $emergencyRelationship ?: '—' }}</p>
                                    </template>
                                    <template x-if="editMode">
                                        <input type="text" name="emergency_relationship" value="{{ old('emergency_relationship', $emergencyRelationship) }}" placeholder="Relationship"
                                            class="w-full rounded-xl border-0 bg-white/10 px-5 py-3.5 text-white font-[600] placeholder-gray-400 backdrop-blur-sm transition-all focus:bg-white/20 focus:ring-2 focus:ring-yellow-400">
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- ACTION BUTTONS (only show in edit mode) -->
                    <div x-show="editMode" class="flex justify-end gap-4">
                        <!-- Cancel Button -->
                        <button type="button" @click="editMode = false"
                            class="px-8 py-4 rounded-2xl font-[700] text-gray-600 bg-gray-100 hover:bg-gray-200 transition-all">
                            Cancel
                        </button>

                        <!-- Save Button -->
                        <button type="submit"
                            class="group relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 py-4 px-8 text-lg font-[800] text-white shadow-xl shadow-blue-200 transition-all hover:-translate-y-1 hover:shadow-2xl active:scale-95">
                            <div class="relative z-10 flex items-center justify-center gap-2">
                                <span>SAVE CHANGES</span>
                                <svg class="h-5 w-5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <div class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/20 to-transparent transition-transform duration-1000 group-hover:translate-x-full"></div>
                        </button>
                    </div>

                </div>
            </form>
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
                statusDiv.innerHTML = '<span class="text-red-500 text-xs">Please select a valid image (JPG, PNG, GIF, WebP)</span>';
                return;
            }
            
            // Validate file size (5MB max)
            if (file.size > 5 * 1024 * 1024) {
                statusDiv.innerHTML = '<span class="text-red-500 text-xs">Image must be less than 5MB</span>';
                return;
            }
            
            // Show uploading status
            statusDiv.innerHTML = '<span class="text-blue-600 text-xs flex items-center gap-1"><svg class="animate-spin h-3 w-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Uploading...</span>';
            
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
                    statusDiv.innerHTML = '<span class="text-green-600 text-xs flex items-center gap-1"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Photo updated!</span>';
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    statusDiv.innerHTML = '<span class="text-red-500 text-xs">Failed to upload. Please try again.</span>';
                }
            })
            .catch(() => {
                statusDiv.innerHTML = '<span class="text-red-500 text-xs">An error occurred. Please try again.</span>';
            });
        }
        
        function removeProfilePhoto() {
            if (!confirm('Are you sure you want to remove your profile photo?')) return;
            
            const statusDiv = document.getElementById('photo-status');
            statusDiv.innerHTML = '<span class="text-blue-600 text-xs">Removing...</span>';
            
            fetch('{{ route("profile.photo.remove") }}', {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                }
            })
            .then(response => {
                if (response.ok) {
                    statusDiv.innerHTML = '<span class="text-green-600 text-xs">Photo removed!</span>';
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    statusDiv.innerHTML = '<span class="text-red-500 text-xs">Failed to remove. Please try again.</span>';
                }
            })
            .catch(() => {
                statusDiv.innerHTML = '<span class="text-red-500 text-xs">An error occurred. Please try again.</span>';
            });
        }
    </script>
</x-app-layout>