<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'SilverCare') }}</title>

        <!-- Favicon -->
        <link rel="icon" type="image/png" href="{{ asset('assets/icons/silvercare.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('assets/icons/silvercare.png') }}">

        <!-- LOAD MONTSERRAT (Tailwind needs this to display the font correctly) -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-[#EBEBEB]">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>

            <!-- AI Assistant Floating Button (Only for Elderly) -->
            @if(Auth::check() && Auth::user()->profile?->user_type === 'elderly')
                <a href="{{ route('elderly.ai-assistant.index') }}" class="fixed bottom-6 right-6 w-14 h-14 bg-gradient-to-r from-[#000080] to-blue-600 rounded-full shadow-2xl flex items-center justify-center text-white hover:scale-110 transition-transform z-50 group">
                    <span class="text-2xl group-hover:animate-bounce">🤖</span>
                    <!-- Tooltip -->
                    <div class="absolute right-full mr-4 top-1/2 -translate-y-1/2 bg-gray-900 text-white text-xs font-bold px-3 py-1.5 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">
                        Ask AI Assistant
                        <div class="absolute top-1/2 -right-1 -translate-y-1/2 border-4 border-transparent border-l-gray-900"></div>
                    </div>
                </a>
            @endif
        </div>
    </body>
</html>