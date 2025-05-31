<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ServiceApiController;
use App\Http\Controllers\ServiceCategoryApiController;
use App\Http\Controllers\ProductCategoryApiController;
use App\Http\Controllers\CategoryApiController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BookingHasServicesController;
use App\Http\Controllers\BookingHasProductsController;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Http\Middleware\AdminOnly;
use Illuminate\Support\Facades\Log;

//sanctum werkt session based en checked of er een user is ingelogged
// sanctum stuurt naar de login, maar de error 403 moet je zelf laten sturen ipv een rederict
//ORIGINAL USER ROUTE
// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

// foute manier
//create apis for products using the product controller
// Route::get('/products', [App\Http\Controllers\ProductController::class, 'index']);
// Route::get('/products/{product}', [App\Http\Controllers\ProductController::class, 'show']);
// Route::post('/products', [App\Http\Controllers\ProductController::class, 'store']);
// Route::put('/products/{product}', [App\Http\Controllers\ProductController::class, 'update']);
// Route::delete('/products/{product}', [App\Http\Controllers\ProductController::class, 'destroy']);


// ---> PUBLIC ROUTES <----------
//|                              |
//|                              |
//|                              |
//------------------------------

//betere manier
Route::apiResource('/products', ProductController::class)->only(['index', 'show']);
Route::apiResource('/services', ServiceApiController::class)->only(['index', 'show']);

// Category Routes
Route::apiResource('/categories', CategoryApiController::class)->only(['index','show']);
//geeft problemen met de id
// Route::apiResource('/categories/{categoryId}', CategoryApiController::class)->only(['show','update','destroy']);
Route::get('/categories/{categoryId}', [CategoryApiController::class, 'show']);
// Route::put('/categories/{categoryId}', [CategoryApiController::class, 'update']);
// Route::delete('/categories/{categoryId}', [CategoryApiController::class, 'destroy']);

// Product Category Routes
Route::get('/products/{productId}/categories', [ProductCategoryApiController::class, 'index']);
// Route::put('/products/{productId}/categories/sync', [ProductCategoryApiController::class, 'sync']);
Route::get('/products-with-categories', [ProductCategoryApiController::class, 'productsWithCategories']);
Route::get('/product-categories', [ProductCategoryApiController::class, 'productCategories']);

// Service Category Routes
Route::get('/services/{serviceId}/categories', [ServiceCategoryApiController::class, 'index']);
Route::put('/services/{serviceId}/categories/sync', [ServiceCategoryApiController::class, 'sync']);
Route::get('/services-with-categories', [ServiceCategoryApiController::class, 'servicesWithCategories']);
Route::get('/service-categories', [ServiceCategoryApiController::class, 'serviceCategories']);

// User Routes
Route::apiResource('/users', UserController::class)->only(['store']);

// Booking Routes
Route::apiResource('/bookings', BookingController::class)->only(['index', 'show', 'store', 'destroy','update']);
Route::post('/bookings/full-store', [BookingController::class, 'fullStore']);

// // Booking Has Products Routes
// Route::get('/bookings/{bookingId}/products', [BookingHasProductsController::class, 'index']);
// Route::get('/bookings-with-products', [BookingHasProductsController::class, 'bookingsWithProducts']);
// Route::put('/bookings/{bookingId}/products/sync', [BookingHasProductsController::class, 'syncProducts']);
// Route::get('/booking-products', [BookingHasProductsController::class, 'bookingProducts']);

// // Booking Has services Routes
// Route::get('/bookings/{bookingId}/services', [BookingHasServicesController::class, 'index']);
// Route::get('/bookings-with-services', [BookingHasServicesController::class, 'bookingsWithServices']);
Route::put('/bookings/{bookingId}/services/sync', [BookingHasServicesController::class, 'syncServices']);
// Route::get('/booking-services', [BookingHasServicesController::class, 'bookingServices']);

// ---> PROTECTED ROUTES <-------
//|                              |
//|                              |
//|                              |
//-------------------------------

