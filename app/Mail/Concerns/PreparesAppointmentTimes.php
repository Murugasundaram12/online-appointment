<?php

namespace App\Mail\Concerns;

use Carbon\Carbon;

trait PreparesAppointmentTimes
{
    public ?Carbon $appointmentStart = null;
    public ?Carbon $appointmentEnd = null;
    public ?int $durationMinutes = null;
    public ?Carbon $previousStart = null;
    public ?Carbon $previousEnd = null;

    protected function prepareAppointmentTimes(): void
    {
        $tz = $this->business['timezone'] ?? $this->appointment->location?->timezone ?? null;

        if ($this->appointment->start_time) {
            $this->appointmentStart = $tz
                ? $this->appointment->start_time->copy()->shiftTimezone($tz)
                : Carbon::parse($this->appointment->start_time->format('Y-m-d H:i:s'));
        }

        if ($this->appointment->end_time) {
            $this->appointmentEnd = $tz
                ? $this->appointment->end_time->copy()->shiftTimezone($tz)
                : Carbon::parse($this->appointment->end_time->format('Y-m-d H:i:s'));
        }

        if ($this->appointmentStart && $this->appointmentEnd) {
            $this->durationMinutes = $this->appointmentStart->diffInMinutes($this->appointmentEnd);
        }

        if (isset($this->previous) && $this->previous) {
            if ($this->previous->start_time) {
                $this->previousStart = $tz
                    ? $this->previous->start_time->copy()->shiftTimezone($tz)
                    : Carbon::parse($this->previous->start_time->format('Y-m-d H:i:s'));
            }

            if ($this->previous->end_time) {
                $this->previousEnd = $tz
                    ? $this->previous->end_time->copy()->shiftTimezone($tz)
                    : Carbon::parse($this->previous->end_time->format('Y-m-d H:i:s'));
            }
        }
    }
}
