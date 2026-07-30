<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Appointment;
use Carbon\Carbon;

class AppointmentSeeder extends Seeder
{
    public function run(): void
    {
        // Appointment for today
        Appointment::create([
            'client_id' => 1,
            'staff_id' => 1,
            'service_id' => 1,
            'location_id' => 1,
            'start_time' => Carbon::now()->setHour(10)->setMinute(0)->setSecond(0),
            'end_time' => Carbon::now()->setHour(11)->setMinute(0)->setSecond(0),
            'status' => 'confirmed',
            'notes' => 'First session.',
        ]);

        // Appointment for tomorrow
        Appointment::create([
            'client_id' => 2,
            'staff_id' => 1,
            'service_id' => 2,
            'location_id' => 1,
            'start_time' => Carbon::tomorrow()->setHour(14)->setMinute(0)->setSecond(0),
            'end_time' => Carbon::tomorrow()->setHour(14)->setMinute(30)->setSecond(0),
            'status' => 'pending',
            'notes' => 'Follow up.',
        ]);

        // Past appointment
        Appointment::create([
            'client_id' => 1,
            'staff_id' => 1,
            'service_id' => 1,
            'location_id' => 1,
            'start_time' => Carbon::yesterday()->setHour(9)->setMinute(0)->setSecond(0),
            'end_time' => Carbon::yesterday()->setHour(10)->setMinute(0)->setSecond(0),
            'status' => 'completed',
            'notes' => 'Completed successfully.',
        ]);
    }
}
