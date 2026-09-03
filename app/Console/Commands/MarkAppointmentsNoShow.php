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

    protected $description = 'Automatically mark past booked appointments whose end time has passed as No Show';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $timezone = $this->getBusinessTimezone();
        $now = now()->setTimezone($timezone);

        $this->info("Checking for expired booked appointments as of ({$timezone}): {$now->toDateTimeString()}");

        $appointments = Appointment::where('status', 'booked')
            ->where('end_time', '<', $now)
            ->get();

        if ($appointments->isEmpty()) {
            $this->info('No booked appointments found requiring No Show transition.');
            return Command::SUCCESS;
        }

        $this->info("Found {$appointments->count()} appointment(s) to transition to No Show.");

        $updatedCount = 0;
        foreach ($appointments as $appointment) {
            if ($isDryRun) {
                $this->info("[DRY RUN] Would mark appointment {$appointment->id} (end: {$appointment->end_time}) as No Show");
                $updatedCount++;
                continue;
            }

            $appointment->update(['status' => 'no_show']);
            $updatedCount++;

            Log::info('Appointment automatically marked as No Show', [
                'appointment_id' => $appointment->id,
                'end_time' => $appointment->end_time,
                'marked_at' => $now->toDateTimeString(),
            ]);
        }

        $this->info("Successfully updated {$updatedCount} appointment(s) to No Show.");
        return Command::SUCCESS;
    }

    private function getBusinessTimezone(): string
    {
        $settings = BusinessSetting::pluck('value', 'key');
        return $settings->get('timezone') ?? config('app.timezone');
    }
}
