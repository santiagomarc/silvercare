<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Set Your Password - SilverCare</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('assets/icons/silvercare.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/icons/silvercare.png') }}">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Montserrat', sans-serif; }
    </style>
</head>
<body class="antialiased bg-[#DEDEDE] relative">

    <!-- Background Image -->
    <div class="fixed inset-0 bg-[url('https://images.unsplash.com/photo-1576765608535-5f04d1e3f289?q=80&w=2048&auto=format&fit=crop')] bg-cover bg-center opacity-30"></div>
    <div class="fixed inset-0 bg-gradient-to-br from-[#DEDEDE]/80 via-[#DEDEDE]/60 to-blue-100/40"></div>

    <div class="min-h-screen w-full flex items-center justify-center px-4 py-12 relative z-10">
        
        <div class="w-full max-w-md bg-white rounded-3xl shadow-[0_20px_60px_rgba(0,0,0,0.15)] p-8 md:p-10">
            
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-[#000080] to-blue-600 rounded-full mb-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <h1 class="text-3xl md:text-4xl font-[900] text-gray-900 tracking-tight mb-2">Set Your Password</h1>
                <p class="text-gray-600 font-medium">Welcome to SilverCare, {{ $user->name }}</p>
            </div>

            <!-- Success Message -->
            <div class="bg-blue-50 border-l-4 border-[#000080] p-4 rounded mb-6">
                <p class="text-sm text-gray-700 font-medium">
                    You've been invited as a caregiver. Please create a secure password to access your account.
                </p>
            </div>

            <form method="POST" action="{{ route('caregiver.password.store', $user->id) }}" class="space-y-5">
                @csrf

                <!-- Email (Read-only) -->
                <div>
                    <label for="email" class="block text-sm font-bold text-gray-700 mb-2">Email Address</label>
                    <input id="email" type="email" value="{{ $email }}" readonly
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 bg-gray-50 font-medium text-gray-600 cursor-not-allowed">
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-bold text-gray-700 mb-2">Password</label>
                    <input id="password" type="password" name="password" required autofocus
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all duration-200 font-medium @error('password') border-red-500 @enderror"
                           placeholder="Enter your password">
                    @error('password')
                        <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="password_confirmation" class="block text-sm font-bold text-gray-700 mb-2">Confirm Password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all duration-200 font-medium"
                           placeholder="Confirm your password">
                </div>

                <!-- Password Requirements -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-xs font-bold text-gray-700 mb-2">Password must contain:</p>
                    <ul class="text-xs text-gray-600 space-y-1">
                        <li>• At least 8 characters</li>
                        <li>• Mix of uppercase and lowercase letters</li>
                        <li>• At least one number</li>
                    </ul>
                </div>

                <!-- Submit Button -->
                <div class="pt-4">
                    <button type="submit" class="group relative w-full">
                        <div class="absolute -inset-0.5 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-[82px] opacity-50 blur transition duration-200 group-hover:opacity-75"></div>
                        <div class="relative w-full py-4 bg-[#000080] text-white font-[800] text-xl rounded-[82px] shadow-[0_8px_20px_rgba(0,0,128,0.3)] transition-all duration-300 transform group-hover:-translate-y-1 group-active:scale-95">
                            SET PASSWORD & CONTINUE
                        </div>
                    </button>
                </div>
            </form>

        </div>
    </div>

</body>
</html>
