<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\ClientController;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Form;
use App\Models\FormRecord;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\PaymentRecord;
use App\Models\Service;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;

function crmResult(string $name, bool $pass, string $detail = ''): void
{
    echo ($pass ? 'PASS' : 'FAIL') . ' | ' . $name . ($detail ? ' | ' . $detail : '') . PHP_EOL;
}

function crmCleanup(): void
{
    $clientIds = Client::where('email', 'like', 'crmprobe_%@example.com')->pluck('id');
    $staffIds = Staff::where('email', 'like', 'crmprobe_%@example.com')->pluck('id');
    $serviceIds = Service::where('name', 'like', 'CRMPROBE_%')->pluck('id');
    $locationIds = Location::where('name', 'like', 'CRMPROBE_%')->pluck('id');
    $invoiceIds = Invoice::where('invoice_number', 'like', 'CRM_INV_%')->pluck('id');
    $formIds = Form::where('name', 'like', 'CRMPROBE_%')->pluck('id');
    PaymentRecord::whereIn('invoice_id', $invoiceIds)->delete();
    FormRecord::whereIn('form_id', $formIds)->delete();
    Form::whereIn('id', $formIds)->delete();
    Invoice::whereIn('id', $invoiceIds)->delete();
    Appointment::whereIn('client_id', $clientIds)->delete();
    Client::whereIn('id', $clientIds)->delete();
    Staff::whereIn('id', $staffIds)->delete();
    Service::whereIn('id', $serviceIds)->delete();
    Location::whereIn('id', $locationIds)->delete();
}

View::share('errors', new ViewErrorBag());
crmCleanup();

$loc = Location::create(['name' => 'CRMPROBE_Clinic', 'timezone' => 'UTC', 'is_active' => true]);
$staff = Staff::create(['location_id' => $loc->id, 'name' => 'CRMPROBE_Staff', 'email' => 'crmprobe_staff@example.com', 'password' => Hash::make('secret123'), 'access_level' => 'staff', 'is_active' => true]);
$service = Service::create(['name' => 'CRMPROBE_Checkup', 'type' => 'in_person', 'price' => 50, 'duration_minutes' => 60, 'buffer_minutes' => 0, 'is_active' => true]);
$client = Client::create([
    'name' => 'CRMPROBE_Doe',
    'email' => 'crmprobe_doe@example.com',
    'phone' => '123-4567',
    'city' => 'Testville',
    'notes' => 'VIP repeat client',
    'client_since' => now()->subMonths(2)->toDateString(),
    'is_vip' => true,
]);

$form = Form::create(['name' => 'CRMPROBE_Intake', 'description' => 'probe', 'fields' => ['question' => 'x'], 'is_active' => true]);
FormRecord::create([
    'form_id' => $form->id,
    'client_id' => $client->id,
    'submitted_data' => ['name' => 'CRMPROBE_Doe'],
    'submitted_at' => now(),
]);

$upcoming = Appointment::create([
    'client_id' => $client->id,
    'staff_id' => $staff->id,
    'service_id' => $service->id,
    'location_id' => $loc->id,
    'start_time' => now()->addDays(5)->startOfHour(),
    'end_time' => now()->addDays(5)->startOfHour()->addMinutes(60),
    'status' => 'booked',
]);
Appointment::create([
    'client_id' => $client->id,
    'staff_id' => $staff->id,
    'service_id' => $service->id,
    'location_id' => $loc->id,
    'start_time' => now()->subDays(10)->startOfHour(),
    'end_time' => now()->subDays(10)->startOfHour()->addMinutes(60),
    'status' => 'completed',
]);
Appointment::create([
    'client_id' => $client->id,
    'staff_id' => $staff->id,
    'service_id' => $service->id,
    'location_id' => $loc->id,
    'start_time' => now()->subDays(20)->startOfHour(),
    'end_time' => now()->subDays(20)->startOfHour()->addMinutes(60),
    'status' => 'cancelled',
]);

$invoice = Invoice::create([
    'client_id' => $client->id,
    'staff_id' => $staff->id,
    'appointment_id' => $upcoming->id,
    'invoice_number' => 'CRM_INV_' . time(),
    'total_amount' => 500,
    'paid_amount' => 200,
    'status' => 'partially_paid',
    'issued_date' => now()->toDateString(),
    'due_date' => now()->addDays(7)->toDateString(),
]);
PaymentRecord::create([
    'invoice_id' => $invoice->id,
    'amount' => 200,
    'payment_method' => 'cash',
    'payment_date' => now()->toDateString(),
    'transaction_id' => 'CRMPROBE_TXN1',
]);

$controller = app(ClientController::class);
$view = $controller->show($client->id);
$html = $view->render();

crmResult('crm show page renders', str_contains($html, 'CRMPROBE_Doe') && str_contains($html, 'Upcoming Appointments') && str_contains($html, 'Appointment History'));
crmResult('crm shows VIP badge', str_contains($html, 'VIP'));
crmResult('crm shows notes', str_contains($html, 'VIP repeat client'));
crmResult('crm upcoming appointment listed', str_contains($html, 'Booked'));
crmResult('crm invoice listed with balance', str_contains($html, $invoice->invoice_number) && str_contains($html, '$500.00') && str_contains($html, '$200.00'));
crmResult('crm outstanding balance shown', str_contains($html, '$300.00'));
crmResult('crm payment listed', str_contains($html, 'cash') && str_contains($html, 'CRMPROBE_TXN1'));
crmResult('crm form record listed', str_contains($html, 'CRMPROBE_Intake'));
crmResult('crm completed appointment shown', str_contains($html, 'Completed'));

$viewIndex = $controller->index(Request::create('/clients', 'GET'));
$htmlIndex = $viewIndex->render();
crmResult('crm index renders client', str_contains($htmlIndex, 'CRMPROBE_Doe'));

try {
    app(ClientController::class)->show(99999999);
    crmResult('crm missing client 404s', false, 'no exception thrown');
} catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
    crmResult('crm missing client 404s', true);
}

crmCleanup();
echo 'CRM probe complete.' . PHP_EOL;
