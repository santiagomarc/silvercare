/**
 * Scroll reveal for elements marked `.sc-reveal`.
 *
 * Only runs when the document opted in via `html.sc-anim`, which the
 * pre-paint boot script withholds under prefers-reduced-motion — so the
 * reduced-motion path never leaves content hidden behind an observer that
 * was never going to animate it. Elements unobserve once shown; nothing
 * re-animates on the way back up.
 */
export function initScrollReveal(root = document) {
    const targets = root.querySelectorAll('.sc-reveal');
    if (!targets.length) return;

    if (!document.documentElement.classList.contains('sc-anim') || !('IntersectionObserver' in window)) {
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
}
