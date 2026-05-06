{{--
    Logout Confirm Modal
    Intercepts the browser back button on root dashboard pages
    and prompts the user to confirm if they want to log out.
--}}
<div x-data="logoutInterceptor" x-cloak>
    <div
        x-show="showModal"
        class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/60 backdrop-blur-md"
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
            class="w-[90%] max-w-md rounded-3xl bg-white p-8 shadow-2xl relative overflow-hidden"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95"
        >
            <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-red-500 to-rose-400"></div>

            <div class="mb-5 flex items-center justify-center w-16 h-16 rounded-full bg-red-50 mx-auto">
                <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
            </div>

            <h3 class="text-center text-xl font-[800] text-slate-800 tracking-tight">Leaving so soon?</h3>
            <p class="mt-3 text-center text-[15px] font-medium text-slate-500 leading-relaxed">
                By continuing back, you will securely log out of your SilverCare account.
            </p>

            <div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:justify-center">
                <button
                    @click="cancelLogout"
                    class="w-full rounded-2xl border-2 border-slate-200 bg-white px-5 py-3 text-[15px] font-bold text-slate-600
                           transition-all hover:bg-slate-50 hover:text-slate-800 focus:outline-none focus:ring-4
                           focus:ring-slate-100 active:scale-[0.98] sm:w-auto"
                >
                    Stay Logged In
                </button>

                <form method="POST" action="{{ route('logout') }}" class="w-full sm:w-auto m-0">
                    @csrf
                    <button
                        type="submit"
                        class="w-full rounded-2xl bg-gradient-to-r from-red-500 to-rose-500 px-5 py-3 text-[15px]
                               font-bold text-white shadow-lg shadow-red-500/30 transition-all hover:from-red-600
                               hover:to-rose-600 hover:shadow-red-600/40 focus:outline-none focus:ring-4
                               focus:ring-red-500/20 active:scale-[0.98] sm:w-auto"
                    >
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