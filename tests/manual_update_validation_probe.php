<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\ClientController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PaymentRecordController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\StaffController;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\Package;
use App\Models\PaymentRecord;
use App\Models\Payroll;
use App\Models\Service;
use App\Models\Staff;
use App\Models\StaffSchedule;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

function uvReq(string $method, string $uri, array $data = []): Request
{
    return Request::create($uri, $method, $data);
}

function uvResult(string $name, bool $pass, string $detail = ''): void
{
    echo ($pass ? 'PASS' : 'FAIL') . ' | ' . $name . ($detail ? ' | ' . $detail : '') . PHP_EOL;
}

function uvValidationField(callable $callback, string $field): bool
{
    try {
        $callback();
    } catch (ValidationException $exception) {
        return array_key_exists($field, $exception->errors());
    }

    return false;
}

function uvCleanup(): void
{
    $clientIds = Client::where('email', 'like', 'uv_probe_%@example.com')->pluck('id');
    $staffIds = Staff::where('email', 'like', 'uv_probe_%@example.com')->pluck('id');
    $serviceIds = Service::where('name', 'like', 'UV_PROBE_%')->pluck('id');
    $packageIds = Package::where('name', 'like', 'UV_PROBE_%')->pluck('id');
    $locationIds = Location::where('name', 'like', 'UV_PROBE_%')->pluck('id');
    $invoiceIds = Invoice::where('invoice_number', 'like', 'UV-PROBE-%')
        ->orWhereIn('client_id', $clientIds)
        ->orWhereIn('staff_id', $staffIds)
        ->pluck('id');

    PaymentRecord::whereIn('invoice_id', $invoiceIds)->delete();
    Invoice::whereIn('id', $invoiceIds)->delete();
    Payroll::whereIn('staff_id', $staffIds)->delete();
    Appointment::whereIn('client_id', $clientIds)
        ->orWhereIn('staff_id', $staffIds)
        ->orWhereIn('service_id', $serviceIds)
        ->delete();
    StaffSchedule::whereIn('staff_id', $staffIds)->delete();
    Package::whereIn('id', $packageIds)->delete();
    Service::whereIn('id', $serviceIds)->delete();
    Staff::whereIn('id', $staffIds)->delete();
    Client::whereIn('id', $clientIds)->delete();
    Location::whereIn('id', $locationIds)->delete();
    Subscription::whereHas('plan', fn ($query) => $query->where('name', 'UV_PROBE_Unlimited'))->delete();
    SubscriptionPlan::where('name', 'UV_PROBE_Unlimited')->delete();
}

uvCleanup();

$plan = SubscriptionPlan::create([
    'name' => 'UV_PROBE_Unlimited',
    'price' => 0,
    'billing_cycle' => 'monthly',
    'staff_limit' => null,
    'location_limit' => null,
    'appointment_limit' => null,
    'is_active' => true,
]);
Subscription::create([
    'subscription_plan_id' => $plan->id,
    'start_date' => now()->subDay()->toDateString(),
    'end_date' => now()->addYear()->toDateString(),
    'status' => 'active',
    'payment_status' => 'paid',
]);

$clientController = app(ClientController::class);
$staffController = app(StaffController::class);
$invoiceController = app(InvoiceController::class);
$serviceController = app(ServiceController::class);
$packageController = app(PackageController::class);
$paymentController = app(PaymentRecordController::class);

$location = Location::create(['name' => 'UV_PROBE_Main', 'timezone' => config('app.timezone'), 'is_active' => true]);
$clientA = Client::create(['name' => 'UV_PROBE_Client_A', 'email' => 'uv_probe_a@example.com', 'phone' => '900001', 'city' => 'City A']);
$clientB = Client::create(['name' => 'UV_PROBE_Client_B', 'email' => 'uv_probe_b@example.com', 'phone' => '900002', 'city' => 'City B']);

