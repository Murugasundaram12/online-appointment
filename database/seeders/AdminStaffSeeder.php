<?php

namespace Database\Seeders;

use App\Models\Staff;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminStaffSeeder extends Seeder
{
    public function run(): void
    {
        $activeAdmin = Staff::where('is_active', true)
            ->where('access_level', 'admin')
            ->orderBy('id')
            ->first();

        if ($activeAdmin) {
            $activeAdmin->forceFill([
                'password' => Hash::make('12345678'),
            ])->save();

            $this->command?->info('Existing active admin staff password reset: ' . $activeAdmin->email);
            return;
        }

        Staff::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'System Admin',
                'phone' => '000-000-0000',
                'bio' => 'Default administrator account.',
                'color' => '#3699ff',
                'access_level' => 'admin',
                'category' => 'Administrator',
                'salary' => 0,
                'password' => Hash::make('Admin@123'),
                'is_active' => true,
            ]
        );

        $this->command?->info('Created active admin staff account: admin@example.com');
    }
}
