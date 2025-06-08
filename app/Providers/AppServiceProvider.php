<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Http\Request;
use App\Http\Requests\BaseRequest;
use App\Models\Booking;
use App\Observers\BookingObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind our custom request class to override default behavior
        $this->app->bind(Request::class, BaseRequest::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register the BookingObserver
        Booking::observe(BookingObserver::class);
        
        if (app()->environment('production')) {
            // 1) Force all URLs to be generated with your APP_URL
            URL::useOrigin(config('app.url'));

            // 2) Force HTTPS on all generated URLs
            URL::forceScheme('https');
        }
    }
}