Route::group(['middleware' => 'auth:sanctum'], function () {

    //products routes behind middleware
    Route::apiResource('/products', ProductController::class)->only(['store', 'destroy','update'])->middleware('admin');

    //services routes behind middleware
    Route::apiResource('/services', ServiceApiController::class)->only([ 'store', 'destroy','update'])->middleware('admin');

    // users routes behind middleware
    Route::get('/users', [UserController::class, 'index'])->middleware('admin');
    Route::apiResource('/users', UserController::class)->except(['index'])->only(['show', 'destroy', 'update'])->middleware('admin');

    //categories routes behind middleware
    Route::put('/categories/{categoryId}', [CategoryApiController::class, 'update'])->middleware('admin');
    Route::delete('/categories/{categoryId}', [CategoryApiController::class, 'destroy'])->middleware('admin');
    Route::apiResource('/categories', CategoryApiController::class)->only(['store'])->middleware('admin');

    //productscategories routes behind middleware
    Route::put('/products/{productId}/categories/sync', [ProductCategoryApiController::class, 'sync'])->middleware('admin');

    // bookings routes behind middleware
    Route::get('/bookings', [BookingController::class, 'index'])->middleware('admin');
    Route::apiResource('/bookings', BookingController::class)->only(['show', 'destroy', 'update'])->middleware('admin');


    // Booking Has Products routes behind middleware
    Route::get('/bookings/{bookingId}/products', [BookingHasProductsController::class, 'index']);
    Route::get('/bookings-with-products', [BookingHasProductsController::class, 'bookingsWithProducts']);
    // Route::put('/bookings/{bookingId}/products/sync', [BookingHasProductsController::class, 'syncProducts']);
    Route::get('/booking-products', [BookingHasProductsController::class, 'bookingProducts']);

    // Booking Has services routes behind middleware
    Route::get('/bookings/{bookingId}/services', [BookingHasServicesController::class, 'index']);
    Route::get('/bookings-with-services', [BookingHasServicesController::class, 'bookingsWithServices']);
    // Route::put('/bookings/{bookingId}/services/sync', [BookingHasServicesController::class, 'syncServices']);
    Route::get('/booking-services', [BookingHasServicesController::class, 'bookingServices']);
});

Route::post('/token', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
        'role' => 'required',
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    $token = $user->createToken($request->device_name)->plainTextToken;

    return response()->json(['token' => $token]);
});

// CSRF Token endpoint for frontend applications
Route::get('/csrf-token', function () {
    return response()->json(['csrf_token' => csrf_token()]);
})->middleware(['web']);

