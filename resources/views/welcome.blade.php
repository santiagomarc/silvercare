<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#FAF9F6" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0B1220" media="(prefers-color-scheme: dark)">
    <title>SilverCare — Dignified Senior Care & Quiet Peace of Mind</title>
    <meta name="description" content="SilverCare gives older adults effortless daily independence at home, while giving family members quiet, real-time reassurance without anxious phone calls.">

    <link rel="icon" type="image/png" href="{{ asset('assets/icons/silvercare.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/icons/silvercare.png') }}">

    @include('partials.sc-theme-boot')

    {{-- Prompt (display) and Newsreader (editorial quote) from Google; Valley
         Sans (body) is self-hosted. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@500;600;700;800&family=Newsreader:ital,opsz,wght@1,6..72,400;1,6..72,500&display=swap" rel="stylesheet">
    @include('partials.sc-fonts')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="sc-page antialiased selection:bg-[#000080] selection:text-white">

<a class="sc-skip" href="#main-content">Skip to main content</a>

@include('partials.sc-icons')

{{-- ════════════════════════════════════════════════════════════════════
     HEADER — persistent orientation and a persistent way to sign in.
     The previous page had no navigation at all, which left keyboard and
     screen-reader users with no route to the auth pages from the top of
     the document.
     ════════════════════════════════════════════════════════════════════ --}}
<header class="sticky top-0 z-50 sc-header"
        x-data="{ stuck: false, mobile: false }"
        x-init="stuck = window.scrollY > 8"
        @scroll.window.passive="stuck = window.scrollY > 8"
        :class="stuck && 'is-stuck'">

    <div class="mx-auto max-w-7xl px-5 sm:px-8">
        <div class="flex items-center justify-between gap-4 h-[4.75rem]">

            {{-- Brand --}}
            <a href="#main-content" class="sc-brand">
                <span class="sc-brand-mark">
                    <img src="{{ asset('assets/icons/silvercare.png') }}" alt="">
                </span>
                <span class="sc-brand-word">SilverCare</span>
                <span class="sr-only">SilverCare home</span>
            </a>

            {{-- Primary navigation (desktop) --}}
            <nav class="sc-nav-desktop items-center gap-6" aria-label="Primary">
                <a href="#for-seniors"  class="sc-nav-link whitespace-nowrap">Older adults</a>
                <a href="#for-family"   class="sc-nav-link whitespace-nowrap">Family</a>
                <a href="#emergency"    class="sc-nav-link whitespace-nowrap">Emergency</a>
                <a href="#how-it-works" class="sc-nav-link whitespace-nowrap">How it works</a>
                <a href="#questions"    class="sc-nav-link whitespace-nowrap">Questions</a>
            </nav>

            <div class="flex items-center gap-2 sm:gap-3">

                {{-- Display & accessibility controls.
                     Grouped into one labelled menu rather than three loose icon
                     buttons, so the header stays quiet while the controls stay
                     discoverable — which matters most for the audience that
                     actually needs them. --}}
                <div class="relative" x-data="displayControls()" @keydown.escape.window="open = false">
                    <button type="button"
                            class="sc-btn sc-btn-ghost sc-btn-sm !px-3 sm:!px-4 whitespace-nowrap"
                            aria-expanded="false"
                            :aria-expanded="open ? 'true' : 'false'"
                            aria-controls="sc-display-menu"
                            aria-haspopup="true"
                            @click="open = !open">
                        <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-accessibility"/></svg>
                        <span class="hidden sm:inline">Display</span>
                        <span class="sr-only sm:hidden">Display and accessibility options</span>
                    </button>

                    <div id="sc-display-menu"
                         x-show="open" x-cloak
                         @click.outside="open = false"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="absolute right-0 mt-3 w-[19rem] p-5 space-y-5 sc-card sc-card-pop z-50"
                         role="group" aria-label="Display and accessibility settings">

                        <div>
                            <p class="flex items-center gap-2 font-semibold mb-2.5" style="color:var(--sc-ink)">
                                <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-type"/></svg>
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
                            <span class="flex items-center gap-2 font-medium" id="sc-dark-label" style="color:var(--sc-ink)">
                                <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-moon"/></svg>
                                Dark mode
                            </span>
                            <button type="button" role="switch"
                                    class="sc-switch"
                                    aria-labelledby="sc-dark-label"
                                    aria-checked="false"
                                    :aria-checked="dark ? 'true' : 'false'"
                                    @click="toggleDark()"></button>
                        </div>

                        <div class="flex items-center justify-between gap-4">
                            <span class="flex items-center gap-2 font-medium" id="sc-contrast-label" style="color:var(--sc-ink)">
                                <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-contrast"/></svg>
                                High contrast
                            </span>
                            <button type="button" role="switch"
                                    class="sc-switch"
                                    aria-labelledby="sc-contrast-label"
                                    aria-checked="false"
                                    :aria-checked="contrast ? 'true' : 'false'"
                                    @click="toggleContrast()"></button>
                        </div>

                        <p class="text-sm" style="color:var(--sc-muted)">
                            Your choice is remembered on this device.
                        </p>
                    </div>
                </div>

                <a href="{{ route('login') }}" class="hidden sm:inline-flex sc-btn sc-btn-ghost sc-btn-sm whitespace-nowrap">Sign in</a>
                <a href="{{ route('register') }}" class="hidden md:inline-flex sc-btn sc-btn-primary sc-btn-sm whitespace-nowrap">
                    Get started
                    <svg class="sc-i sc-arrow w-4 h-4" aria-hidden="true" focusable="false"><use href="#i-arrow-right"/></svg>
                </a>

                {{-- Mobile menu trigger --}}
                <button type="button"
                        class="sc-menu-toggle sc-icon-btn"
                        aria-expanded="false"
                        :aria-expanded="mobile ? 'true' : 'false'"
                        aria-controls="sc-mobile-nav"
                        @click="mobile = !mobile">
                    <svg class="sc-i w-6 h-6" x-show="!mobile" aria-hidden="true" focusable="false"><use href="#i-menu"/></svg>
                    <svg class="sc-i w-6 h-6" x-show="mobile" x-cloak aria-hidden="true" focusable="false"><use href="#i-close"/></svg>
                    <span class="sr-only">Menu</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Mobile navigation --}}
    <div id="sc-mobile-nav" x-show="mobile" x-cloak
         @keydown.escape="mobile = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0">
        <nav class="mx-auto max-w-7xl px-5 sm:px-8 pb-6 pt-2" aria-label="Primary, mobile">
            <div class="sm:max-w-md sm:ml-auto">
            <ul class="sc-card p-2.5 sc-divide">
                <li><a href="#for-seniors"  @click="mobile = false" class="flex items-center justify-between px-4 py-4 min-h-[3.25rem] font-medium sc-link">Older adults <svg class="sc-i w-5 h-5 opacity-50" aria-hidden="true" focusable="false"><use href="#i-arrow-right"/></svg></a></li>
                <li><a href="#for-family"   @click="mobile = false" class="flex items-center justify-between px-4 py-4 min-h-[3.25rem] font-medium sc-link">Family <svg class="sc-i w-5 h-5 opacity-50" aria-hidden="true" focusable="false"><use href="#i-arrow-right"/></svg></a></li>
                <li><a href="#emergency"    @click="mobile = false" class="flex items-center justify-between px-4 py-4 min-h-[3.25rem] font-medium sc-link">Emergency <svg class="sc-i w-5 h-5 opacity-50" aria-hidden="true" focusable="false"><use href="#i-arrow-right"/></svg></a></li>
                <li><a href="#how-it-works" @click="mobile = false" class="flex items-center justify-between px-4 py-4 min-h-[3.25rem] font-medium sc-link">How it works <svg class="sc-i w-5 h-5 opacity-50" aria-hidden="true" focusable="false"><use href="#i-arrow-right"/></svg></a></li>
                <li><a href="#questions"    @click="mobile = false" class="flex items-center justify-between px-4 py-4 min-h-[3.25rem] font-medium sc-link">Questions <svg class="sc-i w-5 h-5 opacity-50" aria-hidden="true" focusable="false"><use href="#i-arrow-right"/></svg></a></li>
            </ul>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-4">
                <a href="{{ route('register') }}" class="sc-btn sc-btn-primary w-full">Get started free</a>
                <a href="{{ route('login') }}" class="sc-btn sc-btn-ghost w-full">Sign in</a>
            </div>
            </div>
        </nav>
    </div>
