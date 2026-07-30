<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentRecord;
use Carbon\Carbon;

class PaymentRecordSeeder extends Seeder
{
    public function run(): void
    {
        PaymentRecord::create([
            'invoice_id' => 1,
            'amount' => 120.00,
            'payment_method' => 'Credit Card',
            'payment_date' => Carbon::yesterday(),
            'transaction_id' => 'TXN123456789',
        ]);
    }
}
