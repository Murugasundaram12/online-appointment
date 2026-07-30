<?php

use App\Models\Payroll;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Manual payroll probe\n";

DB::beginTransaction();

try {
    $staff = Staff::create([
        'name' => 'Payroll Probe Staff',
        'email' => 'payroll-probe-' . time() . '@example.com',
        'password' => Hash::make('password123'),
        'salary' => 2500,
        'is_active' => true,
    ]);

    $payroll = Payroll::create([
        'staff_id' => $staff->id,
        'period_start' => now()->startOfMonth()->toDateString(),
        'period_end' => now()->endOfMonth()->toDateString(),
        'salary_amount' => 2500,
        'commission_amount' => 300,
        'bonus' => 200,
        'deductions' => 150,
        'total_hours' => 160,
        'payment_type' => 'transfer',
        'status' => 'pending',
        'notes' => 'Probe record',
    ]);
    $payroll->total_payout = $payroll->calculateTotalPayout();
    $payroll->save();

    assert((float) $payroll->total_payout === 2850.0);
    assert($payroll->display_status === 'pending');
    assert(!$payroll->isPaid());

    $payroll->status = 'completed';
    $payroll->payment_date = now()->toDateString();
    $payroll->save();

    assert($payroll->display_status === 'paid');
    assert($payroll->isPaid());

    $duplicateExists = Payroll::where('staff_id', $staff->id)
        ->where('period_start', $payroll->period_start)
        ->where('period_end', $payroll->period_end)
        ->exists();

    assert($duplicateExists);

    echo "PASS: create, salary total, mark paid mapping, duplicate-period detection query.\n";
} finally {
    DB::rollBack();
}

