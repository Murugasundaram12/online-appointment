<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Form;

class FormSeeder extends Seeder
{
    public function run(): void
    {
        Form::create([
            'name' => 'Intake Form',
            'description' => 'New patient intake form',
            'fields' => ['questions' => ['Name', 'Medical History', 'Allergies']],
        ]);

        Form::create([
            'name' => 'COVID-19 Screening',
            'description' => 'Mandatory screening before entry',
            'fields' => ['questions' => ['Symptoms?', 'Contact with positive case?']],
        ]);
    }
}
