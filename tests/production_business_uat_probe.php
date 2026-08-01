<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\CalendarController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\OnlineBookingController;
use App\Http\Controllers\PaymentRecordController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\StaffController;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\PaymentRecord;
use App\Models\Payroll;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;

function uatResult(string $name, bool $pass, string $detail = ''): void
{
    echo ($pass ? 'PASS' : 'FAIL') . ' | ' . $name . ($detail ? ' | ' . $detail : '') . PHP_EOL;
}

function uatReq(string $method, string $uri, array $data = []): Request
{
    return Request::create($uri, $method, $data);
}

function expectValidation(callable $callback, string $field): bool
{
    try {
        $callback();
    } catch (ValidationException $exception) {
        return array_key_exists($field, $exception->errors());
    }

    return false;
}

$fixture = json_decode(file_get_contents(__DIR__ . '/production_uat_fixture.json'), true);
View::share('errors', new ViewErrorBag());

$invoiceController = app(InvoiceController::class);
$paymentController = app(PaymentRecordController::class);
$payrollController = app(PayrollController::class);
$staffController = app(StaffController::class);
$locationController = app(LocationController::class);
$calendarController = app(CalendarController::class);
$onlineController = app(OnlineBookingController::class);

$invoiceResponse = $invoiceController->store(uatReq('POST', '/invoices', [
    'appointment_id' => $fixture['appointment_id'],
    'client_id' => $fixture['client_id'],
    'staff_id' => $fixture['staff_id'],
    'invoice_number' => 'PROD_UAT_INV_' . time(),
    'total_amount' => 200,
    'issued_date' => now()->toDateString(),
    'due_date' => now()->addDays(7)->toDateString(),
]));
$invoice = Invoice::where('appointment_id', $fixture['appointment_id'])->latest()->first();
uatResult('Invoice create', $invoice !== null && (float) $invoice->total_amount === 200.0);

$paymentController->store(uatReq('POST', '/payment-records', [
    'invoice_id' => $invoice->id,
    'amount' => 75,
    'payment_method' => 'cash',
    'payment_date' => now()->toDateString(),
]));
$invoice->refresh();
uatResult('Partial payment recalculates invoice', (float) $invoice->paid_amount === 75.0 && $invoice->status === 'partially_paid', $invoice->status . ' ' . $invoice->paid_amount);

$paymentController->store(uatReq('POST', '/payment-records', [
    'invoice_id' => $invoice->id,
    'amount' => 125,
    'payment_method' => 'card',
    'payment_date' => now()->toDateString(),
    'transaction_id' => 'PROD-UAT-FULL',
]));
$invoice->refresh();
uatResult('Full payment recalculates invoice', (float) $invoice->paid_amount === 200.0 && $invoice->status === 'paid', $invoice->status . ' ' . $invoice->paid_amount);

$payment = PaymentRecord::where('invoice_id', $invoice->id)->where('amount', 125)->first();
$paymentController->destroy((string) $payment->id);
$invoice->refresh();
uatResult('Payment deletion recalculates invoice balance/status', (float) $invoice->paid_amount === 75.0 && $invoice->status === 'partially_paid', $invoice->status . ' ' . $invoice->paid_amount);

$pdfResponse = $invoiceController->download((string) $invoice->id);
$pdfContent = $pdfResponse->getContent();
uatResult('Invoice PDF download opens as PDF', str_starts_with($pdfContent, '%PDF'), substr($pdfContent, 0, 4));

$periodStart = Carbon::now()->addMonths(3)->startOfMonth()->toDateString();
$periodEnd = Carbon::now()->addMonths(3)->endOfMonth()->toDateString();
$payrollController->store(uatReq('POST', '/payroll', [
    'staff_id' => $fixture['staff_id'],
    'period_start' => $periodStart,
    'period_end' => $periodEnd,
    'salary_amount' => 1000,
    'commission_amount' => 100,
    'bonus' => 50,
    'deductions' => 25,
    'total_hours' => 40,
    'payment_type' => 'transfer',
    'status' => 'pending',
    'notes' => 'PROD_UAT payroll',
]));
$payroll = Payroll::where('staff_id', $fixture['staff_id'])->where('period_start', $periodStart)->where('period_end', $periodEnd)->first();
uatResult('Payroll create', $payroll !== null && (float) $payroll->total_payout === 1125.0);
uatResult('Payroll duplicate-period prevention', expectValidation(fn () => $payrollController->store(uatReq('POST', '/payroll', [
    'staff_id' => $fixture['staff_id'],
    'period_start' => $periodStart,
    'period_end' => $periodEnd,
    'salary_amount' => 1000,
    'payment_type' => 'transfer',
    'status' => 'pending',
])), 'period_start'));

