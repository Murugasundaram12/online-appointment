<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\Payroll;
use App\Models\PaymentRecord;
use App\Models\Service;
use App\Models\Staff;
use App\Models\StaffSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

$prefix = 'PROD_UAT_';

$clientIds = Client::where('email', 'like', 'prod_uat_%@example.com')
    ->orWhere('email', 'muruga12062002@gmail.com')
    ->where('name', 'like', $prefix . '%')
    ->pluck('id');
$staffIds = Staff::where('email', 'like', 'prod_uat_%@example.com')->pluck('id');
$serviceIds = Service::where('name', 'like', $prefix . '%')->pluck('id');
$locationIds = Location::where('name', 'like', $prefix . '%')->pluck('id');
$appointmentIds = Appointment::whereIn('client_id', $clientIds)
    ->orWhereIn('staff_id', $staffIds)
    ->orWhereIn('service_id', $serviceIds)
    ->pluck('id');
$invoiceIds = Invoice::whereIn('client_id', $clientIds)
    ->orWhereIn('staff_id', $staffIds)
    ->orWhere('invoice_number', 'like', $prefix . '%')
    ->pluck('id');

PaymentRecord::whereIn('invoice_id', $invoiceIds)->delete();
Invoice::whereIn('id', $invoiceIds)->delete();
Payroll::whereIn('staff_id', $staffIds)->delete();
Appointment::whereIn('id', $appointmentIds)->delete();
StaffSchedule::whereIn('staff_id', $staffIds)->delete();
Staff::whereIn('id', $staffIds)->delete();
Service::whereIn('id', $serviceIds)->delete();
Location::whereIn('id', $locationIds)->delete();
Client::whereIn('id', $clientIds)->delete();

$password = 'Password123';
$location = Location::create([
    'name' => $prefix . 'Main Location',
    'address' => '100 Production UAT Road',
    'email' => 'prod_uat_location@example.com',
    'timezone' => config('app.timezone'),
    'is_active' => true,
]);
$inactiveLocation = Location::create([
    'name' => $prefix . 'Inactive Location',
    'timezone' => config('app.timezone'),
    'is_active' => false,
]);

$roles = [];
foreach (['admin', 'receptionist', 'practitioner', 'business_owner'] as $role) {
    $roles[$role] = Staff::create([
        'location_id' => $location->id,
        'name' => $prefix . ucfirst($role),
        'email' => 'prod_uat_' . $role . '@example.com',
        'password' => Hash::make($password),
        'access_level' => $role,
        'category' => ucfirst($role),
        'salary' => $role === 'business_owner' ? 5000 : 3000,
        'is_active' => true,
    ]);
}

$bookableStaff = Staff::create([
    'location_id' => $location->id,
    'name' => $prefix . 'Bookable Doctor',
    'email' => 'prod_uat_bookable@example.com',
    'password' => Hash::make($password),
    'access_level' => 'practitioner',
    'category' => 'Doctor',
    'salary' => 2500,
    'is_active' => true,
]);
$inactiveStaff = Staff::create([
    'location_id' => $location->id,
    'name' => $prefix . 'Inactive Doctor',
    'email' => 'prod_uat_inactive@example.com',
    'password' => Hash::make($password),
    'access_level' => 'practitioner',
    'is_active' => false,
]);

$service = Service::create([
    'name' => $prefix . 'Consultation',
    'type' => 'in_person',
    'price' => 150,
    'duration_minutes' => 60,
    'buffer_minutes' => 0,
    'is_active' => true,
]);
$inactiveService = Service::create([
    'name' => $prefix . 'Inactive Service',
    'type' => 'in_person',
    'price' => 150,
    'duration_minutes' => 60,
    'buffer_minutes' => 0,
    'is_active' => false,
]);

$bookingDate = Carbon::now()->addDays(7)->startOfDay();
while (!$bookingDate->isMonday()) {
    $bookingDate->addDay();
}

StaffSchedule::create([
    'staff_id' => $bookableStaff->id,
    'day_of_week' => (string) ($bookingDate->dayOfWeekIso - 1),
    'start_time' => '09:00',
    'end_time' => '17:00',
    'is_working' => true,
    'breaks' => [],
]);

$client = Client::create([
    'name' => $prefix . 'Invoice Patient',
    'email' => 'prod_uat_invoice_client@example.com',
    'phone' => '555-9000',
    'client_since' => now()->toDateString(),
]);

$appointment = Appointment::create([
    'client_id' => $client->id,
    'staff_id' => $bookableStaff->id,
    'service_id' => $service->id,
    'location_id' => $location->id,
    'start_time' => $bookingDate->copy()->setTime(14, 0),
    'end_time' => $bookingDate->copy()->setTime(15, 0),
    'status' => 'completed',
]);

$fixture = [
    'baseUrl' => config('app.url', 'http://127.0.0.1:8000'),
    'password' => $password,
    'roles' => collect($roles)->map(fn ($staff) => ['email' => $staff->email, 'id' => $staff->id])->all(),
    'location_id' => $location->id,
    'inactive_location_id' => $inactiveLocation->id,
    'staff_id' => $bookableStaff->id,
    'inactive_staff_id' => $inactiveStaff->id,
    'service_id' => $service->id,
    'inactive_service_id' => $inactiveService->id,
    'booking_date' => $bookingDate->toDateString(),
    'appointment_id' => $appointment->id,
    'client_id' => $client->id,
];

file_put_contents(__DIR__ . '/production_uat_fixture.json', json_encode($fixture, JSON_PRETTY_PRINT));

echo json_encode($fixture, JSON_PRETTY_PRINT) . PHP_EOL;
