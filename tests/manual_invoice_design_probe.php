<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\InvoiceController;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\PaymentRecord;
use App\Models\Service;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;

function invoiceProbeResult(string $name, bool $pass, string $detail = ''): void
{
    echo ($pass ? 'PASS' : 'FAIL') . ' | ' . $name . ($detail ? ' | ' . $detail : '') . PHP_EOL;
}

function cleanupInvoiceProbe(): void
{
    $clientIds = Client::where('email', 'like', 'invoiceprobe_%@example.com')->pluck('id');
    $staffIds = Staff::where('email', 'like', 'invoiceprobe_%@example.com')->pluck('id');
    $serviceIds = Service::where('name', 'like', 'INVOICE_PROBE_%')->pluck('id');
    $locationIds = Location::where('name', 'like', 'INVOICE_PROBE_%')->pluck('id');
    $appointmentIds = Appointment::whereIn('client_id', $clientIds)
        ->orWhereIn('staff_id', $staffIds)
        ->orWhereIn('service_id', $serviceIds)
        ->pluck('id');
    $invoiceIds = Invoice::where('invoice_number', 'like', 'INV-DESIGN-PROBE-%')
        ->orWhereIn('client_id', $clientIds)
        ->orWhereIn('staff_id', $staffIds)
        ->orWhereIn('appointment_id', $appointmentIds)
        ->pluck('id');

    PaymentRecord::whereIn('invoice_id', $invoiceIds)->delete();
    Invoice::whereIn('id', $invoiceIds)->delete();
    Appointment::whereIn('id', $appointmentIds)->delete();
    Client::whereIn('id', $clientIds)->delete();
    Staff::whereIn('id', $staffIds)->delete();
    Service::whereIn('id', $serviceIds)->delete();
    Location::whereIn('id', $locationIds)->delete();
}

cleanupInvoiceProbe();
View::share('errors', new ViewErrorBag());

$location = Location::create(['name' => 'INVOICE_PROBE_Main', 'address' => '12 Clinic Road', 'timezone' => config('app.timezone'), 'is_active' => true]);
$staff = Staff::create(['location_id' => $location->id, 'name' => 'Dr Invoice Probe With A Long Display Name', 'email' => 'invoiceprobe_staff@example.com', 'password' => Hash::make('Password123'), 'is_active' => true]);
$client = Client::create(['name' => 'Invoice Probe Patient With A Very Long Name', 'email' => 'invoiceprobe_client@example.com', 'phone' => '555-1200', 'city' => 'Probe City', 'client_since' => now()->subYear()->toDateString(), 'is_vip' => true]);
$service = Service::create(['name' => 'INVOICE_PROBE_Long Consultation Service Name', 'type' => 'in_person', 'price' => 1000, 'duration_minutes' => 60, 'buffer_minutes' => 0, 'is_active' => true]);
$appointment = Appointment::create(['client_id' => $client->id, 'staff_id' => $staff->id, 'service_id' => $service->id, 'location_id' => $location->id, 'start_time' => Carbon::parse('next monday 10:00'), 'end_time' => Carbon::parse('next monday 11:00'), 'status' => 'completed']);
$createFlowAppointment = Appointment::create(['client_id' => $client->id, 'staff_id' => $staff->id, 'service_id' => $service->id, 'location_id' => $location->id, 'start_time' => Carbon::parse('next monday 12:00'), 'end_time' => Carbon::parse('next monday 13:00'), 'status' => 'completed']);

$invoice = Invoice::create([
    'appointment_id' => $createFlowAppointment->id,
    'client_id' => $client->id,
    'staff_id' => $staff->id,
    'invoice_number' => 'INV-DESIGN-PROBE-001',
    'total_amount' => 123456.78,
    'paid_amount' => 50000.00,
    'status' => 'partially_paid',
    'issued_date' => now()->toDateString(),
    'due_date' => now()->addDays(7)->toDateString(),
]);
PaymentRecord::create(['invoice_id' => $invoice->id, 'amount' => 30000, 'payment_method' => 'cash', 'payment_date' => now()->toDateString()]);
PaymentRecord::create(['invoice_id' => $invoice->id, 'amount' => 20000, 'payment_method' => 'card', 'payment_date' => now()->addDay()->toDateString(), 'transaction_id' => 'TXN-PROBE']);

