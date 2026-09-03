{{-- Pass `sc` to opt a page into the SilverCare design system:

         <x-dashboard-layout sc>

     That swaps the body onto the token layer (`sc-page`) and the new
     typefaces. Pages without the flag render exactly as before, so the
     redesign can move one page at a time.
     See FRONTEND_DESIGN_SYSTEM.md. --}}
@props(['title' => null, 'bodyClass' => null, 'sc' => false])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['scroll-smooth' => $sc])>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
        {{-- C3: identifies which Reverb channel this page should subscribe to. --}}
        <meta name="sc-profile"
              data-profile-id="{{ auth()->user()->profile?->id }}"
              data-user-type="{{ auth()->user()->profile?->user_type }}">
    @endauth
    <meta name="theme-color" content="#000080">
    <title>{{ $title ?? config('app.name', 'SilverCare') }}</title>

    <link rel="icon" type="image/png" href="{{ asset('assets/icons/silvercare.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/icons/silvercare.png') }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">

    @include('partials.sc-theme-boot')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    @if ($sc)
        <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@500;600;700;800&family=Newsreader:ital,opsz,wght@1,6..72,400;1,6..72,500&display=swap" rel="stylesheet">
        @include('partials.sc-fonts')
    @else
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    @endif

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
@if ($sc)
<body class="sc-page antialiased min-h-screen {{ $bodyClass }}">
@else
<body class="{{ $bodyClass ?? 'bg-gradient-to-br from-slate-100 via-sky-50 to-rose-50 min-h-screen dark:bg-slate-950 dark:bg-none dark:text-slate-100' }}" style="font-family: 'Montserrat', sans-serif;">
@endif

    {{-- Root Page Back Button Interceptor --}}
    @if(request()->routeIs('caregiver.dashboard') || request()->routeIs('dashboard'))
        <x-logout-confirm-modal />
    @endif

    <a href="#main-content" class="{{ $sc ? 'sc-skip' : 'skip-nav' }}">Skip to main content</a>

    @if ($sc)
        @include('partials.sc-icons')
    @endif

    {{ $slot }}

    @stack('scripts')
</body>
</html>