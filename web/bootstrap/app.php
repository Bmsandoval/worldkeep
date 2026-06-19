<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        if (env('APP_ENV', 'production') !== 'local') {
            $middleware->trustProxies(at: '*');
        }

        $middleware->redirectGuestsTo('/app/login');

        $middleware->validateCsrfTokens(except: [
            'api/auth/*',
        ]);

        $middleware->alias([
            'auth.cognito' => \App\Http\Middleware\AuthenticateCognito::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
