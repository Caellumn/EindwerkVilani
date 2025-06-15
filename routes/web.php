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
    return view('welcome');
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

// Test route for sending welcome email (remove in production)
Route::get('/test-welcome-email', function () {
    $user = Auth::user();
    if (!$user) {
        return 'Please login first to test the welcome email';
    }
    
    Mail::to($user)->send(new \App\Mail\WelcomeEmail($user));
    
    return 'Welcome email sent to ' . $user->email;
})->middleware(['auth']);

// Add this at the end of the file for debugging

// Debug Cloudinary connection (DISABLED - use /direct-cloudinary-test instead)
Route::get('/debug-cloudinary-disabled', function () {
    try {
        // First check if environment variables are loaded
        $envVars = [
            'CLOUDINARY_URL' => env('CLOUDINARY_URL'),
            'CLOUDINARY_CLOUD_NAME' => env('CLOUDINARY_CLOUD_NAME'),
            'CLOUDINARY_API_KEY' => env('CLOUDINARY_API_KEY'),
            'CLOUDINARY_API_SECRET' => env('CLOUDINARY_API_SECRET') ? '***SET***' : 'NOT SET',
        ];
        
        // Check if any critical vars are missing
        $missing = [];
        if (!env('CLOUDINARY_CLOUD_NAME')) $missing[] = 'CLOUDINARY_CLOUD_NAME';
        if (!env('CLOUDINARY_API_KEY')) $missing[] = 'CLOUDINARY_API_KEY';
        if (!env('CLOUDINARY_API_SECRET')) $missing[] = 'CLOUDINARY_API_SECRET';
        
        if (!empty($missing)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Missing environment variables: ' . implode(', ', $missing),
                'env_vars' => $envVars,
                'suggestion' => 'Check your .env file and run: ddev exec php artisan config:clear'
            ]);
        }
        
        // Try to get Cloudinary config
        $cloudinaryConfig = config('cloudinary');
        
        // Test simple ping (this is what's probably failing)
        $response = \CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary::admin()->ping();
        
        return response()->json([
            'status' => 'success',
            'env_vars' => $envVars,
            'cloudinary_config' => $cloudinaryConfig,
            'ping' => $response
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'env_vars' => $envVars ?? [],
            'cloudinary_config' => $cloudinaryConfig ?? null,
            'line' => $e->getLine(),
            'file' => basename($e->getFile())
        ]);
    }
});

// Debug file upload
Route::get('/debug-upload-form', function () {
    return view('debug-upload');
});

Route::post('/debug-upload-test', function (\Illuminate\Http\Request $request) {
    try {
        if (!$request->hasFile('file')) {
            return response()->json(['error' => 'No file uploaded']);
        }
        
        $file = $request->file('file');
        
        $debug = [
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'temp_path' => $file->getRealPath(),
            'is_valid' => $file->isValid(),
            'temp_exists' => file_exists($file->getRealPath()),
        ];
        
        // Try direct Cloudinary upload (bypassing service provider)
        $cloudinary = new \Cloudinary\Cloudinary([
            'cloud' => [
                'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                'api_key' => env('CLOUDINARY_API_KEY'), 
                'api_secret' => env('CLOUDINARY_API_SECRET'),
            ]
        ]);
        
        $response = $cloudinary->uploadApi()->upload($file->getRealPath(), [
            'folder' => 'debug-test',
            'resource_type' => 'image'
        ]);
        
        return response()->json([
            'status' => 'success',
            'debug' => $debug,
            'cloudinary_url' => $response['secure_url'],
            'cloudinary_response' => $response
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'debug' => $debug ?? []
        ]);
    }
});

// Simple Cloudinary test
Route::get('/simple-cloudinary-test', function () {
    try {
        // Test if we can create a Cloudinary instance
        $cloudinary = cloudinary();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Cloudinary instance created successfully',
            'config' => [
                'cloud_name' => config('cloudinary.cloud_name'),
                'api_key' => config('cloudinary.api_key'),
                'has_secret' => !empty(config('cloudinary.api_secret')),
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
});

// Test env loading directly
Route::get('/test-env-loading', function () {
    // Method 1: Laravel's env() function
    $laravelEnv = [
        'APP_NAME' => env('APP_NAME'),
        'CLOUDINARY_CLOUD_NAME' => env('CLOUDINARY_CLOUD_NAME'),
        'CLOUDINARY_API_KEY' => env('CLOUDINARY_API_KEY'),
    ];
    
    // Method 2: Direct file reading
    $envFile = base_path('.env');
    $envContent = file_exists($envFile) ? file_get_contents($envFile) : 'File not found';
    
    // Method 3: Check if .env is loaded
    $dotenvLoaded = class_exists('Dotenv\Dotenv');
    
    return response()->json([
        'laravel_env' => $laravelEnv,
        'env_file_exists' => file_exists($envFile),
        'env_file_readable' => is_readable($envFile),
        'dotenv_class_exists' => $dotenvLoaded,
        'base_path' => base_path(),
        'app_env' => app()->environment(),
        'config_cached' => app()->configurationIsCached(),
    ]);
});

// Direct Cloudinary test without service provider
Route::get('/direct-cloudinary-test', function () {
    try {
        // Create Cloudinary instance directly
        $cloudinary = new \Cloudinary\Cloudinary([
            'cloud' => [
                'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                'api_key' => env('CLOUDINARY_API_KEY'), 
                'api_secret' => env('CLOUDINARY_API_SECRET'),
            ],
            'url' => [
                'secure' => true
            ]
        ]);
        
        // Test a simple API call
        $result = $cloudinary->adminApi()->ping();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Direct Cloudinary connection works!',
            'ping_result' => $result,
            'config' => [
                'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                'api_key' => env('CLOUDINARY_API_KEY'),
                'secure' => true
            ]
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error', 
            'message' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => basename($e->getFile())
        ]);
    }
});

// Custom Cloudinary upload endpoint for Filament (DigitalOcean compatible)
// MOVED TO API ROUTES - /api/upload-to-cloudinary to avoid CSRF token issues

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


