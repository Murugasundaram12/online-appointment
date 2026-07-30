<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StaffSchedule;

class StaffScheduleSeeder extends Seeder
{
    public function run(): void
    {
        // Schedule for Staff 1
        StaffSchedule::create([
            'staff_id' => 1,
            'day_of_week' => 'Monday',
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
        ]);
        StaffSchedule::create([
            'staff_id' => 1,
            'day_of_week' => 'Wednesday',
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
        ]);
        StaffSchedule::create([
            'staff_id' => 1,
            'day_of_week' => 'Friday',
            'start_time' => '09:00:00',
            'end_time' => '16:00:00',
        ]);

        // Schedule for Staff 2
        StaffSchedule::create([
            'staff_id' => 2,
            'day_of_week' => 'Tuesday',
            'start_time' => '10:00:00',
            'end_time' => '18:00:00',
        ]);
        StaffSchedule::create([
            'staff_id' => 2,
            'day_of_week' => 'Thursday',
            'start_time' => '10:00:00',
            'end_time' => '18:00:00',
        ]);
    }
}
