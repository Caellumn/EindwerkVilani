<?php

namespace App\Providers;

use App\Models\User;
use App\Mail\WelcomeEmail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Send welcome email when user registers
        Event::listen(function (Registered $event) {
            Mail::to($event->user)->send(new WelcomeEmail($event->user));
        });
    }
}
