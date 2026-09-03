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
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@500;600;700;800&display=swap" rel="stylesheet">
    @include('partials.sc-fonts')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="sc-page antialiased">

<a class="sc-skip" href="#main-content">Skip to main content</a>

@include('partials.sc-icons')

{{-- `data-daylight` is rewritten in the browser by utils/daylight.js —
     the server's clock is not the reader's. --}}
<div class="sc-auth sc-ambient relative" data-daylight="afternoon">

    {{-- Reader controls stay reachable before sign-in: someone who set
         larger text on the landing page must not lose it at the door. --}}
    <div class="sc-auth-tools" x-data="displayControls()" @keydown.escape.window="open = false">
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

    <div class="sc-auth-inner">

        {{-- ── Greeting ──────────────────────────────────────────── --}}
        <div class="text-center mb-7">
            <a href="{{ route('welcome') }}" class="sc-brand justify-center sc-reveal">
                <span class="sc-brand-mark"><img src="{{ asset('assets/icons/silvercare.png') }}" alt=""></span>
                <span class="sc-brand-word">SilverCare</span>
                <span class="sr-only">SilverCare home</span>
            </a>

            {{-- Hidden until the browser knows the hour; a greeting that
                 guessed wrong would be worse than none. --}}
            <div class="mt-5 sc-reveal" style="--sc-d:80ms">
                <p class="sc-chip" data-daylight-chip hidden>
                    <svg class="sc-i w-4 h-4" aria-hidden="true" focusable="false"><use href="#i-sun"/></svg>
                    <span data-daylight-label></span>
                </p>
            </div>

            <h1 class="sc-auth-title mt-4 sc-reveal" style="--sc-d:140ms">Welcome back.</h1>
        </div>

        {{-- ── Form ──────────────────────────────────────────────── --}}
        <main id="main-content" class="sc-auth-card sc-reveal" style="--sc-d:260ms">

            @if (session('status'))
                <p class="flex items-start gap-2.5 p-4 mb-6 rounded-2xl"
                   style="background:var(--sc-ok-tint);border:1px solid var(--sc-ok-line);color:var(--sc-ok)">
                    <svg class="sc-i w-5 h-5 mt-0.5" aria-hidden="true" focusable="false"><use href="#i-check-circle"/></svg>
                    <span class="font-medium">{{ session('status') }}</span>
                </p>
            @endif

            {{-- More than one field failed: summarise at the top, link each
                 item to its field, and keep the inline messages too. --}}
            @if ($errors->any() && $errors->count() > 1)
                <div class="sc-error-summary" role="alert" tabindex="-1" x-init="$el.focus()">
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
               class="sc-btn sc-btn-ghost w-full">
                <svg class="w-5 h-5" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path fill="#EA4335" d="M12 10.2v3.9h5.5c-.2 1.2-1.4 3.4-5.5 3.4-3.3 0-6-2.7-6-6s2.7-6 6-6c1.9 0 3.1.8 3.8 1.5l2.6-2.5C16.8 2.9 14.6 2 12 2 6.5 2 2 6.5 2 12s4.5 10 10 10c5.8 0 9.7-4.1 9.7-9.9 0-.7-.1-1.3-.2-1.9H12z"/>
                </svg>
                <span id="googleSignInLabel">Continue with Google</span>
            </a>

            <p class="sc-or" aria-hidden="true">or</p>

            <form method="POST" action="{{ route('login') }}" novalidate>
                @csrf

                <div class="sc-field">
                    <label for="email" class="sc-label sc-label-req">Email address</label>
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

                <div class="sc-field" x-data="{ show: false }">
                    <label for="password" class="sc-label sc-label-req">Password</label>
                    <div class="relative">
                        <input id="password" name="password"
                               :type="show ? 'text' : 'password'" type="password"
                               required autocomplete="current-password"
                               class="sc-input pr-16 @error('password') sc-input-error @enderror"
                               @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                        {{-- Someone who cannot see what they typed cannot correct
                             it. This toggle is not optional for our readers. --}}
                        <button type="button"
                                @click="show = !show"
                                aria-pressed="false"
                                :aria-pressed="show ? 'true' : 'false'"
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

                <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 mb-7">
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
            <p class="sc-auth-facts">
                <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-lock"/></svg>
                <span>Health records are encrypted, and never sold.</span>
            </p>
        </main>

        <p class="text-center mt-6 sc-reveal" style="--sc-d:320ms;color:var(--sc-body)">
            New to SilverCare?
            <a href="{{ route('register') }}" class="sc-textlink">Create an account</a>
        </p>
    </div>
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
