<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ServiceApiController;
use App\Http\Controllers\ServiceCategoryApiController;
use App\Http\Controllers\ServiceWithHairlengthApiController;
use App\Http\Controllers\ProductCategoryApiController;
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
Route::apiResource('/serviceswithhairlengths', ServiceWithHairlengthApiController::class)->only(['index', 'show', 'store', 'destroy','update']);


Route::apiResource('/services/{service}/categories', ServiceCategoryApiController::class)->only(['index', 'attach', 'detach', 'sync']);
Route::apiResource('/services/{service}/categories/{category}', ServiceCategoryApiController::class)->only(['updateStatus']);
Route::apiResource('/services/{service}/categories/toggle', ServiceCategoryApiController::class)->only(['toggle']);
Route::get('/services/categories/all', [ServiceCategoryApiController::class, 'showAllWithServices']);

Route::apiResource('/products/{product}/categories', ProductCategoryApiController::class)->only(['index', 'attach', 'detach', 'sync']);
Route::apiResource('/products/{product}/categories/{category}', ProductCategoryApiController::class)->only(['updateStatus']);
Route::apiResource('/products/{product}/categories/toggle', ProductCategoryApiController::class)->only(['toggle']);
// Service-Category relationship routes
// Route::get('/services/{service}/categories', [ServiceCategoryApiController::class, 'index']);
// Route::post('/services/{service}/categories', [ServiceCategoryApiController::class, 'attach']);
// Route::delete('/services/{service}/categories', [ServiceCategoryApiController::class, 'detach']);
// Route::put('/services/{service}/categories', [ServiceCategoryApiController::class, 'sync']);
// Route::patch('/services/{service}/categories/{category}', [ServiceCategoryApiController::class, 'updateStatus']);
// Route::post('/services/{service}/categories/toggle', [ServiceCategoryApiController::class, 'toggle']);
