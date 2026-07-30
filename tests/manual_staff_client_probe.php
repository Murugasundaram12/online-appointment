<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\ClientController;
use App\Http\Controllers\StaffController;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\Payroll;
use App\Models\Service;
use App\Models\Staff;
use App\Models\StaffSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

function cleanupStaffClientProbe(): void
{
    $clientIds = Client::where('email', 'like', 'sctest_%@example.com')->pluck('id');
    $staffIds = Staff::where('email', 'like', 'sctest_%@example.com')->pluck('id');
    $serviceIds = Service::where('name', 'like', 'SC_TEST_%')->pluck('id');
    $locationIds = Location::where('name', 'like', 'SC_TEST_%')->pluck('id');

    Invoice::whereIn('client_id', $clientIds)->orWhereIn('staff_id', $staffIds)->delete();
    Payroll::whereIn('staff_id', $staffIds)->delete();
    Appointment::whereIn('client_id', $clientIds)
        ->orWhereIn('staff_id', $staffIds)
        ->orWhereIn('service_id', $serviceIds)
        ->delete();
    StaffSchedule::whereIn('staff_id', $staffIds)->delete();
    Client::whereIn('id', $clientIds)->delete();
    Staff::whereIn('id', $staffIds)->delete();
    Service::whereIn('id', $serviceIds)->delete();
    Location::whereIn('id', $locationIds)->delete();
}

function scRequest(string $method, string $uri, array $data = []): Request
{
    return Request::create($uri, $method, $data);
}

