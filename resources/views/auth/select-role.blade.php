<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Select Role - SilverCare</title>

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
<body class="antialiased bg-[#DEDEDE] relative min-h-screen">
    <div class="fixed inset-0 bg-[url('https://images.unsplash.com/photo-1576765608535-5f04d1e3f289?q=80&w=2048&auto=format&fit=crop')] bg-cover bg-center opacity-25"></div>
    <div class="fixed inset-0 bg-gradient-to-br from-[#DEDEDE]/85 via-[#DEDEDE]/70 to-blue-100/40"></div>

    <div class="relative z-10 min-h-screen flex items-center justify-center px-4 py-10">
        <div class="w-full max-w-xl bg-white rounded-3xl shadow-[0_20px_60px_rgba(0,0,0,0.15)] p-8 md:p-10">
            <h1 class="text-3xl md:text-4xl font-[900] text-gray-900 tracking-tight mb-2">Choose Your Role</h1>
            <p class="text-gray-600 font-medium mb-8">We will tailor your SilverCare experience based on your role.</p>

            @if ($errors->any())
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded">
                    <ul class="text-sm text-red-700 list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('auth.select-role.store') }}" class="space-y-6">
                @csrf

                <fieldset>
                    <legend class="block text-sm font-bold text-gray-700 mb-3">I am joining as</legend>

                    <label class="flex items-start gap-3 p-4 bg-blue-50 rounded-xl border-2 border-transparent hover:border-[#000080] transition-all duration-200 cursor-pointer mb-3">
                        <input type="radio" name="user_type" value="elderly" {{ old('user_type') === 'elderly' ? 'checked' : '' }} class="mt-1 w-4 h-4 text-[#000080] focus:ring-[#000080]" required>
                        <span>
                            <span class="block font-bold text-gray-900">Elderly / Patient</span>
                            <span class="text-sm text-gray-600">I want to track my own medication and wellness.</span>
                        </span>
                    </label>

                    <label class="flex items-start gap-3 p-4 bg-indigo-50 rounded-xl border-2 border-transparent hover:border-[#000080] transition-all duration-200 cursor-pointer">
                        <input type="radio" name="user_type" value="caregiver" {{ old('user_type') === 'caregiver' ? 'checked' : '' }} class="mt-1 w-4 h-4 text-[#000080] focus:ring-[#000080]" required>
                        <span>
                            <span class="block font-bold text-gray-900">Caregiver</span>
                            <span class="text-sm text-gray-600">I support and monitor an elderly patient.</span>
                        </span>
                    </label>
                </fieldset>

                <button type="submit" class="group relative w-full">
                    <div class="absolute -inset-0.5 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-[82px] opacity-50 blur transition duration-200 group-hover:opacity-75"></div>
                    <div class="relative w-full py-4 bg-[#000080] text-white font-[800] text-xl rounded-[82px] shadow-[0_8px_20px_rgba(0,0,128,0.3)] transition-all duration-300 transform group-hover:-translate-y-1 group-active:scale-95">
                        CONTINUE
                    </div>
                </button>
            </form>
        </div>
    </div>
</body>
</html>
