<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Service;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        Service::create([
            'name' => 'Initial Assessment',
            'duration_minutes' => 60,
            'price' => 120.00,
            'description' => 'Comprehensive initial evaluation.',
            'service_category_id' => 1, // Physiotherapy
        ]);

        Service::create([
            'name' => 'Standard Follow-up',
            'duration_minutes' => 30,
            'price' => 80.00,
            'description' => 'Routine follow-up session.',
            'service_category_id' => 1, // Physiotherapy
        ]);

        Service::create([
            'name' => 'Deep Tissue Massage',
            'duration_minutes' => 60,
            'price' => 100.00,
            'description' => 'Intense massage for muscle recovery.',
            'service_category_id' => 2, // Massage
        ]);

        Service::create([
            'name' => 'Acupuncture',
            'duration_minutes' => 45,
            'price' => 90.00,
            'description' => 'Traditional needle therapy.',
            'service_category_id' => 3, // Acupuncture
        ]);
    }
}
