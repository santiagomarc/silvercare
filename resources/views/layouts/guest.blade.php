{{-- Pass `sc` to opt a page into the SilverCare design system:
         <x-guest-layout sc>
     See FRONTEND_DESIGN_SYSTEM.md. --}}
@props(['sc' => false])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['scroll-smooth' => $sc])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'SilverCare') }}</title>

        <!-- Favicon -->
        <link rel="icon" type="image/png" href="{{ asset('assets/icons/silvercare.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('assets/icons/silvercare.png') }}">

        @include('partials.sc-theme-boot')

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        @if ($sc)
            <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@500;600;700;800&family=Newsreader:ital,opsz,wght@1,6..72,400;1,6..72,500&display=swap" rel="stylesheet">
            @include('partials.sc-fonts')
        @else
            <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
        @endif

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    @if ($sc)
    <body class="sc-page antialiased">
        @include('partials.sc-icons')
        <a class="sc-skip" href="#main-content">Skip to main content</a>

        <div class="sc-ambient min-h-screen flex flex-col justify-center items-center px-5 py-12">
            <a href="/" class="sc-brand mb-8">
                <span class="sc-brand-mark"><img src="{{ asset('assets/icons/silvercare.png') }}" alt=""></span>
                <span class="sc-brand-word">SilverCare</span>
                <span class="sr-only">SilverCare home</span>
            </a>

            <main id="main-content" class="w-full sm:max-w-md sc-card p-7 sm:p-9">
                {{ $slot }}
            </main>
        </div>
    </body>
    @else
    <body class="font-sans text-gray-900 antialiased" style="font-family: 'Montserrat', sans-serif;">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-[#DEDEDE]">
            <div>
                <a href="/" class="flex flex-col items-center gap-2">
                    <img src="{{ asset('assets/icons/silvercare.png') }}" alt="SilverCare" class="w-20 h-20 object-contain">
                    <h1 class="text-2xl font-black tracking-tight text-gray-900">
                        SILVER<span class="text-[#000080]">CARE</span>
                    </h1>
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
    @endif
</html>
