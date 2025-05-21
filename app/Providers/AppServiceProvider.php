<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (app()->environment('production')) {
            // 1) Force all URLs to be generated with your APP_URL
            URL::useOrigin(config('app.url'));

            // 2) Force HTTPS on all generated URLs
            URL::forceScheme('https');
        }
    }
}
