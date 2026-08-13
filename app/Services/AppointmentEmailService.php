<?php

namespace App\Services;

use App\Mail\AppointmentBookedMail;
use App\Mail\AppointmentCancelledMail;
use App\Mail\AppointmentCompletedMail;
use App\Mail\AppointmentConfirmedMail;
use App\Mail\AppointmentNoShowMail;
use App\Mail\AppointmentUpdatedMail;
use App\Models\Appointment;
use App\Models\BusinessSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class AppointmentEmailService
{
    public function sendBooked(Appointment $appointment): array
    {
        return $this->send($appointment, AppointmentBookedMail::class, 'booked');
    }

    public function sendForCreation(Appointment $appointment): array
    {
        // The existing booking email is the customer-facing notification for
        // newly created pending and booked appointments.
        return $this->sendBooked($appointment);
    }

    public function sendForStatusTransition(Appointment $appointment, Appointment $previous): array
    {
        if ($appointment->status === $previous->status) {
            return ['attempted' => false, 'sent' => false, 'message' => 'No status email needed.'];
        }

        return match ($appointment->status) {
            'booked' => $this->sendBooked($appointment),
            'confirmed' => $this->send($appointment, AppointmentConfirmedMail::class, 'confirmed', $previous),
            'completed' => $this->sendCompletedIfTransitioned($appointment, $previous),
            'cancelled' => $this->sendCancelledIfTransitioned($appointment, $previous),
            'no_show' => $this->sendNoShowIfTransitioned($appointment, $previous),
            default => ['attempted' => false, 'sent' => false, 'message' => 'No status email needed.'],
        };
    }

    public function sendUpdatedIfRelevant(Appointment $appointment, Appointment $previous): array
    {
        if (!$this->hasRelevantScheduleChange($appointment, $previous)) {
            return ['attempted' => false, 'sent' => false, 'message' => 'No appointment email needed.'];
        }

        return $this->send($appointment, AppointmentUpdatedMail::class, 'updated', $previous);
    }

    public function sendCancelledIfTransitioned(Appointment $appointment, Appointment $previous): array
    {
        if ($previous->status === 'cancelled' || $appointment->status !== 'cancelled') {
            return ['attempted' => false, 'sent' => false, 'message' => 'No cancellation email needed.'];
        }

        return $this->send($appointment, AppointmentCancelledMail::class, 'cancelled', $previous);
    }

    public function sendCompletedIfTransitioned(Appointment $appointment, Appointment $previous): array
    {
        if ($previous->status === 'completed' || $appointment->status !== 'completed') {
            return ['attempted' => false, 'sent' => false, 'message' => 'No completed email needed.'];
        }

        return $this->send($appointment, AppointmentCompletedMail::class, 'completed', $previous);
    }

    public function sendNoShowIfTransitioned(Appointment $appointment, Appointment $previous): array
    {
        if ($previous->status === 'no_show' || $appointment->status !== 'no_show') {
            return ['attempted' => false, 'sent' => false, 'message' => 'No no-show email needed.'];
        }

        return $this->send($appointment, AppointmentNoShowMail::class, 'no_show', $previous);
    }

    public function publicReference(Appointment $appointment): string
    {
        $seed = $appointment->getKey() . '|' . optional($appointment->created_at)->toIso8601String();

        return 'APT-' . strtoupper(substr(hash('sha256', $seed), 0, 10));
    }

    public function businessContext(?Appointment $appointment = null): array
    {
        $settings = BusinessSetting::pluck('value', 'key');
        $location = $appointment?->location;

        return [
            'name' => $settings->get('business_name') ?: config('app.name', 'Online Appointment'),
            'email' => $settings->get('business_email') ?: $location?->email ?: config('mail.from.address'),
            'phone' => $settings->get('business_phone') ?: $location?->phone,
            'address' => $settings->get('business_address') ?: $location?->address,
            'timezone' => $settings->get('timezone') ?: $location?->timezone ?: config('app.timezone'),
            'logo' => $settings->get('business_logo') ?: $settings->get('logo'),
        ];
    }

    private function send(Appointment $appointment, string $mailableClass, string $type, ?Appointment $previous = null): array
    {
        $appointment->loadMissing(['client', 'staff', 'service', 'location']);
        $client = $appointment->client;

        if (!$client || !$client->email || Validator::make(['email' => $client->email], ['email' => 'email'])->fails()) {
            Log::info('Appointment email skipped', [
                'appointment_id' => $appointment->id,
                'client_email' => $client?->email,
                'mail_type' => $type,
                'reason' => 'Missing or invalid client email',
            ]);

            return ['attempted' => false, 'sent' => false, 'message' => 'No valid client email available.'];
        }

        try {
            $mail = new $mailableClass($appointment, $this->businessContext($appointment), $previous, $this->publicReference($appointment));

            Log::info('Appointment email SMTP attempt', [
                'appointment_id' => $appointment->id,
                'client_email' => $client->email,
                'mail_type' => $type,
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

            Log::info('Appointment email ' . $verb, [
                'appointment_id' => $appointment->id,
                'client_email' => $client->email,
                'mail_type' => $type,
            ]);

            return ['attempted' => true, 'sent' => true, 'message' => 'Confirmation email ' . $verb . '.'];
        } catch (\Throwable $exception) {
            Log::error('Appointment email failed', [
                'appointment_id' => $appointment->id,
                'client_email' => $client->email,
                'mail_type' => $type,
                'mailer' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'encryption' => config('mail.mailers.smtp.encryption'),
                'exception' => $exception->getMessage(),
            ]);

            return ['attempted' => true, 'sent' => false, 'message' => 'Confirmation email could not be sent.'];
        }
    }

    private function hasRelevantScheduleChange(Appointment $appointment, Appointment $previous): bool
    {
        foreach (['start_time', 'end_time', 'staff_id', 'location_id', 'service_id'] as $field) {
            if ((string) $appointment->{$field} !== (string) $previous->{$field}) {
                return true;
            }
        }

        return false;
    }
}
