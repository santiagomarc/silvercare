{{-- Converted to the SilverCare design system. See FRONTEND_DESIGN_SYSTEM.md §3. --}}
<x-guest-layout sc>
    <span class="sc-plate sc-plate-sm mb-5">
        <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-bell"/></svg>
    </span>

    <h1 class="sc-h3">{{ __('Check your email') }}</h1>

    <p class="mt-3" style="color:var(--sc-body)">
        {{ __('We sent you a link to confirm your email address. Click it and you are in. If it has not arrived in a few minutes, we can send another.') }}
    </p>

    @if (session('status') == 'verification-link-sent')
        <p class="mt-6 flex items-start gap-2.5 p-4 rounded-2xl"
           style="background:var(--sc-ok-tint);border:1px solid var(--sc-ok-line);color:var(--sc-ok)"
           role="status">
            <svg class="sc-i w-5 h-5 mt-0.5" aria-hidden="true" focusable="false"><use href="#i-check-circle"/></svg>
            <span class="font-medium">{{ __('A new link is on its way to your inbox.') }}</span>
        </p>
    @endif

    <form method="POST" action="{{ route('verification.send') }}" class="mt-8">
        @csrf
        <x-primary-button class="w-full">
            {{ __('Send the link again') }}
        </x-primary-button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-6 text-center">
        @csrf
        <button type="submit" class="sc-textlink">{{ __('Sign out') }}</button>
    </form>
</x-guest-layout>
