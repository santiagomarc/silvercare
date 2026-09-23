# SilverCare Redesign — Work Plan

Rolling the SilverCare design system across the signed-in app.
**Two agents work on this in parallel: Claude and Gemini.**

Read `FRONTEND_DESIGN_SYSTEM.md` first — it is the rulebook. This file is the
schedule and the ownership map.

---

## 1. Where we are

| | |
| --- | --- |
| Landing page | done |
| All 9 auth views | done |
| 7 shared form components | done |
| `layouts/guest.blade.php` | done, legacy branch **deleted** |
| `components/dashboard-nav.blade.php` (the app bar) | done — Phase 0, see §3 |
| Claude's Phase 1 shared components | done |
| `profile/edit.blade.php` | done — the first converted dashboard **view** |
| Gemini's 17 self-contained views | done |
| Phase 2 — both dashboards | done |
| Phase 3 — the three chart pages | done |
| Phase 4 — deleting the old design | **done — see §7** |

**The rollout is complete.** Every view is on the SilverCare design system,
`layouts/dashboard.blade.php` has one body, and the Montserrat/grey design no
longer exists in the repository.

Two things were deliberately left out of it, and only these remain:

- `components/ai-chat-widget.blade.php` (733) — still written in raw Tailwind
  utilities with no `dark:` variants. It is the sole reason the `html.dark`
  utility overrides survive in `app.css`; §5 gives it its own conversation.
- `caregiver/analytics_pdf.blade.php` and `emails/*` — must stay on inline CSS.
  See §5.

---

## 2. The rule that keeps two agents from colliding

There is exactly one shared layer, and **only Claude edits it.**

### Claude owns — Gemini must never edit these

```
resources/css/silvercare-ui.css
resources/css/app.css
resources/views/components/*          (every shared component)
resources/views/layouts/*
resources/views/partials/*
scripts/check-ui.mjs
FRONTEND_DESIGN_SYSTEM.md
REDESIGN_PLAN.md
```

Two agents defining `.sc-card` differently is how the whole system falls apart.
It cannot happen if only one agent touches the file.

**What the shared chrome now guarantees you** (see §3, Phase 0): a converted
view gets its `<h1>`, its subtitle, the reader controls, the account menu and
the mobile drawer from `<x-dashboard-nav>`. Do not rebuild any of them, and do
not add a second `<h1>`.

### If Gemini needs something that does not exist yet

Do **not** invent a class, and do **not** add CSS to a Blade file. Instead:

1. Build the page with the closest existing component.
2. Add a line to `REDESIGN_REQUESTS.md` (create it if absent):
   `- [ ] <what you needed> — needed by <file> — <why nothing existing fits>`
3. Carry on. Claude picks these up and adds them properly.

### Both agents

- One file is edited by one agent at a time. Check `git status` before starting.
- Commit per file or per small group, never a giant batch.
- Never edit a file the other agent is assigned in the same phase.

---

## 3. Phases

### Phase 0 — the gate (Claude, alone) — **DONE**

`components/dashboard-nav.blade.php` — **used by all 25 dashboard views**.
Every file in this plan depends on it and on nothing else shared.

It is now the **app bar**, and it emits two blocks, not one:

```
<header class="sc-appbar">   sticky: brand, SOS, notifications, messages,
                             Display menu, profile, log out, drawer trigger
<div class="sc-appbar-head"> the title band — the page's one <h1>
```

**Three things every view must now honour** (this is the whole reason Phase 0
came first):

1. **The bar owns the `<h1>`.** It renders the `title` prop as the page's only
   `<h1>`. A view must not declare one of its own — its section headings start
   at `<h2>`. Six views still carry a second `<h1>` and fail
   `check-ui.mjs` on "exactly one h1" until they are converted:
   `elderly/wellness/index`, `wellness/word`, `wellness/breathing`,
   `wellness/memory`, `caregiver/checklists/index`,
   `caregiver/medications/index`. Demote them when you convert them.
2. **The page title is no longer inside the bar**, so a view that also prints
   its own title block under the nav now says it twice. Delete the view's copy
   and let the `title`/`subtitle` props carry it.
3. **Nothing else changed** — every prop, route, link, button and the SOS
   script are exactly as they were.

Phase 1 is done.

### Phase 1 note — the worked example

`profile/edit.blade.php` is converted and is the reference for every
dashboard view that follows. Copy its shape:

```blade
<x-dashboard-layout>
    <x-slot:title>…</x-slot:title>
    <x-dashboard-nav title="…" subtitle="…" … />
    <main id="main-content" class="sc-app-main">
        <div class="max-w-4xl mx-auto px-6 lg:px-12">
            <section class="sc-card p-6 md:p-8" aria-labelledby="section-x">
                <h2 id="section-x" class="sc-h3">…</h2>
```

