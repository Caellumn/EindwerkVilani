<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\AdminOnly;
use App\Http\Middleware\TrustProxies;
use App\Http\Middleware\VerifyCsrfToken;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register global middleware
        $middleware->prepend(TrustProxies::class);
        
        // Web middleware group
        $middleware->web(append: [
            VerifyCsrfToken::class,
        ]);
        
        // Existing aliases
        $middleware->alias([
            'admin' => AdminOnly::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
