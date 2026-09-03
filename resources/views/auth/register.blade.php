<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#FAF9F6" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0B1220" media="(prefers-color-scheme: dark)">
    <title>Create an account — SilverCare</title>

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

    <div class="sc-auth-inner max-w-xl">

        {{-- ── Greeting ──────────────────────────────────────────── --}}
        <div class="text-center mb-7">
            <a href="{{ route('welcome') }}" class="sc-brand justify-center sc-reveal">
                <span class="sc-brand-mark"><img src="{{ asset('assets/icons/silvercare.png') }}" alt=""></span>
                <span class="sc-brand-word">SilverCare</span>
                <span class="sr-only">SilverCare home</span>
            </a>

            {{-- Hidden until the browser knows the hour; a greeting that
                 guessed wrong would be worse than none. --}}
            <div class="mt-6 sc-reveal" style="--sc-d:80ms">
                <p class="sc-chip" data-daylight-chip hidden>
                    <svg class="sc-i w-4 h-4" aria-hidden="true" focusable="false"><use href="#i-sun"/></svg>
                    <span data-daylight-label></span>
                </p>
            </div>

            <h1 class="sc-auth-title mt-4 sc-reveal" style="--sc-d:140ms">Create your account.</h1>
            <p class="mt-2.5 sc-reveal" style="--sc-d:200ms;color:var(--sc-body)">
                Join SilverCare to coordinate care, track health, and stay connected.
            </p>
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
                <div class="sc-error-summary mb-6" role="alert" tabindex="-1" x-init="$el.focus()">
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

            <form method="POST" action="{{ route('register') }}" novalidate>
                @csrf

                {{-- Role selection --}}
                <div class="sc-field">
                    <label for="user_type" class="sc-label sc-label-req">I am signing up as</label>
                    <select id="user_type" name="user_type" required
                            class="sc-select @error('user_type') sc-select-error @enderror"
                            @error('user_type') aria-invalid="true" aria-describedby="user_type-error" @enderror>
                        <option value="">Select role</option>
                        <option value="elderly" {{ old('user_type') == 'elderly' ? 'selected' : '' }}>Elderly / Patient</option>
                        <option value="caregiver" {{ old('user_type') == 'caregiver' ? 'selected' : '' }}>Caregiver</option>
                    </select>
                    <p id="user_type-error" class="sc-error hidden" role="alert"></p>
                    @error('user_type')
                        <p class="sc-error" role="alert">
                            <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-alert"/></svg>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>

                {{-- Full Name --}}
                <div class="sc-field">
                    <label for="name" class="sc-label sc-label-req">Full name</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                           autocomplete="name"
                           class="sc-input @error('name') sc-input-error @enderror"
                           @error('name') aria-invalid="true" aria-describedby="name-error" @enderror
                           placeholder="John Doe">
                    <p id="name-error" class="sc-error hidden" role="alert"></p>
                    @error('name')
                        <p class="sc-error" role="alert">
                            <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-alert"/></svg>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>

                {{-- Email Address --}}
                <div class="sc-field">
                    <label for="email" class="sc-label sc-label-req">Email address</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required
                           autocomplete="email" inputmode="email"
                           class="sc-input @error('email') sc-input-error @enderror"
                           @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                           placeholder="you@example.com">
                    <p id="email-error" class="sc-error hidden" role="alert"></p>
                    @error('email')
                        <p class="sc-error" role="alert">
                            <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-alert"/></svg>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>

                {{-- Secondary details: Phone and Username --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4">
                    {{-- Phone Number (optional) --}}
                    <div class="sc-field">
                        <label for="phone_number" class="sc-label">Phone number <span class="sc-label-opt">(optional)</span></label>
                        <input id="phone_number" type="tel" name="phone_number" value="{{ old('phone_number') }}"
                               autocomplete="tel"
                               class="sc-input @error('phone_number') sc-input-error @enderror"
                               @error('phone_number') aria-invalid="true" aria-describedby="phone_number-error" @enderror
                               placeholder="+1 (555) 000-0000">
                        <p id="phone_number-error" class="sc-error hidden" role="alert"></p>
                        @error('phone_number')
                            <p class="sc-error" role="alert">
                                <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-alert"/></svg>
                                <span>{{ $message }}</span>
                            </p>
                        @enderror
                    </div>

                    {{-- Username (optional) --}}
                    <div class="sc-field">
                        <label for="username" class="sc-label">Username <span class="sc-label-opt">(optional)</span></label>
                        <input id="username" type="text" name="username" value="{{ old('username') }}"
                               autocomplete="username"
                               class="sc-input @error('username') sc-input-error @enderror"
                               @error('username') aria-invalid="true" aria-describedby="username-error" @enderror
                               placeholder="johndoe123">
                        <p id="username-error" class="sc-error hidden" role="alert"></p>
                        @error('username')
                            <p class="sc-error" role="alert">
                                <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-alert"/></svg>
                                <span>{{ $message }}</span>
                            </p>
                        @enderror
                    </div>
                </div>

                {{-- Credentials: Password and Confirm Password --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4">
                    {{-- Password --}}
                    <div class="sc-field">
                        <label for="password" class="sc-label sc-label-req">Password</label>
                        <div class="relative">
                            <input id="password" type="password" name="password" required
                                   autocomplete="new-password"
                                   class="sc-input pr-16 @error('password') sc-input-error @enderror"
                                   @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                                   placeholder="At least 8 characters">
                            <button type="button"
                                    onclick="togglePassword('password', this)"
                                    aria-label="Show password"
                                    class="sc-icon-btn absolute right-1 top-1/2 -translate-y-1/2">
                                <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-eye"/></svg>
                            </button>
                        </div>
                        <p id="password-error" class="sc-error hidden" role="alert"></p>
                        @error('password')
                            <p class="sc-error" role="alert">
                                <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-alert"/></svg>
                                <span>{{ $message }}</span>
                            </p>
                        @enderror
                    </div>

                    {{-- Confirm Password --}}
                    <div class="sc-field">
                        <label for="password_confirmation" class="sc-label sc-label-req">Confirm password</label>
                        <div class="relative">
                            <input id="password_confirmation" type="password" name="password_confirmation" required
                                   autocomplete="new-password"
                                   class="sc-input pr-16 @error('password_confirmation') sc-input-error @enderror"
                                   @error('password_confirmation') aria-invalid="true" aria-describedby="password_confirmation-error" @enderror
                                   placeholder="Repeat password">
                            <button type="button"
                                    onclick="togglePassword('password_confirmation', this)"
                                    aria-label="Show password"
                                    class="sc-icon-btn absolute right-1 top-1/2 -translate-y-1/2">
                                <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-eye"/></svg>
                            </button>
                        </div>
                        <p id="password_confirmation-error" class="sc-error hidden" role="alert"></p>
                        @error('password_confirmation')
                            <p class="sc-error" role="alert">
                                <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-alert"/></svg>
                                <span>{{ $message }}</span>
                            </p>
                        @enderror
                    </div>
                </div>

                <button type="submit" id="submit-btn" disabled class="sc-btn sc-btn-primary w-full opacity-50 cursor-not-allowed mt-2">
                    Create account
                    <svg class="sc-i sc-arrow w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-arrow-right"/></svg>
                </button>
            </form>
        </main>

        <p class="text-center mt-6 sc-reveal" style="--sc-d:320ms;color:var(--sc-body)">
            Already have an account?
            <a href="{{ route('login') }}" class="sc-textlink">Sign in</a>
        </p>

        <ul class="sc-auth-facts sc-reveal" style="--sc-d:380ms">
            <li>
                <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-lock"/></svg>
                Encrypted health records
            </li>
            <li>
                <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-shield"/></svg>
                Your data is never sold
            </li>
        </ul>
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

    function togglePassword(fieldId, btn) {
        const input = document.getElementById(fieldId);
        const isHidden = input.type === 'password';

        // Toggle input type
        input.type = isHidden ? 'text' : 'password';

        // Update accessible label and icon sprite
        btn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
        btn.innerHTML = isHidden
            ? '<svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-eye-off"/></svg>'
            : '<svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-eye"/></svg>';
    }

    const fields = {
        user_type: { required: true, msg: 'Please select your role.' },
        name: { required: true, msg: 'Full name is required.' },
        email: { required: true, regex: /^[^\s@]+@[^\s@]+\.[^\s@]+$/, msg: 'Please enter a valid email address.', checkUnique: true },
        phone_number: { required: false },
        username: { required: false, checkUnique: true },
        password: { required: true, minLength: 8, msg: 'Password must be at least 8 characters.' },
        password_confirmation: { required: true, match: 'password', msg: 'Passwords do not match.' }
    };

    const state = {};
    Object.keys(fields).forEach(id => {
        state[id] = { dirty: false, blurred: false, error: '', loading: false, uniqueError: '' };
    });

    // Track global async checking state to disable button
    let checkingUnique = 0;

    function validateField(id, value) {
        const rule = fields[id];
        if (!rule) return '';
        
        if (rule.required && !value) return rule.msg || 'This field is required.';
        if (!value && !rule.required) return ''; // Optional and empty is valid
        
        if (rule.regex && !rule.regex.test(value)) return rule.msg;
        if (rule.minLength && value.length < rule.minLength) return rule.msg;
        if (rule.match) {
            const matchVal = document.getElementById(rule.match).value;
            if (value !== matchVal) return rule.msg;
        }
        return '';
    }

    function updateUI(id) {
        const errEl = document.getElementById(id + '-error');
        if (!errEl) return;

        // Exception: password and password_confirmation validate on input (no blur needed)
        const shouldShow = (state[id].blurred && state[id].dirty) || ((id === 'password' || id === 'password_confirmation') && state[id].dirty);
        
        const currentErr = state[id].error || state[id].uniqueError;
        
        if (shouldShow && currentErr) {
            errEl.innerHTML = `<svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-alert"/></svg><span>${currentErr}</span>`;
            errEl.classList.remove('hidden');
        } else {
            errEl.classList.add('hidden');
            errEl.textContent = '';
        }
    }

    function checkFormValidity() {
        let isValid = true;
        for (const id of Object.keys(fields)) {
            const el = document.getElementById(id);
            const val = (id.startsWith('password')) ? el.value : el.value.trim();
            const err = validateField(id, val);
            if (err || state[id].uniqueError) isValid = false;
        }

        const btn = document.getElementById('submit-btn');
        // Disable button if any base validation fails, uniqueness fails, or if a check is active
        if (isValid && checkingUnique === 0) {
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
        } else {
            btn.disabled = true;
            btn.classList.add('opacity-50', 'cursor-not-allowed');
        }
    }

    async function checkUniqueness(id, value) {
        if (!value) return;
        
        // Only check if basic validation passes
        if (state[id].error) return;

        checkingUnique++;
        checkFormValidity();
        
        try {
            const formData = new FormData();
            formData.append('_token', document.querySelector('input[name="_token"]').value);
            formData.append('check_unique', id);
            formData.append(id, value);
            
            const response = await fetch("{{ route('register') }}", {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: formData
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.taken) {
                    state[id].uniqueError = id === 'username' 
                        ? 'This username is already taken.' 
                        : 'An account with this email already exists.';
                } else {
                    state[id].uniqueError = '';
                }
            }
        } catch (err) {
            console.error('Validation check failed', err);
        } finally {
            checkingUnique--;
            updateUI(id);
            checkFormValidity();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        Object.keys(fields).forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;

            const evaluate = () => {
                const val = (id.startsWith('password')) ? el.value : el.value.trim();
                state[id].error = validateField(id, val);
                updateUI(id);
                checkFormValidity();
            };

            el.addEventListener('input', () => {
                state[id].dirty = true;
                // Clear unique error immediately on input changes
                if (state[id].uniqueError) {
                    state[id].uniqueError = '';
                }
                evaluate();
                
                // Cross-field validation for password changing
                if (id === 'password' && state['password_confirmation'].dirty) {
                    const confEl = document.getElementById('password_confirmation');
                    state['password_confirmation'].error = validateField('password_confirmation', confEl.value);
                    updateUI('password_confirmation');
                }
            });

            el.addEventListener('blur', () => {
                state[id].blurred = true;
                evaluate();
                
                // Trigger uniqueness validation if applicable and no basic errors
                if (fields[id].checkUnique && state[id].dirty && !state[id].error) {
                    const val = el.value.trim();
                    checkUniqueness(id, val);
                }
            });
            
            el.addEventListener('change', () => {
                state[id].dirty = true;
                evaluate();
            });
        });

        // Initial button state check
        checkFormValidity();
    });
</script>

</body>
</html>