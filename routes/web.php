<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
// comment to push
// Public homepage
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

Route::get('/', function () {
    // return redirect('welcome');
    return redirect('/admin/login');
});

// ─────────────────────────────────────────────────────────────────────────────
// Debug‐only override of the email verification link
// ─────────────────────────────────────────────────────────────────────────────
Route::get('email/verify/{id}/{hash}', function (Request $request) {
    // Log every piece of the incoming request
    Log::info('INCOMING URL: ' . $request->fullUrl());
    Log::info('HOST + SCHEME: ' . $request->getSchemeAndHttpHost());
    Log::info('QUERY STRING: ' . http_build_query($request->query()));
    Log::info('hasValidSignature? ' . (URL::hasValidSignature($request) ? 'YES' : 'NO'));

    // Halt execution so you can inspect logs at storage/logs/laravel.log
    dd('🔍  Check storage/logs/laravel.log for the details of the incoming verification link.');
})
->middleware(['auth', 'signed', 'throttle:6,1'])
->name('verification.verify');

// ─────────────────────────────────────────────────────────────────────────────
// Your existing, post-verification (dashboard) routes
// ─────────────────────────────────────────────────────────────────────────────
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

// Simple Swagger documentation page that bypasses all L5-Swagger components
Route::get('/docs-simple', function () {
    $jsonUrl = url('/docs-simple/json');
    
    return response()->make("
<!DOCTYPE html>
<html>
<head>
    <title>Kapsalon Vilani API Documentation</title>
    <link rel='stylesheet' type='text/css' href='https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui.css' />
    <style>
        html { box-sizing: border-box; overflow: -moz-scrollbars-vertical; overflow-y: scroll; }
        *, *:before, *:after { box-sizing: inherit; }
        body { margin:0; background: #fafafa; }

    </style>
</head>
<body>
    <div id='swagger-ui'></div>
    <script src='https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui-bundle.js'></script>
    <script src='https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui-standalone-preset.js'></script>
    <script>
        window.onload = function() {
            const ui = SwaggerUIBundle({
                url: '{$jsonUrl}',
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: 'StandaloneLayout',
                requestInterceptor: function(request) {
                    // Redirect API calls to mock endpoints for safe testing
                    if (request.url.includes('/api/')) {
                        request.url = request.url.replace('/api/', '/api/mock/');
                    }
                    return request;
                }
            });
        };
    </script>
</body>
</html>", 200)->header('Content-Type', 'text/html');
});

Route::get('/docs-simple/json', function () {
    return response()->file(storage_path('api-docs/api-docs.json'));
});


