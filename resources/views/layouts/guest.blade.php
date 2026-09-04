{{-- Guest layout — every page that uses it is on the SilverCare design system,
     so there is no longer an opt-in flag or a legacy branch here.

     The `sc` prop is still accepted (and ignored) purely so an old
     `<x-guest-layout sc>` call site keeps working; drop it from call sites
     when convenient. See FRONTEND_DESIGN_SYSTEM.md. --}}
@props(['sc' => true])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#FAF9F6" media="(prefers-color-scheme: light)">
        <meta name="theme-color" content="#0B1220" media="(prefers-color-scheme: dark)">

        <title>{{ $title ?? config('app.name', 'SilverCare') }}</title>

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
</html>
