<section class="space-y-6">
    <header>
        <h2 class="text-lg font-serif font-bold text-[var(--sc-ink)]">
            {{ __('Delete Account') }}
        </h2>

        <p class="mt-1 text-sm text-[var(--sc-ink-muted)]">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </p>
    </header>

    <button
        type="button"
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
        class="sc-btn text-[var(--sc-alert)] border border-[var(--sc-alert)] hover:bg-[var(--sc-alert-tint)] min-h-touch"
    >{{ __('Delete Account') }}</button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6 sc-card">
            @csrf
            @method('delete')

            <h2 class="text-lg font-serif font-bold text-[var(--sc-ink)]">
                {{ __('Are you sure you want to delete your account?') }}
            </h2>

            <p class="mt-2 text-sm text-[var(--sc-ink-muted)]">
                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
            </p>

            <div class="mt-6 sc-field">
                <label for="delete_account_password" class="sc-label sr-only">{{ __('Password') }}</label>

                <input
                    id="delete_account_password"
                    name="password"
                    type="password"
                    class="sc-input w-full sm:w-3/4"
                    placeholder="{{ __('Password') }}"
                    aria-label="{{ __('Password') }}"
                />

                @if($errors->userDeletion->get('password'))
                    <p class="sc-field-error mt-2">{{ $errors->userDeletion->first('password') }}</p>
                @endif
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button
                    type="button"
                    x-on:click="$dispatch('close')"
                    class="sc-btn sc-btn-ghost min-h-touch"
                >
                    {{ __('Cancel') }}
                </button>

                <button
                    type="submit"
                    class="sc-btn text-[var(--sc-paper)] bg-[var(--sc-alert)] hover:opacity-90 min-h-touch"
                >
                    {{ __('Delete Account') }}
                </button>
            </div>
        </form>
    </x-modal>
</section>