Three things it settles, so nobody re-decides them per page:

- **`<main id="main-content" class="sc-app-main">` is the page body.**
  The layout's skip link points at that id and nothing else supplies it.
- **A section is `<section class="sc-card">` + `<h2 class="sc-h3">`.**
  The element carries the structure, the class carries the size —
  `sc-h2` is hero scale and is wrong inside a card.
- **`max-w-4xl mx-auto px-6 lg:px-12`** is the column. The app bar runs to
  1600px; a form does not.

### Phase 1 — parallel

Claude takes the shared components and the heavy/risky pages. Gemini takes the
self-contained views. See §4.

### Phase 2 — the two dashboards (Claude) — **DONE**

`elderly/dashboard` and `caregiver/dashboard` are both converted. Remaining:
the three chart pages in Phase 3, then the deletion in Phase 4.

### Phase 3 — charts (Claude) — in progress

`elderly/vitals/show` and `elderly/vitals/analytics` are done;
`caregiver/analytics` remains. `elderly/vitals/analytics` is the worked
example for a chart page: palette from `--sc-chart-*` re-read on theme
change, fixed series order, out-of-range points as triangles, the
healthy band plugin, and a text summary on every canvas. Copy it.

### Phase 4 — deletion (Claude)

The old design comes out of the codebase. See §7.

---

## 4. Assignments

### Claude — 5,300 lines

Shared layer, anything with charts, anything the whole app depends on.

| Phase | File | Lines |
| --- | --- | --- |
| 0 | `components/dashboard-nav.blade.php` | 281 |
| 1 | `components/modal.blade.php` | 78 |
| 1 | `components/logout-confirm-modal.blade.php` | 106 |
| 1 | `components/vital-card.blade.php` | 101 |
| 1 | `components/medication-list.blade.php` | 230 |
| 1 | `components/task-list.blade.php` | 161 |
| 1 | `components/elderly-hero-action.blade.php` | 133 |
| 1 | `components/elderly-steps-card.blade.php` | 103 |
| 1 | `components/elderly-garden.blade.php` | 136 |
| 1 | `components/elderly-mood-tracker.blade.php` | 183 |
| 1 | `components/dropdown.blade.php` + `nav-link` + `responsive-nav-link` + `dropdown-link` | 58 |
| 2 | `elderly/dashboard.blade.php` — **done** | 619 |
| 2 | `caregiver/dashboard.blade.php` — **done** | 801 |
| 3 | `elderly/vitals/show.blade.php` — **done** | 817 |
| 3 | `elderly/vitals/analytics.blade.php` — **done** | 852 |
| 3 | `caregiver/analytics.blade.php` | 646 |
| 1 | `profile/edit.blade.php` — **done** | 842 |
| 4 | all deletions | — |

### Gemini — 4,800 lines

Self-contained views. Every one of them depends on `dashboard-nav` and nothing
else shared, so once Phase 0 lands they can be done in any order.

Suggested order — cheapest first, so the pattern is proven before the big ones:

| # | File | Lines |
| --- | --- | --- |
| 1 | `elderly/wellness/index.blade.php` | 101 |
| 2 | `caregiver/messages/index.blade.php` | 115 |
| 3 | `caregiver/thresholds.blade.php` | 120 |
| 4 | `elderly/wellness/word.blade.php` | 128 |
| 5 | `elderly/checklists.blade.php` | 171 |
| 6 | `caregiver/medications/index.blade.php` | 193 |
| 7 | `elderly/medications.blade.php` | 195 |
| 8 | `elderly/wellness/breathing.blade.php` | 197 |
| 9 | `caregiver/patients/index.blade.php` | 227 |
| 10 | `elderly/wellness/memory.blade.php` | 243 |
| 11 | `elderly/wellness/stretch.blade.php` | 246 |
| 12 | `elderly/messages/index.blade.php` | 252 |
| 13 | `calendar/index.blade.php` | 351 |
| 14 | `elderly/notifications/index.blade.php` | 403 |
| 15 | `caregiver/checklists/create` + `edit` + `index` | 598 |
| 16 | `caregiver/medications/create` + `edit` | 969 |
| 17 | `profile/partials/*` (3 files) | 167 |

---

## 4b. Known blockers

- ~~**`php artisan test` empties `silvercare_db`.**~~ **Not reproducible as of
  2026-09-13.** Both `php artisan test` and `vendor/bin/phpunit` boot with
  `config('database.connections.pgsql.database') === 'silvercare_testing'`,
  as `phpunit.xml` intends. The likeliest cause at the time was a stale
  `bootstrap/cache/config.php` — a cached config freezes `.env` values and
  ignores `phpunit.xml`. If it happens again, run `php artisan config:clear`
  before the suite; do not add a `.env.testing` (it replaces `.env` wholesale,
  so it would need every secret duplicated).

