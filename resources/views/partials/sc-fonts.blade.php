{{-- Valley Sans (self-hosted variable webfont) plus the preload for the file
     that actually renders body copy. Kept in Blade rather than the bundled
     stylesheet because the URLs depend on asset(): Vite's dev server resolves
     a root-relative url() against its own origin, not the application's. --}}
<link rel="preload" as="font" type="font/woff2" crossorigin
      href="{{ asset('assets/fonts/valley-sans/ValleySans-Variable.woff2') }}">
<style>
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
</style>
