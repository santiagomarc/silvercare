{{-- Paint the reader's stored theme, contrast and text size before first
     paint, so someone who needs dark or high-contrast mode never gets a
     bright flash. Mirrors the logic in resources/js/app.js and
     resources/js/components/display-controls.js. --}}
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
