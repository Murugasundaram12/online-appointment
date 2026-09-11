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
        if ($appointment->confirmation_sent_at !== null) {
            return ['attempted' => false, 'sent' => false, 'message' => 'Booking confirmation email already sent.'];
        }

        $res = $this->send($appointment, AppointmentBookedMail::class, 'booked');
        if (!empty($res['attempted']) || !empty($res['sent'])) {
            $appointment->update(['confirmation_sent_at' => now()]);
        }

        return $res;
    }

    public function sendForCreation(Appointment $appointment): array
    {
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

    private function send(Appointment $appointment, string $mailableClass, string $type, ?Appointment $previous = null): array
    {
        $appointment->loadMissing(['client', 'staff', 'service', 'location']);
        $client = $appointment->client;
        $staff = $appointment->staff;

        $clientEmail = ($client && $client->email && !Validator::make(['email' => $client->email], ['email' => 'email'])->fails())
            ? $client->email
            : null;
        $staffEmail = ($staff && $staff->email && !Validator::make(['email' => $staff->email], ['email' => 'email'])->fails())
            ? $staff->email
            : null;

        if (!$clientEmail && !$staffEmail) {
            Log::info('Appointment email skipped', [
                'appointment_id' => $appointment->id,
                'client_email' => $client?->email,
                'staff_email' => $staff?->email,
                'mail_type' => $type,
                'reason' => 'Missing or invalid recipient email(s)',
            ]);

            return ['attempted' => false, 'sent' => false, 'message' => 'No valid recipient email available.'];
        }

        $business = $this->businessContext($appointment);
        $reference = $this->publicReference($appointment);
        $isAsync = config('queue.default') !== 'sync';
        $attempted = false;
        $anySent = false;

        // Send client-oriented email if client email is valid
        if ($clientEmail) {
            $attempted = true;
            try {
                $clientMail = new $mailableClass($appointment, $business, $previous, $reference, 'client');
                if ($isAsync) {
                    Mail::to($clientEmail)->queue($clientMail);
                } else {
                    Mail::to($clientEmail)->send($clientMail);
                }
                $anySent = true;
                Log::info('Appointment email ' . ($isAsync ? 'queued' : 'sent') . ' to client', [
                    'appointment_id' => $appointment->id,
                    'recipient' => $clientEmail,
                    'mail_type' => $type,
                ]);
            } catch (\Throwable $exception) {
                Log::error('Appointment email to client failed', [
                    'appointment_id' => $appointment->id,
                    'recipient' => $clientEmail,
                    'mail_type' => $type,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        // Send staff-oriented email if staff email is valid
        if ($staffEmail) {
            $attempted = true;
            try {
                $staffMail = new $mailableClass($appointment, $business, $previous, $reference, 'staff');
                if ($isAsync) {
                    Mail::to($staffEmail)->queue($staffMail);
                } else {
                    Mail::to($staffEmail)->send($staffMail);
                }
                $anySent = true;
                Log::info('Appointment email ' . ($isAsync ? 'queued' : 'sent') . ' to staff', [
                    'appointment_id' => $appointment->id,
                    'recipient' => $staffEmail,
                    'mail_type' => $type,
                ]);
            } catch (\Throwable $exception) {
                Log::error('Appointment email to staff failed', [
                    'appointment_id' => $appointment->id,
                    'recipient' => $staffEmail,
                    'mail_type' => $type,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        $verb = $isAsync ? 'queued' : 'sent';

        return [
            'attempted' => $attempted,
            'sent' => $anySent,
            'message' => $anySent ? "Notification email {$verb}." : "Notification email could not be sent.",
        ];
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
