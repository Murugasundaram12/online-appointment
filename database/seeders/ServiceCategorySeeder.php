<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServiceCategory;

class ServiceCategorySeeder extends Seeder
{
    public function run(): void
    {
        ServiceCategory::create([
            'name' => 'Physiotherapy',
            'description' => 'Physical therapy treatments.',
        ]);

        ServiceCategory::create([
            'name' => 'Massage',
            'description' => 'Therapeutic massage services.',
        ]);

        ServiceCategory::create([
            'name' => 'Acupuncture',
            'description' => 'Traditional Chinese medicine.',
        ]);
    }
}
