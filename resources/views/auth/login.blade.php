<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In - SilverCare</title>
    
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
            will-change: opacity, visibility;
        }
        .fade-in-section.is-visible {
            opacity: 1;
            transform: none;
        }
    </style>
</head>
<body class="antialiased bg-[#DEDEDE] overflow-hidden">

    <div class="min-h-screen w-full flex relative">
        
        <!-- Left Side: Login Form -->
        <div class="w-full lg:w-5/12 h-screen flex flex-col justify-center items-center px-6 relative z-10">
            
            <div class="w-full max-w-md">
                
                <!-- Back Button -->
                <a href="{{ route('welcome') }}" class="inline-flex items-center gap-2 text-gray-600 hover:text-[#000080] transition-colors mb-8 fade-in-section transition-delay-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <span class="font-semibold">Back</span>
                </a>

                <!-- Title -->
                <div class="mb-8 fade-in-section transition-delay-200">
                    <h1 class="text-4xl font-[900] text-gray-900 tracking-tight mb-2">Sign In</h1>
                    <p class="text-gray-600 font-medium">Welcome back to SilverCare</p>
                </div>

                <!-- Session Status -->
                @if (session('status'))
                    <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded fade-in-section transition-delay-300">
                        <p class="text-green-700 font-semibold">{{ session('status') }}</p>
                    </div>
                @endif

                <!-- Login Form -->
                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf

                    <!-- Email -->
                    <div class="fade-in-section transition-delay-300">
                        <label for="email" class="block text-sm font-bold text-gray-700 mb-2">Email Address</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                               class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all duration-200 font-medium"
                               placeholder="you@example.com">
                        @error('email')
                            <p class="mt-2 text-sm text-red-600 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div class="fade-in-section transition-delay-400">
                        <label for="password" class="block text-sm font-bold text-gray-700 mb-2">Password</label>
                        <input id="password" type="password" name="password" required autocomplete="current-password"
                               class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all duration-200 font-medium"
                               placeholder="Enter your password">
                        @error('password')
                            <p class="mt-2 text-sm text-red-600 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="flex items-center justify-between fade-in-section transition-delay-500">
                        <label class="flex items-center">
                            <input type="checkbox" name="remember" class="w-4 h-4 rounded border-gray-300 text-[#000080] focus:ring-[#000080]">
                            <span class="ml-2 text-sm text-gray-700 font-medium">Remember me</span>
                        </label>

                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-sm font-bold text-[#000080] hover:text-blue-900 transition-colors">
                                Forgot password?
                            </a>
                        @endif
                    </div>

                    <!-- Sign In Button -->
                    <div class="fade-in-section transition-delay-600">
                        <button type="submit" class="group relative w-full">
                            <div class="absolute -inset-0.5 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-[82px] opacity-50 blur transition duration-200 group-hover:opacity-75"></div>
                            <div class="relative w-full py-4 bg-[#000080] text-white font-[800] text-xl rounded-[82px] shadow-[0_8px_20px_rgba(0,0,128,0.3)] transition-all duration-300 transform group-hover:-translate-y-1 group-active:scale-95">
                                SIGN IN
                            </div>
                        </button>
                    </div>

                    <!-- Register Link -->
                    <div class="text-center pt-4 fade-in-section transition-delay-700">
                        <p class="text-gray-600">
                            Don't have an account? 
                            <a href="{{ route('register') }}" class="font-bold text-[#000080] hover:text-blue-900 transition-colors">
                                Sign Up
                            </a>
                        </p>
                    </div>
                </form>

            </div>
        </div>

        <!-- Right Side: Hero Image -->
        <div class="hidden lg:block lg:w-7/12 relative overflow-hidden bg-gray-900">
            <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1576765608535-5f04d1e3f289?q=80&w=2048&auto=format&fit=crop')] bg-cover bg-center opacity-60 transition-transform duration-[10s] ease-linear hover:scale-110"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-[#DEDEDE] via-[#DEDEDE]/20 to-transparent"></div>
            
            <div class="absolute bottom-24 left-16 max-w-lg fade-in-section transition-delay-700 z-20">
                <div class="bg-black/60 backdrop-blur-md border-l-4 border-[#000080] p-8 rounded-r-2xl shadow-2xl transform transition hover:translate-x-2 duration-300">
                    <h3 class="text-3xl font-bold text-white mb-3 drop-shadow-lg">Track. Monitor. Care.</h3>
                    <p class="text-gray-100 text-lg font-medium leading-relaxed drop-shadow-md">
                        "Managing elderly care has never been easier. Sign in to continue your journey."
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const observerOptions = {
                root: null,
                rootMargin: '0px',
                threshold: 0.1
            };

            const observer = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        if (entry.target.classList.contains('transition-delay-100')) {
                            setTimeout(() => entry.target.classList.add('is-visible'), 100);
                        } else if (entry.target.classList.contains('transition-delay-200')) {
                            setTimeout(() => entry.target.classList.add('is-visible'), 300);
                        } else if (entry.target.classList.contains('transition-delay-300')) {
                            setTimeout(() => entry.target.classList.add('is-visible'), 500);
                        } else if (entry.target.classList.contains('transition-delay-400')) {
                            setTimeout(() => entry.target.classList.add('is-visible'), 600);
                        } else if (entry.target.classList.contains('transition-delay-500')) {
                            setTimeout(() => entry.target.classList.add('is-visible'), 700);
                        } else if (entry.target.classList.contains('transition-delay-600')) {
                            setTimeout(() => entry.target.classList.add('is-visible'), 800);
                        } else if (entry.target.classList.contains('transition-delay-700')) {
                            setTimeout(() => entry.target.classList.add('is-visible'), 900);
                        } else {
                            entry.target.classList.add('is-visible');
                        }
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);

            document.querySelectorAll('.fade-in-section').forEach((section) => {
                observer.observe(section);
            });
        });
    </script>
</body>
</html>