<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register custom middleware aliases
        $middleware->alias([
            'elderly'          => \App\Http\Middleware\EnsureUserIsElderly::class,
            'caregiver'        => \App\Http\Middleware\EnsureUserIsCaregiver::class,
            'role.redirect'    => \App\Http\Middleware\RedirectBasedOnRole::class,
            'prevent.back'     => \App\Http\Middleware\PreventBackHistory::class,
            'profile.complete' => \App\Http\Middleware\EnsureProfileCompleted::class,
        ]);

        // M5 FIX: Removed global append of PreventBackHistory to the 'web' group.
        // It was applying no-cache headers to ALL routes including the public welcome
        // page, login form, password reset, and static assets — causing unnecessary
        // cache misses on slow connections for elderly users.
        //
        // Instead, PreventBackHistory is now applied via the 'prevent.back' alias
        // directly on the authenticated route groups in routes/web.php.
        // See: middleware(['auth', 'verified', ..., 'prevent.back']) groups.
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
