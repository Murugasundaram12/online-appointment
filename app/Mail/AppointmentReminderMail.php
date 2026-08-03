<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AppointmentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Appointment $appointment,
        public array $business,
        public string $reference = ''
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject('Appointment reminder - ' . $this->reference)
            ->view('emails.appointments.reminder');
    }
}