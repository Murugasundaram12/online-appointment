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

        if (!class_exists(\App\Models\Category::class)) {
            class_alias(\App\Models\ServiceCategory::class, \App\Models\Category::class);
        }

        Schema::defaultStringLength(191);
        if (env('APP_ENV') !== 'local') {
            URL::forceScheme('https');
        }
        if (config('app.url')) {
            URL::forceRootUrl(config('app.url'));
        }

        try {
            $defaultTz = config('app.timezone') ?: 'America/Toronto';
            if (Schema::hasTable('business_settings')) {
                $timezone = \App\Models\BusinessSetting::where('key', 'timezone')->value('value');
                if ($timezone && in_array($timezone, timezone_identifiers_list(), true)) {
                    $defaultTz = $timezone;
                }
            }
            config(['app.timezone' => $defaultTz]);
            date_default_timezone_set($defaultTz);
        } catch (\Throwable $e) {
            // Keep public pages and Artisan available if the database is temporarily unreachable.
            date_default_timezone_set(config('app.timezone', 'America/Toronto'));
        }
    }
}
