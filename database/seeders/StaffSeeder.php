<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Staff;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        Staff::create([
            'name' => 'Dr. John Smith',
            'email' => 'john@example.com',
            'password' => Hash::make('password'),
            'phone' => '555-1001',
            'category' => 'Therapist',
            'access_level' => 'admin',
            'bio' => 'Senior Physiotherapist with 10 years experience.',
        ]);

        Staff::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => Hash::make('password'),
            'phone' => '555-1002',
            'category' => 'Massage Therapist',
            'access_level' => 'staff',
            'bio' => 'Certified massage therapist specializing in sports recovery.',
        ]);

        Staff::create([
            'name' => 'Emily Chen',
            'email' => 'emily@example.com',
            'password' => Hash::make('password'),
            'phone' => '555-1003',
            'category' => 'Receptionist',
            'access_level' => 'receptionist',
            'bio' => 'Front desk manager.',
        ]);
    }
}