</header>

<main id="main-content">

    {{-- ════════════════════════════════════════════════════════════════
         HERO
         Split rather than centre-stacked: the promise sits on the left,
         the working product sits on the right. Seniors and caregivers
         both see, above the fold, exactly what they would be using.
         ════════════════════════════════════════════════════════════════ --}}
    <section class="sc-ambient pt-14 pb-20 md:pt-20 md:pb-28 overflow-hidden">
        <div class="mx-auto max-w-7xl px-5 sm:px-8">
            {{-- `lg:items-start`, not `items-center`: the preview panel changes
                 height when a tab is switched or the dose is confirmed, and
                 centring made the headline jump every time it did. --}}
            <div class="grid lg:grid-cols-12 gap-14 xl:gap-20 lg:items-start">

                {{-- ── Promise ─────────────────────────────────────── --}}
                <div class="lg:col-span-6 min-w-0 sc-reveal is-in">

                    <h1 class="sc-h1">
                        Care that feels like family.
                        <span style="color:var(--sc-brand-text)">Technology that stays out of the way.</span>
                    </h1>

                    <p class="sc-lead mt-7 max-w-xl">
                        SilverCare gives older adults effortless daily independence at home — and gives
                        family members quiet, real-time reassurance, without the anxious phone calls.
                    </p>

                    <div class="mt-9">
                        <a href="{{ route('register') }}" class="sc-btn sc-btn-primary w-full sm:w-auto">
                            Try SilverCare free
                            <svg class="sc-i sc-arrow w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-arrow-right"/></svg>
                        </a>
                    </div>

                    <ul class="flex flex-wrap items-center gap-x-7 gap-y-3 mt-9" style="color:var(--sc-muted)">
                        <li class="flex items-center gap-2.5">
                            <svg class="sc-i w-5 h-5" style="color:var(--sc-ok)" aria-hidden="true" focusable="false"><use href="#i-check-circle"/></svg>
                            <span>Two-minute setup</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <svg class="sc-i w-5 h-5" style="color:var(--sc-ok)" aria-hidden="true" focusable="false"><use href="#i-check-circle"/></svg>
                            <span>No hardware to buy</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <svg class="sc-i w-5 h-5" style="color:var(--sc-ok)" aria-hidden="true" focusable="false"><use href="#i-check-circle"/></svg>
                            <span>Any phone, tablet or computer</span>
                        </li>
                    </ul>
                </div>

                {{-- ── Living preview ──────────────────────────────── --}}
                <div class="lg:col-span-6 min-w-0" id="dual-experience" x-data="dualView()">

                    <div class="flex flex-wrap items-center gap-4 mb-6">
                        <p class="sc-eyebrow" id="sc-view-label">The same morning, two views</p>

                        <div class="sc-tablist" role="tablist" aria-labelledby="sc-view-label"
                             @keydown.arrow-right.prevent="move(1)"
                             @keydown.arrow-left.prevent="move(-1)"
                             @keydown.home.prevent="select('senior')"
                             @keydown.end.prevent="select('caregiver')">
                            <button type="button" role="tab" class="sc-tab"
                                    id="sc-tab-senior" x-ref="tab_senior"
                                    aria-controls="sc-panel-senior"
                                    aria-selected="true" tabindex="0"
                                    :aria-selected="view === 'senior' ? 'true' : 'false'"
                                    :tabindex="view === 'senior' ? 0 : -1"
                                    @click="select('senior')">Arthur, 78</button>
                            <button type="button" role="tab" class="sc-tab"
                                    id="sc-tab-caregiver" x-ref="tab_caregiver"
                                    aria-controls="sc-panel-caregiver"
                                    aria-selected="false" tabindex="-1"
                                    :aria-selected="view === 'caregiver' ? 'true' : 'false'"
                                    :tabindex="view === 'caregiver' ? 0 : -1"
                                    @click="select('caregiver')">Sarah, his daughter</button>
                        </div>
                    </div>

                    <div class="relative">
                        <span class="sc-frame-shadowplate hidden sm:block" aria-hidden="true"></span>

                        <div class="sc-frame">
                            <div class="sc-frame-bar" aria-hidden="true">
                                <span class="sc-dot"></span><span class="sc-dot"></span><span class="sc-dot"></span>
                                <span class="ml-2 text-sm font-medium" style="color:var(--sc-muted)">SilverCare</span>
                                <span class="ml-auto text-sm sc-num" style="color:var(--sc-muted)">8:04 AM</span>
                            </div>

                            {{-- ── Senior view ───────────────────────── --}}
                            <div id="sc-panel-senior" role="tabpanel" aria-labelledby="sc-tab-senior"
                                 x-show="view === 'senior'"
                                 x-transition:enter="transition ease-out duration-300"
                                 x-transition:enter-start="opacity-0 translate-y-2"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 class="p-6 sm:p-8 space-y-5">

                                <div class="flex flex-col-reverse sm:flex-row sm:items-start sm:justify-between gap-3 sm:gap-4">
                                    <div class="min-w-0">
                                        <p class="sc-eyebrow" style="color:var(--sc-muted)">Wednesday</p>
                                        <p class="sc-display text-[1.6rem] sm:text-[1.9rem] leading-tight mt-1">Good morning, Arthur.</p>
                                    </div>
                                    <span class="sc-chip sc-chip-warn shrink-0 self-start">
                                        <svg class="sc-i w-4 h-4" aria-hidden="true" focusable="false"><use href="#i-sun"/></svg>
                                        Morning
                                    </span>
                                </div>

                                {{-- The one thing that matters right now. Nothing else competes with it. --}}
                                <div class="p-5 sm:p-6 rounded-[1.25rem] border transition-colors duration-300"
                                     :style="taken
                                        ? 'background:var(--sc-ok-tint);border-color:var(--sc-ok-line)'
                                        : 'background:var(--sc-surface-2);border-color:var(--sc-line)'">

                                    <div class="flex flex-col sm:flex-row items-start gap-4">
                                        <span class="sc-plate" :class="taken && 'sc-plate-ok'">
                                            <svg class="sc-i w-6 h-6" aria-hidden="true" focusable="false"><use href="#i-pill"/></svg>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold uppercase tracking-wide" style="color:var(--sc-muted)">
                                                Due 8:00 AM · with breakfast
                                            </p>
                                            <p class="sc-display text-[1.3rem] leading-snug mt-1" style="font-weight:600">
                                                Blood pressure &amp; vitamins
                                            </p>
                                            <p class="mt-1" style="color:var(--sc-body)">Lisinopril, one tablet with a full glass of water</p>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-3 mt-5">
                                        <button type="button" @click="taken = !taken"
                                                class="sc-btn w-full sm:w-auto"
                                                :class="taken ? 'sc-btn-ghost' : 'sc-btn-primary'">
                                            <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false">
                                                <use :href="taken ? '#i-undo' : '#i-check'"/>
                                            </svg>
                                            <span x-text="taken ? 'Undo — mark as not taken' : 'I took this'"></span>
                                        </button>

                                        <p x-show="taken" x-cloak class="flex items-center gap-2 font-medium sc-num" style="color:var(--sc-ok)">
                                            <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-check-circle"/></svg>
                                            Taken at 8:04 AM
                                        </p>
                                    </div>

                                    {{-- The live region is always in the DOM and only its text
                                         changes, so the confirmation is announced without focus
                                         being yanked away from the button. --}}
                                    <p class="sr-only" aria-live="polite"
                                       x-text="taken ? 'Dose confirmed at 8:04 AM. Sarah has been notified.' : ''"></p>

                                    {{-- Always laid out, only sometimes visible: `visibility`
                                         keeps the row's height reserved (no reflow) and still
                                         removes it from the accessibility tree when hidden. --}}
                                    <p class="mt-4 pt-4 border-t text-sm"
                                       :style="`border-color:var(--sc-ok-line);color:var(--sc-ok);visibility:${taken ? 'visible' : 'hidden'}`">
                                        Confirmed. Sarah has been quietly notified — no call needed.
                                    </p>
                                </div>

                                <div class="sc-card-quiet p-4 flex items-center gap-4">
                                    <span class="sc-plate sc-plate-sm">
                                        <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-mic"/></svg>
                                    </span>
                                    <p class="min-w-0">
                                        <span class="font-semibold" style="color:var(--sc-ink)">Voice note from Sarah:</span>
                                        <span style="color:var(--sc-body)">&ldquo;Have a wonderful morning, Dad. Walk at three?&rdquo;</span>
                                    </p>
                                </div>

                                <div class="sc-card-quiet p-4 flex items-center justify-between gap-4">
                                    <div class="flex items-center gap-4 min-w-0">
                                        <span class="sc-plate sc-plate-sm sc-plate-ok">
                                            <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-sprout"/></svg>
                                        </span>
                                        <p class="min-w-0">
                                            <span class="font-semibold block" style="color:var(--sc-ink)">Garden of Wellness</span>
                                            <span class="text-sm" style="color:var(--sc-muted)">Fourteen calm mornings in a row</span>
                                        </p>
                                    </div>
                                    <span class="sc-chip sc-chip-ok shrink-0 sc-num">14 days</span>
                                </div>
                            </div>

                            {{-- ── Caregiver view ────────────────────── --}}
                            <div id="sc-panel-caregiver" role="tabpanel" aria-labelledby="sc-tab-caregiver"
                                 x-show="view === 'caregiver'" x-cloak
                                 x-transition:enter="transition ease-out duration-300"
                                 x-transition:enter-start="opacity-0 translate-y-2"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 tabindex="0"
                                 class="p-6 sm:p-8 space-y-5">

                                <div class="flex flex-col-reverse sm:flex-row sm:items-start sm:justify-between gap-3 sm:gap-4">
                                    <div class="min-w-0">
                                        <p class="sc-eyebrow" style="color:var(--sc-muted)">Care dashboard</p>
                                        <p class="sc-display text-[1.6rem] sm:text-[1.9rem] leading-tight mt-1">Dad is doing well today.</p>
                                    </div>
                                    <span class="sc-chip sc-chip-ok shrink-0 self-start">
                                        <svg class="sc-i w-4 h-4" aria-hidden="true" focusable="false"><use href="#i-check-circle"/></svg>
                                        All clear
                                    </span>
                                </div>

                                <div class="grid gap-2.5 sm:grid-cols-3 sm:gap-3">
                                    <div class="sc-card-quiet p-4 flex items-center justify-between gap-3 sm:block">
                                        <p class="text-sm font-medium" style="color:var(--sc-muted)">Blood pressure</p>
                                        <p class="flex items-baseline gap-2.5 sm:block sm:mt-1.5">
                                            <span class="sc-display text-[1.5rem] sc-num">120/80</span>
                                            <span class="text-sm sm:block sm:mt-0.5" style="color:var(--sc-ok)">Normal</span>
                                        </p>
                                    </div>
                                    <div class="sc-card-quiet p-4 flex items-center justify-between gap-3 sm:block">
                                        <p class="text-sm font-medium" style="color:var(--sc-muted)">Resting pulse</p>
                                        <p class="flex items-baseline gap-2.5 sm:block sm:mt-1.5">
                                            <span class="sc-display text-[1.5rem] sc-num">72</span>
                                            <span class="text-sm sm:block sm:mt-0.5" style="color:var(--sc-ok)">Steady</span>
                                        </p>
                                    </div>
                                    <div class="sc-card-quiet p-4 flex items-center justify-between gap-3 sm:block">
                                        <p class="text-sm font-medium" style="color:var(--sc-muted)">Medication</p>
                                        <p class="flex items-baseline gap-2.5 sm:block sm:mt-1.5">
                                            <span class="sc-display text-[1.5rem] sc-num">8:04</span>
                                            <span class="text-sm sm:block sm:mt-0.5" style="color:var(--sc-ok)">On time</span>
                                        </p>
                                    </div>
                                </div>

                                <ul class="sc-tl space-y-5">
                                    <li class="relative">
                                        <span class="sc-tl-dot" style="background:var(--sc-ok-tint);border-color:var(--sc-ok-line);color:var(--sc-ok)">
                                            <svg class="sc-i w-4 h-4" aria-hidden="true" focusable="false"><use href="#i-check"/></svg>
                                        </span>
                                        <p class="font-semibold" style="color:var(--sc-ink)">Morning medication confirmed</p>
                                        <p class="text-sm sc-num" style="color:var(--sc-muted)">8:04 AM · logged by Arthur</p>
                                    </li>
                                    <li class="relative">
                                        <span class="sc-tl-dot">
                                            <svg class="sc-i w-4 h-4" aria-hidden="true" focusable="false"><use href="#i-activity"/></svg>
                                        </span>
                                        <p class="font-semibold" style="color:var(--sc-ink)">Vitals within his usual range</p>
                                        <p class="text-sm sc-num" style="color:var(--sc-muted)">8:06 AM · 120/80 mmHg</p>
                                    </li>
                                    <li class="relative">
                                        <span class="sc-tl-dot">
                                            <svg class="sc-i w-4 h-4" aria-hidden="true" focusable="false"><use href="#i-home"/></svg>
                                        </span>
                                        <p class="font-semibold" style="color:var(--sc-ink)">At home · 42 Elmwood Terrace</p>
                                        <p class="text-sm" style="color:var(--sc-muted)">Active around the house, eight minutes ago</p>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <p class="mt-5 text-sm text-center sm:text-left" style="color:var(--sc-muted)">
                        A live sample — switch between the two views, or mark the dose as taken.
                    </p>
                </div>
            </div>
        </div>
    </section>


    {{-- ════════════════════════════════════════════════════════════════
         HOW IT WORKS — three steps, one rail, no jargon.
         ════════════════════════════════════════════════════════════════ --}}
    <section id="how-it-works" class="sc-section sc-hair">
        <div class="mx-auto max-w-6xl px-5 sm:px-8">

            <div class="max-w-2xl mb-14 sc-reveal">
                <p class="sc-eyebrow">Getting started</p>
                <h2 class="sc-h2 mt-4">Three steps. Then it simply runs.</h2>
                <p class="sc-lead mt-5">
                    Set it up once, from your own kitchen table. Nothing to install, nothing to plug in,
                    nothing your parent has to remember.
                </p>
            </div>

            <ol class="sc-rail grid md:grid-cols-3 gap-10 md:gap-8">
                <li class="sc-reveal">
                    <span class="sc-step-num sc-num" aria-hidden="true">1</span>
                    <h3 class="sc-h3 mt-6">Create the care circle</h3>
                    <p class="mt-3" style="color:var(--sc-body)">
                        <span class="sr-only">Step 1. </span>
                        Add your parent and the family members who should know how the day is going.
                        Everyone joins from their own phone.
                    </p>
                </li>
                <li class="sc-reveal" style="--sc-d:110ms">
                    <span class="sc-step-num sc-num" aria-hidden="true">2</span>
                    <h3 class="sc-h3 mt-6">Set the daily routine</h3>
                    <p class="mt-3" style="color:var(--sc-body)">
                        <span class="sr-only">Step 2. </span>
                        Medications, check-ins and vitals, arranged simply into morning, afternoon
                        and evening — the way a day is actually lived.
                    </p>
                </li>
                <li class="sc-reveal" style="--sc-d:220ms">
                    <span class="sc-step-num sc-num" aria-hidden="true">3</span>
                    <h3 class="sc-h3 mt-6">Everyone stays in the loop</h3>
                    <p class="mt-3" style="color:var(--sc-body)">
                        <span class="sr-only">Step 3. </span>
                        One large button confirms a dose. The family sees it the moment it happens —
                        and hears from us if it doesn't.
                    </p>
                </li>
            </ol>
        </div>
    </section>

    {{-- ════════════════════════════════════════════════════════════════
         PILLAR ONE — FOR OLDER ADULTS
         ════════════════════════════════════════════════════════════════ --}}
    <section id="for-seniors" class="sc-section sc-hair sc-band-solid">
        <div class="mx-auto max-w-6xl px-5 sm:px-8">
            <div class="grid lg:grid-cols-2 gap-14 lg:gap-20 items-center">

                <div class="sc-reveal">
                    <p class="sc-eyebrow">For older adults</p>
                    <h2 class="sc-h2 mt-4">
                        No confusing menus.<br>Just what matters right now.
                    </h2>
                    <p class="sc-lead mt-6">
                        Nobody should have to wrestle with tiny buttons, buried submenus, or a password
                        they can't recall. SilverCare shows one clear thing at a time, in type large
                        enough to read across the room.
                    </p>

                    <ul class="mt-9 space-y-5">
                        <li class="flex gap-4">
                            <span class="sc-plate sc-plate-sm sc-plate-ok"><svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-check"/></svg></span>
                            <p style="color:var(--sc-body)"><span class="font-semibold" style="color:var(--sc-ink)">Generous targets.</span> Buttons sized for stiff or unsteady hands — never a pixel-perfect tap.</p>
                        </li>
                        <li class="flex gap-4">
                            <span class="sc-plate sc-plate-sm sc-plate-ok"><svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-check"/></svg></span>
                            <p style="color:var(--sc-body)"><span class="font-semibold" style="color:var(--sc-ink)">A day, not a database.</span> Morning, afternoon and evening. Finished tasks quietly tuck themselves away.</p>
                        </li>
                        <li class="flex gap-4">
                            <span class="sc-plate sc-plate-sm sc-plate-ok"><svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-check"/></svg></span>
                            <p style="color:var(--sc-body)"><span class="font-semibold" style="color:var(--sc-ink)">Speak, don't type.</span> Voice capture records how they're feeling in their own words.</p>
                        </li>
                        <li class="flex gap-4">
                            <span class="sc-plate sc-plate-sm sc-plate-ok"><svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-check"/></svg></span>
                            <p style="color:var(--sc-body)"><span class="font-semibold" style="color:var(--sc-ink)">Adjustable to the eye.</span> Larger text and high contrast are a switch away, remembered forever.</p>
                        </li>
                    </ul>
                </div>

                {{-- Visual: what a senior actually sees --}}
                <div class="sc-reveal" style="--sc-d:120ms">
                    <div class="sc-card sc-lift p-6 sm:p-8">
                        <p class="sc-eyebrow mb-5" style="color:var(--sc-muted)">Arthur's screen</p>

                        <div class="rounded-[1.25rem] p-6 space-y-5" style="background:var(--sc-surface-2);border:1px solid var(--sc-line)">
                            <div class="flex items-center gap-4">
                                <span class="sc-plate"><svg class="sc-i w-6 h-6" aria-hidden="true" focusable="false"><use href="#i-pulse"/></svg></span>
                                <div>
                                    <p class="sc-display text-[1.2rem] sm:text-[1.35rem] leading-snug" style="font-weight:600">Heart health medication</p>
                                    <p style="color:var(--sc-muted)">One tablet with breakfast</p>
                                </div>
                            </div>

                            <p class="flex items-center justify-center gap-2.5 w-full py-4 rounded-2xl font-semibold text-[1.05rem]"
                               style="background:var(--sc-ok-tint);border:1px solid var(--sc-ok-line);color:var(--sc-ok)">
                                <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-check"/></svg>
                                Completed this morning
                            </p>

                            <div class="flex items-center gap-3 pt-1" style="color:var(--sc-muted)">
                                <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-clock"/></svg>
                                <p class="text-sm">Next up at 1:00 PM — nothing else until then.</p>
                            </div>
                        </div>

                        <p class="mt-5 text-sm" style="color:var(--sc-muted)">
                            The whole screen holds one decision. Everything already done moves out of the way.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ════════════════════════════════════════════════════════════════
         PILLAR TWO — FOR FAMILY
         ════════════════════════════════════════════════════════════════ --}}
    <section id="for-family" class="sc-section sc-hair sc-band">
        <div class="mx-auto max-w-6xl px-5 sm:px-8">
            <div class="grid lg:grid-cols-2 gap-14 lg:gap-20 items-center">

                {{-- Visual first on large screens, second in source order stays sensible on mobile --}}
                <div class="order-2 lg:order-1 sc-reveal">
                    <div class="sc-card sc-lift p-6 sm:p-8">
                        <div class="flex items-center justify-between mb-6">
                            <p class="sc-eyebrow" style="color:var(--sc-muted)">Sarah's phone</p>
                            <span class="sc-chip sc-chip-ok">
                                <svg class="sc-i w-4 h-4" aria-hidden="true" focusable="false"><use href="#i-bell"/></svg>
                                Today
                            </span>
                        </div>

                        <ul class="sc-tl space-y-6">
                            <li class="relative">
                                <span class="sc-tl-dot" style="background:var(--sc-ok-tint);border-color:var(--sc-ok-line);color:var(--sc-ok)">
                                    <svg class="sc-i w-4 h-4" aria-hidden="true" focusable="false"><use href="#i-check"/></svg>
                                </span>
                                <p class="font-semibold" style="color:var(--sc-ink)">Dad took his morning medication</p>
                                <p class="text-sm sc-num" style="color:var(--sc-muted)">8:04 AM · blood pressure 120/80 mmHg, normal</p>
                            </li>
                            <li class="relative">
                                <span class="sc-tl-dot">
                                    <svg class="sc-i w-4 h-4" aria-hidden="true" focusable="false"><use href="#i-mic"/></svg>
                                </span>
                                <p class="font-semibold" style="color:var(--sc-ink)">He left you a voice note</p>
                                <p class="text-sm sc-num" style="color:var(--sc-muted)">9:12 AM · &ldquo;Feeling good today, love.&rdquo;</p>
                            </li>
                            <li class="relative">
                                <span class="sc-tl-dot" style="background:var(--sc-warn-tint);border-color:var(--sc-warn-line);color:var(--sc-warn)">
                                    <svg class="sc-i w-4 h-4" aria-hidden="true" focusable="false"><use href="#i-clock"/></svg>
                                </span>
                                <p class="font-semibold" style="color:var(--sc-ink)">Afternoon dose due in 30 minutes</p>
                                <p class="text-sm" style="color:var(--sc-muted)">We'll remind him first — you'll only hear from us if it's missed.</p>
                            </li>
                        </ul>

                        <p class="mt-7 pt-6 text-sm" style="border-top:1px solid var(--sc-line);color:var(--sc-muted)">
                            Three quiet lines instead of three anxious phone calls.
                        </p>
                    </div>
                </div>

                <div class="order-1 lg:order-2 sc-reveal" style="--sc-d:120ms">
                    <p class="sc-eyebrow">For family &amp; caregivers</p>
                    <h2 class="sc-h2 mt-4">
                        Stop calling three times a day to check in.
                    </h2>
                    <p class="sc-lead mt-6">
                        Caring for an aging parent shouldn't mean a low hum of worry from morning to night.
                        SilverCare replaces the guessing with a steady, gentle pulse of reassurance.
                    </p>

                    <ul class="mt-9 space-y-5">
                        <li class="flex gap-4">
                            <span class="sc-plate sc-plate-sm"><svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-bell"/></svg></span>
                            <p style="color:var(--sc-body)"><span class="font-semibold" style="color:var(--sc-ink)">Confirmation, the moment it happens.</span> See a dose taken in real time — no chasing, no nagging.</p>
                        </li>
                        <li class="flex gap-4">
                            <span class="sc-plate sc-plate-sm"><svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-activity"/></svg></span>
                            <p style="color:var(--sc-body)"><span class="font-semibold" style="color:var(--sc-ink)">Escalation when it's needed.</span> A missed routine or a vital drifting out of range reaches the care circle automatically.</p>
                        </li>
                        <li class="flex gap-4">
                            <span class="sc-plate sc-plate-sm"><svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-clipboard"/></svg></span>
                            <p style="color:var(--sc-body)"><span class="font-semibold" style="color:var(--sc-ink)">Ready for the doctor.</span> One click produces a clinical summary of thirty days of vitals and adherence.</p>
                        </li>
                        <li class="flex gap-4">
                            <span class="sc-plate sc-plate-sm"><svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-users"/></svg></span>
                            <p style="color:var(--sc-body)"><span class="font-semibold" style="color:var(--sc-ink)">Shared, not shouldered alone.</span> Siblings and carers see the same picture, so the load is split.</p>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- ════════════════════════════════════════════════════════════════
         PILLAR THREE — EMERGENCY
         ════════════════════════════════════════════════════════════════ --}}
    <section id="emergency" class="sc-section sc-hair sc-band-solid">
        <div class="mx-auto max-w-6xl px-5 sm:px-8">

            <div class="max-w-2xl mb-14 sc-reveal">
                <p class="sc-eyebrow" style="color:var(--sc-alert)">Immediate safety</p>
                <h2 class="sc-h2 mt-4">One touch for help. Instant notice for family.</h2>
                <p class="sc-lead mt-5">
                    In an emergency, seconds matter and memory fails. There is no phone to unlock,
                    no contact card to find, no number to recall.
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-6">
                <article class="sc-card sc-card-crest sc-lift p-8 sc-reveal" style="--sc-crest:var(--sc-alert-line)">
                    <span class="sc-plate sc-plate-alert"><svg class="sc-i w-6 h-6" aria-hidden="true" focusable="false"><use href="#i-siren"/></svg></span>
                    <h3 class="sc-h3 mt-6">One unmissable button</h3>
                    <p class="mt-3" style="color:var(--sc-body)">
                        A large red button sits on the home screen. A spoken command works too, if reaching
                        the screen isn't possible.
                    </p>
                </article>

                <article class="sc-card sc-card-crest sc-lift p-8 sc-reveal" style="--sc-crest:var(--sc-alert-line); --sc-d:110ms">
                    <span class="sc-plate sc-plate-alert"><svg class="sc-i w-6 h-6" aria-hidden="true" focusable="false"><use href="#i-map-pin"/></svg></span>
                    <h3 class="sc-h3 mt-6">Location, sent with the alert</h3>
                    <p class="mt-3" style="color:var(--sc-body)">
                        Family sees exactly where he is — at home, or halfway round the block on his
                        afternoon walk.
                    </p>
                </article>

                <article class="sc-card sc-card-crest sc-lift p-8 sc-reveal" style="--sc-crest:var(--sc-alert-line); --sc-d:220ms">
                    <span class="sc-plate sc-plate-alert"><svg class="sc-i w-6 h-6" aria-hidden="true" focusable="false"><use href="#i-phone"/></svg></span>
                    <h3 class="sc-h3 mt-6">The whole circle at once</h3>
                    <p class="mt-3" style="color:var(--sc-body)">
                        Everyone is notified simultaneously, so whoever is nearest can move first instead
                        of waiting to be asked.
                    </p>
                </article>
            </div>
        </div>
    </section>



    {{-- ════════════════════════════════════════════════════════════════
         QUESTIONS — objection handling, in plain language.
         ════════════════════════════════════════════════════════════════ --}}
    <section id="questions" class="sc-section sc-hair sc-band-solid">
        <div class="mx-auto max-w-3xl px-5 sm:px-8">

            <div class="mb-12 sc-reveal">
                <p class="sc-eyebrow">Questions families ask</p>
                <h2 class="sc-h2 mt-4">The things worth knowing first.</h2>
            </div>

            <div class="sc-card overflow-hidden sc-divide sc-reveal" x-data="{ open: 0 }">

                @php
                    $faqs = [
                        [
                            'q' => 'Does my parent need to buy any hardware?',
                            'a' => 'No. SilverCare runs in the web browser on the phone, tablet or computer they already own. There is no wearable to charge, no base station to plug in, and nothing to remember to carry.',
                        ],
                        [
                            'q' => 'What actually happens when a dose is missed?',
                            'a' => 'Nothing alarming, and not straight away. SilverCare reminds your parent gently first and waits out a grace period. Only if the dose is still not confirmed does it escalate to the care circle — so a notification always means something, and never means nagging.',
                        ],
                        [
                            'q' => 'How long does setup really take?',
                            'a' => 'About two minutes. You create the care circle, invite the family members who should be kept informed, and add the daily routine. Your parent signs in on their own device and sees a single, uncluttered screen from the first moment.',
                        ],
                        [
                            'q' => 'Who can see our health information?',
                            'a' => 'Only the people you invite into the care circle. Health records are encrypted, and your data is never sold or shared with advertisers. You can remove a member of the circle at any time, and their access ends immediately.',
                        ],
                        [
                            'q' => 'What if reading small text or tapping precisely is hard?',
                            'a' => 'That is the case we designed for. Text size and high contrast can be turned on from any screen — including this one, using the Display button in the header — and the choice is remembered on that device. Every control meets the minimum touch target size, and the whole product is usable by keyboard and screen reader.',
                        ],
                        [
                            'q' => 'Can my parent speak instead of typing?',
                            'a' => 'Yes. Voice capture lets them record how they are feeling in their own words rather than filling in a form, and the family sees it alongside the rest of the day.',
                        ],
                    ];
                @endphp

                @foreach ($faqs as $i => $faq)
                    <div>
                        <h3>
                            <button type="button" class="sc-faq-q"
                                    id="sc-faq-q-{{ $i }}"
                                    aria-controls="sc-faq-a-{{ $i }}"
                                    aria-expanded="{{ $i === 0 ? 'true' : 'false' }}"
                                    :aria-expanded="open === {{ $i }} ? 'true' : 'false'"
                                    @click="open = (open === {{ $i }}) ? null : {{ $i }}">
                                <span>{{ $faq['q'] }}</span>
                                <svg class="sc-i sc-faq-chev w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-chevron-down"/></svg>
                            </button>
                        </h3>
                        <div id="sc-faq-a-{{ $i }}" role="region" aria-labelledby="sc-faq-q-{{ $i }}"
                             x-show="open === {{ $i }}"
                             @if ($i !== 0) x-cloak @endif
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="px-[1.4rem] pb-6 -mt-1">
                            <p class="max-w-[62ch]" style="color:var(--sc-body)">{{ $faq['a'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

</main>

{{-- ════════════════════════════════════════════════════════════════════
     FOOTER
     ════════════════════════════════════════════════════════════════════ --}}
<footer class="sc-hair sc-band pt-16 pb-12">
    <div class="mx-auto max-w-7xl px-5 sm:px-8">

        <div class="grid gap-12 md:grid-cols-12">

            <div class="md:col-span-4">
                <div class="sc-brand sc-brand-sm">
                    <span class="sc-brand-mark">
                        <img src="{{ asset('assets/icons/silvercare.png') }}" alt="">
                    </span>
                    <span class="sc-brand-word">SilverCare</span>
                </div>
                <p class="mt-5 max-w-xs" style="color:var(--sc-body)">
                    Elder care management and family peace of mind, designed with dignity and
                    quiet simplicity.
                </p>
            </div>

            <nav class="md:col-span-8 grid grid-cols-2 sm:grid-cols-3 gap-8" aria-label="Footer">
                <div>
                    <h2 class="sc-eyebrow" style="color:var(--sc-ink)">Experience</h2>
                    <ul class="mt-4 space-y-3">
                        <li><a href="#for-seniors" class="sc-link">For older adults</a></li>
                        <li><a href="#for-family" class="sc-link">For family caregivers</a></li>
                        <li><a href="#emergency" class="sc-link">Emergency safety</a></li>
                        <li><a href="#how-it-works" class="sc-link">How it works</a></li>
                    </ul>
                </div>
                <div>
                    <h2 class="sc-eyebrow" style="color:var(--sc-ink)">Care</h2>
                    <ul class="mt-4 space-y-3">
                        <li><a href="#dual-experience" class="sc-link">Medication routines</a></li>
                        <li><a href="#for-family" class="sc-link">Health vitals</a></li>
                        <li><a href="#dual-experience" class="sc-link">Garden of Wellness</a></li>
                        <li><a href="#for-family" class="sc-link">Physician summaries</a></li>
                    </ul>
                </div>
                <div class="col-span-2 sm:col-span-1">
                    <h2 class="sc-eyebrow" style="color:var(--sc-ink)">Our commitment</h2>
                    <ul class="mt-4 space-y-3" style="color:var(--sc-muted)">
                        <li class="flex items-start gap-2.5">
                            <svg class="sc-i w-5 h-5 mt-0.5" aria-hidden="true" focusable="false"><use href="#i-accessibility"/></svg>
                            Accessible to WCAG 2.1 AA
                        </li>
                        <li class="flex items-start gap-2.5">
                            <svg class="sc-i w-5 h-5 mt-0.5" aria-hidden="true" focusable="false"><use href="#i-shield"/></svg>
                            Strict privacy, zero data selling
                        </li>
                        <li class="flex items-start gap-2.5">
                            <svg class="sc-i w-5 h-5 mt-0.5" aria-hidden="true" focusable="false"><use href="#i-lock"/></svg>
                            Encrypted health records
                        </li>
                    </ul>
                </div>
            </nav>
        </div>

        <div class="mt-14 pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm"
             style="border-top:1px solid var(--sc-line);color:var(--sc-muted)">
            <p>&copy; {{ date('Y') }} SilverCare. All rights reserved.</p>
            <p>Designed with love for families everywhere.</p>
        </div>
    </div>
</footer>

</body>
</html>
