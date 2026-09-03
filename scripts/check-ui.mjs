#!/usr/bin/env node
/**
 * SilverCare UI check.
 *
 * Run this against any page you have restyled. It catches the things that
 * are invisible in a diff and obvious to a user: sideways scroll on a
 * phone, unreadable colour pairs, tap targets too small for an unsteady
 * hand, broken heading order, unlabelled buttons.
 *
 *   npm i -D playwright && npx playwright install chromium   (once)
 *   node scripts/check-ui.mjs http://127.0.0.1:8000/login
 *
 * Exit code 0 = clean, 1 = something to fix.
 */
import { chromium } from 'playwright';

const url = process.argv[2];
if (!url) {
    console.error('usage: node scripts/check-ui.mjs <url>');
    process.exit(2);
}

const WIDTHS = [320, 375, 390, 768, 1024, 1280, 1440];
let failures = 0;
const fail = (msg) => { failures++; console.log('  FAIL  ' + msg); };
const pass = (msg) => console.log('  ok    ' + msg);

const browser = await chromium.launch();

/* ── 1. Horizontal overflow ──────────────────────────────────────── */
console.log('\nHorizontal overflow');
for (const width of WIDTHS) {
    const ctx = await browser.newContext({ viewport: { width, height: 900 } });
    const page = await ctx.newPage();
    await page.goto(url, { waitUntil: 'networkidle' });
    await page.waitForTimeout(400);
    const { scroll, client } = await page.evaluate(() => ({
        scroll: document.documentElement.scrollWidth,
        client: document.documentElement.clientWidth,
    }));
    scroll > client
        ? fail(`${width}px scrolls sideways (content is ${scroll}px)`)
        : pass(`${width}px`);
    await ctx.close();
}

/* ── 2. Contrast, in all three themes ────────────────────────────── */
const contrastProbe = () => {
    const luminance = ([r, g, b]) => {
        const channel = (c) => { c /= 255; return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4); };
        return 0.2126 * channel(r) + 0.7152 * channel(g) + 0.0722 * channel(b);
    };
    const parse = (value) => {
        const match = value.match(/rgba?\(([^)]+)\)/);
        if (!match) return null;
        const parts = match[1].split(/[,\s/]+/).filter(Boolean).map(Number);
        return { rgb: parts.slice(0, 3), alpha: parts.length > 3 ? parts[3] : 1 };
    };
    const flatten = (fg, bg, alpha) => fg.map((c, i) => c * alpha + bg[i] * (1 - alpha));
    const groundOf = (el) => {
        let node = el, acc = null;
        while (node) {
            const colour = parse(getComputedStyle(node).backgroundColor);
            if (colour && colour.alpha > 0) {
                acc = acc ? { rgb: flatten(acc.rgb, colour.rgb, acc.alpha), alpha: 1 } : colour;
                if (acc.alpha >= 1) return acc.rgb;
            }
            node = node.parentElement;
        }
        return acc ? acc.rgb : [255, 255, 255];
    };
    const ratio = (a, b) => {
        const [l1, l2] = [luminance(a), luminance(b)];
        return (Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05);
    };

    const problems = [];
    document.querySelectorAll('p,h1,h2,h3,h4,a,span,li,button,label,td,th,blockquote,figcaption').forEach((el) => {
        const own = [...el.childNodes].filter((n) => n.nodeType === 3).map((n) => n.textContent.trim()).join('').trim();
        if (own.length < 3) return;
        const style = getComputedStyle(el);
        if (style.display === 'none' || style.visibility === 'hidden' || parseFloat(style.opacity) < 0.9) return;
        if (el.closest('.sr-only') || el.getBoundingClientRect().width === 0) return;

        const parsed = parse(style.color);
        if (!parsed) return;
        const ground = groundOf(el);
        const text = parsed.alpha < 1 ? flatten(parsed.rgb, ground, parsed.alpha) : parsed.rgb;
        const size = parseFloat(style.fontSize);
        const weight = parseInt(style.fontWeight) || 400;
        const large = size >= 24 || (size >= 18.66 && weight >= 700);
        const required = large ? 3 : 4.5;
        const measured = ratio(text, ground);
        if (measured < required) {
            problems.push(`${measured.toFixed(2)}:1 (needs ${required}) — "${own.slice(0, 40)}"`);
        }
    });
    return problems;
};

console.log('\nColour contrast');
for (const theme of ['light', 'dark', 'high-contrast']) {
    const ctx = await browser.newContext({
        viewport: { width: 1440, height: 950 },
        colorScheme: theme === 'dark' ? 'dark' : 'light',
    });
    const page = await ctx.newPage();
    await page.addInitScript((mode) => {
        localStorage.setItem('silvercare-theme', mode === 'dark' ? 'dark' : 'light');
        localStorage.setItem('silvercare-high-contrast', mode === 'high-contrast' ? 'on' : 'off');
    }, theme);
    await page.goto(url, { waitUntil: 'networkidle' });
    await page.waitForTimeout(600);
    const problems = await page.evaluate(contrastProbe);
    problems.length
        ? (fail(`${theme}: ${problems.length} unreadable`), problems.slice(0, 5).forEach((p) => console.log('        ' + p)))
        : pass(theme);
    await ctx.close();
}