function scResult(string $name, bool $pass, string $detail = ''): void
{
    echo ($pass ? 'PASS' : 'FAIL') . ' | ' . $name . ($detail ? ' | ' . $detail : '') . PHP_EOL;
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

cleanupStaffClientProbe();

$staffController = app(StaffController::class);
$clientController = app(ClientController::class);

$location = Location::create([
    'name' => 'SC_TEST_Main',
    'address' => 'Probe address',
    'timezone' => config('app.timezone'),
    'is_active' => true,
]);
$inactiveLocation = Location::create([
    'name' => 'SC_TEST_Inactive',
    'timezone' => config('app.timezone'),
    'is_active' => false,
]);
$service = Service::create([
    'name' => 'SC_TEST_Service',
    'type' => 'in_person',
    'price' => 25,
    'duration_minutes' => 30,
    'buffer_minutes' => 0,
    'is_active' => true,
]);

$staffController->store(scRequest('POST', '/staff', [
    'name' => 'SC_TEST Staff',
    'email' => 'sctest_staff@example.com',
    'password' => 'Password123',
    'access_level' => 'admin',
    'location_id' => $location->id,
    'salary' => '1500.00',
    'color' => '#4f46e5',
    'is_active' => '1',
]));
$staff = Staff::where('email', 'sctest_staff@example.com')->first();
scResult('Staff create stores valid account', $staff !== null && $staff->location_id === $location->id);
scResult('Staff password is hashed', $staff !== null && Hash::check('Password123', $staff->password));
scResult('Staff active flag stored', $staff !== null && $staff->is_active === true);

scResult('Staff duplicate email rejected', expectValidation(fn () => $staffController->store(scRequest('POST', '/staff', [
    'name' => 'SC_TEST Duplicate',
    'email' => 'sctest_staff@example.com',
    'password' => 'Password123',
])), 'email'));
scResult('Staff invalid email rejected', expectValidation(fn () => $staffController->store(scRequest('POST', '/staff', [
    'name' => 'SC_TEST Bad Email',
    'email' => 'not-an-email',
    'password' => 'Password123',
])), 'email'));
scResult('Staff negative salary rejected', expectValidation(fn () => $staffController->store(scRequest('POST', '/staff', [
    'name' => 'SC_TEST Negative Salary',
    'email' => 'sctest_negative@example.com',
    'password' => 'Password123',
    'salary' => '-1',
])), 'salary'));
scResult('Staff invalid access level rejected', expectValidation(fn () => $staffController->store(scRequest('POST', '/staff', [
    'name' => 'SC_TEST Bad Access',
    'email' => 'sctest_badaccess@example.com',
    'password' => 'Password123',
    'access_level' => 'superuser',
])), 'access_level'));
scResult('Staff inactive location rejected', expectValidation(fn () => $staffController->store(scRequest('POST', '/staff', [
    'name' => 'SC_TEST Inactive Location',
    'email' => 'sctest_inactive_location@example.com',
    'password' => 'Password123',
    'location_id' => $inactiveLocation->id,
])), 'location_id'));

$staffController->update(scRequest('PUT', '/staff/' . $staff->id, [
    'name' => 'SC_TEST Staff Updated',
    'email' => 'sctest_staff@example.com',
    'password' => 'Newpass123',
    'access_level' => 'practitioner',
    'location_id' => $location->id,
    'salary' => '1750.00',
    'is_active' => '1',
]), $staff->id);
$staff->refresh();
scResult('Staff update keeps hash behavior', $staff->name === 'SC_TEST Staff Updated' && Hash::check('Newpass123', $staff->password));

$clientController->store(scRequest('POST', '/clients', [
    'name' => 'SC_TEST Client',
    'email' => 'sctest_client@example.com',
    'phone' => '5551000',
    'city' => 'Probe City',
    'client_since' => now()->toDateString(),
    'tags' => 'VIP',
]));
$client = Client::where('email', 'sctest_client@example.com')->first();
scResult('Client create stores valid record', $client !== null && $client->city === 'Probe City');
scResult('Client VIP derived from tags', $client !== null && $client->is_vip === true);
scResult('Client duplicate email rejected', expectValidation(fn () => $clientController->store(scRequest('POST', '/clients', [
    'name' => 'SC_TEST Duplicate Client',
    'email' => 'sctest_client@example.com',
])), 'email'));
scResult('Client invalid email rejected', expectValidation(fn () => $clientController->store(scRequest('POST', '/clients', [
    'name' => 'SC_TEST Bad Client',
    'email' => 'bad-email',
])), 'email'));
scResult('Client missing name rejected', expectValidation(fn () => $clientController->store(scRequest('POST', '/clients', [
    'email' => 'sctest_missing_name@example.com',
])), 'name'));
scResult('Client invalid since date rejected', expectValidation(fn () => $clientController->store(scRequest('POST', '/clients', [
    'name' => 'SC_TEST Bad Date',
    'email' => 'sctest_baddate@example.com',
    'client_since' => 'not-a-date',
])), 'client_since'));

$clientController->update(scRequest('PUT', '/clients/' . $client->id, [
    'name' => 'SC_TEST Client Updated',
    'email' => 'sctest_client@example.com',
    'phone' => '5552000',
    'city' => 'Updated City',
    'is_vip' => '0',
]), $client->id);
$client->refresh();
scResult('Client update stores safe fields', $client->name === 'SC_TEST Client Updated' && $client->is_vip === false);

$appointment = Appointment::create([
    'client_id' => $client->id,
    'staff_id' => $staff->id,
    'service_id' => $service->id,
    'location_id' => $location->id,
    'start_time' => now()->addDay()->setTime(10, 0),
    'end_time' => now()->addDay()->setTime(10, 30),
    'status' => 'booked',
]);
$staffController->destroy($staff->id);
$clientController->destroy($client->id);
$appointmentStillExists = Appointment::whereKey($appointment->id)->exists();
scResult('Staff delete blocked when appointments exist', Staff::whereKey($staff->id)->exists() && $appointmentStillExists);
scResult('Client delete blocked when appointments exist', Client::whereKey($client->id)->exists() && $appointmentStillExists);

$unusedStaff = Staff::create([
    'name' => 'SC_TEST Unused Staff',
    'email' => 'sctest_unused_staff@example.com',
    'password' => Hash::make('Password123'),
    'is_active' => true,
]);
$unusedClient = Client::create([
    'name' => 'SC_TEST Unused Client',
    'email' => 'sctest_unused_client@example.com',
]);
$staffController->destroy($unusedStaff->id);
$clientController->destroy($unusedClient->id);
scResult('Unused staff delete succeeds', !Staff::whereKey($unusedStaff->id)->exists());
scResult('Unused client delete succeeds', !Client::whereKey($unusedClient->id)->exists());

cleanupStaffClientProbe();
