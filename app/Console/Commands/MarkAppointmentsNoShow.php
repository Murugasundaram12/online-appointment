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
        $this->info('Automatic transition to No Show is disabled. Appointments retain their booked/confirmed/pending statuses.');
        Log::info('Automatic appointments:mark-no-show command invoked but disabled by system policy.');
        return Command::SUCCESS;
    }

    private function getBusinessTimezone(): string
    {
        $settings = BusinessSetting::pluck('value', 'key');
        return $settings->get('timezone') ?? config('app.timezone');
    }
}
