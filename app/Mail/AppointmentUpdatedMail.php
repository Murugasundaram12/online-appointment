<?php

namespace App\Mail;

use App\Mail\Concerns\PreparesAppointmentTimes;
use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AppointmentUpdatedMail extends Mailable
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
                ->subject("Appointment Updated – {$clientName}")
                ->view('emails.appointments.updated', [
                    'recipientType' => 'staff',
                ]);
        }

        return $this
            ->subject("Appointment Updated – {$ref}")
            ->view('emails.appointments.updated', [
                'recipientType' => 'client',
            ]);
    }
}
