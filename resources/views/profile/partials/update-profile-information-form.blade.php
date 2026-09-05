<section>
    <header>
        <h2 class="text-lg font-serif font-bold text-[var(--sc-ink)]">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-[var(--sc-ink-muted)]">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-4">
        @csrf
        @method('patch')

        <div class="sc-field">
            <label for="profile_name" class="sc-label sc-label-req">{{ __('Name') }}</label>
            <input id="profile_name" name="name" type="text" class="sc-input w-full" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name" />
            @if($errors->get('name'))
                <p class="sc-field-error mt-2">{{ $errors->first('name') }}</p>
            @endif
        </div>

        <div class="sc-field">
            <label for="profile_email" class="sc-label sc-label-req">{{ __('Email') }}</label>
            <input id="profile_email" name="email" type="email" class="sc-input w-full" value="{{ old('email', $user->email) }}" required autocomplete="username" />
            @if($errors->get('email'))
                <p class="sc-field-error mt-2">{{ $errors->first('email') }}</p>
            @endif

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="sc-card-quiet p-4 mt-4 border border-[var(--sc-line)]">
                    <p class="text-sm text-[var(--sc-ink)]">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="ml-2 underline font-semibold text-[var(--sc-ink)] hover:text-[var(--sc-brand)]">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 text-sm text-[var(--sc-ok)] font-medium">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4 pt-2">
            <button type="submit" class="sc-btn sc-btn-primary min-h-touch">{{ __('Save') }}</button>

            @if (session('status') === 'profile-updated')
                <span
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="sc-mark sc-mark-ok text-sm"
                >
                    <i></i>{{ __('Saved.') }}
                </span>
            @endif
        </div>
    </form>
</section>
