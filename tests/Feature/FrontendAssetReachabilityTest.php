<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * C2/C3 regression guard.
 *
 * Sprint 5 shipped public/js/offline-sync-queue.js and sprint 5's realtime work
 * shipped public/js/caregiver-realtime.js. Neither was ever loaded by a Blade
 * template or imported into the Vite bundle, so the features they implemented
 * did nothing in the browser while the PHP test suite stayed green. Nothing in
 * `npm run build` or `php artisan test` fails when a script is orphaned.
 *
 * This test walks the real reachability graph:
 *
 *   entry points  = vite.config.js `input:` + every asset('js/...') or
 *                   <script src> referenced by a Blade template
 *   reachable     = entry points plus everything transitively imported from
 *                   them via relative `import`/`export ... from` statements
 *
 * Anything left over is dead code. Either wire it up or delete it — a file that
 * looks like a shipped feature but never executes is worse than no file.
 */
class FrontendAssetReachabilityTest extends TestCase
{
    /**
     * Files that are deliberately not reachable yet, each with the finding that
     * tracks the work. Remove an entry as soon as its file is wired up or
     * deleted; do not add to this list to make the test pass.
     *
     * @var array<string, string>
     */
    private const KNOWN_ORPHANS = [
        'public/js/offline-sync-queue.js' => 'C2 — port into resources/js/utils/offline-queue.js, then delete',
        'public/js/caregiver-realtime.js' => 'C3 — fold into the Vite bundle once Echo is uncommented, then delete',
        'resources/js/components/vital-recorder.js' => 'S6 — superseded by voice capture; scheduled for deletion',
        'resources/js/echo.js' => 'C3 — imported by bootstrap.js only as a commented-out line',
    ];

    public function test_no_unexpected_orphaned_javascript(): void
    {
        $base = base_path();
        $reachable = $this->reachableFiles($base);

        $all = collect($this->allJsFiles($base))
            ->reject(fn (string $rel): bool => in_array($rel, array_keys(self::KNOWN_ORPHANS), true))
            ->values();

        $orphans = $all->reject(fn (string $rel): bool => in_array($rel, $reachable, true))->values();

        $this->assertEmpty(
            $orphans->all(),
            "These JavaScript files are never loaded by any Blade template and are not imported "
            . "into the Vite bundle, so they do not run in the browser:\n  - "
            . $orphans->implode("\n  - ")
            . "\n\nWire them up or delete them. If a file is intentionally parked, add it to "
            . 'KNOWN_ORPHANS with the finding that tracks it.'
        );
    }

    /**
     * Every file in KNOWN_ORPHANS must still exist and still be unreachable.
     * When one gets wired up or deleted, this fails and the entry must be
     * removed — that is how the list shrinks instead of rotting.
     */
    public function test_known_orphan_list_is_current(): void
    {
        $base = base_path();
        $reachable = $this->reachableFiles($base);
        $existing = $this->allJsFiles($base);

        foreach (self::KNOWN_ORPHANS as $rel => $note) {
            if (! in_array($rel, $existing, true)) {
                $this->fail("{$rel} no longer exists — remove it from KNOWN_ORPHANS ({$note}).");
            }

            if (in_array($rel, $reachable, true)) {
                $this->fail("{$rel} is now reachable — remove it from KNOWN_ORPHANS ({$note}).");
            }
        }

        $this->assertTrue(true);
    }

    /**
     * @return list<string> repo-relative paths of every project JS file
     */
    private function allJsFiles(string $base): array
    {
        $files = [];

        foreach (['resources/js', 'public/js'] as $dir) {
            $absolute = $base . '/' . $dir;
            if (! is_dir($absolute)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($absolute, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'js') {
                    $files[] = ltrim(str_replace($base, '', $file->getPathname()), '/');
                }
            }
        }

        sort($files);

        return $files;
    }

