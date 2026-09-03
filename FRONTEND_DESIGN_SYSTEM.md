# SilverCare Design Guide

**Read this before you change any page's look.**

This is the guide for the redesign we're rolling out across the whole site,
one page at a time. Follow it and every page will match. Ignore it and the
site drifts back into a patchwork.

Three pages are already done. Copy them:

- `resources/views/welcome.blade.php` — landing page (standalone)
- `resources/views/auth/login.blade.php` — a form page (standalone)
- `resources/views/auth/forgot-password.blade.php` — the shortest possible
  example of the layout route: one word (`sc`) plus design-system classes
- `resources/views/auth/profile-completion.blade.php` — a multi-step form whose
  JavaScript was left working untouched; copy this when a page has real script
- `resources/views/auth/reset-password.blade.php` — built entirely from the
  shared components, which is the shortest correct form you can write

---

## 0. The prompt to give your agent

Copy this, fill in the two blanks, paste it. Don't shorten it — every line
in it exists because leaving it out causes a specific problem.

```
Redesign PAGE_PATH to match the SilverCare design system.

Read these first, in this order:
  1. FRONTEND_DESIGN_SYSTEM.md — the rules
  2. resources/css/silvercare-ui.css — the classes you must use
  3. REFERENCE_PAGE — a finished page of the same kind; copy its patterns

Rules:
- Change only how the page LOOKS. Do not change routes, controller calls,
  form field names, validation rules, or any existing JavaScript. If a
  <script> block exists, keep it working exactly as it is.
- Use only the sc-* classes and the CSS variables. No raw colours
  (no bg-white, no text-gray-600, no hex codes). No emoji.
- Every form field uses the pattern in section 5 of the guide: a visible
  <label>, sc-input, and the error message under the field.
- Icons come from partials/sc-icons.blade.php via <use href="#i-name"/>.
  If you need one that isn't there, add a <symbol> to that file.
- One primary button on the page. Everything else is sc-btn-ghost.
- Keep every field, link and button that exists today. Don't drop any.

When you're done, run this and fix anything it reports:
  node scripts/check-ui.mjs http://127.0.0.1:8000/PAGE_URL
Do not tell me it's finished until that prints "All checks passed".
```

**Which reference page to give it:**

| Redesigning | REFERENCE_PAGE |
| --- | --- |
| A sign-in / sign-up / auth page | `resources/views/auth/login.blade.php` |
| A form (add medication, edit profile) | `resources/views/auth/login.blade.php` |
| A page that uses a layout | `resources/views/auth/forgot-password.blade.php` |
| A marketing or landing page | `resources/views/welcome.blade.php` |

Don't hand it the landing page for a form. It's a marketing page and the
agent will copy hero sections into your signup form.

### Then check its work yourself

Open the page and look for these four things. They're what agents get wrong:

1. **Dark mode.** Toggle it. If anything is white-on-white, the agent left a
   raw colour in.
2. **Phone width.** Resize to 375px. Nothing should scroll sideways.
3. **The form still works.** Actually submit it. Agents break JavaScript while
   restyling.
4. **Nothing went missing.** Count the fields and links against the old
   version. This is the one the checker cannot help with — it does not know
   what *should* be on the page. Run:

   ```bash
   git show HEAD:path/to/page.blade.php | grep -oE 'name="[a-z_]+"' | sort -u
   grep -oE 'name="[a-z_]+"' path/to/page.blade.php | sort -u
   ```

   and diff the two lists. A dropped field usually still submits fine — it
   just silently saves null forever.

---

## 1. What we're going for

Two people use SilverCare and they want opposite things:

- **Arthur, 78.** Wants one clear thing to do. Big text. Big buttons. No
  feeling stupid.
- **Sarah, his daughter.** Wants to trust it with her dad's health. Needs it
  to look like a real medical tool, not a toy.

So: **calm and clinical, but warm.** Think a well-organised doctor's letter,
not a startup landing page.

**Don't do:** neon, gradients on cards, glassmorphism, stock photos of smiling
old people, emoji, tiny grey text, "AI-powered" badges, made-up statistics.

**Do:** lots of white space, one loud button per screen, real content, honest
colours, big touch targets.

---

## 2. Where things live