- ~~**`caregiver/analytics` returns a 500.**~~ **Fixed 2026-09-13.**
  `index()` compacted four variables (`sourceAttribution`, `doseLateness`,
  `alertHistory`, `reportGeneratedAt`) that only `exportPdf()` assigns and only
  `analytics_pdf` renders. They are gone from `index()`; the page is a 200
  and Phase 3 can check it.

## 5. Do not convert these

| File | Why |
| --- | --- |
| `caregiver/analytics_pdf.blade.php` (454) | Rendered by **dompdf**, which supports no CSS custom properties, no flexbox, no grid, no `:has()`. Converting it breaks PDF generation *silently* — you find out when a weekly health report goes out looking like plain text. Inline CSS and tables only. |
| `resources/views/emails/*` (3 files) | Email clients strip `<style>` and ignore custom properties. Inline styles only. |
| `components/ai-chat-widget.blade.php` (733) | Bigger than either dashboard. Gets its own dedicated conversation, not squeezed into this plan. |

---

## 6. Definition of done — per file

A file is not finished until **all four** pass:

```bash
# 1. it compiles
php artisan view:cache && php artisan view:clear

# 2. nothing else broke
php artisan test

# 3. the UI holds up — signed-in pages need --login, or the checker
#    silently grades the login page it was redirected to
php artisan serve
node scripts/check-ui.mjs http://127.0.0.1:8000/<the-page-url> \
    --login=you@example.com:your-password

# 4. you looked at it: 375px wide, and in dark mode
```

`check-ui.mjs` must print **"All checks passed"**.

Two failure modes it will not catch, so check them by hand:

- **Behaviour.** If the page has JavaScript, use it. Submit the form, open the
  modal, switch the tab. Restyling breaks scripts.
- **Loss.** Diff against the original and count the fields, links and buttons.
  An agent dropped a field once already; it saved `age = null` for every new
  user until it was caught.

---

## 7. Phase 4 — deleting the old design — **DONE**

The gate (bare `<x-dashboard-layout>` count reaching 0) was met once
`caregiver/analytics` landed. What that step actually involved, since parts of
the original checklist turned out to be stale:

1. **`layouts/dashboard.blade.php`** — `@if/@else` stripped, Montserrat
   `<link>` removed, `sc` prop gone from the layout and from all 33 call sites
   (26 dashboard + 7 guest). `guest.blade.php` lost its compatibility `sc` prop
   at the same time.
2. **Dead files deleted** — the four the plan listed, plus one it missed:
   `app/View/Components/AppLayout.php`, which was the only thing referencing
   `layouts/app.blade.php`. The whole chain was dead.
3. **`resources/css/app.css`: 904 → 499 lines.** The entire component layer
   went except the toast.

**Three corrections to the original checklist — read these before trusting a
similar list again:**

- **`.dose-*` was never in `app.css`.** Those rules live in
  `silvercare-ui.css`, are built at runtime by `medication-tracker.js`
  (`dose-${status}`), and are live. Deleting "`.dose-*`" as listed would have
  broken the medication tracker.
- **The `html.dark .bg-white` overrides had to stay.** The plan called them
  scaffolding for the legacy component classes, but `ai-chat-widget` — which §5
  excludes from this plan — is written entirely in `bg-white/80`,
  `text-slate-600` and friends with no `dark:` variants of its own. Deleting
  them would have broken the widget in dark mode. They are now commented with
  the condition for their removal.
- **Four classes on the "delete" list were still in the markup.** `.card-glass`
  and `.panel-shell*` survived inside the *converted* `elderly/dashboard` — a
  glass slab behind panels whose content already carried its own `sc-card`
  surfaces. They were converted, not deleted around.

**Two pre-existing bugs surfaced while verifying, and were fixed:**

- `.toast-border-*` is only ever built as `'toast-border-' + t.type`, so
  Tailwind's content scanner never saw it and purged all four rules out of
  `@layer components`. The toast's outcome stripe had never rendered in a
  production build. The rules now sit outside the layer.
- `flatpickr` and `tom-select` pinned `font-family: 'Montserrat'`. With that
  face no longer loaded they fell back to the system sans, rendering a date
  picker in a different typeface from the form around it. Both now carry the
  body stack.

**How to check the state is still clean:**

```bash
# every view on the design system (expect only "<x-dashboard-layout>")
grep -rho "<x-dashboard-layout[^>]*>" resources/views | sort | uniq -c

# no markup references a deleted class
grep -rnE 'class="[^"]*(card-glass|panel-shell|back-nav-pill|empty-state)' resources/views
```

`public/build` is gitignored, so **run `npm run build` after pulling this** —
otherwise arbitrary Tailwind classes in the converted views are missing from
the stylesheet.
