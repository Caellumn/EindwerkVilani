<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

// Public homepage
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