| What | File |
| --- | --- |
| All colours + all component classes | `resources/css/silvercare-ui.css` |
| Icons | `resources/views/partials/sc-icons.blade.php` |
| Fonts | `resources/views/partials/sc-fonts.blade.php` |
| Theme/dark/text-size boot script | `resources/views/partials/sc-theme-boot.blade.php` |
| The checker you must run | `scripts/check-ui.mjs` |

**Do not write CSS inside a Blade file.** If you need a new component, add it
to `silvercare-ui.css` with a comment saying what it's for.

---

## 3. How to convert a page

### If the page uses a layout (25 pages do)

Just add `sc` to the layout tag. That's it — the layout does the rest.

```blade
{{-- before --}}
<x-dashboard-layout>

{{-- after --}}
<x-dashboard-layout sc>
```

Same for `<x-guest-layout sc>`.

Pages without the `sc` flag keep the old look, so you can go one page at a
time without breaking anything.

### If the page is standalone (has its own `<html>`)

Copy the top of `resources/views/auth/login.blade.php`. The parts that matter:

```blade
<body class="sc-page antialiased">
<a class="sc-skip" href="#main-content">Skip to main content</a>
@include('partials.sc-icons')
```

And in `<head>`, in this order:

```blade
@include('partials.sc-theme-boot')   {{-- must be before the CSS --}}
<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@500;600;700;800&family=Newsreader:ital,opsz,wght@1,6..72,400;1,6..72,500&display=swap" rel="stylesheet">
@include('partials.sc-fonts')
@vite(['resources/css/app.css', 'resources/js/app.js'])
```

### Then, in the page body

1. Delete every colour you find. Replace with a token (section 4).
2. Delete every emoji. Replace with an icon (section 6).
3. Replace hand-built cards, buttons and inputs with the classes in section 5.
4. Fix headings: one `<h1>`, then `<h2>` per section, then `<h3>`. No skipping.
5. Run the checker (section 9).

**Don't touch anything that isn't styling.** Leave routes, controllers, form
field names, validation rules and Alpine logic exactly as they are.

---

## 4. Colours

**Never write a colour in a Blade file.** Not `bg-white`, not `text-gray-600`,
not `#000080`. Use a token.

```blade
{{-- no --}}
<div class="bg-white border border-gray-200 text-gray-900">

{{-- yes --}}
<div class="sc-card">

{{-- if you need a one-off --}}
<p style="color:var(--sc-body)">
```

Tokens are already defined for light mode, dark mode and high-contrast mode.
Use them and all three just work. Write a raw colour and dark mode breaks.

| Token | Use it for |
| --- | --- |
| `--sc-canvas` | page background |
| `--sc-canvas-2` | alternating section band |
| `--sc-surface` | card background |
| `--sc-surface-2` | a card inside a card |
| `--sc-surface-3` | hover / pressed fill |
| `--sc-ink` | headings |
| `--sc-body` | normal text |
| `--sc-muted` | small print, timestamps, labels |
| `--sc-line` | borders |
| `--sc-line-strong` | borders on inputs and buttons |
| `--sc-brand` | the navy button fill |
| `--sc-brand-text` | navy as *text* (links, eyebrows) |
| `--sc-brand-tint` / `--sc-brand-line` | pale navy plate + its border |
| `--sc-ok` / `--sc-warn` / `--sc-alert` | good / due soon / problem |
| `--sc-ok-tint`, `--sc-ok-line` (same for warn, alert) | tinted backgrounds |

**Colour is never the only signal.** Green means good *and* has a tick icon
*and* says "Normal". Someone colour-blind, or reading in sunlight, gets the
same information.

### Old classes → new

Search-and-replace these as you go:

