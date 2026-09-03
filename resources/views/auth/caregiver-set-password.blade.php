{{-- Converted to the SilverCare design system. See FRONTEND_DESIGN_SYSTEM.md §3. --}}
<x-guest-layout sc>
    <h1 class="sc-h3">{{ __('Set your password') }}</h1>

    <p class="mt-3" style="color:var(--sc-body)">
        {{ __('Welcome to SilverCare, :name.', ['name' => $user->name]) }}
    </p>

    <div class="mt-4 p-4 rounded-2xl flex items-start gap-3"
         style="background:var(--sc-surface-2);border:1px solid var(--sc-line)">
        <svg class="sc-i w-5 h-5 mt-0.5" style="color:var(--sc-brand-text)" aria-hidden="true" focusable="false"><use href="#i-shield"/></svg>
        <p class="text-sm font-medium" style="color:var(--sc-body)">
            {{ __("You have been invited as a caregiver. Please create a secure password to access your account.") }}
        </p>
    </div>

    <form method="POST" action="{{ route('caregiver.password.store', $user->id) }}" class="mt-8 space-y-6">
        @csrf

        <div class="sc-field">
            <x-input-label for="email" :value="__('Email address')" />
            <x-text-input id="email" type="email" name="email" :value="$email" readonly />
            <x-input-error field="email" />
        </div>

        {{-- One toggle for both password fields: someone who cannot see what
             they typed cannot tell why the two "do not match". --}}
        <div x-data="{ show: false }">
            <div class="sc-field">
                <x-input-label for="password" :value="__('Password')" required />
                <div class="relative">
                    <x-text-input id="password" name="password"
                                  x-bind:type="show ? 'text' : 'password'" type="password"
                                  class="pr-16"
                                  required autofocus autocomplete="new-password"
                                  placeholder="{{ __('At least 8 characters') }}" />
                    <button type="button" @click="show = !show"
                            aria-pressed="false" :aria-pressed="show ? 'true' : 'false'"
                            class="sc-icon-btn absolute right-1 top-1/2 -translate-y-1/2">
                        <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false">
                            <use :href="show ? '#i-eye-off' : '#i-eye'"/>
                        </svg>
                        <span class="sr-only" x-text="show ? 'Hide password' : 'Show password'">Show password</span>
                    </button>
                </div>
                <x-input-error field="password" />
            </div>

            <div class="sc-field">
                <x-input-label for="password_confirmation" :value="__('Confirm password')" required />
                <x-text-input id="password_confirmation" name="password_confirmation"
                              x-bind:type="show ? 'text' : 'password'" type="password"
                              required autocomplete="new-password"
                              placeholder="{{ __('Repeat password') }}" />
                <x-input-error field="password_confirmation" />
            </div>
        </div>

        {{-- Password Requirements --}}
        <div class="sc-card-quiet p-4 rounded-2xl">
            <p class="font-semibold text-sm mb-2" style="color:var(--sc-ink)">{{ __('Password must contain:') }}</p>
            <ul class="text-sm space-y-1.5" style="color:var(--sc-body)">
                <li class="flex items-center gap-2">
                    <svg class="sc-i w-4 h-4" style="color:var(--sc-brand-text)" aria-hidden="true" focusable="false"><use href="#i-check-circle"/></svg>
                    <span>{{ __('At least 8 characters') }}</span>
                </li>
                <li class="flex items-center gap-2">
                    <svg class="sc-i w-4 h-4" style="color:var(--sc-brand-text)" aria-hidden="true" focusable="false"><use href="#i-check-circle"/></svg>
                    <span>{{ __('Mix of uppercase and lowercase letters') }}</span>
                </li>
                <li class="flex items-center gap-2">
                    <svg class="sc-i w-4 h-4" style="color:var(--sc-brand-text)" aria-hidden="true" focusable="false"><use href="#i-check-circle"/></svg>
                    <span>{{ __('At least one number') }}</span>
                </li>
            </ul>
        </div>

        <x-primary-button class="w-full">
            {{ __('Set password and continue') }}
            <svg class="sc-i sc-arrow w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-arrow-right"/></svg>
        </x-primary-button>
    </form>
</x-guest-layout>
