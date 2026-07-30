<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FormRecord;

class FormRecordSeeder extends Seeder
{
    public function run(): void
    {
        FormRecord::create([
            'form_id' => 1,
            'client_id' => 1,
            'submitted_data' => json_encode(['Name' => 'Alice Johnson', 'Medical History' => 'None', 'Allergies' => 'Peanuts']),
            'submitted_at' => now(),
        ]);
    }
}
