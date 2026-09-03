{{-- Converted to the SilverCare design system. See FRONTEND_DESIGN_SYSTEM.md §3. --}}
<x-guest-layout sc>
    <span class="sc-plate sc-plate-sm mb-5">
        <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-lock"/></svg>
    </span>

    <h1 class="sc-h3">{{ __('Confirm it is you') }}</h1>

    <p class="mt-3" style="color:var(--sc-body)">
        {{ __('This part of SilverCare holds health information. Enter your password once more to continue.') }}
    </p>

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-8">
        @csrf

        <div class="sc-field" x-data="{ show: false }">
            <x-input-label for="password" :value="__('Password')" required />
            <div class="relative">
                <x-text-input id="password" name="password"
                              x-bind:type="show ? 'text' : 'password'" type="password"
                              class="pr-16"
                              required autocomplete="current-password" autofocus />
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

        <x-primary-button class="w-full">
            {{ __('Continue') }}
            <svg class="sc-i sc-arrow w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-arrow-right"/></svg>
        </x-primary-button>
    </form>
</x-guest-layout>