$clientController->store(uvReq('POST', '/clients', [
    'name' => 'UV_PROBE_Client_C',
    'email' => 'uv_probe_c@example.com',
    'phone' => '900003',
]));
uvResult('Create client with unique email succeeds', Client::where('email', 'uv_probe_c@example.com')->exists());
uvResult('Create client with duplicate email rejected', uvValidationField(fn () => $clientController->store(uvReq('POST', '/clients', [
    'name' => 'UV_PROBE_Duplicate',
    'email' => 'uv_probe_a@example.com',
])), 'email'));

$clientController->update(uvReq('PUT', '/clients/' . $clientA->id, [
    'name' => 'UV_PROBE_Client_A_Renamed',
    'email' => 'uv_probe_a@example.com',
    'phone' => '900001',
    'city' => 'City A',
]), (string) $clientA->id);
$clientA->refresh();
uvResult('Edit client name only with same email succeeds', $clientA->name === 'UV_PROBE_Client_A_Renamed' && $clientA->email === 'uv_probe_a@example.com');

$clientController->update(uvReq('PUT', '/clients/' . $clientA->id, [
    'name' => $clientA->name,
    'email' => 'uv_probe_a@example.com',
    'phone' => '900001',
    'city' => 'City A Updated',
]), (string) $clientA->id);
$clientA->refresh();
uvResult('Edit client city only with same email succeeds', $clientA->city === 'City A Updated');

$clientController->update(uvReq('PUT', '/clients/' . $clientA->id, [
    'name' => $clientA->name,
    'email' => 'uv_probe_a_new@example.com',
    'phone' => '900001',
    'city' => $clientA->city,
]), (string) $clientA->id);
$clientA->refresh();
uvResult('Edit client email to unused email succeeds', $clientA->email === 'uv_probe_a_new@example.com');
uvResult('Edit client email to another client rejected', uvValidationField(fn () => $clientController->update(uvReq('PUT', '/clients/' . $clientA->id, [
    'name' => $clientA->name,
    'email' => 'uv_probe_b@example.com',
    'phone' => '900001',
]), (string) $clientA->id), 'email'));

$clientController->update(uvReq('PUT', '/clients/' . $clientA->id, [
    'name' => $clientA->name,
    'email' => $clientA->email,
    'phone' => '900001',
    'city' => $clientA->city,
]), (string) $clientA->id);
uvResult('Edit client unchanged phone succeeds because phone is not unique', Client::whereKey($clientA->id)->exists());
uvResult('No duplicate client row created during updates', Client::where('email', 'uv_probe_a_new@example.com')->count() === 1);

$staffA = Staff::create([
    'location_id' => $location->id,
    'name' => 'UV_PROBE_Staff_A',
    'email' => 'uv_probe_staff_a@example.com',
    'password' => Hash::make('Password123'),
    'access_level' => 'admin',
    'salary' => 100,
    'is_active' => true,
]);
$staffB = Staff::create([
    'location_id' => $location->id,
    'name' => 'UV_PROBE_Staff_B',
    'email' => 'uv_probe_staff_b@example.com',
    'password' => Hash::make('Password123'),
    'access_level' => 'admin',
    'salary' => 100,
    'is_active' => true,
]);
$oldHash = $staffA->password;
$staffController->update(uvReq('PUT', '/staff/' . $staffA->id, [
    'name' => 'UV_PROBE_Staff_A_Renamed',
    'email' => 'uv_probe_staff_a@example.com',
    'password' => '',
    'access_level' => 'admin',
    'location_id' => $location->id,
    'salary' => 100,
    'is_active' => '1',
]), (string) $staffA->id);
$staffA->refresh();
uvResult('Edit staff name only with same email succeeds', $staffA->name === 'UV_PROBE_Staff_A_Renamed');
uvResult('Blank staff password preserves existing hash', $staffA->password === $oldHash);
uvResult('Edit staff email to another staff rejected', uvValidationField(fn () => $staffController->update(uvReq('PUT', '/staff/' . $staffA->id, [
    'name' => $staffA->name,
    'email' => 'uv_probe_staff_b@example.com',
    'password' => '',
    'access_level' => 'admin',
    'location_id' => $location->id,
    'salary' => 100,
    'is_active' => '1',
]), (string) $staffA->id), 'email'));