| Old | New |
| --- | --- |
| `bg-white`, `.card`, `.card-glass`, `.panel-shell` | `sc-card` |
| `bg-gray-50`, `bg-gray-100`, `.card-gradient` | `sc-card-quiet` |
| `text-gray-900`, `text-slate-900` | `sc-h2` / `sc-h3`, or `style="color:var(--sc-ink)"` |
| `text-gray-600`, `text-slate-600` | `style="color:var(--sc-body)"` |
| `text-gray-500`, `text-gray-400` | `style="color:var(--sc-muted)"` |
| `border-gray-200` | drop it — `sc-card` has its own border |
| `.badge`, `.badge-success`, `.badge-danger` | `sc-badge`, `sc-badge-ok`, `sc-badge-alert` |
| `.empty-state` | `sc-empty` |
| `.progress-track` / `.progress-fill` | `sc-progress` / `sc-progress-fill` |
| `.profile-field-input` | `sc-input` |
| `.profile-field-label` | `sc-label` |
| hand-rolled `<button class="bg-[#000080] ...">` | `sc-btn sc-btn-primary` |
| `bg-[#EBEBEB]`, `bg-[#DEDEDE]`, gradient body backgrounds | delete — `sc-page` sets the ground |

`bg-white` matters most: it appears in 49 views, and `app.css` overrides it
with `!important` in dark mode. That override is why half-converted pages look
broken in dark mode. Replace it, don't work around it.

---

## 5. Components

Use these instead of building your own. Full list is in
`resources/css/silvercare-ui.css` — it's commented.

### Buttons

```blade
<button class="sc-btn sc-btn-primary">Save changes</button>
<a class="sc-btn sc-btn-ghost">Cancel</a>
<button class="sc-btn sc-btn-primary sc-btn-sm">Add</button>
<button class="sc-icon-btn"><svg .../><span class="sr-only">Close</span></button>
```

**One primary button per screen.** Everything else is a ghost button or a
link. Two loud buttons means the user has to make a decision you didn't ask
them to make.

**Disabled buttons:** use the real `disabled` attribute. Do **not** add
`opacity-50` — the brand navy at half opacity reads as lavender, i.e. as a
different brand colour, and the label goes unreadable. `.sc-btn:disabled`
already has its own flat treatment.

Better still, think twice before disabling a submit button at all. A button
that does nothing and doesn't say why is a dead end. Prefer leaving it
enabled and showing errors on submit.

### Cards

```blade
<div class="sc-card p-6">…</div>              {{-- normal --}}
<div class="sc-card sc-lift p-6">…</div>      {{-- lifts on hover, for clickable cards --}}
<div class="sc-card-quiet p-4">…</div>        {{-- nested, quieter --}}
```

### Forms

Every field looks like this. No exceptions.

```blade
<div class="sc-field">
    <label for="dose" class="sc-label sc-label-req">Dose</label>
    <input id="dose" name="dose" type="text"
           class="sc-input @error('dose') sc-input-error @enderror"
           @error('dose') aria-invalid="true" aria-describedby="dose-error" @enderror>
    <span class="sc-help">For example: one tablet</span>
    @error('dose')
        <p id="dose-error" class="sc-error" role="alert">
            <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-alert"/></svg>
            <span>{{ $message }}</span>
        </p>
    @enderror
</div>
```

Rules:

- **A visible label above every field.** Never use the placeholder as the
  label — it vanishes the moment someone types, which is exactly when an older
  person looks up to check what they were filling in.
- The error goes **under the field it belongs to**, not at the top of the page.
- If more than one field failed, *also* put a `sc-error-summary` at the top
  that links to each field. See login.blade.php.
- Mark required fields with `sc-label-req` (adds a red asterisk).
- Use `sc-select`, `sc-textarea`, `sc-check`, `sc-fieldset` + `sc-legend`.
- Use the right `type` and `autocomplete` so phones show the right keyboard
  and password managers work.

### Shared Blade components — prefer these over raw HTML

These are already converted, so a form built from them is correct by default.
They also handle their own error state.

```blade
<div class="sc-field">
    <x-input-label for="email" :value="__('Email address')" required />
    <x-text-input id="email" name="email" type="email" required autocomplete="email" />
    <x-input-error field="email" />
</div>

<x-primary-button class="w-full">Save changes</x-primary-button>
<x-secondary-button>Cancel</x-secondary-button>
<x-danger-button>Delete account</x-danger-button>
<x-auth-session-status :status="session('status')" />
```

`<x-text-input>` reads its own `name`, checks the error bag, and adds
`sc-input-error`, `aria-invalid` and `aria-describedby` for you. Don't pass
those in by hand.

### Everything else

