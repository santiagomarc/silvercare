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
            'elderly' => \App\Http\Middleware\EnsureUserIsElderly::class,
            'caregiver' => \App\Http\Middleware\EnsureUserIsCaregiver::class,
            'role.redirect' => \App\Http\Middleware\RedirectBasedOnRole::class,
            'prevent.back' => \App\Http\Middleware\PreventBackHistory::class,
        ]);

        // Apply prevent back history to all authenticated routes
        $middleware->appendToGroup('web', [
            \App\Http\Middleware\PreventBackHistory::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
