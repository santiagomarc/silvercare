<section>
    <header>
        <h2 class="text-lg font-serif font-bold text-[var(--sc-ink)]">
            {{ __('Update Password') }}
        </h2>

        <p class="mt-1 text-sm text-[var(--sc-ink-muted)]">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-4">
        @csrf
        @method('put')

        <div class="sc-field">
            <label for="update_password_current_password" class="sc-label sc-label-req">{{ __('Current Password') }}</label>
            <input id="update_password_current_password" name="current_password" type="password" class="sc-input w-full" autocomplete="current-password" required />
            @if($errors->updatePassword->get('current_password'))
                <p class="sc-field-error mt-2">{{ $errors->updatePassword->first('current_password') }}</p>
            @endif
        </div>

        <div class="sc-field">
            <label for="update_password_password" class="sc-label sc-label-req">{{ __('New Password') }}</label>
            <input id="update_password_password" name="password" type="password" class="sc-input w-full" autocomplete="new-password" required />
            @if($errors->updatePassword->get('password'))
                <p class="sc-field-error mt-2">{{ $errors->updatePassword->first('password') }}</p>
            @endif
        </div>

        <div class="sc-field">
            <label for="update_password_password_confirmation" class="sc-label sc-label-req">{{ __('Confirm Password') }}</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="sc-input w-full" autocomplete="new-password" required />
            @if($errors->updatePassword->get('password_confirmation'))
                <p class="sc-field-error mt-2">{{ $errors->updatePassword->first('password_confirmation') }}</p>
            @endif
        </div>

        <div class="flex items-center gap-4 pt-2">
            <button type="submit" class="sc-btn sc-btn-primary min-h-touch">{{ __('Save') }}</button>

            @if (session('status') === 'password-updated')
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
