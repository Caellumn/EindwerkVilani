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

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// foute manier
//create apis for products using the product controller
// Route::get('/products', [App\Http\Controllers\ProductController::class, 'index']);
// Route::get('/products/{product}', [App\Http\Controllers\ProductController::class, 'show']);
// Route::post('/products', [App\Http\Controllers\ProductController::class, 'store']);
// Route::put('/products/{product}', [App\Http\Controllers\ProductController::class, 'update']);
// Route::delete('/products/{product}', [App\Http\Controllers\ProductController::class, 'destroy']);

//betere manier
Route::apiResource('/products', ProductController::class)->only(['index', 'show', 'store', 'destroy','update']);
Route::apiResource('/services', ServiceApiController::class)->only(['index', 'show', 'store', 'destroy','update']);

// Category Routes
Route::apiResource('/categories', CategoryApiController::class)->only(['index','store']);
//geeft problemen met de id
// Route::apiResource('/categories/{categoryId}', CategoryApiController::class)->only(['show','update','destroy']);
Route::get('/categories/{categoryId}', [CategoryApiController::class, 'show']);
Route::put('/categories/{categoryId}', [CategoryApiController::class, 'update']);
Route::delete('/categories/{categoryId}', [CategoryApiController::class, 'destroy']);

// Product Category Routes
Route::get('/products/{productId}/categories', [ProductCategoryApiController::class, 'index']);
Route::put('/products/{productId}/categories/sync', [ProductCategoryApiController::class, 'sync']);
Route::get('/products-with-categories', [ProductCategoryApiController::class, 'productsWithCategories']);
Route::get('/product-categories', [ProductCategoryApiController::class, 'productCategories']);

// Service Category Routes
Route::get('/services/{serviceId}/categories', [ServiceCategoryApiController::class, 'index']);
Route::put('/services/{serviceId}/categories/sync', [ServiceCategoryApiController::class, 'sync']);
Route::get('/services-with-categories', [ServiceCategoryApiController::class, 'servicesWithCategories']);
Route::get('/service-categories', [ServiceCategoryApiController::class, 'serviceCategories']);

// User Routes
Route::apiResource('/users', UserController::class)->only(['index', 'show', 'store', 'destroy','update']);

// Booking Routes
Route::apiResource('/bookings', BookingController::class)->only(['index', 'show', 'store', 'destroy','update']);

// Booking Has Products Routes
Route::get('/bookings/{bookingId}/products', [BookingHasProductsController::class, 'index']);
Route::get('/bookings-with-products', [BookingHasProductsController::class, 'bookingsWithProducts']);
Route::put('/bookings/{bookingId}/products/sync', [BookingHasProductsController::class, 'syncProducts']);
Route::get('/booking-products', [BookingHasProductsController::class, 'bookingProducts']);

// Booking Has services Routes
Route::get('/bookings/{bookingId}/services', [BookingHasServicesController::class, 'index']);
Route::get('/bookings-with-services', [BookingHasServicesController::class, 'bookingsWithServices']);
Route::put('/bookings/{bookingId}/services/sync', [BookingHasServicesController::class, 'syncServices']);
Route::get('/booking-services', [BookingHasServicesController::class, 'bookingServices']);


