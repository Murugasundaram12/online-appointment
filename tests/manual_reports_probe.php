<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\ReportController;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\PaymentRecord;
use App\Models\Service;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;

function repResult(string $name, bool $pass, string $detail = ''): void
{
    echo ($pass ? 'PASS' : 'FAIL') . ' | ' . $name . ($detail ? ' | ' . $detail : '') . PHP_EOL;
}

function repCleanup(): void
{
    $clientIds = Client::where('email', 'like', 'repprobe_%@example.com')->pluck('id');
    $staffIds = Staff::where('email', 'like', 'repprobe_%@example.com')->pluck('id');
    $serviceIds = Service::where('name', 'like', 'REPPROBE_%')->pluck('id');
    $locationIds = Location::where('name', 'like', 'REPPROBE_%')->pluck('id');
    $invoiceIds = Invoice::where('invoice_number', 'like', 'REP_INV_%')->pluck('id');
    PaymentRecord::whereIn('invoice_id', $invoiceIds)->delete();
    Invoice::whereIn('id', $invoiceIds)->delete();
    Appointment::whereIn('client_id', $clientIds)->delete();
    Client::whereIn('id', $clientIds)->delete();
    Staff::whereIn('id', $staffIds)->delete();
    Service::whereIn('id', $serviceIds)->delete();
    Location::whereIn('id', $locationIds)->delete();
}

function repRange(): array
{
    return [
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->endOfMonth()->toDateString(),
    ];
}

function repView(Request $request, string $method)
{
    return app(ReportController::class)->{$method}($request);
}

View::share('errors', new ViewErrorBag());
repCleanup();

$loc = Location::create(['name' => 'REPPROBE_Clinic', 'timezone' => 'UTC', 'is_active' => true]);
$staff = Staff::create(['location_id' => $loc->id, 'name' => 'REPPROBE_Staff', 'email' => 'repprobe_staff@example.com', 'password' => Hash::make('secret123'), 'access_level' => 'staff', 'is_active' => true]);
$service = Service::create(['name' => 'REPPROBE_Checkup', 'type' => 'in_person', 'price' => 60, 'duration_minutes' => 60, 'buffer_minutes' => 0, 'is_active' => true]);
$client = Client::create([
    'name' => 'REPPROBE_Client',
    'email' => 'repprobe_client@example.com',
    'client_since' => now()->startOfMonth()->toDateString(),
    'is_vip' => true,
]);

$inMonth = fn ($day) => now()->startOfMonth()->addDays($day)->setHour(10);
Appointment::create(['client_id' => $client->id, 'staff_id' => $staff->id, 'service_id' => $service->id, 'location_id' => $loc->id, 'start_time' => $inMonth(1), 'end_time' => $inMonth(1)->addMinutes(60), 'status' => 'completed']);
$bookingService = Service::create(['name' => 'REPPROBE_BookingService', 'type' => 'in_person', 'price' => 70, 'duration_minutes' => 30, 'buffer_minutes' => 0, 'is_active' => true]);
$bookedClient = Client::create([
    'name' => 'REPPROBE_BookedClient',
    'email' => 'repprobe_booked@example.com',
    'client_since' => now()->startOfMonth()->toDateString(),
]);
Appointment::create(['client_id' => $bookedClient->id, 'staff_id' => $staff->id, 'service_id' => $bookingService->id, 'location_id' => $loc->id, 'start_time' => $inMonth(2), 'end_time' => $inMonth(2)->addMinutes(30), 'status' => 'booked']);
Appointment::create(['client_id' => $client->id, 'staff_id' => $staff->id, 'service_id' => $service->id, 'location_id' => $loc->id, 'start_time' => $inMonth(3), 'end_time' => $inMonth(3)->addMinutes(60), 'status' => 'cancelled']);

$invoice = Invoice::create([
    'client_id' => $client->id,
    'staff_id' => $staff->id,
    'invoice_number' => 'REP_INV_' . time(),
    'total_amount' => 123.45,
    'paid_amount' => 50,
    'status' => 'partially_paid',
    'issued_date' => now()->startOfMonth()->addDays(4)->toDateString(),
    'due_date' => now()->addDays(7)->toDateString(),
]);
PaymentRecord::create([
    'invoice_id' => $invoice->id,
    'amount' => 50,
    'payment_method' => 'bank_transfer',
    'payment_date' => now()->startOfMonth()->addDays(4)->toDateString(),
    'transaction_id' => 'REPPROBE_TXN',
]);

$base = repRange();

$index = repView(Request::create('/reports', 'GET', $base), 'index')->render();
repResult('reports hub renders', str_contains($index, 'Appointments') && str_contains($index, 'Revenue') && str_contains($index, 'Staff'));

$appointments = repView(Request::create('/reports/appointments', 'GET', $base), 'appointments');
repResult('reports appointments renders seeded rows', str_contains($appointments->render(), 'REPPROBE_Client') && str_contains($appointments->render(), 'REPPROBE_BookingService'));
$apptsFiltered = repView(Request::create('/reports/appointments', 'GET', $base + ['status' => 'completed']), 'appointments');
repResult('reports appointments status filter', str_contains($apptsFiltered->render(), 'REPPROBE_Client') && !str_contains($apptsFiltered->render(), 'REPPROBE_BookedClient'));

$revenue = repView(Request::create('/reports/revenue', 'GET', $base), 'revenue');
$revenueHtml = $revenue->render();
repResult('reports revenue shows billed', str_contains($revenueHtml, '123.45') && str_contains($revenueHtml, '50.00'));

$invoices = repView(Request::create('/reports/invoices', 'GET', $base), 'invoices');
$invoicesHtml = $invoices->render();
repResult('reports invoices lists seeded invoice', str_contains($invoicesHtml, $invoice->invoice_number) && str_contains($invoicesHtml, 'Partially paid'));

$payments = repView(Request::create('/reports/payments', 'GET', $base), 'payments');
$paymentsHtml = $payments->render();
repResult('reports payments lists seeded payment', str_contains($paymentsHtml, 'REPPROBE_TXN') && str_contains($paymentsHtml, 'bank_transfer'));

$clients = repView(Request::create('/reports/clients', 'GET', $base), 'clients');
$clientsHtml = $clients->render();
repResult('reports clients lists seeded client', str_contains($clientsHtml, 'REPPROBE_Client') && str_contains($clientsHtml, '123.45'));

$staff = repView(Request::create('/reports/staff', 'GET', $base), 'staff');
$staffHtml = $staff->render();
repResult('reports staff shows counts and revenue', str_contains($staffHtml, 'REPPROBE_Staff') && str_contains($staffHtml, '123.45'));

$export = app(ReportController::class)->export('appointments', Request::create('/reports/export/appointments', 'GET', $base));
repResult('reports export appointments csv', $export->getStatusCode() === 200 && str_contains($export->headers->get('Content-Type') ?? '', 'text/csv'));

$exportRevenue = app(ReportController::class)->export('revenue', Request::create('/reports/export/revenue', 'GET', $base));
repResult('reports export revenue csv', $exportRevenue->getStatusCode() === 200);

$exportClients = app(ReportController::class)->export('clients', Request::create('/reports/export/clients', 'GET', $base + ['search' => 'REPPROBE']));
repResult('reports export clients csv', $exportClients->getStatusCode() === 200);

try {
    app(ReportController::class)->export('nonsense', Request::create('/reports/export/nonsense', 'GET', $base));
    repResult('reports export unknown type 404s', false, 'no exception thrown');
} catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
    repResult('reports export unknown type 404s', true);
}

repCleanup();
echo 'Reports probe complete.' . PHP_EOL;
