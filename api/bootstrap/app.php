<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // Laravel discovers listeners in app/Listeners only, and modules keep
    // theirs beside the events they handle — so each module's Listeners
    // directory is named, and nothing else is.
    //
    // Scanning `app/Modules` wholesale does NOT work: discovery registers any
    // class whose handle() takes a typed first argument, which swept up every
    // Action in the app as a listener for its own first parameter's type.
    // `glob` returns only the directories that exist, so a module without
    // listeners costs nothing.
    ->withEvents(discover: glob(__DIR__.'/../app/Modules/*/Listeners') ?: [])
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
