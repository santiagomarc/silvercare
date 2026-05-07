<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#000080">
    <title>{{ $title ?? config('app.name', 'SilverCare') }}</title>

    <link rel="icon" type="image/png" href="{{ asset('assets/icons/silvercare.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/icons/silvercare.png') }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @stack('styles')
    @stack('head-scripts')

    {{-- Push the back-button trap state immediately, before Alpine or any framework boots.
         This must be in <head> so it runs synchronously before any popstate listener
         could fire. The key is to push a sentinel state on top of the history stack
         the moment this page loads, so the first "back" pops US, not the login page. --}}
    @if(request()->routeIs('caregiver.dashboard') || request()->routeIs('dashboard'))
    <script>
        // Only push the trap once per page load. We check that the current state
        // is not already our trap to avoid stacking multiple sentinels on refresh.
        (function() {
            if (!history.state || history.state.silvercareTrap !== true) {
                history.pushState({ silvercareTrap: true }, '', window.location.href);
            }
        })();
    </script>
    @endif
</head>
<body class="{{ $bodyClass ?? 'bg-gradient-to-br from-slate-100 via-sky-50 to-rose-50 min-h-screen dark:bg-slate-950 dark:bg-none dark:text-slate-100' }}" style="font-family: 'Montserrat', sans-serif;">

    {{-- Root Page Back Button Interceptor --}}
    @if(request()->routeIs('caregiver.dashboard') || request()->routeIs('dashboard'))
        <x-logout-confirm-modal />
    @endif

    <a href="#main-content" class="skip-nav">Skip to main content</a>

    {{ $slot }}

    @stack('scripts')
</body>
</html>