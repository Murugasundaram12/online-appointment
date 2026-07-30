<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Location;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        Location::create([
            'name' => 'Main Branch',
            'address' => '123 Main St, New York, NY',
            'phone' => '555-0100',
            'email' => 'contact@mainbranch.com',
        ]);

        Location::create([
            'name' => 'Downtown Clinic',
            'address' => '456 Market Ave, New York, NY',
            'phone' => '555-0200',
            'email' => 'contact@downtown.com',
        ]);
    }
}
