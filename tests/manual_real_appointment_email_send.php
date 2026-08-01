<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Location;
use App\Models\Service;
use App\Models\Staff;
use App\Services\AppointmentEmailService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

$suffix = now()->format('YmdHis');

$client = Client::create([
    'name' => 'REAL_EMAIL_PROBE_Client',
    'email' => 'muruga12062002@gmail.com',
    'client_since' => now()->toDateString(),
]);
$location = Location::create([
    'name' => 'REAL_EMAIL_PROBE_Location_' . $suffix,
    'timezone' => config('app.timezone'),
    'is_active' => true,
]);
$staff = Staff::create([
    'location_id' => $location->id,
    'name' => 'REAL_EMAIL_PROBE_Staff_' . $suffix,
    'email' => 'real_email_probe_' . $suffix . '@example.com',
    'password' => Hash::make('Password123'),
    'access_level' => 'admin',
    'is_active' => true,
]);
$service = Service::create([
    'name' => 'REAL_EMAIL_PROBE_Service_' . $suffix,
    'type' => 'in_person',
    'price' => 50,
    'duration_minutes' => 60,
    'buffer_minutes' => 0,
    'is_active' => true,
]);

$start = Carbon::now()->addDays(2)->setTime(10, 0);
$appointment = Appointment::create([
    'client_id' => $client->id,
    'staff_id' => $staff->id,
    'service_id' => $service->id,
    'location_id' => $location->id,
    'start_time' => $start,
    'end_time' => $start->copy()->addHour(),
    'status' => 'booked',
    'notes' => 'Real SMTP appointment email probe.',
]);

$result = app(AppointmentEmailService::class)->sendBooked($appointment);

echo json_encode([
    'appointment_id' => $appointment->id,
    'client_email' => $client->email,
    'result' => $result,
], JSON_PRETTY_PRINT) . PHP_EOL;
