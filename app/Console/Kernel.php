<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $tz = config('app.timezone', 'America/Toronto');

        $schedule->command('appointments:send-reminders')
            ->everyFifteenMinutes()
            ->timezone($tz);

        $schedule->command('appointments:mark-no-show')
            ->everyFifteenMinutes()
            ->timezone($tz);
    }

    /**
     * Get the timezone that should be used by default for scheduled events.
     */
    protected function scheduleTimezone(): \DateTimeZone|string|null
    {
        return config('app.timezone', 'America/Toronto');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
