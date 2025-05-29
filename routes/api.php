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

// Booking Routes - Only store (create) is public
Route::post('/bookings', [BookingController::class, 'store']);

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

    // Products routes behind middleware - using individual routes to avoid conflicts
    Route::post('/products', [ProductController::class, 'store'])->middleware('admin');
    Route::put('/products/{product}', [ProductController::class, 'update'])->middleware('admin');
    Route::patch('/products/{product}', [ProductController::class, 'update'])->middleware('admin');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->middleware('admin');

    // Services routes behind middleware - using individual routes to avoid conflicts
    Route::post('/services', [ServiceApiController::class, 'store'])->middleware('admin');
    Route::put('/services/{service}', [ServiceApiController::class, 'update'])->middleware('admin');
    Route::patch('/services/{service}', [ServiceApiController::class, 'update'])->middleware('admin');
    Route::delete('/services/{service}', [ServiceApiController::class, 'destroy'])->middleware('admin');

    // users routes behind middleware
    Route::get('/users', [UserController::class, 'index'])->middleware('admin');
    Route::get('/users/{user}', [UserController::class, 'show'])->middleware('admin');
    Route::put('/users/{user}', [UserController::class, 'update'])->middleware('admin');
    Route::patch('/users/{user}', [UserController::class, 'update'])->middleware('admin');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('admin');

    // Categories routes behind middleware - using individual routes to avoid conflicts
    Route::post('/categories', [CategoryApiController::class, 'store'])->middleware('admin');
    Route::put('/categories/{categoryId}', [CategoryApiController::class, 'update'])->middleware('admin');
    Route::delete('/categories/{categoryId}', [CategoryApiController::class, 'destroy'])->middleware('admin');

    //productscategories routes behind middleware
    Route::put('/products/{productId}/categories/sync', [ProductCategoryApiController::class, 'sync'])->middleware('admin');

    // bookings routes behind middleware
    // Note: GET /bookings (index) is admin-only, but store remains public
    Route::get('/bookings-admin', [BookingController::class, 'index'])->middleware('admin');
    Route::get('/bookings/{booking}', [BookingController::class, 'show'])->middleware('admin');
    Route::put('/bookings/{booking}', [BookingController::class, 'update'])->middleware('admin');
    Route::patch('/bookings/{booking}', [BookingController::class, 'update'])->middleware('admin');
    Route::delete('/bookings/{booking}', [BookingController::class, 'destroy'])->middleware('admin');

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
    // Fix: Get data from either request parameters or JSON
    $requestData = !empty($request->all()) ? $request->all() : $request->json()->all();
    
    $validated = \Illuminate\Support\Facades\Validator::make($requestData, [
        'email' => 'required|email',
        'password' => 'required',
    ])->validate();

    $user = User::where('email', $validated['email'])->first();

    if (!$user || !Hash::check($validated['password'], $user->password)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    // Check if user has active status
    if ($user->status !== 1) {
        return response()->json(['message' => 'Account is not active'], 401);
    }

    $token = $user->createToken($request->device_name ?? 'api-token')->plainTextToken;

    return response()->json([
        'token' => $token,
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role
        ]
    ]);
});
