<?php

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
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // API-only app (routes/web.php has no login page), so an unauthenticated
        // request must never fall back to Authenticate::redirectTo()'s route('login')
        // — that route doesn't exist here and turns what should be a 401 into a 500.
        $exceptions->shouldRenderJsonWhen(fn () => true);
    })->create();
