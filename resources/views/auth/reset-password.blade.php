{{-- Converted to the SilverCare design system. See FRONTEND_DESIGN_SYSTEM.md §3. --}}
<x-guest-layout sc>
    <h1 class="sc-h3">{{ __('Choose a new password') }}</h1>

    <p class="mt-3" style="color:var(--sc-body)">
        {{ __('Pick something you will remember. Your password manager can fill this in for you.') }}
    </p>

    <form method="POST" action="{{ route('password.store') }}" class="mt-8">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="sc-field">
            <x-input-label for="email" :value="__('Email address')" required />
            <x-text-input id="email" type="email" name="email"
                          :value="old('email', $request->email)"
                          required autofocus autocomplete="username" inputmode="email" />
            <x-input-error field="email" />
        </div>

        {{-- One toggle for both password fields: someone who cannot see what
             they typed cannot tell why the two "do not match". --}}
        <div x-data="{ show: false }">
            <div class="sc-field">
                <x-input-label for="password" :value="__('New password')" required />
                <div class="relative">
                    <x-text-input id="password" name="password"
                                  x-bind:type="show ? 'text' : 'password'" type="password"
                                  class="pr-16"
                                  required autocomplete="new-password" />
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
                <x-input-label for="password_confirmation" :value="__('Confirm new password')" required />
                <x-text-input id="password_confirmation" name="password_confirmation"
                              x-bind:type="show ? 'text' : 'password'" type="password"
                              required autocomplete="new-password" />
                <x-input-error field="password_confirmation" />
            </div>
        </div>

        <x-primary-button class="w-full">
            {{ __('Save new password') }}
            <svg class="sc-i sc-arrow w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-arrow-right"/></svg>
        </x-primary-button>
    </form>
</x-guest-layout>
