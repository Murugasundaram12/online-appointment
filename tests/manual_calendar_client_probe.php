<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ClientController;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Location;
use App\Models\Service;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

function probeReport(string $name, bool $pass, string $detail = ''): void
{
    echo ($pass ? 'PASS' : 'FAIL') . ' | ' . $name . ($detail ? ' | ' . $detail : '') . PHP_EOL;
}

function probeCleanup(): void
{
    $clientIds = Client::where('email', 'like', 'quickprobe_%@example.com')
        ->orWhere('phone', 'like', '555-QUICK-%')
        ->pluck('id');
    $staffIds = Staff::where('email', 'like', 'quickprobe_%@example.com')->pluck('id');
    $serviceIds = Service::where('name', 'like', 'QUICKPROBE_%')->pluck('id');
    $locationIds = Location::where('name', 'like', 'QUICKPROBE_%')->pluck('id');

    Appointment::whereIn('client_id', $clientIds)->orWhereIn('staff_id', $staffIds)->delete();
    Client::whereIn('id', $clientIds)->delete();
    Staff::whereIn('id', $staffIds)->delete();
    Service::whereIn('id', $serviceIds)->delete();
    Location::whereIn('id', $locationIds)->delete();
}

probeCleanup();

try {
    $loc = Location::create(['name' => 'QUICKPROBE_Clinic', 'timezone' => 'UTC', 'is_active' => true]);
    $adminStaff = Staff::create([
        'location_id' => $loc->id,
        'name' => 'QUICKPROBE_Admin',
        'email' => 'quickprobe_admin@example.com',
        'password' => Hash::make('secret123'),
        'access_level' => 'admin',
        'is_active' => true
    ]);
    $staffA = Staff::create([
        'location_id' => $loc->id,
        'name' => 'QUICKPROBE_StaffA',
        'email' => 'quickprobe_staffa@example.com',
        'password' => Hash::make('secret123'),
        'access_level' => 'staff',
        'is_active' => true
    ]);
    $staffB = Staff::create([
        'location_id' => $loc->id,
        'name' => 'QUICKPROBE_StaffB',
        'email' => 'quickprobe_staffb@example.com',
        'password' => Hash::make('secret123'),
        'access_level' => 'staff',
        'is_active' => true
    ]);
    $service = Service::create([
        'name' => 'QUICKPROBE_Service',
        'type' => 'in_person',
        'price' => 100,
        'duration_minutes' => 60,
        'buffer_minutes' => 0,
        'is_active' => true
    ]);

    $controller = app(CalendarController::class);

    // 1. Quick create with first_name, last_name, phone, NO email
    Auth::guard('staff')->login($adminStaff);
    $req1 = Request::create('/calendar/quick-client', 'POST', [
        'first_name' => 'John',
        'last_name' => 'NoEmail',
        'phone' => '555-QUICK-01',
    ]);
    $res1 = $controller->quickCreateClient($req1);
    $data1 = json_decode($res1->getContent(), true);

    $client1 = Client::where('phone', '555-QUICK-01')->first();
    probeReport('Quick create without email succeeds', $res1->getStatusCode() === 201 && !empty($data1['client']['id']));
    probeReport('Name correctly constructed as John NoEmail', $client1 && $client1->name === 'John NoEmail');
    probeReport('first_name and last_name stored correctly', $client1 && $client1->first_name === 'John' && $client1->last_name === 'NoEmail');

    // 2. Quick create with email
    $req2 = Request::create('/calendar/quick-client', 'POST', [
        'first_name' => 'Jane',
        'last_name' => 'WithEmail',
        'email' => 'quickprobe_jane@example.com',
        'phone' => '555-QUICK-02',
    ]);
    $res2 = $controller->quickCreateClient($req2);
    $data2 = json_decode($res2->getContent(), true);

    $client2 = Client::where('email', 'quickprobe_jane@example.com')->first();
    probeReport('Quick create with email succeeds', $res2->getStatusCode() === 201 && $client2 && $client2->email === 'quickprobe_jane@example.com');

    // 3. Quick create with full address / extended details
    $req3 = Request::create('/calendar/quick-client', 'POST', [
        'first_name' => 'Alice',
        'last_name' => 'FullDetails',
        'email' => 'quickprobe_alice@example.com',
        'phone' => '555-QUICK-03',
        'gender' => 'female',
        'dob' => '1990-05-15',
        'alternate_phone' => '555-ALT-03',
        'address_line1' => '123 Main St',
        'address_line2' => 'Apt 4B',
        'city' => 'Metropolis',
        'state' => 'NY',
        'country' => 'USA',
        'postal_code' => '10001',
        'emergency_contact' => 'Bob FullDetails',
        'emergency_phone' => '555-EMERG-03',
        'notes' => 'Prefers morning appointments',
        'is_vip' => true,
    ]);
    $res3 = $controller->quickCreateClient($req3);
    $client3 = Client::where('phone', '555-QUICK-03')->first();
    probeReport('Quick create with full details persists address & notes', $client3 && $client3->city === 'Metropolis' && $client3->notes === 'Prefers morning appointments' && $client3->postal_code === '10001');

    // 4. Duplicate phone rejection
    $phoneDupFailed = false;
    try {
        $reqDupPhone = Request::create('/calendar/quick-client', 'POST', [
            'first_name' => 'Dup',
            'last_name' => 'Phone',
            'phone' => '555-QUICK-01',
        ]);
        $controller->quickCreateClient($reqDupPhone);
    } catch (ValidationException $e) {
        $phoneDupFailed = isset($e->errors()['phone']);
    }
    probeReport('Duplicate phone rejected with validation error', $phoneDupFailed);

    // 5. Duplicate email rejection
    $emailDupFailed = false;
    try {
        $reqDupEmail = Request::create('/calendar/quick-client', 'POST', [
            'first_name' => 'Dup',
            'last_name' => 'Email',
            'email' => 'quickprobe_jane@example.com',
            'phone' => '555-QUICK-99',
        ]);
        $controller->quickCreateClient($reqDupEmail);
    } catch (ValidationException $e) {
        $emailDupFailed = isset($e->errors()['email']);
    }
    probeReport('Duplicate email rejected with validation error', $emailDupFailed);

    // 6. Created client appears in CRM with first_name / last_name
    $crmController = app(ClientController::class);
    $reqCrmIndex = Request::create('/clients', 'GET');
    $crmIndexRes = $crmController->index($reqCrmIndex);
    probeReport('CRM index view renders created clients', $crmIndexRes && $client1->first_name === 'John');

    // 7. Client search by first_name / last_name
    $reqSearch = Request::create('/calendar/clients/search', 'GET', ['q' => 'NoEmail']);
    $resSearch = $controller->searchClients($reqSearch);
    $searchResults = json_decode($resSearch->getContent(), true);
    $found = false;
    foreach ($searchResults as $item) {
        if ($item['id'] === $client1->id) {
            $found = true;
            break;
        }
    }
    probeReport('Client search by last_name returns created client', $found);

    // 8. Auto-selected client payload structure
    probeReport('Quick create response returns expected client payload for JS auto-selection', isset($data1['client']['id']) && isset($data1['client']['name']) && isset($data1['client']['phone']));

    // Setup an appointment for Staff A
    $appointment = Appointment::create([
        'staff_id' => $staffA->id,
        'service_id' => $service->id,
        'client_id' => $client1->id,
        'location_id' => $loc->id,
        'start_time' => now()->addDay()->setHour(10)->setMinute(0),
        'end_time' => now()->addDay()->setHour(11)->setMinute(0),
        'status' => 'booked',
    ]);

    // 9. Unauthorized updateAppointment attempt by Staff B on Staff A's appointment
    Auth::guard('staff')->login($staffB);
    $reqUnauthUpdate = Request::create("/calendar/appointments/{$appointment->id}", 'PUT', [
        'notes' => 'Hacked notes',
    ]);
    $resUnauthUpdate = $controller->updateAppointment($reqUnauthUpdate, $appointment->id);
    probeReport('Unauthorized updateAppointment attempt rejected with 403', $resUnauthUpdate->getStatusCode() === 403);

    // 10. Unauthorized assignClient attempt by Staff B on Staff A's appointment
    $reqUnauthAssign = Request::create("/calendar/appointments/{$appointment->id}/assign-client", 'POST', [
        'client_id' => $client2->id,
    ]);
    $resUnauthAssign = $controller->assignClient($reqUnauthAssign, $appointment->id);
    probeReport('Unauthorized assignClient attempt rejected with 403', $resUnauthAssign->getStatusCode() === 403);

    // Authorized update by Staff A on Staff A's appointment
    Auth::guard('staff')->login($staffA);
    $reqAuthUpdate = Request::create("/calendar/appointments/{$appointment->id}", 'PUT', [
        'notes' => 'Authorized notes by Staff A',
    ]);
    $resAuthUpdate = $controller->updateAppointment($reqAuthUpdate, $appointment->id);
    probeReport('Authorized updateAppointment attempt by assigned staff succeeds with 200', $resAuthUpdate->getStatusCode() === 200);

} catch (\Throwable $e) {
    echo 'EXCEPTION: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL;
} finally {
    probeCleanup();
}