| Class | For |
| --- | --- |
| `sc-badge` (+ `-ok` `-warn` `-alert` `-brand`) | status pills |
| `sc-chip` (+ `-ok` `-warn` `-brand`) | small labels with an icon |
| `sc-plate` (+ `-sm` `-ok` `-alert`) | the rounded square behind an icon |
| `sc-table-wrap` + `sc-table` | tables (the wrapper stops sideways scroll) |
| `sc-empty` | "nothing here yet" — always with a button to fix that |
| `sc-skeleton` | loading placeholder |
| `sc-scrim` + `sc-dialog` | modals |
| `sc-progress` + `sc-progress-fill` | progress bars (always show the number too) |
| `sc-stat` + `sc-stat-label` + `sc-stat-value` | dashboard numbers |
| `sc-page-head` + `sc-page-title` | the top of an app page |
| `sc-num` | **any** number, time or dose — stops columns jittering |
| `sc-textlink` | a link inside a sentence |
| `sc-reveal` | fades in on scroll |
| `sc-steps` + `sc-step-dot` + `sc-step-label` | multi-step form progress |
| `sc-input-muted` | a field switched off by a "none of these" checkbox |
| `sc-btn-danger` | destructive action |

### Auth pages

`sc-auth` (the centred column) + `sc-auth-card` (the floating form card) +
`sc-or` (the "or" rule) + `sc-auth-facts` (the quiet line under the form).

The sign-in page's signature is **the light**. SilverCare organises a day into
morning, afternoon and evening, so the ambient wash behind the card follows
the reader's clock: warm and from the east in the morning, cool and from the
west at night, with a greeting to match. `utils/daylight.js` sets it.

Reuse it on any auth page by putting `data-daylight="afternoon"` on the
`sc-ambient` wrapper and including the hidden greeting chip. It has to run in
the browser — the server's clock is not the reader's.

Note what it is *not*: an invented testimonial or a borrowed statistic. The
time of day is the one true thing we can say to someone who hasn't signed in
yet. Hold new pages to that standard.

---

## 6. Icons

Never emoji. They look different on every phone, ignore our colours, and
screen readers read them out loud in the middle of a sentence.

```blade
<svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false">
    <use href="#i-pill"/>
</svg>
```

Available: `arrow-right`, `chevron-down`, `chevron-left`, `check`,
`check-circle`, `alert`, `close`, `menu`, `plus`, `search`, `edit`, `trash`,
`eye`, `eye-off`, `lock`, `shield`, `pill`, `pulse`, `activity`, `bell`,
`clock`, `calendar`, `map-pin`, `home`, `phone`, `users`, `siren`, `mic`,
`sun`, `moon`, `sprout`, `device`, `clipboard`, `accessibility`, `type`,
`contrast`, `quote`, `undo`, `sliders`.

