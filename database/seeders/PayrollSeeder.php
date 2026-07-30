<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Payroll;
use Carbon\Carbon;

class PayrollSeeder extends Seeder
{
    public function run(): void
    {
        Payroll::create([
            'staff_id' => 1,
            'period_start' => Carbon::now()->startOfMonth(),
            'period_end' => Carbon::now()->endOfMonth(),
            'total_hours' => 160.00,
            'total_commission' => 500.00,
            'total_payout' => 5000.00,
            'status' => 'pending',
        ]);
    }
}