    /**
     * Entry points plus everything transitively imported from them.
     *
     * @return list<string>
     */
    private function reachableFiles(string $base): array
    {
        $queue = $this->entryPoints($base);
        $seen = [];

        while ($queue !== []) {
            $rel = array_shift($queue);

            if (isset($seen[$rel])) {
                continue;
            }

            $seen[$rel] = true;

            $absolute = $base . '/' . $rel;
            if (! is_file($absolute)) {
                continue;
            }

            foreach ($this->relativeImports((string) file_get_contents($absolute)) as $spec) {
                $resolved = $this->resolveImport($base, dirname($rel), $spec);
                if ($resolved !== null) {
                    $queue[] = $resolved;
                }
            }
        }

        return array_keys($seen);
    }

    /**
     * @return list<string>
     */
    private function entryPoints(string $base): array
    {
        $entries = [];

        // 1. Vite build inputs.
        $viteConfig = $base . '/vite.config.js';
        if (is_file($viteConfig)) {
            preg_match_all(
                "/['\"](resources\/js\/[^'\"]+\.js)['\"]/",
                (string) file_get_contents($viteConfig),
                $matches
            );
            $entries = array_merge($entries, $matches[1]);
        }

        // 2. Anything a Blade template loads directly, plus any resources/js
        //    path named in a @vite([...]) directive.
        $views = $base . '/resources/views';
        if (is_dir($views)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($views, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                    continue;
                }

                $contents = (string) file_get_contents($file->getPathname());

                // asset('js/foo.js') / asset("js/foo.js") -> public/js/foo.js
                preg_match_all("/asset\(\s*['\"](js\/[^'\"]+\.js)['\"]/", $contents, $assetMatches);
                foreach ($assetMatches[1] as $path) {
                    $entries[] = 'public/' . $path;
                }

                // Plain <script src="/js/foo.js">
                preg_match_all('/<script[^>]+src=["\']\/(js\/[^"\']+\.js)["\']/', $contents, $srcMatches);
                foreach ($srcMatches[1] as $path) {
                    $entries[] = 'public/' . $path;
                }

                // @vite([... 'resources/js/foo.js' ...])
                preg_match_all("/['\"](resources\/js\/[^'\"]+\.js)['\"]/", $contents, $viteMatches);
                $entries = array_merge($entries, $viteMatches[1]);
            }
        }

        return array_values(array_unique($entries));
    }

    /**
     * Relative import specifiers only — bare specifiers are npm packages.
     *
     * @return list<string>
     */
    private function relativeImports(string $source): array
    {
        // Strip line and block comments so a commented-out import (the exact
        // shape of the C3 bug in bootstrap.js) is not counted as a reference.
        $source = preg_replace('#/\*.*?\*/#s', '', $source) ?? $source;
        $source = preg_replace('#^\s*//.*$#m', '', $source) ?? $source;

        $specs = [];

        // import x from './y'  /  import './y'  /  export * from './y'
        preg_match_all("/(?:import|export)[^;'\"]*['\"](\.[^'\"]+)['\"]/", $source, $static);
        $specs = array_merge($specs, $static[1]);

        // import('./y')
        preg_match_all("/import\(\s*['\"](\.[^'\"]+)['\"]\s*\)/", $source, $dynamic);
        $specs = array_merge($specs, $dynamic[1]);

        return array_values(array_unique($specs));
    }

    private function resolveImport(string $base, string $fromDir, string $spec): ?string
    {
        $candidate = $this->normalizePath($fromDir . '/' . $spec);

        foreach ([$candidate, $candidate . '.js', $candidate . '/index.js'] as $option) {
            if (is_file($base . '/' . $option)) {
                return $option;
            }
        }

        return null;
    }

    private function normalizePath(string $path): string
    {
        $parts = [];

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                array_pop($parts);
                continue;
            }

            $parts[] = $segment;
        }

        return implode('/', $parts);
    }
}