/* ── 3. Structure, targets, keyboard ─────────────────────────────── */
console.log('\nStructure and interaction');
{
    const ctx = await browser.newContext({ viewport: { width: 390, height: 844 } });
    const page = await ctx.newPage();
    const jsErrors = [];
    page.on('pageerror', (e) => jsErrors.push(e.message));
    page.on('response', (r) => { if (r.status() >= 400) jsErrors.push(`${r.status()} ${r.url()}`); });
    await page.goto(url, { waitUntil: 'networkidle' });
    await page.waitForTimeout(600);

    const report = await page.evaluate(() => {
        const out = {};
        const headings = [...document.querySelectorAll('h1,h2,h3,h4,h5,h6')];
        let previous = 0;
        out.skips = [];
        headings.forEach((h) => {
            const level = +h.tagName[1];
            if (previous && level > previous + 1) out.skips.push(`${h.tagName} after H${previous}`);
            previous = level;
        });
        out.h1 = document.querySelectorAll('h1').length;

        const seen = {};
        document.querySelectorAll('[id]').forEach((el) => { seen[el.id] = (seen[el.id] || 0) + 1; });
        out.duplicateIds = Object.entries(seen).filter(([, n]) => n > 1).map(([id]) => id);

        out.small = [];
        out.nameless = [];
        document.querySelectorAll('a[href], button, input, select, textarea').forEach((el) => {
            const style = getComputedStyle(el);
            if (style.display === 'none' || style.visibility === 'hidden') return;
            const rect = el.getBoundingClientRect();
            if (rect.width === 0) return;
            if (rect.width < 24 || rect.height < 24) {
                out.small.push(`${Math.round(rect.width)}×${Math.round(rect.height)} "${(el.textContent || el.name || '').trim().slice(0, 24)}"`);
            }
            if (el.tagName === 'A' || el.tagName === 'BUTTON') {
                const labelledBy = el.getAttribute('aria-labelledby');
                const name = el.getAttribute('aria-label')
                    || (labelledBy ? labelledBy.split(' ').map((id) => document.getElementById(id)?.textContent || '').join(' ') : '')
                    || el.textContent;
                if (!name.trim()) out.nameless.push(el.outerHTML.slice(0, 70));
            }
        });

        out.emoji = document.body.innerText.match(/[\u{1F300}-\u{1FAFF}\u{2600}-\u{27BF}]/gu) || [];
        out.unlabelledInputs = [...document.querySelectorAll('input:not([type=hidden]), select, textarea')]
            .filter((el) => !el.labels?.length && !el.getAttribute('aria-label') && !el.getAttribute('aria-labelledby'))
            .map((el) => el.name || el.id || el.tagName);
        out.brokenAnchors = [...document.querySelectorAll('a[href^="#"]')]
            .map((a) => a.getAttribute('href'))
            .filter((href) => href !== '#' && !document.querySelector(href));
        out.decorativeSvgs = [...document.querySelectorAll('svg.sc-i')].filter((s) => s.getAttribute('aria-hidden') !== 'true').length;
        return out;
    });

    report.h1 === 1 ? pass('exactly one h1') : fail(`${report.h1} h1 elements (want exactly 1)`);
    report.skips.length ? fail('heading level skipped: ' + report.skips.join(', ')) : pass('heading order');
    report.duplicateIds.length ? fail('duplicate id: ' + report.duplicateIds.join(', ')) : pass('unique ids');
    report.small.length ? fail('tap target under 24px: ' + report.small.slice(0, 5).join('; ')) : pass('tap target sizes');
    report.nameless.length ? fail(`${report.nameless.length} control(s) with no accessible name`) : pass('every control is named');
    report.unlabelledInputs.length ? fail('input without a label: ' + report.unlabelledInputs.join(', ')) : pass('every input has a label');
    report.brokenAnchors.length ? fail('anchor points nowhere: ' + report.brokenAnchors.join(', ')) : pass('in-page anchors');
    report.emoji.length ? fail(`${report.emoji.length} emoji in the page — use the icon sprite: ${[...new Set(report.emoji)].join(' ')}`) : pass('no emoji');
    report.decorativeSvgs ? fail(`${report.decorativeSvgs} sprite icon(s) missing aria-hidden="true"`) : pass('icons hidden from screen readers');
    jsErrors.length ? fail('page errors: ' + jsErrors.slice(0, 3).join(' | ')) : pass('no console or network errors');
    await ctx.close();
}

/* ── 4. Reduced motion must not hide content ─────────────────────── */
console.log('\nReduced motion');
{
    const ctx = await browser.newContext({ viewport: { width: 1440, height: 950 }, reducedMotion: 'reduce' });
    const page = await ctx.newPage();
    await page.goto(url, { waitUntil: 'networkidle' });
    await page.waitForTimeout(600);
    const invisible = await page.evaluate(() =>
        [...document.querySelectorAll('.sc-reveal')].filter((el) => getComputedStyle(el).opacity !== '1').length);
    invisible ? fail(`${invisible} .sc-reveal block(s) stay invisible with reduced motion on`) : pass('all content visible');
    await ctx.close();
}

await browser.close();
console.log(failures ? `\n${failures} problem(s) to fix.\n` : '\nAll checks passed.\n');
process.exit(failures ? 1 : 0);
