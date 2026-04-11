<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign Up - SilverCare</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('assets/icons/silvercare.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/icons/silvercare.png') }}">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Montserrat', sans-serif; }
        .fade-in-section {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.8s ease-out, transform 0.8s ease-out;
        }
        .fade-in-section.is-visible {
            opacity: 1;
            transform: none;
        }
    </style>
</head>
<body class="antialiased bg-[#DEDEDE] relative">

    <!-- Background Image -->
    <div class="fixed inset-0 bg-[url('https://images.unsplash.com/photo-1576765608535-5f04d1e3f289?q=80&w=2048&auto=format&fit=crop')] bg-cover bg-center opacity-30"></div>
    <div class="fixed inset-0 bg-gradient-to-br from-[#DEDEDE]/80 via-[#DEDEDE]/60 to-blue-100/40"></div>

    <div class="min-h-screen w-full flex items-center justify-center px-4 py-12 relative z-10">
        
        <div class="w-full max-w-4xl bg-white rounded-3xl shadow-[0_20px_60px_rgba(0,0,0,0.15)] p-8 md:p-12">
            
            <!-- Header -->
            <div class="relative text-center mb-8 pt-10">
                <a href="{{ route('welcome') }}" class="absolute right-0 top-0 inline-flex items-center gap-2 text-gray-600 hover:text-[#000080] transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <span class="font-semibold">Back to Home</span>
                </a>
                <h1 class="text-4xl md:text-5xl font-[900] text-gray-900 tracking-tight mb-2">Create Account</h1>
                <p class="text-gray-600 font-medium">Join SilverCare today</p>
            </div>

            <form method="POST" action="{{ route('register') }}" class="space-y-6">
                @csrf

                <!-- Show all errors at top -->
                @if ($errors->any())
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded fade-in-section transition-delay-100">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-bold text-red-800">There were errors with your submission:</h3>
                                <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Google Sign-In -->
                <div class="fade-in-section transition-delay-200">
                    <a href="{{ route('auth.google.redirect') }}" class="w-full inline-flex items-center justify-center gap-3 border-2 border-gray-200 hover:border-[#000080] bg-white py-3 rounded-xl font-bold text-gray-800 transition-all duration-200">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="#EA4335" d="M12 10.2v3.9h5.5c-.2 1.2-1.4 3.4-5.5 3.4-3.3 0-6-2.7-6-6s2.7-6 6-6c1.9 0 3.1.8 3.8 1.5l2.6-2.5C16.8 2.9 14.6 2 12 2 6.5 2 2 6.5 2 12s4.5 10 10 10c5.8 0 9.7-4.1 9.7-9.9 0-.7-.1-1.3-.2-1.9H12z"/>
                        </svg>
                        Continue with Google
                    </a>
                </div>

                <div class="flex items-center gap-3 fade-in-section transition-delay-300">
                    <div class="h-px flex-1 bg-gray-200"></div>
                    <span class="text-xs font-semibold tracking-wider text-gray-500">OR SIGN UP WITH EMAIL</span>
                    <div class="h-px flex-1 bg-gray-200"></div>
                </div>

                <!-- Two Column Layout for Fields -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 fade-in-section transition-delay-400">
                    <div class="space-y-5">
                        <div>
                            <label for="user_type" class="block text-sm font-bold text-gray-700 mb-2">I am signing up as</label>
                            <select id="user_type" name="user_type" required
                                    class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all duration-200 font-medium">
                                <option value="">Select role</option>
                                <option value="elderly" {{ old('user_type') == 'elderly' ? 'selected' : '' }}>Elderly / Patient</option>
                                <option value="caregiver" {{ old('user_type') == 'caregiver' ? 'selected' : '' }}>Caregiver</option>
                            </select>
                        </div>

                        <div>
                            <label for="name" class="block text-sm font-bold text-gray-700 mb-2">Full Name</label>
                            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                                   class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all duration-200 font-medium"
                                   placeholder="John Doe">
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-bold text-gray-700 mb-2">Email Address</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required
                                   class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all duration-200 font-medium"
                                   placeholder="you@example.com">
                        </div>

                        <div>
                            <label for="phone_number" class="block text-sm font-bold text-gray-700 mb-2">Phone Number (optional)</label>
                            <input id="phone_number" type="tel" name="phone_number" value="{{ old('phone_number') }}"
                                   class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all duration-200 font-medium"
                                   placeholder="+1234567890">
                        </div>
                    </div>

                    <div class="space-y-5">
                        <div>
                            <label for="username" class="block text-sm font-bold text-gray-700 mb-2">Username (optional)</label>
                            <input id="username" type="text" name="username" value="{{ old('username') }}"
                                   class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all duration-200 font-medium"
                                   placeholder="johndoe123">
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-bold text-gray-700 mb-2">Password</label>
                            <div class="relative">
                                <input id="password" type="password" name="password" required
                                    class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all duration-200 font-medium pr-12"
                                    placeholder="Enter password">
                                <button type="button"
                                        onclick="togglePassword('password', this)"
                                        class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 hover:text-gray-600 transition-colors duration-200">
                                    <!-- Eye Open Icon (default) -->
                                    <svg xmlns="http://www.w3.org/2000/svg" class="eye-icon h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-sm font-bold text-gray-700 mb-2">Confirm Password</label>
                            <div class="relative">
                                <input id="password_confirmation" type="password" name="password_confirmation" required
                                    class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all duration-200 font-medium pr-12"
                                    placeholder="Confirm password">
                                <button type="button"
                                        onclick="togglePassword('password_confirmation', this)"
                                        class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 hover:text-gray-600 transition-colors duration-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="eye-icon h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{--<div>
                            <label for="age" class="block text-sm font-bold text-gray-700 mb-2">Age (optional)</label>
                            <input id="age" type="number" name="age" value="{{ old('age') }}" min="1" max="150"
                                   class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all duration-200 font-medium"
                                   placeholder="65">
                        </div>--}}
                    </div>
                </div>

                <!-- Register Button (Centered) -->
                <div class="pt-6 flex justify-center fade-in-section transition-delay-500">
                    <button type="submit" id="submit-btn" disabled class="group relative w-full max-w-md opacity-50 cursor-not-allowed">
                        <div class="absolute -inset-0.5 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-[82px] opacity-50 blur transition duration-200 group-hover:opacity-75"></div>
                        <div class="relative w-full py-4 bg-[#000080] text-white font-[800] text-xl rounded-[82px] shadow-[0_8px_20px_rgba(0,0,128,0.3)] transition-all duration-300 transform group-hover:-translate-y-1 group-active:scale-95">
                            CREATE ACCOUNT
                        </div>
                    </button>
                </div>

                <!-- Login Link (Centered) -->
                <div class="text-center pt-4 fade-in-section transition-delay-600">
                    <p class="text-gray-600">
                        Already have an account? 
                        <a href="{{ route('login') }}" class="font-bold text-[#000080] hover:text-blue-900 transition-colors">
                            Sign In
                        </a>
                    </p>
                </div>
            </form>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const observerOptions = {root: null, rootMargin: '0px', threshold: 0.1};
            const observer = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const delays = {
                            'transition-delay-100': 0, 'transition-delay-200': 100, 'transition-delay-300': 200,
                            'transition-delay-400': 250, 'transition-delay-500': 300, 'transition-delay-600': 350
                        };
                        let delay = 0;
                        for (const [className, ms] of Object.entries(delays)) {
                            if (entry.target.classList.contains(className)) { delay = ms; break; }
                        }
                        setTimeout(() => entry.target.classList.add('is-visible'), delay);
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);
            document.querySelectorAll('.fade-in-section').forEach((section) => { observer.observe(section); });
        });

        function togglePassword(fieldId, btn) {
            const input = document.getElementById(fieldId);
            const isHidden = input.type === 'password';

            // Toggle input type
            input.type = isHidden ? 'text' : 'password';

            // Swap icon
            btn.innerHTML = isHidden ? `
                <!-- Eye Slash (password visible) -->
                <svg xmlns="http://www.w3.org/2000/svg" class="eye-icon h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 4.411m0 0L21 21" />
                </svg>
            ` : `
                <!-- Eye Open (password hidden) -->
                <svg xmlns="http://www.w3.org/2000/svg" class="eye-icon h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
            `;
        }

        function checkFormValidity() {
            const role = document.getElementById('user_type').value;
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('password_confirmation').value;

            const isValid = role !== '' && name !== '' && email !== '' && password !== '' && confirmPassword !== '';

            const btn = document.getElementById('submit-btn');
            if (isValid) {
                btn.disabled = false;
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                btn.disabled = true;
                btn.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const requiredFields = ['user_type', 'name', 'email', 'password', 'password_confirmation'];
            requiredFields.forEach(id => {
                document.getElementById(id).addEventListener('input', checkFormValidity);
                document.getElementById(id).addEventListener('change', checkFormValidity);
            });
        });
    </script>
</body>
</html>
                