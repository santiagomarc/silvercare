<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#FAF9F6" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0B1120" media="(prefers-color-scheme: dark)">
    <title>SilverCare — Dignified Senior Care & Quiet Peace of Mind</title>
    <meta name="description" content="SilverCare gives older adults effortless daily independence at home, while giving family members quiet, real-time reassurance without anxious phone calls.">

    <link rel="icon" type="image/png" href="{{ asset('assets/icons/silvercare.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/icons/silvercare.png') }}">

    {{-- Paint the correct theme before first paint so seniors who use dark or
         high-contrast mode never see a bright flash. Mirrors resources/js/app.js. --}}
    <script>
        (function () {
            var root = document.documentElement;
            try {
                var theme = localStorage.getItem('silvercare-theme');
                var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (theme === 'dark' || (!theme && prefersDark)) root.classList.add('dark');

                var contrast = localStorage.getItem('silvercare-high-contrast');
                var prefersContrast = window.matchMedia('(prefers-contrast: more)').matches;
                if (contrast === 'on' || (contrast === null && prefersContrast)) root.classList.add('high-contrast');

                var scale = parseFloat(localStorage.getItem('silvercare-text-scale'));
                if (scale >= 1 && scale <= 1.35) {
                    root.style.fontSize = (18 * scale) + 'px';
                    if (scale > 1) root.classList.add('sc-text-scaled');
                }
            } catch (e) { /* private browsing — defaults are fine */ }

            if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                root.classList.add('sc-anim');
            }
        })();
    </script>

    {{-- Typography: Prompt (display), Newsreader (editorial quote) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="font" type="font/woff2" crossorigin
          href="{{ asset('assets/fonts/valley-sans/ValleySans-Variable.woff2') }}">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@500;600;700;800&family=Newsreader:ital,opsz,wght@1,6..72,400;1,6..72,500&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* ════════════════════════════════════════════════════════════════
           SilverCare — Landing page
           Self-hosted Valley Sans + a page-scoped token layer.

           Everything visual is driven by custom properties declared on
           `.sc-page`, so light, dark and high-contrast modes are defined
           once instead of being sprinkled through the markup. The tokens
           also keep this page clear of the global `html.dark .bg-white`
           overrides in app.css, which would otherwise fight the layout.
           ════════════════════════════════════════════════════════════════ */

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
            font-weight: 500; font-style: normal; font-display: swap;
        }
        @font-face {
            font-family: 'Valley Sans';
            src: url("{{ asset('assets/fonts/valley-sans/ValleySans-SemiBold.woff2') }}") format('woff2');
            font-weight: 600; font-style: normal; font-display: swap;
        }

        /* ── Tokens: light ───────────────────────────────────────────── */
        .sc-page {
            --sc-canvas:        #FAF9F6;
            --sc-canvas-2:      #F3F1EA;
            --sc-surface:       #FFFFFF;
            --sc-surface-2:     #FBFAF6;
            --sc-surface-3:     #F5F3ED;

            --sc-ink:           #0B1120;
            --sc-body:          #475569;
            --sc-muted:         #5C6675;

            --sc-line:          rgba(15, 23, 42, .10);
            --sc-line-strong:   rgba(15, 23, 42, .17);
            --sc-grid:          rgba(15, 23, 42, .045);

            --sc-brand:         #000080;
            --sc-brand-hover:   #000066;
            --sc-brand-on:      #FFFFFF;
            --sc-brand-text:    #161C8F;
            --sc-brand-tint:    #EEF1FF;
            --sc-brand-line:    #D5DCFF;

            --sc-ok:            #047857;
            --sc-ok-tint:       #ECFDF5;
            --sc-ok-line:       #A7F3D0;

            --sc-warn:          #92400E;
            --sc-warn-tint:     #FFFBEB;
            --sc-warn-line:     #FDE68A;

            --sc-alert:         #BE123C;
            --sc-alert-tint:    #FFF1F2;
            --sc-alert-line:    #FECDD3;

            --sc-ring:          #000080;

            --sc-sh-sm: 0 1px 2px rgba(15,23,42,.05);
            --sc-sh:    0 1px 2px rgba(15,23,42,.05), 0 12px 28px -14px rgba(15,23,42,.18);
            --sc-sh-md: 0 2px 4px rgba(15,23,42,.05), 0 20px 44px -20px rgba(15,23,42,.22);
            --sc-sh-lg: 0 2px 6px rgba(15,23,42,.06), 0 36px 72px -28px rgba(15,23,42,.30);

            --sc-glow-a: rgba(52, 81, 209, .16);
            --sc-glow-b: rgba(4, 120, 87, .10);

            background-color: var(--sc-canvas);
            color: var(--sc-body);
            font-family: 'Valley Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
            overflow-wrap: break-word;
        }

        /* ── Tokens: dark ────────────────────────────────────────────── */
        html.dark .sc-page {
            --sc-canvas:        #0B1120;
            --sc-canvas-2:      #0E1526;
            --sc-surface:       #141D31;
            --sc-surface-2:     #18223A;
            --sc-surface-3:     #1D2842;

            --sc-ink:           #F1F5F9;
            --sc-body:          #C8D3E3;
            --sc-muted:         #9BA9BF;

            --sc-line:          rgba(148, 163, 184, .20);
            --sc-line-strong:   rgba(148, 163, 184, .34);
            --sc-grid:          rgba(148, 163, 184, .07);

            --sc-brand:         #3451D1;
            --sc-brand-hover:   #4763E0;
            --sc-brand-on:      #FFFFFF;
            --sc-brand-text:    #AEBEFF;
            --sc-brand-tint:    rgba(52, 81, 209, .18);
            --sc-brand-line:    rgba(122, 148, 255, .34);

            --sc-ok:            #6EE7B7;
            --sc-ok-tint:       rgba(5, 150, 105, .16);
            --sc-ok-line:       rgba(110, 231, 183, .30);

            --sc-warn:          #FCD34D;
            --sc-warn-tint:     rgba(180, 83, 9, .18);
            --sc-warn-line:     rgba(252, 211, 77, .28);

            --sc-alert:         #FDA4AF;
            --sc-alert-tint:    rgba(190, 18, 60, .18);
            --sc-alert-line:    rgba(253, 164, 175, .30);

            --sc-ring:          #A5B4FC;

            --sc-sh-sm: 0 1px 2px rgba(0,0,0,.4);
            --sc-sh:    0 1px 2px rgba(0,0,0,.4), 0 12px 28px -14px rgba(0,0,0,.7);
            --sc-sh-md: 0 2px 4px rgba(0,0,0,.4), 0 20px 44px -20px rgba(0,0,0,.75);
            --sc-sh-lg: 0 2px 6px rgba(0,0,0,.45), 0 36px 72px -28px rgba(0,0,0,.8);

            --sc-glow-a: rgba(52, 81, 209, .26);
            --sc-glow-b: rgba(16, 185, 129, .12);
        }

        /* The global `html.dark nav` rule in app.css paints a slate slab behind
           any <nav>. This page draws its own header surface, so opt out. */
        html.dark .sc-page nav {
            background-color: transparent !important;
            border-color: transparent !important;
        }

        /* ── Typography ──────────────────────────────────────────────── */
        .sc-page h1, .sc-page h2, .sc-page h3, .sc-page h4, .sc-display {
            font-family: 'Prompt', 'Valley Sans', sans-serif;
            color: var(--sc-ink);
            text-wrap: balance;
        }
        .sc-page h1, .sc-page h2, .sc-display { font-weight: 800; letter-spacing: -.025em; }
        .sc-page h3, .sc-page h4            { font-weight: 600; letter-spacing: -.012em; }

        .sc-h1    { font-size: clamp(2.3rem, 1.6rem + 2.9vw, 3.9rem); line-height: 1.08; }
        .sc-h2    { font-size: clamp(1.85rem, 1.35rem + 2.1vw, 2.95rem); line-height: 1.14; }
        .sc-h3    { font-size: clamp(1.15rem, 1.05rem + .45vw, 1.4rem);  line-height: 1.32; }
        .sc-lead  { font-size: clamp(1.1rem, 1.02rem + .5vw, 1.35rem);   line-height: 1.62; color: var(--sc-body); }
        .sc-quote { font-family: 'Newsreader', Georgia, serif; font-style: italic; font-weight: 400; }

        .sc-eyebrow {
            font-family: 'Prompt', sans-serif;
            font-weight: 600;
            font-size: .82rem;
            letter-spacing: .13em;
            text-transform: uppercase;
            color: var(--sc-brand-text);
        }
        .sc-num { font-variant-numeric: tabular-nums; }

        /* ── Focus: always visible, never clipped ────────────────────── */
        .sc-page :focus-visible {
            outline: 3px solid var(--sc-ring);
            outline-offset: 3px;
            border-radius: 6px;
        }
        .sc-skip {
            position: fixed; top: .75rem; left: .75rem; z-index: 90;
            transform: translateY(-160%);
            transition: transform .18s ease-out;
            background: var(--sc-brand); color: var(--sc-brand-on);
            padding: .85rem 1.25rem; border-radius: 999px; font-weight: 600;
            box-shadow: var(--sc-sh-md);
        }
        .sc-skip:focus { transform: translateY(0); }

        /* Sticky header must never cover the element the keyboard just
           focused, nor the heading an anchor jumps to (WCAG 2.2 §2.4.11). */
        .sc-page [id] { scroll-margin-top: 6.5rem; }

        /* ── Surfaces ────────────────────────────────────────────────── */
        .sc-card {
            background: var(--sc-surface);
            border: 1px solid var(--sc-line);
            border-radius: 1.5rem;
            box-shadow: var(--sc-sh);
        }
        .sc-card-quiet {
            background: var(--sc-surface-2);
            border: 1px solid var(--sc-line);
            border-radius: 1.25rem;
        }
        .sc-lift {
            transition: transform .28s cubic-bezier(.16,1,.3,1),
                        box-shadow .28s cubic-bezier(.16,1,.3,1),
                        border-color .28s ease;
        }
        @media (hover: hover) {
            .sc-lift:hover { transform: translateY(-4px); box-shadow: var(--sc-sh-md); border-color: var(--sc-line-strong); }
        }

        /* A hairline of brand light along the top edge — the only "shine" on
           the page, used to mark the three primary feature cards. */
        .sc-card-crest { position: relative; overflow: hidden; }
        .sc-card-crest::before {
            content: ''; position: absolute; inset-inline: 1.5rem; top: 0; height: 1px;
            background: linear-gradient(90deg, transparent, var(--sc-crest, var(--sc-brand-line)), transparent);
        }

        /* ── Buttons ─────────────────────────────────────────────────── */
        .sc-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: .55rem;
            min-height: 3.25rem; padding: .85rem 1.75rem;
            border-radius: 999px;
            font-weight: 600; font-size: 1.05rem; line-height: 1;
            cursor: pointer;
            transition: transform .16s cubic-bezier(.16,1,.3,1), box-shadow .2s ease,
                        background-color .18s ease, border-color .18s ease, color .18s ease;
        }
        .sc-btn-primary {
            background: var(--sc-brand); color: var(--sc-brand-on);
            box-shadow: var(--sc-sh), inset 0 1px 0 rgba(255,255,255,.16);
        }
        .sc-btn-primary:hover { background: var(--sc-brand-hover); box-shadow: var(--sc-sh-md), inset 0 1px 0 rgba(255,255,255,.16); transform: translateY(-2px); }
        .sc-btn-primary:active { transform: translateY(0) scale(.985); }
        .sc-btn-primary .sc-arrow { transition: transform .22s cubic-bezier(.16,1,.3,1); }
        .sc-btn-primary:hover .sc-arrow { transform: translateX(3px); }

        .sc-btn-ghost {
            background: var(--sc-surface); color: var(--sc-ink);
            border: 1px solid var(--sc-line-strong);
            box-shadow: var(--sc-sh-sm);
        }
        .sc-btn-ghost:hover { background: var(--sc-surface-3); transform: translateY(-2px); box-shadow: var(--sc-sh); }
        .sc-btn-ghost:active { transform: translateY(0) scale(.985); }

        .sc-btn-sm { min-height: 2.75rem; padding: .6rem 1.2rem; font-size: .95rem; }

        /* Every icon-only control keeps a 48px hit area regardless of glyph size. */
        .sc-icon-btn {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 3rem; min-height: 3rem; border-radius: 999px;
            color: var(--sc-body); cursor: pointer;
            transition: background-color .18s ease, color .18s ease;
        }
        .sc-icon-btn:hover { background: var(--sc-surface-3); color: var(--sc-ink); }

        /* ── Chips, plates, rails ────────────────────────────────────── */
        .sc-chip {
            display: inline-flex; align-items: center; gap: .5rem;
            padding: .45rem .9rem; border-radius: 999px;
            font-size: .92rem; font-weight: 500; line-height: 1.3;
            border: 1px solid var(--sc-line); background: var(--sc-surface); color: var(--sc-body);
        }
        .sc-chip-ok    { background: var(--sc-ok-tint);    border-color: var(--sc-ok-line);    color: var(--sc-ok); }
        .sc-chip-warn  { background: var(--sc-warn-tint);  border-color: var(--sc-warn-line);  color: var(--sc-warn); }
        .sc-chip-brand { background: var(--sc-brand-tint); border-color: var(--sc-brand-line); color: var(--sc-brand-text); }

        .sc-plate {
            display: inline-flex; align-items: center; justify-content: center;
            width: 3.25rem; height: 3.25rem; border-radius: 1rem; flex: none;
            background: var(--sc-brand-tint); color: var(--sc-brand-text);
            border: 1px solid var(--sc-brand-line);
            box-shadow: inset 0 1px 0 rgba(255,255,255,.5);
        }
        html.dark .sc-plate { box-shadow: inset 0 1px 0 rgba(255,255,255,.06); }
        .sc-plate-ok    { background: var(--sc-ok-tint);    color: var(--sc-ok);    border-color: var(--sc-ok-line); }
        .sc-plate-alert { background: var(--sc-alert-tint); color: var(--sc-alert); border-color: var(--sc-alert-line); }
        .sc-plate-sm    { width: 2.5rem; height: 2.5rem; border-radius: .8rem; }

        .sc-i { flex: none; }

        .sc-divide > * + * { border-top: 1px solid var(--sc-line); }

        /* Grid items default to min-width:auto, so one long word inside a
           card can widen its whole track and push the page sideways on a
           narrow phone. Let tracks shrink; `break-word` above keeps the
           word itself from spilling. */
        .sc-page .grid > * { min-width: 0; }

        /* ── Ambient hero field ──────────────────────────────────────── */
        .sc-field { position: relative; isolation: isolate; }
        .sc-field::before,
        .sc-field::after { content: ''; position: absolute; inset: 0; z-index: -1; pointer-events: none; }
        .sc-field::before {
            background-image:
                linear-gradient(var(--sc-grid) 1px, transparent 1px),
                linear-gradient(90deg, var(--sc-grid) 1px, transparent 1px);
            background-size: 72px 72px;
            -webkit-mask-image: radial-gradient(ellipse 78% 62% at 50% 0%, #000 35%, transparent 100%);
                    mask-image: radial-gradient(ellipse 78% 62% at 50% 0%, #000 35%, transparent 100%);
        }
        .sc-field::after {
            background:
                radial-gradient(58% 46% at 78% 4%,  var(--sc-glow-a), transparent 72%),
                radial-gradient(48% 40% at 8% 14%, var(--sc-glow-b), transparent 72%);
        }

        /* ── Section rhythm ──────────────────────────────────────────── */
        .sc-section     { padding-block: clamp(4.5rem, 3rem + 6vw, 8rem); }
        .sc-band        { background: var(--sc-canvas-2); }
        .sc-band-solid  { background: var(--sc-surface); }
        .sc-hair        { border-top: 1px solid var(--sc-line); }

        /* ── Scroll reveal (opt-in, motion-safe) ─────────────────────── */
        html.sc-anim .sc-reveal {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity .7s cubic-bezier(.16,1,.3,1) var(--sc-d, 0ms),
                        transform .7s cubic-bezier(.16,1,.3,1) var(--sc-d, 0ms);
            will-change: opacity, transform;
        }
        html.sc-anim .sc-reveal.is-in { opacity: 1; transform: none; will-change: auto; }

        /* ── Device frame for the live product preview ───────────────── */
        .sc-frame {
            background: var(--sc-surface);
            border: 1px solid var(--sc-line-strong);
            border-radius: 1.85rem;
            box-shadow: var(--sc-sh-lg);
            overflow: hidden;
        }
        .sc-frame-bar {
            display: flex; align-items: center; gap: .5rem;
            padding: .85rem 1.15rem;
            background: var(--sc-surface-3);
            border-bottom: 1px solid var(--sc-line);
        }
        .sc-dot { width: .6rem; height: .6rem; border-radius: 999px; background: var(--sc-line-strong); }

        /* Offset backing panel: cheap, honest depth behind the preview. */
        .sc-frame-shadowplate {
            position: absolute; inset: 0; z-index: -1;
            transform: translate(16px, 18px);
            border-radius: 2.1rem;
            background: var(--sc-brand-tint);
            border: 1px solid var(--sc-brand-line);
        }

        /* ── Tabs ────────────────────────────────────────────────────── */
        .sc-tablist {
            display: inline-flex; flex-wrap: wrap; max-width: 100%;
            padding: .32rem; gap: .25rem;
            border-radius: 999px;
            background: var(--sc-surface-3);
            border: 1px solid var(--sc-line);
        }
        .sc-tab {
            min-height: 2.9rem; padding: .55rem 1.15rem;
            border-radius: 999px; cursor: pointer;
            font-weight: 600; font-size: .95rem; color: var(--sc-body);
            transition: background-color .2s ease, color .2s ease, box-shadow .2s ease;
        }
        .sc-tab:hover { color: var(--sc-ink); }
        @media (max-width: 420px) {
            .sc-tab { padding-inline: .9rem; font-size: .9rem; }
        }
        .sc-tab[aria-selected="true"] {
            background: var(--sc-brand); color: var(--sc-brand-on);
            box-shadow: var(--sc-sh-sm), inset 0 1px 0 rgba(255,255,255,.18);
        }

        /* ── Numbered steps with a connecting rail ───────────────────── */
        .sc-step-num {
            display: inline-flex; align-items: center; justify-content: center;
            width: 3rem; height: 3rem; border-radius: 999px; flex: none;
            font-family: 'Prompt', sans-serif; font-weight: 700; font-size: 1.15rem;
            background: var(--sc-brand); color: var(--sc-brand-on);
            box-shadow: var(--sc-sh);
        }
        @media (min-width: 768px) {
            .sc-rail { position: relative; }
            .sc-rail::before {
                content: ''; position: absolute; top: 1.5rem;
                left: 1.5rem; right: calc(33.33% - 1.5rem); height: 2px;
                background: var(--sc-brand-line);
            }
        }

        /* ── Timeline rail for the caregiver notification story ──────── */
        .sc-tl { position: relative; }
        .sc-tl > li { position: relative; padding-left: 3.4rem; }
        /* One segment per item rather than one rail for the list, so the line
           stops at the last dot instead of trailing past it. */
        .sc-tl > li:not(:last-child)::before {
            content: ''; position: absolute; left: 1.06rem; top: 2.45rem; bottom: -1.6rem;
            width: 2px; background: var(--sc-line);
        }
        .sc-tl-dot {
            position: absolute; left: 0; top: .1rem;
            width: 2.25rem; height: 2.25rem; border-radius: 999px;
            display: inline-flex; align-items: center; justify-content: center;
            background: var(--sc-surface); border: 1px solid var(--sc-line); color: var(--sc-muted);
        }

        /* ── Closing CTA panel ───────────────────────────────────────── */
        .sc-cta-panel {
            position: relative; overflow: hidden;
            border-radius: 2.25rem;
            background:
                radial-gradient(90% 120% at 12% 0%, #16227A 0%, transparent 60%),
                radial-gradient(80% 110% at 92% 100%, #2A3FAD 0%, transparent 58%),
                #000080;
            box-shadow: var(--sc-sh-lg);
        }
        html.dark .sc-cta-panel {
            background:
                radial-gradient(90% 120% at 12% 0%, #24337F 0%, transparent 60%),
                radial-gradient(80% 110% at 92% 100%, #3451D1 0%, transparent 58%),
                #10193C;
        }
        .sc-cta-panel::after {
            content: ''; position: absolute; inset: 0; pointer-events: none;
            background-image:
                linear-gradient(rgba(255,255,255,.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.05) 1px, transparent 1px);
            background-size: 56px 56px;
            -webkit-mask-image: radial-gradient(ellipse 70% 80% at 50% 0%, #000, transparent 78%);
                    mask-image: radial-gradient(ellipse 70% 80% at 50% 0%, #000, transparent 78%);
        }

        /* ── FAQ ─────────────────────────────────────────────────────── */
        .sc-faq-q {
            width: 100%; display: flex; align-items: center; justify-content: space-between; gap: 1.25rem;
            min-height: 3.75rem; padding: 1.15rem 1.4rem; text-align: left; cursor: pointer;
            font-family: 'Prompt', sans-serif; font-weight: 600; font-size: 1.08rem;
            color: var(--sc-ink);
            transition: background-color .18s ease;
        }
        .sc-faq-q:hover { background: var(--sc-surface-3); }
        .sc-faq-chev { transition: transform .25s cubic-bezier(.16,1,.3,1); color: var(--sc-muted); }
        .sc-faq-q[aria-expanded="true"] .sc-faq-chev { transform: rotate(180deg); }

        /* ── Links ───────────────────────────────────────────────────── */
        .sc-link { color: var(--sc-body); transition: color .18s ease; }
        .sc-link:hover { color: var(--sc-ink); }
        footer .sc-link { display: inline-block; padding-block: .3rem; }
        .sc-nav-link {
            position: relative; padding: .5rem .2rem;
            font-weight: 500; color: var(--sc-body);
            transition: color .18s ease;
        }
        .sc-nav-link::after {
            content: ''; position: absolute; left: 0; right: 0; bottom: .1rem; height: 2px;
            background: var(--sc-brand-text); border-radius: 2px;
            transform: scaleX(0); transform-origin: left;
            transition: transform .24s cubic-bezier(.16,1,.3,1);
        }
        .sc-nav-link:hover { color: var(--sc-ink); }
        .sc-nav-link:hover::after { transform: scaleX(1); }

        /* ── Header ──────────────────────────────────────────────────── */
        .sc-header {
            background: color-mix(in srgb, var(--sc-canvas) 82%, transparent);
            border-bottom: 1px solid transparent;
            transition: background-color .25s ease, border-color .25s ease, box-shadow .25s ease;
        }
        @supports not (background: color-mix(in srgb, red 50%, blue)) {
            .sc-header { background: var(--sc-canvas); }
        }
        .sc-header.is-stuck {
            border-bottom-color: var(--sc-line);
            box-shadow: 0 8px 24px -18px rgba(15,23,42,.35);
            -webkit-backdrop-filter: saturate(180%) blur(14px);
                    backdrop-filter: saturate(180%) blur(14px);
        }

        .sc-switch {
            width: 3.1rem; height: 1.75rem; border-radius: 999px; flex: none;
            background: var(--sc-line-strong); position: relative; cursor: pointer;
            transition: background-color .22s ease;
        }
        .sc-switch[aria-checked="true"] { background: var(--sc-brand); }
        /* Visual pill stays 31px tall; the pressable area does not. */
        .sc-switch::before { content: ''; position: absolute; inset: -.72rem -.4rem; }
        .sc-switch::after {
            content: ''; position: absolute; top: .19rem; left: .19rem;
            width: 1.37rem; height: 1.37rem; border-radius: 999px; background: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,.28);
            transition: transform .22s cubic-bezier(.16,1,.3,1);
        }
        .sc-switch[aria-checked="true"]::after { transform: translateX(1.35rem); }

        .sc-scale-on {
            background: var(--sc-brand) !important;
            color: var(--sc-brand-on) !important;
            border-color: transparent !important;
        }

        /* A navy outline is invisible on a navy panel. */
        .sc-cta-panel :focus-visible { outline-color: #FFFFFF; }

        /* ── Tokens: high contrast ───────────────────────────────────
           app.css blacks out <body> and lifts text, but it knows nothing
           about this page's surfaces. Without this the section bands would
           stay cream while the text went white. */
        html.high-contrast .sc-page {
            --sc-canvas:      #000000;
            --sc-canvas-2:    #000000;
            --sc-surface:     #0A0A0A;
            --sc-surface-2:   #0A0A0A;
            --sc-surface-3:   #171717;
            --sc-ink:         #FFFFFF;
            --sc-body:        #FFFFFF;
            --sc-muted:       #E5E7EB;
            --sc-line:        #FFFFFF;
            --sc-line-strong: #FFFFFF;
            --sc-grid:        transparent;
            --sc-brand:       #0B2FD0;
            --sc-brand-on:    #FFFFFF;
            --sc-brand-text:  #7DD3FC;
            --sc-brand-tint:  #0A0A0A;
            --sc-brand-line:  #FFFFFF;
            --sc-ok:          #4ADE80;
            --sc-ok-tint:     #0A0A0A;
            --sc-ok-line:     #FFFFFF;
            --sc-warn:        #FDE047;
            --sc-warn-tint:   #0A0A0A;
            --sc-warn-line:   #FFFFFF;
            --sc-alert:       #FCA5A5;
            --sc-alert-tint:  #0A0A0A;
            --sc-alert-line:  #FFFFFF;
            --sc-ring:        #FDE047;
        }
        html.high-contrast .sc-cta-panel {
            background: #0A0A0A;
            border: 2px solid #FFFFFF;
        }
        /* app.css recolours every <a> to #7DD3FC in this mode, which landed
           pale blue on the white CTA button. Give both buttons the dark
           treatment the rest of high-contrast mode uses. */
        html.high-contrast .sc-cta-panel .sc-btn {
            background: #0A0A0A !important;
            color: #7DD3FC !important;
            border: 2px solid #FFFFFF !important;
            box-shadow: none !important;
        }

        /* High-contrast mode already forces its own palette globally; make sure
           this page's decorative fields step out of the way. */
        html.high-contrast .sc-field::before,
        html.high-contrast .sc-field::after,
        html.high-contrast .sc-cta-panel::after,
        html.high-contrast .sc-frame-shadowplate { display: none; }
        html.high-contrast .sc-page,
        html.high-contrast .sc-card,
        html.high-contrast .sc-card-quiet,
        html.high-contrast .sc-frame,
        html.high-contrast .sc-cta-panel { box-shadow: none !important; }

        /* ── Header navigation breakpoint ────────────────────────────
           Below this the bar folds into the menu rather than wrapping its
           labels. `rem` is no help here — inside a media query it resolves
           against the browser's initial 16px, not the document root — so
           the raised-text case is handled by an explicit class instead. */
        .sc-nav-desktop  { display: none; }
        .sc-menu-toggle  { display: inline-flex; }
        @media (min-width: 1360px) {
            .sc-nav-desktop { display: flex; }
            .sc-menu-toggle { display: none; }
            #sc-mobile-nav  { display: none !important; }
        }
        html.sc-text-scaled .sc-nav-desktop { display: none; }
        html.sc-text-scaled .sc-menu-toggle { display: inline-flex; }

        /* Small, old handsets are squarely in this audience. At 320px the
           full-size wordmark crowded the menu button off the screen. */
        @media (max-width: 360px) {
            .sc-brand-mark { width: 2.4rem; height: 2.4rem; padding: .4rem; }
            .sc-brand-word { font-size: 1.05rem; }
        }

        /* ── Display utilities, re-asserted ──────────────────────────
           The Vite stylesheet is linked before this block, so a component
           class that sets `display` (.sc-btn, .sc-icon-btn, .sc-chip …)
           would otherwise win over Tailwind's `hidden` at equal specificity
           and leak desktop-only controls onto small screens. Scoping the
           handful of variants this page uses under .sc-page restores the
           intended cascade. */
        .sc-page .hidden { display: none; }
        @media (min-width: 640px) {
            .sc-page .sm\:block       { display: block; }
            .sc-page .sm\:inline      { display: inline; }
            .sc-page .sm\:inline-flex { display: inline-flex; }
            .sc-page .sm\:flex        { display: flex; }
            .sc-page .sm\:grid        { display: grid; }
            .sc-page .sm\:hidden      { display: none; }
        }
        @media (min-width: 768px) {
            .sc-page .md\:inline-flex { display: inline-flex; }
            .sc-page .md\:grid        { display: grid; }
        }
        @media (min-width: 1024px) {
            .sc-page .lg\:grid        { display: grid; }
        }

        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="sc-page antialiased selection:bg-[#000080] selection:text-white">

<a class="sc-skip" href="#main-content">Skip to main content</a>

{{-- ────────────────────────────────────────────────────────────────────
     Icon sprite — Lucide geometry, 1.75 stroke, single visual language.
     Emoji were replaced throughout: they render differently on every
     platform, ignore the colour tokens, and are read aloud verbatim by
     screen readers.
     ──────────────────────────────────────────────────────────────────── --}}
<svg aria-hidden="true" focusable="false" style="position:absolute;width:0;height:0;overflow:hidden">
  <defs>
    <g id="sc-icon-defaults"></g>
  </defs>
  <symbol id="i-arrow-right" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></symbol>
  <symbol id="i-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></symbol>
  <symbol id="i-check-circle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M21.8 10A10 10 0 1 1 17 3.34"/><path d="m9 11 3 3L22 4"/></symbol>
  <symbol id="i-shield" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></symbol>
  <symbol id="i-lock" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></symbol>
  <symbol id="i-pill" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z"/><path d="m8.5 8.5 7 7"/></symbol>
  <symbol id="i-pulse" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/><path d="M3.22 13H9.5l.5-1 2 4.5 2-7 1.5 3.5h5.27"/></symbol>
  <symbol id="i-activity" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"/></symbol>
  <symbol id="i-bell" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M10.27 21a2 2 0 0 0 3.46 0"/><path d="M3.26 15.33A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.67C19.41 13.96 18 12.5 18 8A6 6 0 0 0 6 8c0 4.5-1.41 5.96-2.74 7.33"/></symbol>
  <symbol id="i-map-pin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 4.99-5.54 10.19-7.4 11.8a1 1 0 0 1-1.2 0C9.54 20.19 4 14.99 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></symbol>
  <symbol id="i-users" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M18 21a8 8 0 0 0-16 0"/><circle cx="10" cy="8" r="5"/><path d="M22 20c0-3.37-2-6.5-4-8a5 5 0 0 0-.45-8.3"/></symbol>
  <symbol id="i-siren" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M7 18v-6a5 5 0 1 1 10 0v6"/><path d="M5 21a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-1a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2z"/><path d="M21 12h1"/><path d="M18.5 4.5 18 5"/><path d="M2 12h1"/><path d="M12 2v1"/><path d="M5.5 4.5 6 5"/></symbol>
  <symbol id="i-mic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19v3"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><rect x="9" y="2" width="6" height="13" rx="3"/></symbol>
  <symbol id="i-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></symbol>
  <symbol id="i-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></symbol>
  <symbol id="i-sprout" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M7 20h10"/><path d="M10 20c5.5-2.5.8-6.4 3-10"/><path d="M9.5 9.4c1.1.8 1.8 2.2 2.3 3.7-2 .4-3.5.4-4.8-.3-1.2-.6-2.3-1.9-3-4.2 2.8-.5 4.4 0 5.5.8z"/><path d="M14.1 6a7 7 0 0 0-1.1 4c1.9-.1 3.3-.6 4.3-1.4 1-1 1.6-2.3 1.7-4.6-2.7.1-4 1-4.9 2z"/></symbol>
  <symbol id="i-home" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"/><path d="M3 10a2 2 0 0 1 .71-1.53l7-6a2 2 0 0 1 2.58 0l7 6A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></symbol>
  <symbol id="i-clock" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></symbol>
  <symbol id="i-phone" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M13.83 16.57a1 1 0 0 0 1.22-.3l.35-.47A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.47.35a1 1 0 0 0-.29 1.24 14 14 0 0 0 6.39 6.38"/></symbol>
  <symbol id="i-device" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="20" x="5" y="2" rx="2.5"/><path d="M12 18h.01"/></symbol>
  <symbol id="i-clipboard" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect width="8" height="4" x="8" y="2" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/></symbol>
  <symbol id="i-accessibility" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="16" cy="4" r="1"/><path d="m18 19 1-7-6 1"/><path d="m5 8 3-3 5.5 3-2.36 3.5"/><path d="M4.24 14.5a5 5 0 0 0 6.88 6"/><path d="M13.76 17.5a5 5 0 0 0-6.88-6"/></symbol>
  <symbol id="i-type" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7V4h16v3"/><path d="M9 20h6"/><path d="M12 4v16"/></symbol>
  <symbol id="i-contrast" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 18a6 6 0 0 0 0-12z"/></symbol>
  <symbol id="i-menu" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h16"/></symbol>
  <symbol id="i-close" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></symbol>
  <symbol id="i-chevron-down" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></symbol>
  <symbol id="i-undo" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"/></symbol>
  <symbol id="i-quote" viewBox="0 0 24 24" fill="currentColor" stroke="none"><path d="M9.5 5.5A6.5 6.5 0 0 0 3 12v6.5h6.5V12H6.2a3.3 3.3 0 0 1 3.3-3.3zm11 0A6.5 6.5 0 0 0 14 12v6.5h6.5V12h-3.3a3.3 3.3 0 0 1 3.3-3.3z"/></symbol>
  <symbol id="i-sliders" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round"><path d="M14 17H4"/><path d="M20 7h-9"/><circle cx="17" cy="17" r="3"/><circle cx="7" cy="7" r="3"/></symbol>
</svg>

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
            <a href="#main-content" class="flex items-center gap-3 shrink-0 group">
                <span class="sc-brand-mark w-11 h-11 rounded-2xl p-2 flex items-center justify-center"
                      style="background:var(--sc-surface);border:1px solid var(--sc-line);box-shadow:var(--sc-sh-sm)">
                    <img src="{{ asset('assets/icons/silvercare.png') }}" alt="" class="w-full h-full object-contain">
                </span>
                <span class="sc-brand-word sc-display text-[1.35rem] leading-none" style="color:var(--sc-ink)">SilverCare</span>
                <span class="sr-only">SilverCare home</span>
            </a>

            {{-- Primary navigation (desktop) --}}
            <nav class="sc-nav-desktop items-center gap-6" aria-label="Primary">
                <a href="#for-seniors"  class="sc-nav-link whitespace-nowrap">Older adults</a>
                <a href="#for-family"   class="sc-nav-link whitespace-nowrap">For family</a>
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
                <div class="relative" x-data="scDisplayControls()" @keydown.escape.window="open = false">
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
                         class="absolute right-0 mt-3 w-[19rem] p-5 space-y-5 sc-card z-50"
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
                                            class="sc-btn sc-btn-ghost !min-h-[3rem] !px-2"
                                            :class="scale === opt.value && 'sc-scale-on'"
                                            :style="`font-size:${opt.preview}`"
                                            x-text="opt.label"></button>
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
            <ul class="sc-card p-2.5 sc-divide">
                <li><a href="#for-seniors"  @click="mobile = false" class="flex items-center justify-between px-4 py-4 min-h-[3.25rem] font-medium sc-link">For older adults <svg class="sc-i w-5 h-5 opacity-50" aria-hidden="true" focusable="false"><use href="#i-arrow-right"/></svg></a></li>
                <li><a href="#for-family"   @click="mobile = false" class="flex items-center justify-between px-4 py-4 min-h-[3.25rem] font-medium sc-link">For family <svg class="sc-i w-5 h-5 opacity-50" aria-hidden="true" focusable="false"><use href="#i-arrow-right"/></svg></a></li>
                <li><a href="#emergency"    @click="mobile = false" class="flex items-center justify-between px-4 py-4 min-h-[3.25rem] font-medium sc-link">Emergency <svg class="sc-i w-5 h-5 opacity-50" aria-hidden="true" focusable="false"><use href="#i-arrow-right"/></svg></a></li>
                <li><a href="#how-it-works" @click="mobile = false" class="flex items-center justify-between px-4 py-4 min-h-[3.25rem] font-medium sc-link">How it works <svg class="sc-i w-5 h-5 opacity-50" aria-hidden="true" focusable="false"><use href="#i-arrow-right"/></svg></a></li>
                <li><a href="#questions"    @click="mobile = false" class="flex items-center justify-between px-4 py-4 min-h-[3.25rem] font-medium sc-link">Questions <svg class="sc-i w-5 h-5 opacity-50" aria-hidden="true" focusable="false"><use href="#i-arrow-right"/></svg></a></li>
            </ul>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-4">
                <a href="{{ route('register') }}" class="sc-btn sc-btn-primary w-full">Get started free</a>
                <a href="{{ route('login') }}" class="sc-btn sc-btn-ghost w-full">Sign in</a>
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
    <section class="sc-field pt-14 pb-20 md:pt-20 md:pb-28 overflow-hidden">
        <div class="mx-auto max-w-7xl px-5 sm:px-8">
            <div class="grid lg:grid-cols-12 gap-14 xl:gap-20 items-center">

                {{-- ── Promise ─────────────────────────────────────── --}}
                <div class="lg:col-span-6 min-w-0 sc-reveal is-in">

                    <p class="sc-chip sc-chip-brand mb-7">
                        <svg class="sc-i w-4 h-4" aria-hidden="true" focusable="false"><use href="#i-sprout"/></svg>
                        Designed with older adults, not just for them
                    </p>

                    <h1 class="sc-h1">
                        Care that feels like family.
                        <span style="color:var(--sc-brand-text)">Technology that stays out of the way.</span>
                    </h1>

                    <p class="sc-lead mt-7 max-w-xl">
                        SilverCare gives older adults effortless daily independence at home — and gives
                        family members quiet, real-time reassurance, without the anxious phone calls.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-4 mt-9">
                        <a href="{{ route('register') }}" class="sc-btn sc-btn-primary w-full sm:w-auto">
                            Try SilverCare free
                            <svg class="sc-i sc-arrow w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-arrow-right"/></svg>
                        </a>
                        <a href="#how-it-works" class="sc-btn sc-btn-ghost w-full sm:w-auto">
                            See how it works
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
                <div class="lg:col-span-6 min-w-0" id="dual-experience" x-data="scDualView()">

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

                                    <p class="mt-4 pt-4 border-t text-sm"
                                       x-show="taken" x-cloak
                                       style="border-color:var(--sc-ok-line);color:var(--sc-ok)">
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
         TRUST STRIP
         ════════════════════════════════════════════════════════════════ --}}
    <section class="sc-hair sc-band-solid py-8" aria-label="What SilverCare commits to">
        <div class="mx-auto max-w-7xl px-5 sm:px-8">
            <ul class="grid grid-cols-2 lg:grid-cols-4 gap-6 lg:gap-4">
                <li class="flex items-center gap-3.5 sc-reveal">
                    <svg class="sc-i w-6 h-6" style="color:var(--sc-brand-text)" aria-hidden="true" focusable="false"><use href="#i-lock"/></svg>
                    <span class="font-medium" style="color:var(--sc-body)">Encrypted health records</span>
                </li>
                <li class="flex items-center gap-3.5 sc-reveal" style="--sc-d:80ms">
                    <svg class="sc-i w-6 h-6" style="color:var(--sc-brand-text)" aria-hidden="true" focusable="false"><use href="#i-shield"/></svg>
                    <span class="font-medium" style="color:var(--sc-body)">Your data is never sold</span>
                </li>
                <li class="flex items-center gap-3.5 sc-reveal" style="--sc-d:160ms">
                    <svg class="sc-i w-6 h-6" style="color:var(--sc-brand-text)" aria-hidden="true" focusable="false"><use href="#i-accessibility"/></svg>
                    <span class="font-medium" style="color:var(--sc-body)">Built to WCAG 2.1 AA</span>
                </li>
                <li class="flex items-center gap-3.5 sc-reveal" style="--sc-d:240ms">
                    <svg class="sc-i w-6 h-6" style="color:var(--sc-brand-text)" aria-hidden="true" focusable="false"><use href="#i-device"/></svg>
                    <span class="font-medium" style="color:var(--sc-body)">Runs on the device they own</span>
                </li>
            </ul>
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
         EDITORIAL QUOTE
         ════════════════════════════════════════════════════════════════ --}}
    <section class="sc-section sc-hair sc-band">
        <div class="mx-auto max-w-4xl px-5 sm:px-8">
            <figure class="relative sc-reveal">
                <svg class="sc-i w-12 h-12 mx-auto mb-8" style="color:var(--sc-brand-line)" aria-hidden="true" focusable="false"><use href="#i-quote"/></svg>

                <blockquote class="sc-quote text-center leading-[1.35]"
                            style="font-size:clamp(1.6rem, 1.1rem + 2.2vw, 2.75rem);color:var(--sc-ink)">
                    SilverCare gave my dad his independence back. And for the first time in three years,
                    I sleep through the night knowing he is safe.
                </blockquote>

                <figcaption class="flex items-center justify-center gap-4 mt-10">
                    <span class="sc-plate" aria-hidden="true">
                        <span class="sc-display text-[1.1rem]" style="color:var(--sc-brand-text)">SP</span>
                    </span>
                    <span class="text-left">
                        <span class="sc-display block text-[1.1rem]" style="font-weight:600">Sarah Pendelton</span>
                        <span class="block text-sm" style="color:var(--sc-muted)">Daughter and primary caregiver · Chicago, Illinois</span>
                    </span>
                </figcaption>
            </figure>
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

    {{-- ════════════════════════════════════════════════════════════════
         CLOSING CTA
         ════════════════════════════════════════════════════════════════ --}}
    <section class="sc-section sc-hair">
        <div class="mx-auto max-w-6xl px-5 sm:px-8">
            <div class="sc-cta-panel px-6 py-16 sm:px-14 sm:py-20 text-center sc-reveal">
                <div class="relative max-w-2xl mx-auto" style="z-index:1">

                    <h2 class="sc-h2" style="color:#FFFFFF">
                        Start protecting the people you love, quietly.
                    </h2>

                    <p class="sc-lead mt-6" style="color:rgba(255,255,255,.86)">
                        Set your family up in under two minutes, on the tablet, phone or computer
                        that's already sitting on the table.
                    </p>

                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mt-10">
                        <a href="{{ route('register') }}"
                           class="sc-btn w-full sm:w-auto"
                           style="background:#FFFFFF;color:#000080;box-shadow:0 12px 32px -12px rgba(0,0,0,.55)">
                            Try SilverCare free
                            <svg class="sc-i sc-arrow w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-arrow-right"/></svg>
                        </a>
                        <a href="{{ route('login') }}"
                           class="sc-btn w-full sm:w-auto"
                           style="background:rgba(255,255,255,.10);color:#FFFFFF;border:1px solid rgba(255,255,255,.34)">
                            Sign in
                        </a>
                    </div>

                    <ul class="flex flex-wrap items-center justify-center gap-x-7 gap-y-3 mt-10" style="color:rgba(255,255,255,.78)">
                        <li class="flex items-center gap-2.5">
                            <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-check-circle"/></svg>
                            Free for families
                        </li>
                        <li class="flex items-center gap-2.5">
                            <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-lock"/></svg>
                            Private and encrypted
                        </li>
                        <li class="flex items-center gap-2.5">
                            <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-device"/></svg>
                            No hardware to purchase
                        </li>
                    </ul>
                </div>
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
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl p-1.5 flex items-center justify-center"
                          style="background:var(--sc-surface);border:1px solid var(--sc-line)">
                        <img src="{{ asset('assets/icons/silvercare.png') }}" alt="" class="w-full h-full object-contain">
                    </span>
                    <span class="sc-display text-[1.2rem]" style="color:var(--sc-ink)">SilverCare</span>
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

<script>
    /* ────────────────────────────────────────────────────────────────
       Alpine components for this page.
       Declared on `window` before the Vite module runs, so Alpine can
       resolve them from `x-data` when it starts.
       ──────────────────────────────────────────────────────────────── */

    /**
     * Display & accessibility menu.
     *
     * Text scale multiplies the 18px root set in app.css, so the whole
     * rem-based layout grows with it instead of only the copy. Dark mode
     * reuses the app-wide helper; high contrast writes the same storage
     * key that resources/js/utils/high-contrast.js reads.
     */
    window.scDisplayControls = function scDisplayControls() {
        return {
            open: false,
            dark: false,
            contrast: false,
            scale: 1,
            scales: [
                { value: 1,    label: 'A', preview: '1rem',    aria: 'Standard text size' },
                { value: 1.12, label: 'A', preview: '1.25rem', aria: 'Large text size' },
                { value: 1.25, label: 'A', preview: '1.5rem',  aria: 'Extra large text size' },
            ],

            init() {
                const root = document.documentElement;
                this.dark = root.classList.contains('dark');
                this.contrast = root.classList.contains('high-contrast');

                let stored = NaN;
                try { stored = parseFloat(localStorage.getItem('silvercare-text-scale')); } catch (e) {}
                this.scale = (stored >= 1 && stored <= 1.35) ? stored : 1;
            },

            setScale(value) {
                this.scale = value;
                document.documentElement.style.fontSize = (18 * value) + 'px';
                // Enlarged labels no longer fit the desktop bar; hand it the menu.
                document.documentElement.classList.toggle('sc-text-scaled', value > 1);
                try { localStorage.setItem('silvercare-text-scale', String(value)); } catch (e) {}
            },

            toggleDark() {
                this.dark = window.toggleSilverCareTheme
                    ? window.toggleSilverCareTheme()
                    : document.documentElement.classList.toggle('dark');
            },

            toggleContrast() {
                this.contrast = document.documentElement.classList.toggle('high-contrast');
                try { localStorage.setItem('silvercare-high-contrast', this.contrast ? 'on' : 'off'); } catch (e) {}
            },
        };
    };

    /**
     * Hero preview tabs.
     *
     * Implements the WAI-ARIA tabs pattern properly: one tab stop for the
     * whole set, arrow keys move between them, and focus follows selection.
     */
    window.scDualView = function scDualView() {
        return {
            view: 'senior',
            taken: false,
            order: ['senior', 'caregiver'],

            select(next) {
                this.view = next;
                this.$refs['tab_' + next]?.focus();
            },

            move(delta) {
                const at = this.order.indexOf(this.view);
                const to = (at + delta + this.order.length) % this.order.length;
                this.select(this.order[to]);
            },
        };
    };

    /* ────────────────────────────────────────────────────────────────
       Scroll reveal.

       Only runs when the document opted in (`html.sc-anim`), which the
       head script withholds under prefers-reduced-motion — so the reduced
       -motion path never hides content waiting for an observer that will
       not animate. Elements unobserve once shown; nothing re-animates on
       scroll back up.
       ──────────────────────────────────────────────────────────────── */
    (function () {
        const root = document.documentElement;
        const targets = document.querySelectorAll('.sc-reveal');

        if (!root.classList.contains('sc-anim') || !('IntersectionObserver' in window)) {
            targets.forEach((el) => el.classList.add('is-in'));
            return;
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('is-in');
                observer.unobserve(entry.target);
            });
        }, { rootMargin: '0px 0px -12% 0px', threshold: 0.08 });

        targets.forEach((el) => observer.observe(el));
    })();
</script>

</body>
</html>
