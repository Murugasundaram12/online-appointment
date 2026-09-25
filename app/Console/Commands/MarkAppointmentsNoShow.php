<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\BusinessSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MarkAppointmentsNoShow extends Command
{
    protected $signature = 'appointments:mark-no-show
                            {--dry-run : Run without updating database}';

    protected $description = 'Disabled: automatic transition of past appointments to No Show is disabled';

    public function handle(): int
    {
        $timezone = $this->getBusinessTimezone();
        $now = now()->setTimezone($timezone);

        $this->info("Evaluation time ({$timezone}): {$now->toDateTimeString()}");
        $this->info('Automatic transition to No Show is disabled. Appointments retain their booked/confirmed/pending statuses.');
        Log::info('Automatic appointments:mark-no-show command invoked but disabled by system policy.', [
            'timezone' => $timezone,
            'evaluated_at' => $now->toDateTimeString(),
        ]);
        return Command::SUCCESS;
    }

    public function getBusinessTimezone(): string
    {
        $settings = BusinessSetting::pluck('value', 'key');
        $settingTz = $settings->get('timezone');
        if (!empty($settingTz) && in_array($settingTz, timezone_identifiers_list(), true)) {
            return $settingTz;
        }

        return config('app.timezone') ?: 'America/Toronto';
    }
}