$payrollController->update(uatReq('PUT', '/payroll/' . $payroll->id, [
    'period_start' => $periodStart,
    'period_end' => $periodEnd,
    'salary_amount' => 1100,
    'commission_amount' => 100,
    'bonus' => 50,
    'deductions' => 25,
    'total_hours' => 42,
    'payment_type' => 'transfer',
    'status' => 'pending',
    'notes' => 'PROD_UAT payroll edited',
]), (string) $payroll->id);
$payroll->refresh();
uatResult('Payroll edit recalculates total', (float) $payroll->total_payout === 1225.0 && (float) $payroll->total_hours === 42.0);
uatResult('Payroll print view renders', str_contains($payrollController->show((string) $payroll->id)->render(), 'window.print()'));
uatResult('Payroll PDF download opens as PDF', str_starts_with($payrollController->download((string) $payroll->id)->getContent(), '%PDF'));
$payrollController->markPaid((string) $payroll->id);
$payroll->refresh();
uatResult('Payroll mark paid', $payroll->isPaid() && $payroll->payment_date !== null, $payroll->status);
$payrollController->destroy((string) $payroll->id);
uatResult('Paid payroll delete protection', Payroll::whereKey($payroll->id)->exists());

$staff = Staff::findOrFail($fixture['staff_id']);
$oldHash = $staff->password;
$staffController->update(uatReq('PUT', '/staff/' . $staff->id, [
    'name' => $staff->name,
    'email' => $staff->email,
    'password' => '',
    'access_level' => $staff->access_level,
    'location_id' => $staff->location_id,
    'salary' => $staff->salary,
    'is_active' => '1',
]), (string) $staff->id);
$staff->refresh();
uatResult('Staff blank-password edit preserves existing login hash', $staff->password === $oldHash && Hash::check($fixture['password'], $staff->password));

$location = Location::findOrFail($fixture['location_id']);
$locationCountBefore = Location::where('name', 'like', 'PROD_UAT_Main Location%')->count();
$locationController->update(uatReq('PUT', '/locations/' . $location->id, [
    'name' => 'PROD_UAT_Main Location Edited',
    'address' => 'Edited address',
    'email' => 'prod_uat_location_edited@example.com',
    'timezone' => config('app.timezone'),
    'is_active' => '1',
]), (string) $location->id);
$location->refresh();
$locationCountAfter = Location::where('name', 'like', 'PROD_UAT_Main Location%')->count();
uatResult('Location edit updates existing record only', $location->name === 'PROD_UAT_Main Location Edited' && $locationCountAfter === $locationCountBefore, 'before ' . $locationCountBefore . ' after ' . $locationCountAfter);

$start = Carbon::parse($fixture['booking_date'])->setTime(9, 0)->format('Y-m-d\TH:i:s');
$end = Carbon::parse($fixture['booking_date'])->setTime(10, 0)->format('Y-m-d\TH:i:s');
uatResult('Inactive staff calendar booking rejected', expectValidation(fn () => $calendarController->storeAppointment(uatReq('POST', '/calendar/appointments', [
    'staff_id' => $fixture['inactive_staff_id'],
    'client_id' => $fixture['client_id'],
    'service_id' => $fixture['service_id'],
    'location_id' => $fixture['location_id'],
    'start_time' => $start,
    'end_time' => $end,
])), 'staff_id'));
uatResult('Inactive service calendar booking rejected', expectValidation(fn () => $calendarController->storeAppointment(uatReq('POST', '/calendar/appointments', [
    'staff_id' => $fixture['staff_id'],
    'client_id' => $fixture['client_id'],
    'service_id' => $fixture['inactive_service_id'],
    'location_id' => $fixture['location_id'],
    'start_time' => $start,
    'end_time' => $end,
])), 'service_id'));
$inactiveLocationResponse = $calendarController->storeAppointment(uatReq('POST', '/calendar/appointments', [
    'staff_id' => $fixture['staff_id'],
    'client_id' => $fixture['client_id'],
    'service_id' => $fixture['service_id'],
    'location_id' => $fixture['inactive_location_id'],
    'start_time' => $start,
    'end_time' => $end,
]));
uatResult('Inactive location calendar booking rejected', $inactiveLocationResponse->status() === 422, 'status ' . $inactiveLocationResponse->status());
uatResult('Inactive service hidden from public booking page', !str_contains($onlineController->index()->render(), 'PROD_UAT_Inactive Service'));
