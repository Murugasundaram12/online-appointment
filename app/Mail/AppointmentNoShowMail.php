<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AppointmentNoShowMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Appointment $appointment,
        public array $business,
        public ?Appointment $previous = null,
        public string $reference = ''
    ) {
    }

    public function build(): self
    {
        return $this->subject('Appointment marked as no show - ' . $this->reference)
            ->view('emails.appointments.no_show');
    }
}
