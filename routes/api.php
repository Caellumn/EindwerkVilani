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
-
// User Routes
Route::apiResource('/users', UserController::class)->only(['store']);

// Booking Routes
Route::apiResource('/bookings', BookingController::class)->only(['index', 'show', 'store', 'destroy','update']);

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
