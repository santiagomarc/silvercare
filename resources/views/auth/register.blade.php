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
            <div class="text-center mb-8">
                <a href="{{ route('welcome') }}" class="inline-flex items-center gap-2 text-gray-600 hover:text-[#000080] transition-colors mb-6">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <span class="font-semibold">Back to Home</span>
                </a>
                <h1 class="text-4xl md:text-5xl font-[900] text-gray-900 tracking-tight mb-2">Create Account</h1>
                <p class="text-gray-600 font-medium">Join SilverCare today</p>
            </div>

            <form method="POST" action="{{ route('register') }}" x-data="{ addCaregiver: false }" class="space-y-6">
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

                <!-- Two Column Layout for Fields -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 fade-in-section transition-delay-200">
                    
                    <!-- Left Column -->
                    <div class="space-y-5">
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
                            <label for="phone_number" class="block text-sm font-bold text-gray-700 mb-2">Phone Number</label>
                            <input id="phone_number" type="tel" name="phone_number" value="{{ old('phone_number') }}" required
                                   class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all duration-200 font-medium"
                                   placeholder="+1234567890">
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-bold text-gray-700 mb-2">Password</label>
                            <input id="password" type="password" name="password" required
                                   class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all duration-200 font-medium"
                                   placeholder="Enter password">
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-5">
                        <div>
                            <label for="username" class="block text-sm font-bold text-gray-700 mb-2">Username</label>
                            <input id="username" type="text" name="username" value="{{ old('username') }}" required
                                   class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all duration-200 font-medium"
                                   placeholder="johndoe123">
                        </div>

                        <div>
                            <label for="sex" class="block text-sm font-bold text-gray-700 mb-2">Sex</label>
                            <select id="sex" name="sex" required
                                    class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all duration-200 font-medium">
                                <option value="">Select</option>
                                <option value="Male" {{ old('sex') == 'Male' ? 'selected' : '' }}>Male</option>
                                <option value="Female" {{ old('sex') == 'Female' ? 'selected' : '' }}>Female</option>
                            </select>
                        </div>

                        <div>
                            <label for="address" class="block text-sm font-bold text-gray-700 mb-2">Address</label>
                            <input id="address" type="text" name="address" value="{{ old('address') }}"
                                   class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all duration-200 font-medium"
                                   placeholder="123 Main St, City">
                        </div>

                        <div>
                            <label for="age" class="block text-sm font-bold text-gray-700 mb-2">Age</label>
                            <input id="age" type="number" name="age" value="{{ old('age') }}" min="1" max="150"
                                   class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all duration-200 font-medium"
                                   placeholder="65">
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-sm font-bold text-gray-700 mb-2">Confirm Password</label>
                            <input id="password_confirmation" type="password" name="password_confirmation" required
                                   class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all duration-200 font-medium"
                                   placeholder="Confirm password">
                        </div>
                    </div>

                </div>

                <!-- Divider -->
                <div class="border-t-2 border-gray-100 my-6"></div>

                <!-- Caregiver Checkbox (Full Width, Centered) -->
                <div class="flex justify-center fade-in-section transition-delay-400">
                    <label class="flex items-center gap-3 p-4 bg-blue-50 rounded-lg cursor-pointer border-2 border-transparent hover:border-[#000080] transition-all duration-200 max-w-md w-full">
                        <input type="checkbox" name="add_caregiver" value="1" x-model="addCaregiver"
                               class="w-5 h-5 rounded border-gray-300 text-[#000080] focus:ring-[#000080]">
                        <div>
                            <span class="text-sm font-bold text-gray-900">I have a caregiver</span>
                            <p class="text-xs text-gray-600 font-medium">They'll receive a password reset email</p>
                        </div>
                    </label>
                </div>

                <!-- Caregiver Details (Conditional, Centered) -->
                <div x-show="addCaregiver" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     class="max-w-2xl mx-auto space-y-5 p-6 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl border-2 border-[#000080]/20">
                    
                    <h3 class="font-bold text-xl text-gray-900 text-center mb-4">Caregiver Information</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label for="caregiver_name" class="block text-sm font-bold text-gray-700 mb-2">Caregiver's Full Name</label>
                            <input id="caregiver_name" type="text" name="caregiver_name" value="{{ old('caregiver_name') }}"
                                   class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all duration-200 font-medium bg-white"
                                   placeholder="Jane Doe">
                        </div>

                        <div>
                            <label for="caregiver_email" class="block text-sm font-bold text-gray-700 mb-2">Caregiver's Email</label>
                            <input id="caregiver_email" type="email" name="caregiver_email" value="{{ old('caregiver_email') }}"
                                   class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all duration-200 font-medium bg-white"
                                   placeholder="caregiver@example.com">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label for="caregiver_phone" class="block text-sm font-bold text-gray-700 mb-2">Caregiver's Phone</label>
                            <input id="caregiver_phone" type="tel" name="caregiver_phone" value="{{ old('caregiver_phone') }}"
                                   class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all duration-200 font-medium bg-white"
                                   placeholder="+1234567890">
                        </div>

                        <div>
                            <label for="caregiver_age" class="block text-sm font-bold text-gray-700 mb-2">Caregiver's Age</label>
                            <input id="caregiver_age" type="number" name="caregiver_age" value="{{ old('caregiver_age') }}" min="1" max="150"
                                   class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all duration-200 font-medium bg-white"
                                   placeholder="45">
                        </div>
                    </div>

                    <div>
                        <label for="caregiver_address" class="block text-sm font-bold text-gray-700 mb-2">Caregiver's Address</label>
                        <input id="caregiver_address" type="text" name="caregiver_address" value="{{ old('caregiver_address') }}"
                               class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all duration-200 font-medium bg-white"
                               placeholder="123 Main St, City">
                    </div>

                    <div>
                        <label for="caregiver_relationship" class="block text-sm font-bold text-gray-700 mb-2">Relationship</label>
                        <select id="caregiver_relationship" name="caregiver_relationship"
                                class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all duration-200 font-medium bg-white">
                            <option value="">Select relationship</option>
                            <option value="Spouse" {{ old('caregiver_relationship') == 'Spouse' ? 'selected' : '' }}>Spouse</option>
                            <option value="Child" {{ old('caregiver_relationship') == 'Child' ? 'selected' : '' }}>Child</option>
                            <option value="Professional Caregiver" {{ old('caregiver_relationship') == 'Professional Caregiver' ? 'selected' : '' }}>Professional Caregiver</option>
                        </select>
                    </div>
                </div>

                <!-- Register Button (Centered) -->
                <div class="pt-6 flex justify-center fade-in-section transition-delay-500">
                    <button type="submit" class="group relative w-full max-w-md">
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
                            'transition-delay-100': 100, 'transition-delay-200': 300, 'transition-delay-300': 500,
                            'transition-delay-400': 600, 'transition-delay-500': 700, 'transition-delay-600': 800
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
    </script>
</body>
</html>
                