// Cloudinary upload endpoint (no CSRF required in API routes)
Route::post('/upload-to-cloudinary', function (\Illuminate\Http\Request $request) {
    // Start comprehensive logging
    Log::info('=== CLOUDINARY UPLOAD REQUEST START ===');
    Log::info('Request URL: ' . $request->fullUrl());
    Log::info('Request Method: ' . $request->method());
    Log::info('Request Headers: ', $request->headers->all());
    Log::info('Request Content Type: ' . $request->header('Content-Type'));
    Log::info('PHP $_FILES: ', $_FILES ?? []);
    Log::info('Has File (image): ' . ($request->hasFile('image') ? 'YES' : 'NO'));
    Log::info('All Files: ', $request->file());
    Log::info('All Input: ', $request->except(['_token']));
    
    try {
        // Check $_FILES first as a fallback
        if (!$request->hasFile('image') && empty($_FILES['image'])) {
            Log::error('No image file found in request');
            return response()->json([
                'error' => 'No file uploaded',
                'debug' => [
                    'has_file_image' => $request->hasFile('image'),
                    'all_files' => $request->file(),
                    'files_array' => $_FILES ?? [],
                    'content_type' => $request->header('Content-Type'),
                    'method' => $request->method(),
                    'url' => $request->fullUrl()
                ]
            ], 400);
        }
        
        // Try to get the file from request first, then fallback to $_FILES
        $file = $request->hasFile('image') ? $request->file('image') : null;
        
        if (!$file && !empty($_FILES['image'])) {
            Log::info('Using $_FILES fallback for file handling');
            // Create a temporary uploaded file from $_FILES
            $uploadedFile = new \Illuminate\Http\UploadedFile(
                $_FILES['image']['tmp_name'],
                $_FILES['image']['name'],
                $_FILES['image']['type'],
                $_FILES['image']['error'],
                true
            );
            $file = $uploadedFile;
        }
        
        if (!$file) {
            Log::error('Could not retrieve file from request or $_FILES');
            return response()->json([
                'error' => 'Could not process uploaded file',
                'debug' => [
                    'request_has_file' => $request->hasFile('image'),
                    'files_array_exists' => !empty($_FILES['image']),
                    'files_count' => count($_FILES ?? [])
                ]
            ], 400);
        }
        
        Log::info('File details: ', [
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'temp_path' => $file->getRealPath(),
            'is_valid' => $file->isValid(),
            'error' => $file->getError(),
            'temp_exists' => file_exists($file->getRealPath())
        ]);
        
        // Validate file
        try {
            // Manual validation since file might be from $_FILES
            $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
            $maxSize = 2048 * 1024; // 2MB in bytes
            
            if (!in_array($file->getMimeType(), $allowedMimes)) {
                throw new \Exception('Invalid file type. Allowed: jpeg, png, jpg, gif');
            }
            
            if ($file->getSize() > $maxSize) {
                throw new \Exception('File too large. Maximum size: 2MB');
            }
            
            if ($file->getError() !== UPLOAD_ERR_OK) {
                throw new \Exception('File upload error: ' . $file->getError());
            }
            
            Log::info('File validation passed');
        } catch (\Exception $validationError) {
            Log::error('File validation failed: ' . $validationError->getMessage());
            return response()->json([
                'error' => 'File validation failed: ' . $validationError->getMessage(),
                'debug' => [
                    'file_mime' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'file_extension' => $file->getClientOriginalExtension(),
                    'file_error' => $file->getError()
                ]
            ], 400);
        }
        
        // Check Cloudinary environment variables
        $cloudinaryConfig = [
            'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
            'api_key' => env('CLOUDINARY_API_KEY'),
            'api_secret' => env('CLOUDINARY_API_SECRET') ? '***SET***' : 'NOT_SET'
        ];
        Log::info('Cloudinary config: ', $cloudinaryConfig);
        
        if (!env('CLOUDINARY_CLOUD_NAME') || !env('CLOUDINARY_API_KEY') || !env('CLOUDINARY_API_SECRET')) {
            Log::error('Missing Cloudinary environment variables');
            return response()->json([
                'error' => 'Cloudinary configuration missing',
                'debug' => $cloudinaryConfig
            ], 500);
        }
        
        // Upload directly to Cloudinary
        Log::info('Attempting Cloudinary upload...');
        $cloudinary = new \Cloudinary\Cloudinary([
            'cloud' => [
                'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                'api_key' => env('CLOUDINARY_API_KEY'), 
                'api_secret' => env('CLOUDINARY_API_SECRET'),
            ]
        ]);
        
        $response = $cloudinary->uploadApi()->upload($file->getRealPath(), [
            'folder' => 'products',
            'resource_type' => 'image'
        ]);
        
        Log::info('Cloudinary upload successful: ', [
            'public_id' => $response['public_id'] ?? 'unknown',
            'secure_url' => $response['secure_url'] ?? 'unknown',
            'format' => $response['format'] ?? 'unknown'
        ]);
        
        $successResponse = [
            'success' => true,
            'url' => $response['secure_url'],
            'public_id' => $response['public_id']
        ];
        
        Log::info('Sending success response: ', $successResponse);
        Log::info('=== CLOUDINARY UPLOAD REQUEST END (SUCCESS) ===');
        
        return response()->json($successResponse);
        
    } catch (\Exception $e) {
        Log::error('Cloudinary upload error: ' . $e->getMessage());
        Log::error('Error line: ' . $e->getLine());
        Log::error('Error file: ' . $e->getFile());
        Log::error('Stack trace: ' . $e->getTraceAsString());
        
        $errorResponse = [
            'error' => $e->getMessage(),
            'debug' => [
                'line' => $e->getLine(),
                'file' => basename($e->getFile()),
                'class' => get_class($e)
            ]
        ];
        
        Log::info('Sending error response: ', $errorResponse);
        Log::info('=== CLOUDINARY UPLOAD REQUEST END (ERROR) ===');
        
        return response()->json($errorResponse, 500);
    }
});
