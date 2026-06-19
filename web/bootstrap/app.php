<?php

use App\Http\Controllers\McpController;
use App\Http\Middleware\WorldKeepBearerAuth;
use App\Http\Middleware\WorldKeepCors;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware(['worldkeep.cors', 'worldkeep.bearer'])
                ->group(function () {
                    Route::get('/healthz', fn () => response()->json(['status' => 'ok']));
                    Route::post('/mcp', McpController::class);
                });
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        if (env('APP_ENV', 'production') !== 'local') {
            $middleware->trustProxies(at: '*');
        }

        $middleware->redirectGuestsTo('/app/login');

        $middleware->validateCsrfTokens(except: [
            'api/auth/*',
            'api/*',
            'mcp',
            'healthz',
        ]);

        $middleware->alias([
            'auth.cognito' => \App\Http\Middleware\AuthenticateCognito::class,
            'worldkeep.bearer' => WorldKeepBearerAuth::class,
            'worldkeep.cors' => WorldKeepCors::class,
        ]);

        $middleware->api(prepend: [
            WorldKeepCors::class,
            WorldKeepBearerAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
