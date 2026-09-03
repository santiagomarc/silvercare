<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#FAF9F6" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0B1220" media="(prefers-color-scheme: dark)">
    <title>Sign in — SilverCare</title>

    <link rel="icon" type="image/png" href="{{ asset('assets/icons/silvercare.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/icons/silvercare.png') }}">

    @include('partials.sc-theme-boot')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@500;600;700;800&family=Newsreader:ital,opsz,wght@1,6..72,400;1,6..72,500&display=swap" rel="stylesheet">
    @include('partials.sc-fonts')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="sc-page antialiased">

<a class="sc-skip" href="#main-content">Skip to main content</a>

@include('partials.sc-icons')

<div class="min-h-screen lg:grid lg:grid-cols-12">

    {{-- ── Form ──────────────────────────────────────────────────── --}}
    <div class="sc-ambient lg:col-span-6 xl:col-span-5 flex flex-col justify-center px-5 sm:px-10 py-10">
        <div class="w-full max-w-md mx-auto">

            <div class="flex items-center justify-between gap-4 mb-10">
                <a href="{{ route('welcome') }}" class="sc-brand">
                    <span class="sc-brand-mark"><img src="{{ asset('assets/icons/silvercare.png') }}" alt=""></span>
                    <span class="sc-brand-word">SilverCare</span>
                    <span class="sr-only">SilverCare home</span>
                </a>

                {{-- Same reader controls as the landing page: someone who set
                     larger text before signing up must not lose it at the door. --}}
                <div class="relative" x-data="displayControls()" @keydown.escape.window="open = false">
                    <button type="button" class="sc-icon-btn"
                            aria-expanded="false"
                            :aria-expanded="open ? 'true' : 'false'"
                            aria-controls="sc-display-menu"
                            aria-haspopup="true"
                            @click="open = !open">
                        <svg class="sc-i w-6 h-6" aria-hidden="true" focusable="false"><use href="#i-accessibility"/></svg>
                        <span class="sr-only">Display and accessibility options</span>
                    </button>

                    <div id="sc-display-menu" x-show="open" x-cloak
                         @click.outside="open = false"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="absolute right-0 mt-3 w-[19rem] p-5 space-y-5 sc-card sc-card-pop z-50"
                         role="group" aria-label="Display and accessibility settings">

                        <div>
                            <p class="flex items-center gap-2 font-semibold mb-2.5" style="color:var(--sc-ink)">
                                <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-type"/></svg>
                                Text size
                            </p>
                            <div class="grid grid-cols-3 gap-2" role="group" aria-label="Text size">
                                <template x-for="opt in scales" :key="opt.value">
                                    <button type="button" @click="setScale(opt.value)"
                                            :aria-pressed="scale === opt.value ? 'true' : 'false'"
                                            :aria-label="opt.aria"
                                            class="sc-size-btn" :class="scale === opt.value && 'sc-size-btn-on'">
                                        <span :style="`font-size:${opt.preview}`" x-text="opt.label"></span>
                                        <span class="text-sm leading-none" x-text="opt.name"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-4">
                            <span class="flex items-center gap-2 font-medium" id="sc-dark-label" style="color:var(--sc-ink)">
                                <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-moon"/></svg>
                                Dark mode
                            </span>
                            <button type="button" role="switch" class="sc-switch"
                                    aria-labelledby="sc-dark-label" aria-checked="false"
                                    :aria-checked="dark ? 'true' : 'false'" @click="toggleDark()"></button>
                        </div>

                        <div class="flex items-center justify-between gap-4">
                            <span class="flex items-center gap-2 font-medium" id="sc-contrast-label" style="color:var(--sc-ink)">
                                <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-contrast"/></svg>
                                High contrast
                            </span>
                            <button type="button" role="switch" class="sc-switch"
                                    aria-labelledby="sc-contrast-label" aria-checked="false"
                                    :aria-checked="contrast ? 'true' : 'false'" @click="toggleContrast()"></button>
                        </div>
                    </div>
                </div>
            </div>

            <main id="main-content">
                <h1 class="sc-h2">Welcome back.</h1>
                <p class="mt-3" style="color:var(--sc-body)">Sign in to pick up where you left off.</p>

                @if (session('status'))
                    <p class="mt-7 flex items-start gap-2.5 p-4 rounded-2xl"
                       style="background:var(--sc-ok-tint);border:1px solid var(--sc-ok-line);color:var(--sc-ok)">
                        <svg class="sc-i w-5 h-5 mt-0.5" aria-hidden="true" focusable="false"><use href="#i-check-circle"/></svg>
                        <span class="font-medium">{{ session('status') }}</span>
                    </p>
                @endif

                {{-- More than one thing wrong: summarise at the top and link each
                     item to its field, keeping the inline messages as well. --}}
                @if ($errors->any() && $errors->count() > 1)
                    <div class="sc-error-summary mt-7" role="alert" tabindex="-1" x-init="$el.focus()">
                        <p class="font-semibold">Please check {{ $errors->count() }} things before continuing:</p>
                        <ul class="mt-2 space-y-1 list-disc list-inside">
                            @foreach ($errors->keys() as $field)
                                <li><a href="#{{ $field }}">{{ $errors->first($field) }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <a href="{{ route('auth.google.redirect') }}"
                   id="googleSignInBtn"
                   onclick="handleGoogleSignIn(event)"
                   class="sc-btn sc-btn-ghost w-full mt-8">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path fill="#EA4335" d="M12 10.2v3.9h5.5c-.2 1.2-1.4 3.4-5.5 3.4-3.3 0-6-2.7-6-6s2.7-6 6-6c1.9 0 3.1.8 3.8 1.5l2.6-2.5C16.8 2.9 14.6 2 12 2 6.5 2 2 6.5 2 12s4.5 10 10 10c5.8 0 9.7-4.1 9.7-9.9 0-.7-.1-1.3-.2-1.9H12z"/>
                    </svg>
                    <span id="googleSignInLabel">Continue with Google</span>
                </a>

                <div class="flex items-center gap-4 my-7" aria-hidden="true">
                    <span class="h-px flex-1" style="background:var(--sc-line)"></span>
                    <span class="text-sm font-medium" style="color:var(--sc-muted)">or</span>
                    <span class="h-px flex-1" style="background:var(--sc-line)"></span>
                </div>

                <form method="POST" action="{{ route('login') }}" novalidate>
                    @csrf

                    <div class="sc-field">
                        <label for="email" class="sc-label sc-label-req">Email address</label>
                        <input id="email" name="email" type="email"
                               value="{{ old('email') }}"
                               required autofocus autocomplete="email"
                               inputmode="email"
                               class="sc-input @error('email') sc-input-error @enderror"
                               @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                        @error('email')
                            <p id="email-error" class="sc-error" role="alert">
                                <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-alert"/></svg>
                                <span>{{ $message }}</span>
                            </p>
                        @enderror
                    </div>

                    <div class="sc-field" x-data="{ show: false }">
                        <label for="password" class="sc-label sc-label-req">Password</label>
                        <div class="relative">
                            <input id="password" name="password"
                                   :type="show ? 'text' : 'password'" type="password"
                                   required autocomplete="current-password"
                                   class="sc-input pr-16 @error('password') sc-input-error @enderror"
                                   @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                            {{-- A password manager cannot help someone who cannot see
                                 what they typed; the toggle is not optional here. --}}
                            <button type="button"
                                    @click="show = !show"
                                    :aria-pressed="show ? 'true' : 'false'"
                                    aria-pressed="false"
                                    class="sc-icon-btn absolute right-1 top-1/2 -translate-y-1/2">
                                <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false">
                                    <use :href="show ? '#i-eye-off' : '#i-eye'"/>
                                </svg>
                                <span class="sr-only" x-text="show ? 'Hide password' : 'Show password'">Show password</span>
                            </button>
                        </div>
                        @error('password')
                            <p id="password-error" class="sc-error" role="alert">
                                <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-alert"/></svg>
                                <span>{{ $message }}</span>
                            </p>
                        @enderror
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
                        <label class="sc-check">
                            <input type="checkbox" name="remember">
                            <span style="color:var(--sc-body)">Keep me signed in</span>
                        </label>

                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="sc-textlink">Forgot password?</a>
                        @endif
                    </div>

                    <button type="submit" class="sc-btn sc-btn-primary w-full">
                        Sign in
                        <svg class="sc-i sc-arrow w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-arrow-right"/></svg>
                    </button>
                </form>

                <p class="text-center mt-8" style="color:var(--sc-body)">
                    New to SilverCare?
                    <a href="{{ route('register') }}" class="sc-textlink">Create an account</a>
                </p>
            </main>
        </div>
    </div>

    {{-- ── Reassurance panel ─────────────────────────────────────────
         Replaces the stock photograph: no external image request, no
         models, and it says something true about the product. --}}
    <aside class="hidden lg:flex lg:col-span-6 xl:col-span-7 items-center justify-center p-14 sc-cta-panel" style="border-radius:0">
        <div class="relative max-w-lg" style="z-index:1">
            <svg class="sc-i w-11 h-11 mb-7" style="color:rgba(255,255,255,.35)" aria-hidden="true" focusable="false"><use href="#i-quote"/></svg>

            <p class="sc-quote leading-[1.35]" style="font-size:clamp(1.6rem,1.2rem+1vw,2.2rem);color:#FFFFFF">
                SilverCare gave my dad his independence back. And for the first time in
                three years, I sleep through the night knowing he is safe.
            </p>

            <p class="mt-8 font-semibold" style="color:rgba(255,255,255,.9)">Sarah Pendelton</p>
            <p class="text-sm" style="color:rgba(255,255,255,.7)">Daughter and primary caregiver · Chicago, Illinois</p>

            <ul class="flex flex-wrap gap-x-7 gap-y-3 mt-12" style="color:rgba(255,255,255,.78)">
                <li class="flex items-center gap-2.5">
                    <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-lock"/></svg>
                    Encrypted health records
                </li>
                <li class="flex items-center gap-2.5">
                    <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-shield"/></svg>
                    Your data is never sold
                </li>
            </ul>
        </div>
    </aside>
</div>

<script>
    // Google OAuth: show a loading state and block the double-click.
    function handleGoogleSignIn(e) {
        const btn   = document.getElementById('googleSignInBtn');
        const label = document.getElementById('googleSignInLabel');
        if (btn.dataset.loading) { e.preventDefault(); return; }
        btn.dataset.loading = '1';
        btn.setAttribute('aria-disabled', 'true');
        btn.classList.add('opacity-70', 'cursor-not-allowed');
        label.textContent = 'Redirecting…';
    }

    @if (session('swal_error'))
    // OAuth failure flashed by ProviderController.
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.scToast === 'function') {
            window.scToast({{ Js::from(session('swal_error')) }}, 'error');
        }
    });
    @endif
</script>

</body>
</html>
