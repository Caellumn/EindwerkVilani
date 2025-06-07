<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class DatabaseServiceProvider extends ServiceProvider
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
        // Set database timezone to match application timezone
        $this->setDatabaseTimezone();
    }

    private function setDatabaseTimezone(): void
    {
        $appTimezone = Config::get('app.timezone');
        
        try {
            // First try to set the named timezone directly (requires MySQL timezone tables)
            DB::statement("SET time_zone = '$appTimezone'");
        } catch (\Exception $e) {
            try {
                // Fallback: Convert to offset (this is what we had before)
                $datetime = new \DateTime('now', new \DateTimeZone($appTimezone));
                $offset = $datetime->format('P'); // Format: +02:00
                
                DB::statement("SET time_zone = '$offset'");
            } catch (\Exception $e2) {
                // Final fallback to UTC if everything fails
                DB::statement("SET time_zone = '+00:00'");
            }
        }
    }
}