Need one that isn't there? Add a `<symbol>` to the sprite. Copy the SVG path
from [lucide.dev](https://lucide.dev), keep `stroke-width="1.75"`.

Common swaps: 💊→`i-pill` · ❤️→`i-pulse` · 🔔→`i-bell` · 📍→`i-map-pin` ·
🏠→`i-home` · 📞→`i-phone` · 🚨→`i-siren` · ✅→`i-check-circle` · 🎙️→`i-mic` ·
📅→`i-calendar` · 🌸→`i-sprout` · ☀️→`i-sun`

---

## 7. Type

Three fonts, three jobs. Don't add a fourth.

- **Prompt** — headings and numbers only
- **Valley Sans** — all body text
- **Newsreader italic** — quotes only, and rarely

Use the size classes, don't set your own: `sc-h1`, `sc-h2`, `sc-h3`,
`sc-lead`, `sc-eyebrow`, `sc-page-title`, `sc-stat-value`.

**The base font size is 18px, not 16px.** So `text-xs` is about 16px, not 12px.
There is no genuinely small text on this site. That's deliberate.

Keep paragraphs to about 65–75 characters wide (`max-w-xl` usually does it).

---

## 8. The rules that aren't negotiable

These are the product, not polish. Do them while you build, not after.

1. **One `<h1>` per page.** Then `<h2>`, then `<h3>`. Never skip a level.
2. **Every button and link needs a name.** Icon-only? Add
   `<span class="sr-only">What it does</span>`.
3. **Every input needs a `<label for="…">`.**
4. **Nothing smaller than 44px tall** that you can tap.
5. **Never remove the focus outline.** Keyboard users need to see where they are.
6. **Wrap tables** in `sc-table-wrap` so they scroll instead of the page.
7. **Set static ARIA as well as the Alpine binding:**
   `aria-expanded="false" :aria-expanded="open"`. Before Alpine loads, a
   binding-only attribute doesn't exist yet.
8. **Live regions must always be in the DOM** and change their *text*. A
   message revealed by `x-show` often isn't announced.
9. **Animate only `transform` and `opacity`.** Nothing else.
10. **Test dark mode and high contrast**, not just light.

### The three reader controls

The header has a **Display** menu: text size, dark mode, high contrast. It's
part of the product, not a demo. Copy it onto any page with a header — the
Alpine component is `displayControls()`, already registered.

Turning text size up adds `sc-text-scaled` to `<html>`, which collapses the
desktop nav into a menu. If you build a nav bar, handle that class. A media
query can't see it, because `rem` in a media query measures against the
browser's 16px, not ours.

---

## 9. Before you say you're done

Run the checker. It finds the things you can't see in a diff.

```bash
# once
npm i -D playwright && npx playwright install chromium

# then, for each page you changed
php artisan serve
node scripts/check-ui.mjs http://127.0.0.1:8000/your-page
```

It checks: sideways scroll at 7 widths, colour contrast in all three themes,
heading order, duplicate ids, tap target sizes, unnamed buttons, unlabelled
inputs, broken anchors, leftover emoji, icons missing `aria-hidden`, console
and network errors, and whether reduced motion hides your content.

**It must print "All checks passed" before you call a page finished.**

Then look at it yourself at 375px wide and in dark mode.

---

## 10. Traps that have already bitten us

- **`@error(...)` does not work inside a Blade *component* tag.** This is
  silent and it looks like a design bug:

  ```blade
  {{-- WRONG — "sc-input-error" becomes a literal class on every field --}}
  <x-text-input class="@error('email') sc-input-error @enderror" />

  {{-- RIGHT — the component works it out from the field's own name --}}
  <x-text-input id="email" name="email" />
  <x-input-error field="email" />
  ```

  `@error` compiles fine inside a normal `<input>`; only component tags break.
- **`silvercare-ui.css` loads before Tailwind**, so a Tailwind class always
  beats a component class. That's on purpose. But it means you must *not* size
  a component with utilities if its own CSS sizes it responsively — the
  utility wins and the component's media query never fires.
- **`@tailwindcss/forms` overrides `sc-input`.** The plugin injects
  `[type='email'] { background-color: #fff }` into Tailwind's base layer,
  which loads after our stylesheet — so inputs came out white in dark mode.
  Every form-control rule in `silvercare-ui.css` is therefore written as
  `.sc-page .sc-input`, not `.sc-input`. Keep that prefix if you add more.
- **`divide-y` uses Tailwind's grey** and ignores our colours. Use `sc-divide`.
- **`.sc-display` sets its own colour.** Don't put it on text inside a filled
  button or it'll be navy-on-navy and invisible.
- **`app.css` recolours every link in high contrast with `!important`.** A
  custom-coloured button inside a coloured panel needs its own high-contrast rule.
- **Don't put asset paths in a bundled CSS file.** Vite's dev server resolves
  `/assets/...` against port 5173, not your app. That stays in Blade.
- **Grid children need `min-width: 0`** or one long word pushes the whole page
  sideways on a phone. Already applied globally to `.sc-page .grid > *`.

---

## 11. Writing the words

The copy is design too.

- Say what the button does: **"Save changes"**, not "Submit".
- Keep the same word all the way through. If the button says *Get started*,
  the next page doesn't say *Register*.
- Use the person's words, not the database's. "Care circle", not "user group".
- Sentence case. Plain verbs. No exclamation marks.
- Errors say what went wrong *and* how to fix it. Not "Invalid input".
- Empty screens tell you what goes there and give you the button to add it.
- **Never invent numbers, reviews, logos or certifications.** Every claim has
  to be something the product actually does.
