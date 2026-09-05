{{-- My Profile — the record the rest of SilverCare depends on: who to call
     in an emergency, what Arthur is allergic to, and which caregiver is
     allowed to see any of it.

     The page has two states and the state IS the interaction, so that is the
     one loud thing here. `editMode` swaps every editable field for an input
     and says so in a band at the top of the form; view mode is the default
     and reads as a record, not a greyed-out form.

     The app bar owns the <h1>, so sections start at <h2>. --}}
<x-dashboard-layout sc>
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
        :show-back="$profile->isCaregiver()"
        :back-url="$profile->isCaregiver() ? route('caregiver.dashboard') : null"
        back-label="Back to Dashboard"
    />

    {{-- ============================================================
         FIX: caregiverConnector is registered as Alpine.data()
         instead of inline x-data so that template literals with
         ${data.existing_name} / ${data.new_name} are evaluated as
         JS — not rendered as raw text by the browser.
         The html: value uses string concatenation (no backticks).
         Routes are injected via data attributes to avoid Blade/JS conflicts.
         ============================================================ --}}
    @if($profile->isElderly())
    @push('scripts')
    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('caregiverConnector', () => ({
            pin: @js(session('prefill_link_code', '')),
            step: 'enter',
            loading: false,
            error: '',
            caregiver: null,
 
            // Routes injected via data attributes on the element
            validateUrl: '',
            confirmUrl: '',
 
            init() {
                // Read routes from data attributes set on the x-data element
                this.validateUrl = this.$el.dataset.validateUrl;
                this.confirmUrl  = this.$el.dataset.confirmUrl;
 
                if (this.pin && this.pin.length === 6) {
                    this.$nextTick(() => this.validatePin());
                }
            },
 
            async validatePin() {
                if (this.pin.length !== 6) {
                    window.scAlert({ icon: 'warning', title: 'That PIN did not work', text: 'Please enter all 6 digits.', elderly: true });
                    return;
                }
                this.loading = true;
                this.error = '';
                try {
                    const res = await fetch(this.validateUrl, {
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
                        window.scAlert({ icon: 'error', title: 'That PIN did not work', text: data.message || 'The PIN you entered is invalid or expired.', elderly: true });
                    }
                } catch (e) {
                    window.scAlert({ icon: 'error', title: 'Error', text: 'Something went wrong. Please check your connection and try again.', elderly: true });
                } finally {
                    this.loading = false;
                }
            },
 
            async confirmLink(forceSwitch = false) {
                this.loading = true;
                try {
                    const res = await fetch(this.confirmUrl, {
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
 
                        // FIX: string concatenation instead of backtick template literal
                        const confirmed = await window.scConfirm({
                            icon: 'warning',
                            elderly: true,
                            title: 'Switch caregiver?',
                            html: '<p style="color:var(--sc-body);margin-top:.5rem">You are currently connected to <strong>' + data.existing_name + '</strong>.<br>Do you want to switch to <strong>' + data.new_name + '</strong>?<br><br>Your current caregiver will lose access.</p>',
                            confirmButtonText: 'Yes, switch caregiver',
                            cancelButtonText: 'Keep current caregiver',
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
                        title: 'Connected',
                        text: data.message || 'You have successfully linked with your caregiver.',
                        timer: 2000,
                        timerProgressBar: true,
                        showConfirmButton: false,
                        allowOutsideClick: false,
                    });
                    window.location.reload();
                } catch (e) {
                    window.scAlert({ icon: 'error', elderly: true, title: 'Could not connect', text: e.message || 'Please try again.' });
                } finally {
                    this.loading = false;
                }
            },
 
            reset() {
                this.step = 'enter';
                this.pin = '';
                this.error = '';
                this.caregiver = null;
            },
        }));
    });
    </script>
    @endpush
    @endif
    <main id="main-content" class="sc-app-main"
          x-data="{ editMode: {{ $hasProfileValidationErrors ? 'true' : 'false' }}, showLogoutConfirm: false }">
        <div class="max-w-4xl mx-auto px-6 lg:px-12">

            {{-- The mode is the page's one real interaction, so it is announced
                 rather than merely shown. This lives in the DOM from the start
                 and changes its text: a region revealed by x-show often is not
                 announced at all. --}}
            <p class="sr-only" role="status"
               x-text="editMode
                    ? 'Editing your details. Nothing is saved until you choose Save changes.'
                    : 'Viewing your details.'"></p>

            @if (session('status') === 'profile-updated')
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     role="status" class="sc-flash sc-flash-ok mb-6">
                    <x-lucide-circle-check class="sc-i w-6 h-6 mt-0.5" aria-hidden="true" />
                    <span>Your changes are saved.</span>
                </div>
            @endif

            @if($hasProfileValidationErrors)
                <div class="sc-error-summary" role="alert" tabindex="-1" id="profile-error-summary">
                    <p class="font-semibold">Your changes were not saved.</p>
                    <ul class="mt-2 space-y-1">
                        @foreach($profileErrorFields as $field)
                            @error($field)
                                <li><a href="#{{ $field }}">{{ $message }}</a></li>
                            @enderror
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('profile.update') }}"
                x-data="{
                    validateForm(e) {
                        const phone = document.querySelector('input[name=phone_number]');
                        const phoneError = document.getElementById('phone-format-error');
                        const name = document.querySelector('input[name=name]');

                        {{-- Clear the previous attempt first, or a corrected field stays red. --}}
                        if (phone) phone.classList.remove('sc-input-error');
                        if (name) name.classList.remove('sc-input-error');
                        if (phoneError) phoneError.style.display = 'none';

                        if (phone && phone.value && !/^[0-9+\-\s\(\)]+$/.test(phone.value)) {
                            e.preventDefault();
                            phone.classList.add('sc-input-error');
                            phone.nextElementSibling.style.display = 'block';
                            phone.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            return false;
                        }
                        if (name && name.value.trim() === '') {
                            e.preventDefault();
                            name.classList.add('sc-input-error');
                            name.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            return false;
                        }
                    }
                }"
                @submit="validateForm($event)">
                @csrf
                @method('PATCH')

                {{-- Mode row. In view mode the page is a record and Edit is a
                     quiet way in; in edit mode the band says what is true right
                     now and where the way out is. --}}
                <div class="mb-8">
                    <div x-show="!editMode" class="flex justify-end">
                        <button type="button" @click="editMode = true" class="sc-btn sc-btn-ghost">
                            <x-lucide-pencil class="sc-i w-5 h-5" aria-hidden="true" />
                            Edit details
                        </button>
                    </div>

                    <div x-show="editMode" x-cloak class="sc-note">
                        <x-lucide-pencil class="sc-i w-6 h-6 mt-0.5" aria-hidden="true" />
                        <div>
                            <p class="sc-note-title">You are editing your details</p>
                            <p class="mt-1">Nothing changes until you choose Save changes at the bottom of this form.</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">

                    {{-- Your details --}}
                    <section class="sc-card p-6 md:p-8" aria-labelledby="section-details">
                        <h2 id="section-details" class="sc-h3">Your details</h2>

                        <div class="mt-6 pt-6 sc-hair flex flex-col sm:flex-row sm:items-center gap-5">
                            <span class="sc-avatar sc-avatar-xl">
                                @if($profile->profile_photo)
                                    <img id="profile-photo-preview" src="{{ Storage::url($profile->profile_photo) }}" alt="">
                                @else
                                    <span id="profile-photo-initial" aria-hidden="true">{{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}</span>
                                @endif
                            </span>

                            <div class="min-w-0">
                                <p class="sc-label">Profile photo</p>

                                <template x-if="!editMode">
                                    <p class="sc-field-value @unless($profile->profile_photo) sc-field-value-empty @endunless">
                                        {{ $profile->profile_photo ? 'Added' : 'No photo yet' }}
                                    </p>
                                </template>

                                <template x-if="editMode">
                                    <div>
                                        <input type="file" id="photo-upload" name="profile_photo"
                                               accept="image/jpeg,image/png,image/gif,image/webp"
                                               class="sr-only" @change="openCropModal($event)">

                                        <div class="flex flex-wrap items-center gap-3">
                                            <label for="photo-upload" class="sc-btn sc-btn-ghost sc-btn-sm">
                                                <x-lucide-camera class="sc-i w-5 h-5" aria-hidden="true" />
                                                {{ $profile->profile_photo ? 'Change photo' : 'Add photo' }}
                                            </label>

                                            @if($profile->profile_photo)
                                                <button type="button"
                                                    @click="window.scConfirm({ icon: 'warning', elderly: true, title: 'Remove your photo?', text: 'Your initial will be shown instead.', confirmButtonText: 'Yes, remove it', cancelButtonText: 'Keep photo' }).then(ok => { if(ok) removeProfilePhoto(); })"
                                                    class="sc-btn sc-btn-ghost sc-btn-sm">
                                                    <x-lucide-trash-2 class="sc-i w-5 h-5" aria-hidden="true" />
                                                    Remove photo
                                                </button>
                                            @endif
                                        </div>

                                        <p class="sc-help">JPG, PNG, GIF or WebP, up to 5MB. You can crop it before it is saved.</p>
                                        <div id="photo-status" class="mt-2"></div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="mt-6 pt-6 sc-hair">
                            {{-- Full name --}}
                            <div class="sc-field">
                                <template x-if="!editMode">
                                    <div>
                                        <p class="sc-label">Full name</p>
                                        <p class="sc-field-value @unless($user->name) sc-field-value-empty @endunless">{{ $user->name ?: 'Not set' }}</p>
                                    </div>
                                </template>
                                <template x-if="editMode">
                                    <div>
                                        <x-input-label for="name" :value="__('Full name')" required />
                                        <x-text-input id="name" name="name" type="text" required autocomplete="name"
                                                      value="{{ old('name', $user->name) }}" />
                                        <x-input-error field="name" />
                                    </div>
                                </template>
                            </div>

                            {{-- Email is shown, never edited here. --}}
                            <div class="sc-field">
                                <p class="sc-label">Email address</p>
                                <p class="sc-field-value @unless($user->email) sc-field-value-empty @endunless">{{ $user->email ?: 'Not set' }}</p>
                            </div>

                            {{-- Phone --}}
                            <div class="sc-field">
                                <template x-if="!editMode">
                                    <div>
                                        <p class="sc-label">Phone number</p>
                                        <p class="sc-field-value @unless($profile->phone_number) sc-field-value-empty @endunless">{{ $profile->phone_number ?: 'Not set' }}</p>
                                    </div>
                                </template>
                                <template x-if="editMode">
                                    <div>
                                        <x-input-label for="phone_number" :value="__('Phone number')" />
                                        <x-text-input id="phone_number" name="phone_number" type="tel" autocomplete="tel"
                                                      value="{{ old('phone_number', $profile->phone_number) }}" />
                                        {{-- Revealed by validateForm() above; it targets the input's
                                             next sibling, so this stays directly under the field. --}}
                                        <p id="phone-format-error" class="sc-error" style="display:none">
                                            <x-lucide-triangle-alert class="sc-i w-5 h-5" aria-hidden="true" />
                                            <span>Use only numbers, spaces and + ( ) -</span>
                                        </p>
                                        <x-input-error field="phone_number" />
                                    </div>
                                </template>
                            </div>

                            @if($profile->isCaregiver())
                            {{-- Relationship --}}
                            <div class="sc-field">
                                <template x-if="!editMode">
                                    <div>
                                        <p class="sc-label">Relationship to the person you care for</p>
                                        <p class="sc-field-value @unless($profile->relationship) sc-field-value-empty @endunless">{{ $profile->relationship ?: 'Not set' }}</p>
                                    </div>
                                </template>
                                <template x-if="editMode">
                                    <div>
                                        <x-input-label for="relationship" :value="__('Relationship to the person you care for')" />
                                        <x-text-input id="relationship" name="relationship" type="text"
                                                      value="{{ old('relationship', $profile->relationship) }}" />
                                        <span class="sc-help">For example: daughter, son, or professional carer.</span>
                                        <x-input-error field="relationship" />
                                    </div>
                                </template>
                            </div>
                            @endif
                        </div>
                    </section>

                    @if($profile->isElderly())
                    {{-- Health information. Three lists of the same shape, so
                         they read as one set. Only allergies are tinted: they
                         are the one entry a stranger acts on in a hurry, and
                         the colour is backed by an icon and the word itself. --}}
                    <section class="sc-card p-6 md:p-8" aria-labelledby="section-health">
                        <h2 id="section-health" class="sc-h3">Health information</h2>
                        <p class="sc-help">Shown to your caregiver and on your emergency summary.</p>

                        <div class="mt-6 pt-6 sc-hair">
                            {{-- Conditions --}}
                            <div class="sc-field">
                                <template x-if="!editMode">
                                    <div>
                                        <p class="sc-label">Medical conditions</p>
                                        @if($conditionsVal)
                                            <ul class="flex flex-wrap gap-2 pt-1">
                                                @foreach(explode(',', $conditionsVal) as $condition)
                                                    @if(trim($condition) !== '')
                                                        <li class="sc-chip">
                                                            <x-lucide-heart-pulse class="sc-i w-5 h-5" aria-hidden="true" />
                                                            {{ trim($condition) }}
                                                        </li>
                                                    @endif
                                                @endforeach
                                            </ul>
                                        @else
                                            <p class="sc-field-value sc-field-value-empty">None recorded</p>
                                        @endif
                                    </div>
                                </template>
                                <template x-if="editMode">
                                    <div>
                                        <x-input-label for="medical_conditions" :value="__('Medical conditions')" />
                                        <x-text-input id="medical_conditions" name="medical_conditions" type="text"
                                                      value="{{ old('medical_conditions', $conditionsVal) }}" />
                                        <span class="sc-help">Separate each one with a comma. For example: diabetes, high blood pressure.</span>
                                        <x-input-error field="medical_conditions" />
                                    </div>
                                </template>
                            </div>

                            {{-- Medications --}}
                            <div class="sc-field">
                                <template x-if="!editMode">
                                    <div>
                                        <p class="sc-label">Medications</p>
                                        @if($medsVal)
                                            <ul class="flex flex-wrap gap-2 pt-1">
                                                @foreach(explode(',', $medsVal) as $med)
                                                    @if(trim($med) !== '')
                                                        <li class="sc-chip">
                                                            <x-lucide-pill class="sc-i w-5 h-5" aria-hidden="true" />
                                                            {{ trim($med) }}
                                                        </li>
                                                    @endif
                                                @endforeach
                                            </ul>
                                        @else
                                            <p class="sc-field-value sc-field-value-empty">None recorded</p>
                                        @endif
                                    </div>
                                </template>
                                <template x-if="editMode">
                                    <div>
                                        <x-input-label for="medications" :value="__('Medications')" />
                                        <x-text-input id="medications" name="medications" type="text"
                                                      value="{{ old('medications', $medsVal) }}" />
                                        <span class="sc-help">Separate each one with a comma. For example: metformin, aspirin.</span>
                                        <x-input-error field="medications" />
                                    </div>
                                </template>
                            </div>

                            {{-- Allergies --}}
                            <div class="sc-field">
                                <template x-if="!editMode">
                                    <div>
                                        <p class="sc-label">Allergies</p>
                                        @if($allergiesVal)
                                            <ul class="flex flex-wrap gap-2 pt-1">
                                                @foreach(explode(',', $allergiesVal) as $allergy)
                                                    @if(trim($allergy) !== '')
                                                        <li class="sc-chip sc-chip-warn">
                                                            <x-lucide-triangle-alert class="sc-i w-5 h-5" aria-hidden="true" />
                                                            <span class="sr-only">Allergy:</span>
                                                            {{ trim($allergy) }}
                                                        </li>
                                                    @endif
                                                @endforeach
                                            </ul>
                                        @else
                                            <p class="sc-field-value sc-field-value-empty">None recorded</p>
                                        @endif
                                    </div>
                                </template>
                                <template x-if="editMode">
                                    <div>
                                        <x-input-label for="allergies" :value="__('Allergies')" />
                                        <x-text-input id="allergies" name="allergies" type="text"
                                                      value="{{ old('allergies', $allergiesVal) }}" />
                                        <span class="sc-help">Separate each one with a comma. For example: peanuts, penicillin.</span>
                                        <x-input-error field="allergies" />
                                    </div>
                                </template>
                            </div>
                        </div>
                    </section>

                    {{-- Emergency contact --}}
                    <section class="sc-card p-6 md:p-8" aria-labelledby="section-emergency">
                        <h2 id="section-emergency" class="sc-h3">Emergency contact</h2>
                        <p class="sc-help">The person to reach first if something goes wrong.</p>

                        <div class="mt-6 pt-6 sc-hair">
                            <template x-if="!editMode">
                                <div>
                                    @if($emergencyName || $emergencyPhone || $emergencyRelationship)
                                        <div class="grid gap-x-6 md:grid-cols-3">
                                            <div class="sc-field">
                                                <p class="sc-label">Contact name</p>
                                                <p class="sc-field-value @unless($emergencyName) sc-field-value-empty @endunless">{{ $emergencyName ?: 'Not set' }}</p>
                                            </div>
                                            <div class="sc-field">
                                                <p class="sc-label">Phone number</p>
                                                <p class="sc-field-value sc-num @unless($emergencyPhone) sc-field-value-empty @endunless">{{ $emergencyPhone ?: 'Not set' }}</p>
                                            </div>
                                            <div class="sc-field">
                                                <p class="sc-label">Relationship</p>
                                                <p class="sc-field-value @unless($emergencyRelationship) sc-field-value-empty @endunless">{{ $emergencyRelationship ?: 'Not set' }}</p>
                                            </div>
                                        </div>
                                    @else
                                        <div class="sc-note sc-note-warn">
                                            <x-lucide-triangle-alert class="sc-i w-6 h-6 mt-0.5" aria-hidden="true" />
                                            <div>
                                                <p class="sc-note-title">No emergency contact yet</p>
                                                <p class="mt-1">Choose Edit details to add the person we should reach first.</p>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </template>

                            <template x-if="editMode">
                                <div class="grid gap-x-6 md:grid-cols-3">
                                    <div class="sc-field">
                                        <x-input-label for="emergency_name" :value="__('Contact name')" />
                                        <x-text-input id="emergency_name" name="emergency_name" type="text" autocomplete="off"
                                                      value="{{ old('emergency_name', $emergencyName) }}" />
                                        <x-input-error field="emergency_name" />
                                    </div>
                                    <div class="sc-field">
                                        <x-input-label for="emergency_phone" :value="__('Phone number')" />
                                        <x-text-input id="emergency_phone" name="emergency_phone" type="tel" autocomplete="off"
                                                      value="{{ old('emergency_phone', $emergencyPhone) }}" />
                                        <x-input-error field="emergency_phone" />
                                    </div>
                                    <div class="sc-field">
                                        <x-input-label for="emergency_relationship" :value="__('Relationship')" />
                                        <x-text-input id="emergency_relationship" name="emergency_relationship" type="text" autocomplete="off"
                                                      value="{{ old('emergency_relationship', $emergencyRelationship) }}" />
                                        <x-input-error field="emergency_relationship" />
                                    </div>
                                </div>
                            </template>
                        </div>
                    </section>
                    @endif

                    {{-- The page's one loud button, and only while it has
                         something to save. --}}
                    <div x-show="editMode" x-cloak class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-2">
                        <x-secondary-button @click="editMode = false">Cancel</x-secondary-button>
                        <x-primary-button>
                            <x-lucide-check class="sc-i w-5 h-5" aria-hidden="true" />
                            Save changes
                        </x-primary-button>
                    </div>
                </div>
            </form>

            {{-- Care connection — outside the form: linking is its own
                 transaction and has nothing to do with saving details. --}}
            @if($profile->isElderly())
                {{--
                    FIX: x-data now just references the registered Alpine.data component name.
                    Routes are passed as data attributes so JS can read them cleanly
                    without Blade/template-literal conflicts.
                --}}
                <section class="sc-card p-6 md:p-8 mt-10" id="care-connection" aria-labelledby="section-care-connection"
                     x-data="caregiverConnector"
                     data-validate-url="{{ route('elderly.validate-link-code') }}"
                     data-confirm-url="{{ route('elderly.confirm-link') }}">

                    <h2 id="section-care-connection" class="sc-h3">Care connection</h2>
                    <p class="sc-help">The one person allowed to see your health information.</p>

                    <div class="mt-6 pt-6 sc-hair">
                    @if($profile->caregiver)
                        <div class="sc-card-quiet p-5" x-data="{ showUnlink: false }">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                <div class="flex items-center gap-4 min-w-0">
                                    <span class="sc-plate sc-plate-ok sc-plate-sm">
                                        <x-lucide-circle-check class="sc-i w-6 h-6" aria-hidden="true" />
                                    </span>
                                    <div class="min-w-0">
                                        <p class="sc-field-value">Connected to {{ $profile->caregiver->user?->name ?? $profile->caregiver->username ?? 'your caregiver' }}</p>
                                        <p style="color:var(--sc-muted)">They can see your vitals, medications and checklists.</p>
                                    </div>
                                </div>

                                <button type="button" x-show="!showUnlink" @click="showUnlink = true"
                                        class="sc-btn sc-btn-ghost sc-btn-sm w-full md:w-auto md:flex-none">
                                    <x-lucide-unlink class="sc-i w-5 h-5" aria-hidden="true" />
                                    Unlink caregiver
                                </button>
                            </div>

                            <div x-show="showUnlink" x-cloak class="sc-card p-5 mt-4">
                                <form
                                    method="POST"
                                    action="{{ route('elderly.unlink-caregiver') }}"
                                    data-confirm="This removes your caregiver's access to your health information."
                                    data-confirm-title="Unlink your caregiver?"
                                    data-confirm-icon="warning"
                                    data-confirm-confirm-text="Yes, unlink"
                                    data-confirm-cancel-text="Keep my caregiver"
                                    data-confirm-elderly="true"
                                >
                                    @csrf
                                    <div class="sc-field">
                                        <label for="unlink-password" class="sc-label sc-label-req">Your password</label>
                                        <input type="password"
                                               id="unlink-password"
                                               name="password"
                                               required
                                               autocomplete="current-password"
                                               class="sc-input @error('password') sc-input-error @enderror"
                                               @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                                        <span class="sc-help">We ask for it so nobody else can disconnect your care.</span>
                                        <x-input-error field="password" />
                                    </div>
                                    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                                        <button type="button" @click="showUnlink = false" class="sc-btn sc-btn-ghost sc-btn-sm">Cancel</button>
                                        <button type="submit" class="sc-btn sc-btn-danger sc-btn-sm">Unlink caregiver</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @else
                        <div class="sc-note">
                            <x-lucide-link-2 class="sc-i w-6 h-6 mt-0.5" aria-hidden="true" />
                            <div class="min-w-0 w-full">
                                <p class="sc-note-title">You are not connected to a caregiver yet</p>
                                <p class="mt-1">Ask them for the 6-digit PIN on their SilverCare profile, then type it here.</p>

                                <div class="sc-field mt-4">
                                    <label for="care-pin" class="sc-label">Caregiver's 6-digit PIN</label>
                                    <div class="flex flex-col sm:flex-row sm:items-start gap-3">
                                        {{-- `.sc-page .sc-input` sets width:100% and outranks a
                                             `sm:w-44` utility, so the width lives on the wrapper. --}}
                                        <div class="sm:w-44 sm:flex-none">
                                        <input type="text"
                                               id="care-pin"
                                               x-model="pin"
                                               inputmode="numeric"
                                               autocomplete="one-time-code"
                                               maxlength="6"
                                               pattern="[0-9]{6}"
                                               placeholder="000000"
                                               @keyup.enter="validatePin()"
                                               class="sc-input sc-num text-center"
                                               style="letter-spacing:.28em; font-weight:700">
                                        </div>
                                        <button type="button" @click="validatePin()" class="sc-btn sc-btn-ghost sm:flex-none">
                                            <span x-show="!loading">Check PIN</span>
                                            <span x-show="loading" x-cloak>Checking…</span>
                                        </button>
                                    </div>
                                    <p class="sc-error" x-show="error" x-cloak>
                                        <x-lucide-triangle-alert class="sc-i w-5 h-5" aria-hidden="true" />
                                        <span x-text="error"></span>
                                    </p>
                                </div>

                                <div x-show="step === 'confirm'" x-cloak class="sc-card p-5">
                                    <p class="sc-eyebrow">Confirm the person</p>
                                    <div class="flex items-center gap-3 mt-3 mb-4">
                                        <span class="sc-avatar" aria-hidden="true" x-text="caregiver?.caregiver_name?.[0] ?? '?'"></span>
                                        <div class="min-w-0">
                                            <p class="sc-field-value" x-text="caregiver?.caregiver_name ?? ''"></p>
                                            <p style="color:var(--sc-muted)" x-text="caregiver?.caregiver_role ?? 'Caregiver'"></p>
                                        </div>
                                    </div>
                                    <div class="flex flex-col-reverse sm:flex-row gap-2">
                                        <button type="button" @click="reset()" class="sc-btn sc-btn-ghost sc-btn-sm">Cancel</button>
                                        <button type="button" @click="confirmLink()" :disabled="loading" class="sc-btn sc-btn-primary sc-btn-sm">
                                            <span x-show="!loading">Connect this caregiver</span>
                                            <span x-show="loading" x-cloak>Connecting…</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                    </div>
                </section>
            @endif

            {{-- Patient linking (caregiver) --}}
            @if($profile->isCaregiver())
                <section class="sc-card p-6 md:p-8 mt-10" id="patient-linking" aria-labelledby="section-patient-linking">
                    <h2 id="section-patient-linking" class="sc-h3">Patient linking</h2>
                    <p class="sc-help">Give this PIN to the person you care for so they can connect to you.</p>

                    <div class="mt-6 pt-6 sc-hair">
                        @if(session('link_code') || $activeLinkCode)
                            @php
                                $displayCode = session('link_code', $activeLinkCode?->code);
                                $qrSvg = session('link_qr_svg', $activeLinkQrSvg ?? null);
                                $shareUrl = session('link_signed_url', $activeLinkSignedUrl ?? null);
                            @endphp
                            <div class="flex flex-col sm:flex-row gap-6 items-start" x-data="{ copied: false }">
                                @if($qrSvg)
                                    <div class="sc-card-quiet p-3 flex-none">
                                        {!! $qrSvg !!}
                                        <p class="text-center mt-1" style="color:var(--sc-muted)">Scan with a phone</p>
                                    </div>
                                @endif

                                <div class="min-w-0">
                                    <p class="sc-label">Linking PIN</p>
                                    <p id="link-pin" class="sc-stat-value sc-num" style="letter-spacing:.22em">{{ $displayCode }}</p>

                                    @if($activeLinkCode?->expires_at)
                                        <p class="mt-1" style="color:var(--sc-muted)">
                                            Expires {{ $activeLinkCode->expires_at->format('j M Y, g:i a') }}
                                        </p>
                                    @endif

                                    <div class="flex flex-wrap gap-2 mt-4">
                                        <button type="button"
                                            @click="navigator.clipboard.writeText('{{ $displayCode }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                            class="sc-btn sc-btn-ghost sc-btn-sm">
                                            <x-lucide-copy class="sc-i w-5 h-5" aria-hidden="true" />
                                            <span x-text="copied ? 'Copied' : 'Copy PIN'">Copy PIN</span>
                                        </button>

                                        @if($shareUrl)
                                            <button type="button"
                                                @click="navigator.clipboard.writeText('{{ $shareUrl }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                                class="sc-btn sc-btn-ghost sc-btn-sm">
                                                <x-lucide-link-2 class="sc-i w-5 h-5" aria-hidden="true" />
                                                Copy link
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="sc-note">
                                <x-lucide-key class="sc-i w-6 h-6 mt-0.5" aria-hidden="true" />
                                <div>
                                    <p class="sc-note-title">No linking PIN yet</p>
                                    <p class="mt-1">Generate one, then read it out or send the QR code to the person you care for.</p>
                                </div>
                            </div>
                        @endif

                        <div class="mt-6 pt-6 sc-hair" x-data="{
                                loading: false,
                                async generate() {
                                    if(this.loading) return;
                                    this.loading = true;
                                    try {
                                        const res = await fetch('{{ route('caregiver.link-code.generate') }}', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'Accept': 'application/json',
                                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                                            }
                                        });
                                        if(!res.ok) throw new Error('The PIN could not be generated. Please try again.');
                                        window.location.reload();
                                    } catch(e) {
                                        window.Swal?.fire({ icon: 'error', title: 'Something went wrong', text: e.message });
                                    } finally {
                                        this.loading = false;
                                    }
                                }
                            }">
                            <button type="button" @click="generate()" class="sc-btn sc-btn-ghost w-full sm:w-auto">
                                @if($activeLinkCode)
                                    <x-lucide-refresh-cw class="sc-i w-5 h-5" aria-hidden="true" />
                                @else
                                    <x-lucide-key class="sc-i w-5 h-5" aria-hidden="true" />
                                @endif
                                <span x-show="!loading">{{ $activeLinkCode ? 'Replace PIN and QR code' : 'Generate PIN and QR code' }}</span>
                                <span x-show="loading" x-cloak>Generating…</span>
                            </button>
                        </div>
                    </div>
                </section>
            @endif

            {{-- Signing out. The app bar owns this everywhere it can show it.
                 On a caregiver sub-page it cannot: the bar spends its room on
                 Back and folds the account actions into the drawer, so the
                 control surfaces here instead. Both at once is the same button
                 twice. Mirrors $showAccountActions in x-dashboard-nav. --}}
            @if($profile->isCaregiver())
            <div class="mt-10 pt-6 sc-hair flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <p style="color:var(--sc-muted)">Signed in as {{ $user->name }}</p>
                <button type="button" @click="showLogoutConfirm = true" class="sc-btn sc-btn-ghost sc-btn-sm">
                    <x-lucide-log-out class="sc-i w-5 h-5" aria-hidden="true" />
                    Log out
                </button>
            </div>
            @endif
        </div>

        {{-- Crop dialog. The cropper script toggles `hidden`/`flex` on this
             element by id, so both classes stay exactly where they were. --}}
        <div id="profile-photo-crop-modal" class="sc-scrim hidden items-center justify-center p-4"
             role="dialog" aria-modal="true" aria-labelledby="crop-dialog-title">
            <div class="sc-dialog">
                <div class="flex items-start justify-between gap-4">
                    <h2 id="crop-dialog-title" class="sc-dialog-title">Crop your photo</h2>
                    <button type="button" onclick="cancelCrop()" class="sc-icon-btn">
                        <x-lucide-x class="sc-i w-6 h-6" aria-hidden="true" />
                        <span class="sr-only">Close and keep my current photo</span>
                    </button>
                </div>

                <div class="sc-card-quiet mt-5 p-4 flex justify-center">
                    <img id="crop-image" alt="" class="max-h-96 max-w-full" style="max-width: 100%;">
                </div>

                <p class="sc-help">Drag the picture to move it, and drag a corner to change how much is shown. A square works best.</p>

                <div class="mt-5 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                    <button type="button" onclick="cancelCrop()" class="sc-btn sc-btn-ghost sc-btn-sm">Cancel</button>
                    {{-- The script replaces this button's textContent while
                         uploading, so it stays text-only. --}}
                    <button id="crop-upload-button" type="button" onclick="applyCrop()" class="sc-btn sc-btn-primary sc-btn-sm">Crop and upload</button>
                </div>
            </div>
        </div>

        {{-- Log out confirmation --}}
        <div x-show="showLogoutConfirm" x-cloak x-transition.opacity
             class="sc-scrim flex items-center justify-center p-4"
             role="dialog" aria-modal="true" aria-labelledby="logout-confirm-title">
            <div class="absolute inset-0" @click="showLogoutConfirm = false" aria-hidden="true"></div>

            <div class="sc-dialog max-w-md">
                <h2 id="logout-confirm-title" class="sc-dialog-title">Log out of SilverCare?</h2>
                <p class="mt-2" style="color:var(--sc-body)">You will need your password to sign back in.</p>

                <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                    <button type="button" @click="showLogoutConfirm = false" class="sc-btn sc-btn-ghost sc-btn-sm">Stay signed in</button>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="sc-btn sc-btn-danger sc-btn-sm w-full">Log out</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</x-dashboard-layout>
