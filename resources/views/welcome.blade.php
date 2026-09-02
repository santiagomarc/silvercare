<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SilverCare — Dignified Senior Care & Quiet Peace of Mind</title>
    <meta name="description" content="SilverCare gives older adults effortless daily independence at home, while giving family members quiet, real-time reassurance without anxious phone calls.">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('assets/icons/silvercare.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/icons/silvercare.png') }}">

    <!-- Typography: Google Font 'Prompt' (800 main headers, 600 sub headers) & 'Newsreader' -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;600;700;800&family=Newsreader:ital,opsz,wght@1,6..72,400;1,6..72,500&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Valley Sans font definition (self-hosted open source variable webfont) */
        @font-face {
            font-family: 'Valley Sans';
            src: url("{{ asset('assets/fonts/valley-sans/ValleySans-Variable.woff2') }}") format('woff2-variations'),
                 url("{{ asset('assets/fonts/valley-sans/ValleySans-Regular.woff2') }}") format('woff2');
            font-weight: 100 900;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'Valley Sans';
            src: url("{{ asset('assets/fonts/valley-sans/ValleySans-Medium.woff2') }}") format('woff2');
            font-weight: 500;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'Valley Sans';
            src: url("{{ asset('assets/fonts/valley-sans/ValleySans-SemiBold.woff2') }}") format('woff2');
            font-weight: 600;
            font-style: normal;
            font-display: swap;
        }

        body {
            font-family: 'Valley Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #FAF9F6;
            color: #1E293B;
            -webkit-font-smoothing: antialiased;
        }

        .font-prompt {
            font-family: 'Prompt', sans-serif;
        }

        .font-valley {
            font-family: 'Valley Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        /* Typography Hierarchy */
        h1, h2, .font-heading-main {
            font-family: 'Prompt', sans-serif;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        h3, h4, .font-heading-sub {
            font-family: 'Prompt', sans-serif;
            font-weight: 600;
            letter-spacing: -0.01em;
        }

        .font-serif-quote {
            font-family: 'Newsreader', Georgia, serif;
            font-style: italic;
        }

        /* Accessible focus rings */
        a:focus-visible, button:focus-visible {
            outline: 2px solid #000080 !important;
            outline-offset: 4px !important;
        }
    </style>
</head>
<body class="bg-[#FAF9F6] text-slate-800 antialiased selection:bg-slate-900 selection:text-white font-valley">

    <main id="main-content">
        <!-- ====================================================================
             HERO SECTION: Generous Whitespace, Prompt 800 Headline, Direct CTAs
             ==================================================================== -->
        <section class="pt-8 pb-24 md:pt-14 md:pb-36 overflow-hidden">
            <div class="max-w-5xl mx-auto px-6 sm:px-8 text-center space-y-8">

                <!-- Brand Mark -->
                <div class="flex items-center justify-center gap-3 pt-2 sm:pt-6 mb-2">
                    <div class="w-11 h-11 rounded-2xl bg-white border border-stone-200/80 p-2 shadow-[0_1px_4px_rgba(0,0,0,0.03)] flex items-center justify-center">
                        <img src="{{ asset('assets/icons/silvercare.png') }}" alt="SilverCare Logo" class="w-full h-full object-contain">
                    </div>
                    <span class="font-prompt font-[800] text-2xl tracking-tight text-slate-900">
                        SilverCare
                    </span>
                </div>

                <!-- Main Headline: Prompt Extrabold 800 -->
                <h1 class="font-prompt font-[800] text-4xl sm:text-6xl md:text-7xl text-slate-900 leading-[1.12] max-w-4xl mx-auto">
                    Care that feels like family.<br class="hidden sm:inline">
                    <span class="text-[#000080]">Technology that stays out of the way.</span>
                </h1>

                <!-- Sub-headline: Valley Sans Body Text -->
                <p class="font-valley text-lg sm:text-xl text-slate-600 font-normal leading-relaxed max-w-2xl mx-auto">
                    SilverCare gives older adults effortless daily independence at home, while giving family members quiet, real-time reassurance without anxious phone calls.
                </p>

                <!-- Action CTAs: Direct and Visible on Initial View -->
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-2 font-valley">
                    <a href="{{ route('register') }}" 
                       class="w-full sm:w-auto min-h-[52px] px-8 py-3.5 inline-flex items-center justify-center gap-2 text-base font-semibold text-white bg-[#000080] hover:bg-[#000066] rounded-full shadow-sm hover:shadow-lg transition-all active:scale-[0.98]">
                        <span>Try SilverCare Free</span>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                    <a href="{{ route('login') }}" 
                       class="w-full sm:w-auto min-h-[52px] px-8 py-3.5 inline-flex items-center justify-center text-base font-semibold text-slate-700 bg-white hover:bg-stone-50 border border-stone-200 rounded-full shadow-[0_1px_2px_rgba(0,0,0,0.03)] transition-all">
                        Sign In
                    </a>
                </div>

                <!-- Trust note -->
                <p class="font-valley text-xs text-stone-500 font-normal pt-1">
                    Simple 2-minute setup &bull; Works on any phone, tablet, or computer &bull; No hardware required
                </p>

                <!-- ================================================================
                     INTERACTIVE DUAL-PERSPECTIVE PREVIEW (Alpine.js)
                     ================================================================ -->
                <div class="pt-12 md:pt-16 max-w-3xl mx-auto text-left font-valley" id="dual-experience" x-data="{ 
                    view: 'senior', 
                    doseTaken: false,
                    toggleDose() {
                        this.doseTaken = !this.doseTaken;
                    }
                }">
                    
                    <!-- Segmented Switcher -->
                    <div class="flex items-center justify-center mb-6">
                        <div class="inline-flex p-1.5 rounded-full bg-white border border-stone-200/80 shadow-[0_1px_3px_rgba(0,0,0,0.04)]" role="tablist">
                            <button type="button" 
                                    role="tab"
                                    :aria-selected="view === 'senior'"
                                    @click="view = 'senior'" 
                                    :class="view === 'senior' ? 'bg-[#000080] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                                    class="min-h-[44px] px-6 py-2 rounded-full text-sm font-semibold transition-all duration-200">
                                For Arthur (Senior)
                            </button>

                            <button type="button" 
                                    role="tab"
                                    :aria-selected="view === 'caregiver'"
                                    @click="view = 'caregiver'" 
                                    :class="view === 'caregiver' ? 'bg-[#000080] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                                    class="min-h-[44px] px-6 py-2 rounded-full text-sm font-semibold transition-all duration-200">
                                For Sarah (Daughter &amp; Caregiver)
                            </button>
                        </div>
                    </div>

                    <!-- Clean Showcase Card -->
                    <div class="bg-white rounded-3xl border border-stone-200/80 shadow-[0_4px_24px_rgba(0,0,0,0.04)] overflow-hidden p-6 sm:p-10">
                        
                        <!-- TAB 1: SENIOR PERSPECTIVE -->
                        <div x-show="view === 'senior'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="space-y-6">
                            
                            <div class="flex items-center justify-between pb-4 border-b border-stone-100">
                                <div>
                                    <p class="font-prompt font-[600] text-xs uppercase tracking-wider text-stone-500">Wednesday Routine</p>
                                    <h2 class="font-prompt font-[800] text-2xl sm:text-3xl text-slate-900 mt-0.5">Good morning, Arthur.</h2>
                                </div>
                                <span class="font-valley text-sm font-medium text-amber-900 bg-amber-50 px-3.5 py-1.5 rounded-full">
                                    ☀️ Morning Routine
                                </span>
                            </div>

                            <!-- Single Calm Check-off Card -->
                            <div class="p-6 rounded-2xl border transition-all"
                                 :class="doseTaken ? 'bg-emerald-50/40 border-emerald-300' : 'bg-stone-50/50 border-stone-200'">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-5">
                                    <div class="space-y-1">
                                        <p class="font-valley text-xs font-semibold text-stone-500 uppercase tracking-wide">Due at 8:00 AM &bull; With Breakfast</p>
                                        <h3 class="font-prompt font-[600] text-xl text-slate-900">Morning Blood Pressure &amp; Vitamins</h3>
                                        <p class="font-valley text-sm text-slate-600 font-normal">Lisinopril (1 tablet) with a full glass of water</p>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <button type="button" 
                                                @click="toggleDose()"
                                                :class="doseTaken ? 'bg-emerald-700 text-white' : 'bg-[#000080] hover:bg-[#000066] text-white'"
                                                class="min-h-[48px] px-6 py-3 rounded-full text-sm font-semibold transition-all shadow-sm flex items-center gap-2 font-valley">
                                            <span x-show="!doseTaken">I took this</span>
                                            <span x-show="doseTaken" x-cloak class="flex items-center gap-1.5">
                                                <span>✓ Taken at 8:04 AM</span>
                                            </span>
                                        </button>

                                        <button type="button" 
                                                x-show="doseTaken" 
                                                x-cloak
                                                @click="toggleDose()" 
                                                class="font-valley text-xs font-medium text-stone-500 hover:text-slate-800 underline">
                                            Undo
                                        </button>
                                    </div>
                                </div>

                                <div x-show="doseTaken" x-cloak class="mt-4 pt-3 border-t border-emerald-200/60 font-valley text-xs font-medium text-emerald-800 flex items-center gap-1.5">
                                    <span>✓ Confirmed. Sarah has been softly notified.</span>
                                </div>
                            </div>

                            <!-- Gentle Voice Note from Daughter -->
                            <div class="p-5 rounded-2xl bg-indigo-50/50 border border-indigo-100 flex items-center gap-4">
                                <div class="w-10 h-10 rounded-full bg-white border border-indigo-100 flex items-center justify-center text-indigo-700 flex-shrink-0 text-lg">
                                    🎙️
                                </div>
                                <div class="font-valley text-sm">
                                    <p class="font-semibold text-slate-900">Note from Sarah:</p>
                                    <p class="text-slate-600 font-normal">&ldquo;Have a wonderful morning, Dad! Let's go for our walk at three this afternoon.&rdquo;</p>
                                </div>
                            </div>

                            <!-- Garden of Wellness Habit Streak -->
                            <div class="p-5 rounded-2xl bg-white border border-stone-200/80 flex items-center justify-between">
                                <div class="flex items-center gap-3.5">
                                    <span class="text-2xl">🌸</span>
                                    <div>
                                        <p class="font-prompt font-[600] text-sm text-slate-900">Garden of Wellness</p>
                                        <p class="font-valley text-xs text-slate-500 font-normal">14 days in a row of peaceful morning routines</p>
                                    </div>
                                </div>
                                <span class="font-valley text-xs font-semibold text-emerald-800 bg-emerald-50 px-3 py-1 rounded-full">
                                    14-Day Bloom
                                </span>
                            </div>

                        </div>

                        <!-- TAB 2: CAREGIVER PERSPECTIVE -->
                        <div x-show="view === 'caregiver'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="space-y-6">
                            
                            <div class="flex items-center justify-between pb-4 border-b border-stone-100">
                                <div>
                                    <p class="font-prompt font-[600] text-xs uppercase tracking-wider text-stone-500">Reassurance Dashboard</p>
                                    <h2 class="font-prompt font-[800] text-2xl sm:text-3xl text-slate-900 mt-0.5">Arthur is doing well today.</h2>
                                </div>
                                <span class="font-valley text-xs font-medium text-emerald-900 bg-emerald-50 px-3.5 py-1.5 rounded-full flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    <span>All morning routines completed</span>
                                </span>
                            </div>

                            <!-- Three Gentle Vitals (No Medical Clutter) -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div class="p-4 rounded-2xl bg-stone-50/70 border border-stone-200/80">
                                    <p class="font-prompt font-[600] text-xs text-stone-500">Blood Pressure</p>
                                    <p class="font-prompt font-[800] text-2xl text-slate-900 mt-1">120/80</p>
                                    <p class="font-valley text-xs text-emerald-800 font-medium mt-0.5">Normal &amp; healthy</p>
                                </div>

                                <div class="p-4 rounded-2xl bg-stone-50/70 border border-stone-200/80">
                                    <p class="font-prompt font-[600] text-xs text-stone-500">Resting Heart Rate</p>
                                    <p class="font-prompt font-[800] text-2xl text-slate-900 mt-1">72 bpm</p>
                                    <p class="font-valley text-xs text-emerald-800 font-medium mt-0.5">Steady rhythm</p>
                                </div>

                                <div class="p-4 rounded-2xl bg-stone-50/70 border border-stone-200/80">
                                    <p class="font-prompt font-[600] text-xs text-stone-500">Morning Medication</p>
                                    <p class="font-prompt font-[800] text-2xl text-slate-900 mt-1">8:04 AM</p>
                                    <p class="font-valley text-xs text-emerald-800 font-medium mt-0.5">Taken on time</p>
                                </div>
                            </div>

                            <!-- Safety & Location Reassurance -->
                            <div class="p-5 rounded-2xl bg-white border border-stone-200/80 flex items-center justify-between font-valley">
                                <div class="flex items-center gap-3.5">
                                    <div class="w-9 h-9 rounded-full bg-stone-100 flex items-center justify-center text-slate-700">
                                        🏡
                                    </div>
                                    <div>
                                        <p class="font-prompt font-[600] text-sm text-slate-900">At Home &bull; 42 Elmwood Terrace</p>
                                        <p class="text-xs text-slate-500 font-normal">Active around the house &bull; Last movement 8 minutes ago</p>
                                    </div>
                                </div>
                                <span class="text-xs font-medium text-stone-600">All calm</span>
                            </div>

                        </div>

                    </div>
                </div>

            </div>
        </section>

        <!-- ====================================================================
             PILLAR 1: DESIGNED FOR SIMPLICITY (FOR SENIORS)
             ==================================================================== -->
        <section id="simplicity" class="py-24 md:py-32 bg-white border-t border-stone-200/60">
            <div class="max-w-5xl mx-auto px-6 sm:px-8">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-12 lg:gap-16 items-center">
                    <div class="space-y-6">
                        <span class="font-prompt font-[600] text-xs uppercase tracking-wider text-[#000080]">Dignified Simplicity</span>
                        <h2 class="font-prompt font-[800] text-3xl sm:text-4xl text-slate-900 leading-snug">
                            No confusing menus.<br>
                            Just what matters right now.
                        </h2>
                        <p class="font-valley text-base sm:text-lg text-slate-600 font-normal leading-relaxed">
                            Older adults shouldn't have to wrestle with small buttons, confusing submenus, or passwords they can't remember.
                        </p>
                        <ul class="space-y-3.5 text-sm sm:text-base text-slate-700 font-medium pt-2 font-valley">
                            <li class="flex items-start gap-3">
                                <span class="text-emerald-600 font-bold">✓</span>
                                <span>Large, comfortable buttons designed for trembling or stiff hands</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="text-emerald-600 font-bold">✓</span>
                                <span>Morning, afternoon, and evening routines organized cleanly</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="text-emerald-600 font-bold">✓</span>
                                <span>Voice assistance so Arthur can speak naturally instead of typing</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Clean Visual Card -->
                    <div class="bg-[#FAF9F6] p-8 rounded-3xl border border-stone-200/80 space-y-4 font-valley">
                        <p class="font-prompt font-[600] text-xs text-stone-500 uppercase tracking-wide">Senior Interface View</p>
                        <div class="bg-white p-6 rounded-2xl border border-stone-200/80 shadow-sm space-y-4">
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">💊</span>
                                <div>
                                    <h3 class="font-prompt font-[600] text-lg text-slate-900">Heart Health Medication</h3>
                                    <p class="font-valley text-xs text-slate-500 font-normal">1 tablet with breakfast</p>
                                </div>
                            </div>
                            <div class="w-full py-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 text-center rounded-xl font-semibold text-sm font-valley">
                                ✓ Completed this morning
                            </div>
                        </div>
                        <p class="font-valley text-xs text-stone-500 text-center font-normal">Past tasks quietly tuck away so only what's next is visible.</p>
                    </div>
                </div>

            </div>
        </section>

        <!-- ====================================================================
             PILLAR 2: QUIET PEACE OF MIND (FOR CAREGIVERS)
             ==================================================================== -->
        <section id="peace-of-mind" class="py-24 md:py-32 bg-[#FAF9F6] border-t border-stone-200/60">
            <div class="max-w-5xl mx-auto px-6 sm:px-8">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-12 lg:gap-16 items-center">
                    <!-- Clean Visual Card -->
                    <div class="order-2 md:order-1 bg-white p-8 rounded-3xl border border-stone-200/80 space-y-4 shadow-sm font-valley">
                        <div class="flex items-center justify-between">
                            <span class="font-prompt font-[600] text-xs text-stone-500 uppercase tracking-wide">Caregiver Notification</span>
                            <span class="text-xs text-stone-500 font-normal">8:04 AM</span>
                        </div>
                        <div class="p-4 rounded-2xl bg-emerald-50/60 border border-emerald-100 flex items-center gap-3.5">
                            <span class="w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-xs">✓</span>
                            <div>
                                <p class="font-prompt font-[600] text-sm text-slate-900">Arthur took his morning medication</p>
                                <p class="font-valley text-xs text-slate-500 font-normal">Blood pressure logged: 120/80 mmHg (Normal)</p>
                            </div>
                        </div>
                        <p class="font-valley text-xs text-stone-500 text-center font-normal">Gentle confirmation delivered straight to your phone.</p>
                    </div>

                    <div class="order-1 md:order-2 space-y-6">
                        <span class="font-prompt font-[600] text-xs uppercase tracking-wider text-[#000080]">Quiet Peace of Mind</span>
                        <h2 class="font-prompt font-[800] text-3xl sm:text-4xl text-slate-900 leading-snug">
                            No more calling three times a day to check in.
                        </h2>
                        <p class="font-valley text-base sm:text-lg text-slate-600 font-normal leading-relaxed">
                            Caring for an aging parent shouldn't mean feeling anxious all day. SilverCare gives you a continuous, gentle pulse of reassurance.
                        </p>
                        <ul class="space-y-3.5 text-sm sm:text-base text-slate-700 font-medium pt-2 font-valley">
                            <li class="flex items-start gap-3">
                                <span class="text-emerald-600 font-bold">✓</span>
                                <span>See that medication was taken the moment it happens</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="text-emerald-600 font-bold">✓</span>
                                <span>Automatic alerts if a routine is missed or vitals drift out of range</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="text-emerald-600 font-bold">✓</span>
                                <span>One-click doctor visit summaries showing 30-day vitals and adherence</span>
                            </li>
                        </ul>
                    </div>
                </div>

            </div>
        </section>

        <!-- ====================================================================
             PILLAR 3: EMERGENCY CARE
             ==================================================================== -->
        <section id="emergency" class="py-24 md:py-32 bg-white border-t border-stone-200/60">
            <div class="max-w-5xl mx-auto px-6 sm:px-8">
                
                <div class="max-w-2xl mx-auto text-center space-y-5 mb-16">
                    <span class="font-prompt font-[600] text-xs uppercase tracking-wider text-rose-700">Immediate Safety</span>
                    <h2 class="font-prompt font-[800] text-3xl sm:text-4xl text-slate-900">
                        One touch for help. Immediate notice for family.
                    </h2>
                    <p class="font-valley text-base sm:text-lg text-slate-600 font-normal leading-relaxed">
                        In an emergency, seconds matter. Arthur doesn't need to unlock a phone or find a contact card.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 font-valley">
                    <div class="p-8 rounded-3xl bg-[#FAF9F6] border border-stone-200/80 space-y-3">
                        <span class="text-2xl">🚨</span>
                        <h3 class="font-prompt font-[600] text-lg text-slate-900">One-Touch Emergency Button</h3>
                        <p class="text-sm text-slate-600 leading-relaxed font-normal">
                            A large, unmissable red button on Arthur's home screen or a simple voice command sends for help immediately.
                        </p>
                    </div>

                    <div class="p-8 rounded-3xl bg-[#FAF9F6] border border-stone-200/80 space-y-3">
                        <span class="text-2xl">📍</span>
                        <h3 class="font-prompt font-[600] text-lg text-slate-900">Instant Location Sharing</h3>
                        <p class="text-sm text-slate-600 leading-relaxed font-normal">
                            Your family receives an urgent notification showing exactly where Arthur is, whether at home or on a neighborhood walk.
                        </p>
                    </div>

                    <div class="p-8 rounded-3xl bg-[#FAF9F6] border border-stone-200/80 space-y-3">
                        <span class="text-2xl">📞</span>
                        <h3 class="font-prompt font-[600] text-lg text-slate-900">Connected Family Circle</h3>
                        <p class="text-sm text-slate-600 leading-relaxed font-normal">
                            Notifies everyone in Arthur's care circle simultaneously so the closest person can respond right away.
                        </p>
                    </div>
                </div>

            </div>
        </section>

        <!-- ====================================================================
             FAMILY EDITORIAL QUOTE / TESTIMONIAL
             ==================================================================== -->
        <section class="py-24 md:py-32 bg-[#FAF9F6] border-t border-stone-200/60">
            <div class="max-w-3xl mx-auto px-6 sm:px-8 text-center space-y-8">
                
                <span class="text-3xl text-stone-300">&ldquo;</span>

                <blockquote class="text-2xl sm:text-3xl md:text-4xl text-slate-900 font-serif-quote leading-relaxed -mt-4">
                    SilverCare gave my dad his independence back. And for the first time in three years, I can sleep through the night knowing he is safe.
                </blockquote>

                <div class="pt-2 font-valley">
                    <p class="font-prompt font-[600] text-base text-slate-900">Sarah Pendelton</p>
                    <p class="text-xs text-stone-500 font-normal">Daughter &amp; Primary Caregiver &bull; Chicago, IL</p>
                </div>

            </div>
        </section>

        <!-- ====================================================================
             CLOSING CTA: Serene & Inviting
             ==================================================================== -->
        <section class="py-24 md:py-32 bg-white border-t border-stone-200/60 font-valley">
            <div class="max-w-4xl mx-auto px-6 sm:px-8 text-center space-y-8">
                
                <h2 class="font-prompt font-[800] text-3xl sm:text-5xl text-slate-900 leading-tight">
                    Start protecting your loved ones<br class="hidden sm:inline">
                    with quiet confidence today.
                </h2>

                <p class="font-valley text-base sm:text-lg text-slate-600 max-w-xl mx-auto leading-relaxed font-normal">
                    Set up your family in under 2 minutes. Works on any existing tablet, phone, or computer.
                </p>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4 font-valley">
                    <a href="{{ route('register') }}" 
                       class="w-full sm:w-auto min-h-[52px] px-9 py-3.5 inline-flex items-center justify-center gap-2 text-base font-semibold text-white bg-[#000080] hover:bg-[#000066] rounded-full shadow-sm hover:shadow-lg transition-all active:scale-[0.98]">
                        <span>Try SilverCare Free</span>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                    <a href="{{ route('login') }}" 
                       class="w-full sm:w-auto min-h-[52px] px-8 py-3.5 inline-flex items-center justify-center text-base font-semibold text-slate-700 bg-white hover:bg-stone-50 border border-stone-200 rounded-full shadow-[0_1px_2px_rgba(0,0,0,0.03)] transition-all">
                        Sign In
                    </a>
                </div>

                <p class="font-valley text-xs text-stone-500 font-normal">
                    Free for families &bull; Private &amp; secure &bull; No hardware purchase needed
                </p>

            </div>
        </section>
    </main>

    <!-- ====================================================================
         FOOTER: Clean, Minimalist & Spacious
         ==================================================================== -->
    <footer class="bg-[#FAF9F6] border-t border-stone-200/60 py-16 text-slate-600 font-valley">
        <div class="max-w-6xl mx-auto px-6 sm:px-8 space-y-12">
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                
                <!-- Col 1: Brand -->
                <div class="space-y-3 md:col-span-1">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-white border border-stone-200/80 p-1.5 shadow-sm flex items-center justify-center">
                            <img src="{{ asset('assets/icons/silvercare.png') }}" alt="SilverCare Logo" class="w-full h-full object-contain">
                        </div>
                        <span class="font-prompt font-[800] text-base text-slate-900">SilverCare</span>
                    </div>
                    <p class="text-xs text-stone-500 leading-relaxed font-normal">
                        Elder care management and family peace of mind, designed with dignity and quiet simplicity.
                    </p>
                </div>

                <!-- Col 2: The Experience -->
                <div class="space-y-2.5">
                    <p class="font-prompt font-[600] text-xs uppercase tracking-wider text-slate-900">Experience</p>
                    <ul class="space-y-2 text-xs font-medium text-slate-600 font-valley">
                        <li><a href="#simplicity" class="hover:text-slate-900 transition-colors">For Older Adults</a></li>
                        <li><a href="#peace-of-mind" class="hover:text-slate-900 transition-colors">For Family Caregivers</a></li>
                        <li><a href="#emergency" class="hover:text-slate-900 transition-colors">Emergency Safety</a></li>
                        <li><a href="{{ route('register') }}" class="hover:text-slate-900 transition-colors">Try SilverCare Free</a></li>
                    </ul>
                </div>

                <!-- Col 3: Care & Health -->
                <div class="space-y-2.5">
                    <p class="font-prompt font-[600] text-xs uppercase tracking-wider text-slate-900">Care</p>
                    <ul class="space-y-2 text-xs font-medium text-slate-600 font-valley">
                        <li><a href="#dual-experience" class="hover:text-slate-900 transition-colors">Medication Routines</a></li>
                        <li><a href="#peace-of-mind" class="hover:text-slate-900 transition-colors">Health Vitals</a></li>
                        <li><a href="#dual-experience" class="hover:text-slate-900 transition-colors">Garden of Wellness</a></li>
                        <li><a href="#peace-of-mind" class="hover:text-slate-900 transition-colors">Physician Reports</a></li>
                    </ul>
                </div>

                <!-- Col 4: Trust -->
                <div class="space-y-2.5">
                    <p class="font-prompt font-[600] text-xs uppercase tracking-wider text-slate-900">Commitment</p>
                    <ul class="space-y-1.5 text-xs text-stone-500 font-valley">
                        <li>WCAG 2.1 AAA Accessibility</li>
                        <li>Strict Privacy &bull; Zero Data Selling</li>
                        <li>Encrypted Health Records</li>
                    </ul>
                </div>

            </div>

            <div class="pt-8 border-t border-stone-200/60 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-stone-500 font-normal font-valley">
                <p>&copy; {{ date('Y') }} SilverCare. All rights reserved.</p>
                <p>Designed with love for families everywhere.</p>
            </div>

        </div>
    </footer>

</body>
</html>