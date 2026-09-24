<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Services\AppointmentEmailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendAppointmentEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appointment:send-emails {appointment_id} {type=booked}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send appointment notification emails asynchronously in background';

    /**
     * Execute the console command.
     */
    public function handle(AppointmentEmailService $emailService): int
    {
        $appointmentId = (int) $this->argument('appointment_id');
        $type = (string) $this->argument('type');

        $appointment = Appointment::with(['client', 'staff', 'service', 'location'])->find($appointmentId);

        if (!$appointment) {
            $this->error("Appointment #{$appointmentId} not found.");
            return self::FAILURE;
        }

        try {
            $result = $emailService->sendDirectly($appointment, $type);
            Log::info("Background appointment email command finished for #{$appointmentId}", [
                'type' => $type,
                'result' => $result,
            ]);
            $this->info("Emails processed for appointment #{$appointmentId} ({$type}).");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error("Background appointment email command failed for #{$appointmentId}: " . $e->getMessage(), [
                'exception' => $e,
            ]);
            $this->error("Failed sending emails: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