$controller = app(InvoiceController::class);
$createHtml = $controller->create()->render();
invoiceProbeResult('Invoice create form renders', str_contains($createHtml, 'Create Invoice') && str_contains($createHtml, 'appointment_id'));

$storeResponse = $controller->store(\Illuminate\Http\Request::create('/invoices', 'POST', [
    'appointment_id' => $appointment->id,
    'client_id' => $client->id,
    'staff_id' => $staff->id,
    'total_amount' => 99.50,
    'issued_date' => now()->toDateString(),
    'due_date' => now()->addDays(5)->toDateString(),
]));
$storedInvoice = Invoice::where('appointment_id', $createFlowAppointment->id)->first();
invoiceProbeResult('Invoice store creates invoice in transaction path', $storedInvoice !== null && !empty($storedInvoice->invoice_number));
try {
    $controller->store(\Illuminate\Http\Request::create('/invoices', 'POST', [
        'appointment_id' => $createFlowAppointment->id,
        'client_id' => $client->id,
        'staff_id' => $staff->id,
        'total_amount' => 88.00,
        'issued_date' => now()->toDateString(),
    ]));
    invoiceProbeResult('Duplicate appointment invoice rejected', false);
} catch (\Illuminate\Validation\ValidationException $exception) {
    invoiceProbeResult('Duplicate appointment invoice rejected', array_key_exists('appointment_id', $exception->errors()));
}

$show = $controller->show((string) $invoice->id);
$showHtml = $show->render();
invoiceProbeResult('Browser invoice renders premium document', str_contains($showHtml, 'invoice-document') && str_contains($showHtml, 'Payment History'));
invoiceProbeResult('Browser invoice includes balance due', str_contains($showHtml, 'Balance Due') && str_contains($showHtml, '73,456.78'));

$download = $controller->download((string) $invoice->id);
$downloadContent = $download->getContent();
invoiceProbeResult('Download returns real PDF content', str_starts_with($downloadContent, '%PDF'));
invoiceProbeResult('Download filename is professional PDF', str_contains($download->headers->get('Content-Disposition'), 'invoice-INV-DESIGN-PROBE-001.pdf'));

$noAppointment = Invoice::create([
    'appointment_id' => null,
    'client_id' => $client->id,
    'staff_id' => $staff->id,
    'invoice_number' => 'INV-DESIGN-PROBE-NO-APPT',
    'total_amount' => 100,
    'paid_amount' => 100,
    'status' => 'paid',
    'issued_date' => now()->toDateString(),
]);
$noAppointmentHtml = $controller->show((string) $noAppointment->id)->render();
invoiceProbeResult('Invoice without appointment renders safely', str_contains($noAppointmentHtml, 'Not available') && str_contains($noAppointmentHtml, 'Paid'));

foreach (['outstanding' => [0, 250], 'paid' => [400, 400], 'void' => [0, 300]] as $status => [$paid, $total]) {
    $statusInvoice = Invoice::create([
        'appointment_id' => $appointment->id,
        'client_id' => $client->id,
        'staff_id' => $staff->id,
        'invoice_number' => 'INV-DESIGN-PROBE-' . strtoupper($status),
        'total_amount' => $total,
        'paid_amount' => $paid,
        'status' => $status,
        'issued_date' => now()->toDateString(),
    ]);

    $statusPdf = $controller->download((string) $statusInvoice->id)->getContent();
    invoiceProbeResult(ucfirst($status) . ' invoice PDF renders', str_starts_with($statusPdf, '%PDF'));
}

cleanupInvoiceProbe();
