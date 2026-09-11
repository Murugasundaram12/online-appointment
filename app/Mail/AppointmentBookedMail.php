<?php

namespace App\Mail;

use App\Mail\Concerns\PreparesAppointmentTimes;
use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AppointmentBookedMail extends Mailable
{
    use Queueable, SerializesModels, PreparesAppointmentTimes;

    public function __construct(
        public Appointment $appointment,
        public array $business,
        public ?Appointment $previous = null,
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
                ->subject("New Appointment Assigned – {$clientName}")
                ->view('emails.appointments.booked', [
                    'recipientType' => 'staff',
                ]);
        }

        return $this
            ->subject("Appointment Confirmation – {$ref}")
            ->view('emails.appointments.booked', [
                'recipientType' => 'client',
            ]);
    }
}
