{{-- Converted to the SilverCare design system. See FRONTEND_DESIGN_SYSTEM.md §3. --}}
<x-guest-layout sc>
    <h1 class="sc-h3">{{ __('Choose your role') }}</h1>

    <p class="mt-3" style="color:var(--sc-body)">
        {{ __('We will tailor your SilverCare experience based on your role.') }}
    </p>

    <form method="POST" action="{{ route('auth.select-role.store') }}" class="mt-8 space-y-6">
        @csrf

        <fieldset class="space-y-3">
            <legend class="sc-label sc-label-req mb-3">{{ __('I am joining as') }}</legend>

            <label class="sc-check sc-card-quiet p-4 flex items-start gap-3.5 cursor-pointer transition-colors hover:border-[var(--sc-brand-line)]">
                <input type="radio" id="role-elderly" name="user_type" value="elderly"
                       {{ old('user_type') === 'elderly' ? 'checked' : '' }} required>
                <span class="space-y-0.5">
                    <span class="block font-semibold" style="color:var(--sc-ink)">{{ __('Elderly / Patient') }}</span>
                    <span class="block text-sm" style="color:var(--sc-body)">{{ __('I want to track my own medication and wellness.') }}</span>
                </span>
            </label>

            <label class="sc-check sc-card-quiet p-4 flex items-start gap-3.5 cursor-pointer transition-colors hover:border-[var(--sc-brand-line)]">
                <input type="radio" id="role-caregiver" name="user_type" value="caregiver"
                       {{ old('user_type') === 'caregiver' ? 'checked' : '' }} required>
                <span class="space-y-0.5">
                    <span class="block font-semibold" style="color:var(--sc-ink)">{{ __('Caregiver') }}</span>
                    <span class="block text-sm" style="color:var(--sc-body)">{{ __('I support and monitor an elderly patient.') }}</span>
                </span>
            </label>
        </fieldset>

        <x-input-error field="user_type" />

        <x-primary-button class="w-full">
            {{ __('Continue') }}
            <svg class="sc-i sc-arrow w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-arrow-right"/></svg>
        </x-primary-button>
    </form>
</x-guest-layout>
