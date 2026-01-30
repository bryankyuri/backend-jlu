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
    ->withMiddleware(function (Middleware $middleware) {
        // Create custom CORS middleware that actually works
        $middleware->api(prepend: [
            \App\Http\Middleware\CustomCors::class,
        ]);
        
        // Add token validation middleware alias
        $middleware->alias([
            'validate.token' => \App\Http\Middleware\ValidateToken::class,
        ]);
        
        // Remove the stateful middleware for API-only authentication
        // $middleware->api(prepend: [
        //     \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        // ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
