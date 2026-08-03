<?php

namespace App\Console\Commands;

use App\Mail\AppointmentReminderMail;
use App\Models\Appointment;
use App\Models\BusinessSetting;
use App\Services\AppointmentEmailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-reminders 
                            {--dry-run : Run without sending emails or updating database}';

    protected $description = 'Send appointment reminder emails 24 hours before appointments';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $timezone = $this->getBusinessTimezone();

        $now = now()->setTimezone($timezone);
        $windowStart = $now->copy()->addHours(23)->startOfMinute();
        $windowEnd = $now->copy()->addHours(25)->endOfMinute();

        $this->info("Current time ({$timezone}): {$now->toDateTimeString()}");
        $this->info("Search window: {$windowStart->toDateTimeString()} to {$windowEnd->toDateTimeString()}");

        $appointments = Appointment::whereIn('status', ['pending', 'booked'])
            ->whereNull('reminder_sent_at')
            ->whereBetween('start_time', [$windowStart, $windowEnd])
            ->with(['client', 'staff', 'service', 'location'])
            ->get();

        if ($appointments->isEmpty()) {
            $this->info('No appointments found requiring reminders.');
            return Command::SUCCESS;
        }

        $this->info("Found {$appointments->count()} appointment(s) to process.");

        $sent = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($appointments as $appointment) {
            $result = $this->processAppointment($appointment, $isDryRun);

            switch ($result) {
                case 'sent':
                    $sent++;
                    break;
                case 'skipped':
                    $skipped++;
                    break;
                case 'failed':
                    $failed++;
                    break;
            }
        }

        $this->newLine();
        $this->info("Summary: {$sent} sent, {$skipped} skipped, {$failed} failed");

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function processAppointment(Appointment $appointment, bool $isDryRun): string
    {
        $client = $appointment->client;

        if (!$client || !$client->email || Validator::make(['email' => $client->email], ['email' => 'email'])->fails()) {
            $this->warn("Skipping appointment {$appointment->id}: Missing or invalid client email");
            Log::info('Appointment reminder skipped', [
                'appointment_id' => $appointment->id,
                'client_email' => $client?->email,
                'reason' => 'Missing or invalid client email',
            ]);
            return 'skipped';
        }

        $reference = $this->generateReference($appointment);
        $business = $this->getBusinessContext($appointment);

        if ($isDryRun) {
            $this->info("[DRY RUN] Would send reminder for appointment {$appointment->id} to {$client->email}");
            Log::info('Appointment reminder dry run', [
                'appointment_id' => $appointment->id,
                'client_email' => $client->email,
                'reference' => $reference,
            ]);
            return 'sent';
        }

        try {
            $mail = new AppointmentReminderMail($appointment, $business, $reference);

            Log::info('Appointment reminder SMTP attempt', [
                'appointment_id' => $appointment->id,
                'client_email' => $client->email,
                'mailer' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'encryption' => config('mail.mailers.smtp.encryption'),
                'queue' => config('queue.default'),
            ]);

            if (config('queue.default') !== 'sync') {
                Mail::to($client->email)->queue($mail);
                $verb = 'queued';
            } else {
                Mail::to($client->email)->send($mail);
                $verb = 'sent';
            }

            $appointment->update(['reminder_sent_at' => now()]);

            $this->info("Reminder {$verb} for appointment {$appointment->id} to {$client->email} (ref: {$reference})");
            Log::info('Appointment reminder ' . $verb, [
                'appointment_id' => $appointment->id,
                'client_email' => $client->email,
                'reference' => $reference,
            ]);

            return 'sent';
        } catch (\Throwable $exception) {
            $this->error("Failed to send reminder for appointment {$appointment->id}: {$exception->getMessage()}");
            Log::error('Appointment reminder failed', [
                'appointment_id' => $appointment->id,
                'client_email' => $client->email,
                'mailer' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'encryption' => config('mail.mailers.smtp.encryption'),
                'exception' => $exception->getMessage(),
            ]);
            return 'failed';
        }
    }

    private function getBusinessTimezone(): string
    {
        $settings = BusinessSetting::pluck('value', 'key');
        return $settings->get('timezone') ?? config('app.timezone');
    }

    private function getBusinessContext(Appointment $appointment): array
    {
        $settings = BusinessSetting::pluck('value', 'key');
        $location = $appointment->location;

        return [
            'name' => $settings->get('business_name') ?: config('app.name', 'Online Appointment'),
            'email' => $settings->get('business_email') ?: $location?->email ?: config('mail.from.address'),
            'phone' => $settings->get('business_phone') ?: $location?->phone,
            'address' => $settings->get('business_address') ?: $location?->address,
            'timezone' => $settings->get('timezone') ?: $location?->timezone ?: config('app.timezone'),
            'logo' => $settings->get('business_logo') ?: $settings->get('logo'),
        ];
    }

    private function generateReference(Appointment $appointment): string
    {
        $seed = $appointment->getKey() . '|' . optional($appointment->created_at)->toIso8601String();
        return 'APT-' . strtoupper(substr(hash('sha256', $seed), 0, 10));
    }
}