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

        $appointments = Appointment::whereIn('status', ['pending', 'booked', 'confirmed'])
            ->whereNull('reminder_sent_at')
            ->whereBetween('start_time', [$windowStart->format('Y-m-d H:i:s'), $windowEnd->format('Y-m-d H:i:s')])
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
        $staff = $appointment->staff;
        $clientEmail = ($client && $client->email && !Validator::make(['email' => $client->email], ['email' => 'email'])->fails())
            ? $client->email
            : null;
        $staffEmail = ($staff && $staff->email && !Validator::make(['email' => $staff->email], ['email' => 'email'])->fails())
            ? $staff->email
            : null;

        if (!$clientEmail && !$staffEmail) {
            $this->warn("Skipping appointment {$appointment->id}: Missing or invalid recipient email(s)");
            Log::info('Appointment reminder skipped', [
                'appointment_id' => $appointment->id,
                'client_email' => $client?->email,
                'staff_email' => $staff?->email,
                'reason' => 'Missing or invalid recipient email(s)',
            ]);
            return 'skipped';
        }

        $reference = $this->generateReference($appointment);
        $business = $this->getBusinessContext($appointment);
        $recipientsList = implode(', ', array_filter([$clientEmail, $staffEmail]));

        if ($isDryRun) {
            $this->info("[DRY RUN] Would send reminder for appointment {$appointment->id} to {$recipientsList}");
            Log::info('Appointment reminder dry run', [
                'appointment_id' => $appointment->id,
                'recipients' => $recipientsList,
                'reference' => $reference,
            ]);
            return 'sent';
        }

        $attempted = false;
        $anySent = false;
        $allQueued = config('queue.default') !== 'sync';

        // Send client reminder
        if ($clientEmail) {
            $attempted = true;
            try {
                $clientMail = new AppointmentReminderMail($appointment, $business, $reference, 'client');
                if ($allQueued) {
                    Mail::to($clientEmail)->queue($clientMail);
                } else {
                    Mail::to($clientEmail)->send($clientMail);
                }
                $anySent = true;
            } catch (\Throwable $mailEx) {
                Log::warning('SMTP send failed for client reminder: ' . $mailEx->getMessage());
            }
        }

        // Send staff reminder
        if ($staffEmail) {
            $attempted = true;
            try {
                $staffMail = new AppointmentReminderMail($appointment, $business, $reference, 'staff');
                if ($allQueued) {
                    Mail::to($staffEmail)->queue($staffMail);
                } else {
                    Mail::to($staffEmail)->send($staffMail);
                }
                $anySent = true;
            } catch (\Throwable $mailEx) {
                Log::warning('SMTP send failed for staff reminder: ' . $mailEx->getMessage());
            }
        }

        if ($attempted) {
            $appointment->update(['reminder_sent_at' => now()]);
        }

        $verb = $anySent ? ($allQueued ? 'queued' : 'sent') : 'attempted';
        $this->info("Reminder {$verb} for appointment {$appointment->id} to {$recipientsList} (ref: {$reference})");
        Log::info('Appointment reminder ' . $verb, [
            'appointment_id' => $appointment->id,
            'recipients' => $recipientsList,
            'reference' => $reference,
        ]);

        return $anySent ? 'sent' : 'failed';
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
        $businessName = $settings->get('business_name');
        if (empty($businessName) || strtolower(trim($businessName)) === 'laravel') {
            $businessName = 'mrclinicpro';
        }

        return [
            'name' => $businessName,
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