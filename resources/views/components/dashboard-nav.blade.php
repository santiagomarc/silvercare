{{--
    The app bar — shared chrome for every signed-in page.

    Two blocks come out of this component:

      1. <header class="sc-appbar">  the sticky bar: brand, reader controls,
         and the handful of actions that belong to every page.
      2. <div class="sc-appbar-head">  the title band underneath, which holds
         the page's one <h1>. It is NOT sticky — it scrolls away with the
         content it names.

    Because the bar owns the <h1>, a page that uses this component must not
    declare one of its own; its section headings start at <h2>.
    See REDESIGN_PLAN.md §2.

    Props:
        - title: string — the page's heading
        - subtitle: string|null — one line under it
        - role: 'elderly'|'caregiver' — decides which actions appear
        - unreadNotifications: int — count on the bell (elderly only)
        - showBack, backUrl, backLabel — caregiver sub-pages
--}}

@props([
    'title' => 'Dashboard',
    'subtitle' => null,
    'role' => 'elderly',
    'unreadNotifications' => 0,
    'showBack' => false,
    'backUrl' => null,
    'backLabel' => 'Back to Dashboard',
])

@php
    $isCaregiver = $role === 'caregiver';
    $roleLabel = $isCaregiver ? 'Caregiver' : 'Patient';
    $actualBackUrl = $backUrl ?? route('caregiver.dashboard');
    $homeUrl = $isCaregiver ? route('caregiver.dashboard') : route('dashboard');
    $messagesUrl = $isCaregiver ? route('caregiver.messages.index') : route('elderly.messages.index');

    // A caregiver sub-page already spends its bar on "Back"; the account
    // actions stay in the drawer there, exactly as they did before.
    $showAccountActions = ! ($isCaregiver && $showBack);

    $navUser = Auth::user();
    $navPhoto = $navUser->profile?->profile_photo;
    $navInitial = mb_substr($navUser->name, 0, 1);
    $bellLabel = $unreadNotifications > 0
        ? 'Notifications, ' . $unreadNotifications . ' unread'
        : 'Notifications';
@endphp

