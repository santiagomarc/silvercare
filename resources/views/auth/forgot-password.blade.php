{{-- Converted to the SilverCare design system. The `sc` flag is all the
     layout needs; everything below is plain design-system classes.
     See FRONTEND_DESIGN_SYSTEM.md §3. --}}
<x-guest-layout sc>
    <h1 class="sc-h3">Reset your password</h1>

    <p class="mt-3" style="color:var(--sc-body)">
        {{ __('Tell us the email address you sign in with and we will send you a link to choose a new password.') }}
    </p>

    @if (session('status'))
        <p class="mt-6 flex items-start gap-2.5 p-4 rounded-2xl"
           style="background:var(--sc-ok-tint);border:1px solid var(--sc-ok-line);color:var(--sc-ok)">
            <svg class="sc-i w-5 h-5 mt-0.5" aria-hidden="true" focusable="false"><use href="#i-check-circle"/></svg>
            <span class="font-medium">{{ session('status') }}</span>
        </p>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="mt-8">
        @csrf

        <div class="sc-field">
            <label for="email" class="sc-label sc-label-req">{{ __('Email address') }}</label>
            <input id="email" name="email" type="email"
                   value="{{ old('email') }}"
                   required autofocus autocomplete="email" inputmode="email"
                   class="sc-input @error('email') sc-input-error @enderror"
                   @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            @error('email')
                <p id="email-error" class="sc-error" role="alert">
                    <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-alert"/></svg>
                    <span>{{ $message }}</span>
                </p>
            @enderror
        </div>

        <button type="submit" class="sc-btn sc-btn-primary w-full">
            {{ __('Send reset link') }}
            <svg class="sc-i sc-arrow w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-arrow-right"/></svg>
        </button>
    </form>

    <p class="text-center mt-7" style="color:var(--sc-body)">
        <a href="{{ route('login') }}" class="sc-textlink">Back to sign in</a>
    </p>
</x-guest-layout>
