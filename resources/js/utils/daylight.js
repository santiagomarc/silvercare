/**
 * Time-of-day ambience for the auth pages.
 *
 * SilverCare organises a day into morning, afternoon and evening, so the
 * sign-in page greets people the way the product does. This has to run in
 * the browser, not on the server: the server's clock is not the reader's.
 *
 * Sets `data-daylight` (which swaps two ambient glow colours in CSS) and
 * fills the greeting chip. If it never runs, the chip stays hidden and the
 * page is unchanged — the greeting is a grace note, not load-bearing.
 */
const BANDS = [
    { until: 12, name: 'morning',   label: 'Good morning',   icon: '#i-sun',  chip: 'sc-chip-warn'  },
    { until: 18, name: 'afternoon', label: 'Good afternoon', icon: '#i-sun',  chip: 'sc-chip-brand' },
    { until: 24, name: 'evening',   label: 'Good evening',   icon: '#i-moon', chip: 'sc-chip-brand' },
];

export function initDaylight(root = document) {
    const field = root.querySelector('[data-daylight]');
    if (!field) return;

    const hour = new Date().getHours();
    const band = BANDS.find((b) => hour < b.until) ?? BANDS[BANDS.length - 1];

    field.setAttribute('data-daylight', band.name);

    const chip = root.querySelector('[data-daylight-chip]');
    if (!chip) return;

    chip.querySelector('[data-daylight-label]').textContent = band.label;
    chip.querySelector('use')?.setAttribute('href', band.icon);
    chip.classList.add(band.chip);
    chip.hidden = false;
}
