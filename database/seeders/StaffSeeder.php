<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Staff;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        Staff::updateOrCreate(
            ['email' => 'john@example.com'],
            [
                'name' => 'John',
                'password' => Hash::make('12345678'),
                'phone' => '555-1001',
                'category' => 'Therapist',
                'access_level' => 'admin',
                'is_active' => true,
                'bio' => 'Senior Physiotherapist.',
            ]
        );

        Staff::updateOrCreate(
            ['email' => 'jane@example.com'],
            [
                'name' => 'Jane Doe',
                'password' => Hash::make('password'),
                'phone' => '555-1002',
                'category' => 'Massage Therapist',
                'access_level' => 'staff',
                'is_active' => true,
                'bio' => 'Certified massage therapist specializing in sports recovery.',
            ]
        );

        Staff::updateOrCreate(
            ['email' => 'emily@example.com'],
            [
                'name' => 'Emily Chen',
                'password' => Hash::make('password'),
                'phone' => '555-1003',
                'category' => 'Receptionist',
                'access_level' => 'receptionist',
                'is_active' => true,
                'bio' => 'Front desk manager.',
            ]
        );
    }
}
