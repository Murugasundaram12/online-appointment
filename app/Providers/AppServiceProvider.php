<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        Schema::defaultStringLength(191);
        if (env('APP_ENV') !== 'local') {
            URL::forceScheme('https');
        }

        try {
            if (Schema::hasTable('business_settings')) {
                $timezone = \App\Models\BusinessSetting::where('key', 'timezone')->value('value');
                if ($timezone && in_array($timezone, timezone_identifiers_list(), true)) {
                    config(['app.timezone' => $timezone]);
                    date_default_timezone_set($timezone);
                }
            }
        } catch (\Throwable $e) {
            // Keep public pages and Artisan available if the database is temporarily unreachable.
        }
    }
}
