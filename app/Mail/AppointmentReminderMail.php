<?php

namespace App\Mail;

use App\Mail\Concerns\PreparesAppointmentTimes;
use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AppointmentReminderMail extends Mailable
{
    use Queueable, SerializesModels, PreparesAppointmentTimes;

    public function __construct(
        public Appointment $appointment,
        public array $business,
        public string $reference = '',
        public string $recipientType = 'client'
    ) {
        $this->prepareAppointmentTimes();
    }

    public function build(): self
    {
        $clientName = $this->appointment->client->name ?? 'Client';
        $ref = $this->reference ?: 'APT-' . $this->appointment->id;

        if ($this->recipientType === 'staff') {
            return $this
                ->subject("Appointment Reminder – {$clientName}")
                ->view('emails.appointments.reminder', [
                    'recipientType' => 'staff',
                ]);
        }

        return $this
            ->subject("Appointment Reminder – {$ref}")
            ->view('emails.appointments.reminder', [
                'recipientType' => 'client',
            ]);
    }
}