$service = Service::create(['name' => 'UV_PROBE_Service', 'type' => 'in_person', 'price' => 50, 'duration_minutes' => 60, 'is_active' => true]);
$invoiceA = Invoice::create([
    'client_id' => $clientA->id,
    'staff_id' => $staffA->id,
    'invoice_number' => 'UV-PROBE-001',
    'total_amount' => 100,
    'paid_amount' => 0,
    'status' => 'outstanding',
    'issued_date' => now()->toDateString(),
]);
$invoiceB = Invoice::create([
    'client_id' => $clientB->id,
    'staff_id' => $staffB->id,
    'invoice_number' => 'UV-PROBE-002',
    'total_amount' => 100,
    'paid_amount' => 0,
    'status' => 'outstanding',
    'issued_date' => now()->toDateString(),
]);
$invoiceController->update(uvReq('PUT', '/invoices/' . $invoiceA->id, [
    'client_id' => $clientA->id,
    'staff_id' => $staffA->id,
    'invoice_number' => 'UV-PROBE-001',
    'total_amount' => 125,
    'paid_amount' => 0,
    'status' => 'outstanding',
    'issued_date' => now()->toDateString(),
]), (string) $invoiceA->id);
$invoiceA->refresh();
uvResult('Edit invoice with same invoice number succeeds', (float) $invoiceA->total_amount === 125.0);
uvResult('Edit invoice number to another invoice rejected', uvValidationField(fn () => $invoiceController->update(uvReq('PUT', '/invoices/' . $invoiceA->id, [
    'client_id' => $clientA->id,
    'staff_id' => $staffA->id,
    'invoice_number' => 'UV-PROBE-002',
    'total_amount' => 125,
    'paid_amount' => 0,
    'status' => 'outstanding',
    'issued_date' => now()->toDateString(),
]), (string) $invoiceA->id), 'invoice_number'));

$serviceController->store(uvReq('POST', '/services', [
    'name' => 'UV_PROBE_Duplicate_Name',
    'type' => 'in_person',
    'price' => 10,
    'duration_minutes' => 30,
    'is_active' => '1',
]));
$serviceController->store(uvReq('POST', '/services', [
    'name' => 'UV_PROBE_Duplicate_Name',
    'type' => 'online',
    'price' => 20,
    'duration_minutes' => 45,
    'is_active' => '1',
]));
uvResult('Service name is not unique by schema or validation', Service::where('name', 'UV_PROBE_Duplicate_Name')->count() === 2);

$packageController->store(uvReq('POST', '/packages', [
    'name' => 'UV_PROBE_Duplicate_Package',
    'price' => 10,
    'is_active' => '1',
]));
$packageController->store(uvReq('POST', '/packages', [
    'name' => 'UV_PROBE_Duplicate_Package',
    'price' => 20,
    'is_active' => '1',
]));
uvResult('Package name is not unique by schema or validation', Package::where('name', 'UV_PROBE_Duplicate_Package')->count() === 2);

$paymentController->store(uvReq('POST', '/payment-records', [
    'invoice_id' => $invoiceA->id,
    'amount' => 10,
    'payment_method' => 'cash',
    'payment_date' => now()->toDateString(),
    'transaction_id' => 'UV-PROBE-TXN',
]));
$paymentController->store(uvReq('POST', '/payment-records', [
    'invoice_id' => $invoiceA->id,
    'amount' => 10,
    'payment_method' => 'card',
    'payment_date' => now()->toDateString(),
    'transaction_id' => 'UV-PROBE-TXN',
]));
uvResult('Payment transaction_id is not unique by schema or validation', PaymentRecord::where('transaction_id', 'UV-PROBE-TXN')->count() === 2);

uvCleanup();
