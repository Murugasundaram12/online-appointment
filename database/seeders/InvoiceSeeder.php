<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Invoice;
use Carbon\Carbon;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        Invoice::create([
            'client_id' => 1,
            'appointment_id' => 1, // Use valid appointment ID
            'staff_id' => 1,
            'invoice_number' => 'INV-001',
            'total_amount' => 120.00,
            'paid_amount' => 120.00,
            'status' => 'paid',
            'issued_date' => Carbon::yesterday(),
            'due_date' => Carbon::yesterday()->addDays(30),
        ]);

        Invoice::create([
            'client_id' => 1,
            'appointment_id' => 2,
            'staff_id' => 1,
            'invoice_number' => 'INV-002',
            'total_amount' => 120.00,
            'paid_amount' => 0.00,
            'status' => 'outstanding',
            'issued_date' => Carbon::today(),
            'due_date' => Carbon::today()->addDays(30),
        ]);
    }
}
