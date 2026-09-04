{{--
    Logout Confirm Modal
    Intercepts the browser back button on root dashboard pages
    and prompts the user to confirm if they want to log out.
--}}
<div x-data="logoutInterceptor" x-cloak>
    <div
        x-show="showModal"
        class="sc-scrim z-[100] flex items-center justify-center p-4"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div
            x-show="showModal"
            @click.away="cancelLogout"
            role="dialog"
            aria-modal="true"
            aria-labelledby="sc-logout-title"
            class="sc-dialog w-[90%] max-w-md text-center"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95"
        >
            <span class="sc-plate sc-plate-alert mb-5">
                <x-lucide-log-out class="sc-i w-7 h-7" aria-hidden="true" />
            </span>

            <p id="sc-logout-title" class="sc-dialog-title">Leaving so soon?</p>
            <p class="mt-3 leading-relaxed" style="color:var(--sc-body)">
                By continuing back, you will securely log out of your SilverCare account.
            </p>

            <div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:justify-center">
                <button type="button" @click="cancelLogout" class="sc-btn sc-btn-ghost w-full sm:w-auto">
                    Stay Logged In
                </button>

                <form method="POST" action="{{ route('logout') }}" class="w-full sm:w-auto m-0">
                    @csrf
                    <button type="submit" class="sc-btn sc-btn-danger w-full">
                        Yes, Log Out
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('logoutInterceptor', () => ({
        showModal: false,

        init() {
            window.addEventListener('popstate', (e) => {
                // A tab change from dashboardTabs.js pushes a state with a `tab` key.
                // Our login-page sentinel has no state at all (state is null) OR has
                // state that is neither our trap nor a tab object.
                // We only want to intercept when the popped state is null (we've gone
                // past our sentinel back toward the login page) OR when it's our own
                // trap sentinel being popped.

                const state = e.state;

                // If the popped state has a `tab` property, it's a dashboardTabs
                // internal navigation — let it pass through untouched.
                if (state && state.tab) {
                    return;
                }

                // For every other back navigation (state is null, or any unknown state),
                // we intercept: re-push the trap to keep the user on this page,
                // then show the modal.
                history.pushState({ silvercareTrap: true }, '', window.location.href);
                this.showModal = true;
            });
        },

        cancelLogout() {
            this.showModal = false;
            // The trap state was already re-pushed when the modal opened, so we're good.
        }
    }));
});
</script>
@endpush