{{-- Dashboard layout. Every page that uses it is on the SilverCare design
     system: there is no opt-in flag and no legacy branch. The Montserrat/grey
     body this file used to carry alongside the real one is gone, along with
     the `sc` prop that chose between them.

     See FRONTEND_DESIGN_SYSTEM.md. --}}
@props(['title' => null, 'bodyClass' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
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
    {{-- IBM Plex Mono carries measurements only — dose, time, unit — so a
         column of readings lines up and reads as instrument output rather
         than prose. Two weights, nothing more. --}}
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@500;600;700;800&family=IBM+Plex+Mono:wght@400;500&family=Newsreader:ital,opsz,wght@1,6..72,400;1,6..72,500&display=swap" rel="stylesheet">
    @include('partials.sc-fonts')

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
<body class="sc-page antialiased min-h-screen {{ $bodyClass }}">

    {{-- Root Page Back Button Interceptor --}}
    @if(request()->routeIs('caregiver.dashboard') || request()->routeIs('dashboard'))
        <x-logout-confirm-modal />
    @endif

    <a href="#main-content" class="sc-skip">Skip to main content</a>

    {{-- The shared components (x-input-error and friends) reference sprite
         icons, so the sprite must be present. It renders nothing on its own. --}}
    @include('partials.sc-icons')

    {{ $slot }}

    @stack('scripts')
</body>
</html>