<header class="sc-appbar" x-data="{ mobileMenuOpen: false }">
    <div class="max-w-[1600px] mx-auto px-6 lg:px-12">
        <div class="flex items-center justify-between gap-4 min-h-[4.5rem]">

            {{-- Brand --}}
            <a href="{{ $homeUrl }}" class="sc-brand">
                <span class="sc-brand-mark">
                    <img src="{{ asset('assets/icons/silvercare.png') }}" alt="">
                </span>
                <span class="sc-brand-word">SilverCare</span>
                <span class="sr-only">SilverCare home</span>
            </a>

            {{-- Actions --}}
            <div class="flex items-center gap-2">

                {{-- SOS. The one loud control in the bar, and the only place
                     in the app where that is the right answer. --}}
                @if(!$isCaregiver)
                    @php $linkedCg = Auth::user()->profile?->caregiver; @endphp
                    @if($linkedCg)
                        <div x-data="{
                                confirming: false,
                                sending: false,
                                async sendSos() {
                                    if (this.sending) return;
                                    this.sending = true;
                                    try {
                                        const resp = await fetch('{{ route('elderly.sos') }}', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'Accept': 'application/json',
                                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                            },
                                        });
                                        const data = await resp.json();
                                        if (data.success) {
                                            Alpine.store('toast')?.success('SOS sent to your caregiver!');
                                            if (navigator.vibrate) navigator.vibrate([200, 100, 200]);
                                        } else {
                                            Alpine.store('toast')?.error(data.message || 'Failed to send SOS');
                                        }
                                    } catch (e) {
                                        Alpine.store('toast')?.error('Failed to send SOS. Try calling your caregiver.');
                                    } finally {
                                        this.sending = false;
                                        this.confirming = false;
                                    }
                                }
                            }"
                            class="relative">
                            <button type="button"
                                    x-show="!confirming"
                                    @click="confirming = true"
                                    class="sc-btn sc-btn-danger sc-btn-sm">
                                <x-lucide-siren class="sc-i sc-sos-glyph w-5 h-5" aria-hidden="true" />
                                SOS
                            </button>
                            <div x-show="confirming"
                                 @keydown.escape.window="confirming = false"
                                 role="dialog"
                                 aria-modal="true"
                                 aria-label="Send an emergency alert"
                                 x-cloak
                                 class="sc-sos-pop p-4 sc-card sc-card-pop z-50">
                                <p class="font-medium mb-3" style="color:var(--sc-ink)">Send emergency alert now?</p>
                                <div class="flex items-center gap-2">
                                    <button type="button" @click="sendSos()" :disabled="sending" class="sc-btn sc-btn-danger sc-btn-sm">
                                        <span x-show="!sending">Yes, send</span>
                                        <span x-show="sending" x-cloak>Sending…</span>
                                    </button>
                                    <button type="button" @click="confirming = false" class="sc-btn sc-btn-ghost sc-btn-sm">Cancel</button>
                                </div>
                            </div>
                        </div>
                    @endif
                @endif

                {{-- Notifications and Messages fold into the drawer on a
                     narrow screen, or when the reader has turned the text up.
                     The Display menu does not: it is how someone turns the
                     text up in the first place, and a phone is exactly where
                     they need it. --}}
                @if(!$isCaregiver)
                        <a href="{{ route('elderly.notifications.index') }}"
                           class="sc-appbar-desktop sc-icon-btn relative"
                           title="Notifications"
                           aria-label="{{ $bellLabel }}">
                            <x-lucide-bell class="sc-i w-6 h-6" aria-hidden="true" />
                            @if($unreadNotifications > 0)
                                <span class="sc-count sc-num" aria-hidden="true">{{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}</span>
                            @endif
                        </a>
                @endif

                {{-- Messages --}}
                <a href="{{ $messagesUrl }}" class="sc-appbar-desktop sc-icon-btn" title="Messages" aria-label="Messages">
                    <x-lucide-message-square class="sc-i w-6 h-6" aria-hidden="true" />
                </a>

                {{-- Display and accessibility controls. One labelled menu
                     rather than three loose icon buttons: the bar stays quiet
                     and the controls stay findable, which matters most for the
                     readers who actually need them. --}}
                <div class="relative" x-data="displayControls()" @keydown.escape.window="open = false">
                    <button type="button"
                            class="sc-icon-btn"
                            aria-expanded="false"
                            :aria-expanded="open ? 'true' : 'false'"
                            aria-controls="sc-appbar-display"
                            aria-haspopup="true"
                            aria-label="Display and accessibility options"
                            title="Display options"
                            @click="open = !open">
                        <x-lucide-accessibility class="sc-i w-6 h-6" aria-hidden="true" />
                    </button>

                    <div id="sc-appbar-display"
                         x-show="open" x-cloak
                         @click.outside="open = false"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="absolute right-0 mt-3 p-5 space-y-5 sc-card sc-card-pop sc-display-menu z-50"
                         role="group" aria-label="Display and accessibility settings">

                        <div>
                            <p class="flex items-center gap-2 font-semibold mb-2.5" style="color:var(--sc-ink)">
                                <x-lucide-type class="sc-i w-5 h-5" aria-hidden="true" />
                                Text size
                            </p>
                            <div class="grid grid-cols-3 gap-2" role="group" aria-label="Text size">
                                <template x-for="opt in scales" :key="opt.value">
                                    <button type="button"
                                            @click="setScale(opt.value)"
                                            :aria-pressed="scale === opt.value ? 'true' : 'false'"
                                            :aria-label="opt.aria"
                                            class="sc-size-btn"
                                            :class="scale === opt.value && 'sc-size-btn-on'">
                                        <span :style="`font-size:${opt.preview}`" x-text="opt.label"></span>
                                        <span class="text-sm leading-none" x-text="opt.name"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-4">
                            <span class="flex items-center gap-2 font-medium" id="sc-appbar-dark-label" style="color:var(--sc-ink)">
                                <x-lucide-moon class="sc-i w-5 h-5" aria-hidden="true" />
                                Dark mode
                            </span>
                            <button type="button" role="switch"
                                    class="sc-switch"
                                    aria-labelledby="sc-appbar-dark-label"
                                    aria-checked="false"
                                    :aria-checked="dark ? 'true' : 'false'"
                                    @click="toggleDark()"></button>
                        </div>

                        <div class="flex items-center justify-between gap-4">
                            <span class="flex items-center gap-2 font-medium" id="sc-appbar-contrast-label" style="color:var(--sc-ink)">
                                <x-lucide-contrast class="sc-i w-5 h-5" aria-hidden="true" />
                                High contrast
                            </span>
                            <button type="button" role="switch"
                                    class="sc-switch"
                                    aria-labelledby="sc-appbar-contrast-label"
                                    aria-checked="false"
                                    :aria-checked="contrast ? 'true' : 'false'"
                                    @click="toggleContrast()"></button>
                        </div>

                        <p class="text-sm" style="color:var(--sc-muted)">
                            Your choice is remembered on this device.
                        </p>
                    </div>
                </div>

                {{-- Back to Dashboard (caregiver sub-pages) --}}
                @if($isCaregiver && $showBack)
                    <span class="sc-appbar-desktop sc-appbar-rule" aria-hidden="true"></span>
                    <a href="{{ $actualBackUrl }}" class="sc-appbar-desktop sc-btn sc-btn-ghost sc-btn-sm">
                        <x-lucide-arrow-left class="sc-i w-5 h-5" aria-hidden="true" />
                        {{ $backLabel }}
                    </a>
                @endif

                @if($showAccountActions)
                    <span class="sc-appbar-desktop sc-appbar-rule" aria-hidden="true"></span>

                    {{-- Profile --}}
                    <a href="{{ route('profile.edit') }}" class="sc-appbar-desktop sc-appbar-profile" title="Manage Profile">
                        <span class="sc-avatar">
                            @if($navPhoto)
                                <img src="{{ Storage::url($navPhoto) }}" alt="">
                            @else
                                {{ $navInitial }}
                            @endif
                        </span>
                        <span class="block">
                            <span class="sc-appbar-name block">{{ $navUser->name }}</span>
                            <span class="sc-appbar-role block">{{ $roleLabel }}</span>
                        </span>
                        <span class="sr-only">Manage your profile</span>
                    </a>

                    {{-- Log out --}}
                    <form method="POST" action="{{ route('logout') }}" class="sc-appbar-desktop">
                        @csrf
                        <button type="submit" class="sc-btn sc-btn-ghost sc-btn-sm">Log Out</button>
                    </form>
                @endif

                {{-- Drawer trigger --}}
                <button type="button"
                        class="sc-appbar-toggle sc-icon-btn"
                        @click="mobileMenuOpen = true"
                        aria-expanded="false"
                        :aria-expanded="mobileMenuOpen ? 'true' : 'false'"
                        aria-controls="sc-appbar-menu"
                        aria-label="Open menu">
                    <x-lucide-menu class="sc-i w-6 h-6" aria-hidden="true" />
                </button>

            </div>
        </div>
    </div>

    {{-- Drawer --}}
    <div id="sc-appbar-menu"
         class="sc-appbar-drawer"
         x-show="mobileMenuOpen"
         x-cloak
         role="dialog"
         aria-modal="true"
         aria-label="Menu">

        <div class="sc-scrim"
             x-show="mobileMenuOpen"
             x-transition.opacity
             @click="mobileMenuOpen = false"></div>

        <div class="sc-drawer"
             x-show="mobileMenuOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full"
             @click.away="mobileMenuOpen = false">

            <div class="sc-drawer-head">
                <p class="sc-dialog-title">Menu</p>
                <button type="button" @click="mobileMenuOpen = false" class="sc-icon-btn" aria-label="Close menu">
                    <x-lucide-x class="sc-i w-6 h-6" aria-hidden="true" />
                </button>
            </div>

            <div class="p-4">
                <div class="flex items-center gap-3 px-1 pb-4 mb-2" style="border-bottom:1px solid var(--sc-line)">
                    <span class="sc-avatar sc-avatar-lg">
                        @if($navPhoto)
                            <img src="{{ Storage::url($navPhoto) }}" alt="">
                        @else
                            {{ $navInitial }}
                        @endif
                    </span>
                    <span class="block">
                        <span class="block font-semibold" style="color:var(--sc-ink)">{{ $navUser->name }}</span>
                        <span class="sc-appbar-role block">{{ $roleLabel }}</span>
                    </span>
                </div>

                <nav class="space-y-1" aria-label="Account">
                    <a href="{{ route('profile.edit') }}" class="sc-drawer-link">
                        <x-lucide-user-round class="sc-i w-5 h-5" aria-hidden="true" />
                        Manage Profile
                    </a>

                    <a href="{{ $messagesUrl }}" class="sc-drawer-link">
                        <x-lucide-message-square class="sc-i w-5 h-5" aria-hidden="true" />
                        Messages
                    </a>

                    @if(!$isCaregiver)
                        <a href="{{ route('elderly.notifications.index') }}" class="sc-drawer-link">
                            <x-lucide-bell class="sc-i w-5 h-5" aria-hidden="true" />
                            Notifications
                            @if($unreadNotifications > 0)
                                <span class="sc-badge sc-badge-alert sc-num ml-auto">{{ $unreadNotifications }} new</span>
                            @endif
                        </a>
                    @endif

                    @if($isCaregiver && $showBack)
                        <a href="{{ $actualBackUrl }}" class="sc-drawer-link">
                            <x-lucide-arrow-left class="sc-i w-5 h-5" aria-hidden="true" />
                            {{ $backLabel }}
                        </a>
                    @endif
                </nav>

                <div class="pt-4 mt-3" style="border-top:1px solid var(--sc-line)">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="sc-btn sc-btn-ghost sc-btn-sm w-full">
                            <x-lucide-log-out class="sc-i w-5 h-5" aria-hidden="true" />
                            Log Out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>

{{-- The title band. Not sticky: it belongs to the page, not the chrome. --}}
<div class="sc-appbar-head">
    <div class="max-w-[1600px] mx-auto px-6 lg:px-12">
        <h1 class="sc-page-title">{{ $title }}</h1>
        @if(!empty($subtitle))
            <p class="sc-appbar-sub">{{ $subtitle }}</p>
        @endif
    </div>
</div>
