<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Package;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        Package::create([
            'name' => '5 Session Pack',
            'price' => 450.00,
            'description' => 'Save $50 on 5 sessions.',
            'validity_days' => 60,
        ]);

        Package::create([
            'name' => '10 Session Pack',
            'price' => 850.00,
            'description' => 'Save $150 on 10 sessions.',
            'validity_days' => 90,
        ]);
    }
}
