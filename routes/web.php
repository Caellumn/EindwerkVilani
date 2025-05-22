<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

Route::get('/', function () {
    return view('welcome');
});


// controller products --> get products.php
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
