<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\CalendarController;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Location;
use App\Models\Service;
use App\Models\Staff;
use App\Models\StaffSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

function cleanupCalendarProbe(): void
{
    $clientIds = Client::where('email', 'like', 'caltest_%@example.com')->pluck('id');
    $staffIds = Staff::where('email', 'like', 'caltest_%@example.com')->pluck('id');
    $serviceIds = Service::where('name', 'like', 'CAL_TEST_%')->pluck('id');
    $locationIds = Location::where('name', 'like', 'CAL_TEST_%')->pluck('id');

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

function calendarReq(string $method, string $uri, array $data = []): Request
{
    return Request::create($uri, $method, $data);
}

function responseStatus($response): int
{
    return method_exists($response, 'status') ? $response->status() : 200;
}

function responseJson($response): array
{
    return json_decode($response->getContent(), true) ?: [];
}

function probeResult(string $name, bool $pass, string $detail = ''): void
{
    echo ($pass ? 'PASS' : 'FAIL') . ' | ' . $name . ($detail ? ' | ' . $detail : '') . PHP_EOL;
}

cleanupCalendarProbe();

$tz = config('app.timezone');
$monday = Carbon::parse('next monday')->startOfDay();
$tuesday = $monday->copy()->addDay();
$dateSpecific = $monday->copy()->addWeek();

$location = Location::create(['name' => 'CAL_TEST_Main', 'address' => 'Test Address', 'timezone' => $tz, 'is_active' => true]);
$inactiveLocation = Location::create(['name' => 'CAL_TEST_Inactive', 'timezone' => $tz, 'is_active' => false]);
$staff = Staff::create(['location_id' => $location->id, 'name' => 'CAL_TEST_Admin', 'email' => 'caltest_staff@example.com', 'password' => Hash::make('secret123'), 'access_level' => 'admin', 'category' => 'Tester', 'is_active' => true]);
$inactiveStaff = Staff::create(['location_id' => $location->id, 'name' => 'CAL_TEST_Inactive_Staff', 'email' => 'caltest_inactive_staff@example.com', 'password' => Hash::make('secret123'), 'access_level' => 'staff', 'category' => 'Tester', 'is_active' => false]);
$service = Service::create(['name' => 'CAL_TEST_Service', 'type' => 'in_person', 'price' => 50, 'duration_minutes' => 60, 'buffer_minutes' => 15, 'is_active' => true]);
$inactiveService = Service::create(['name' => 'CAL_TEST_Inactive_Service', 'type' => 'in_person', 'price' => 50, 'duration_minutes' => 60, 'buffer_minutes' => 0, 'is_active' => false]);
$clientA = Client::create(['name' => 'CAL_TEST_Client_A', 'email' => 'caltest_a@example.com', 'phone' => '111']);
$clientB = Client::create(['name' => 'CAL_TEST_Client_B', 'email' => 'caltest_b@example.com', 'phone' => '222']);

StaffSchedule::create(['staff_id' => $staff->id, 'day_of_week' => (string) ($monday->dayOfWeekIso - 1), 'start_time' => '10:00', 'end_time' => '18:00', 'is_working' => true, 'breaks' => [['start' => '13:00', 'end' => '14:00']]]);
StaffSchedule::create(['staff_id' => $staff->id, 'day_of_week' => (string) ($tuesday->dayOfWeekIso - 1), 'start_time' => '10:00', 'end_time' => '18:00', 'is_working' => true, 'breaks' => []]);
StaffSchedule::create(['staff_id' => $staff->id, 'day_of_week' => (string) ($dateSpecific->dayOfWeekIso - 1), 'working_date' => $dateSpecific->toDateString(), 'start_time' => '12:00', 'end_time' => '16:00', 'is_working' => true, 'breaks' => []]);

$controller = app(CalendarController::class);

$validStart = $monday->copy()->setTime(11, 0)->format('Y-m-d\TH:i:s');
$validEnd = $monday->copy()->setTime(12, 0)->format('Y-m-d\TH:i:s');
$res = $controller->storeAppointment(calendarReq('POST', '/calendar/appointments', ['staff_id' => $staff->id, 'client_id' => $clientA->id, 'service_id' => $service->id, 'location_id' => $location->id, 'start_time' => $validStart, 'end_time' => $validEnd, 'status' => 'booked', 'notes' => 'CAL_TEST valid']));
$appointmentId = responseJson($res)['appointment']['id'] ?? null;
probeResult('valid appointment create', responseStatus($res) === 201 && (bool) $appointmentId, 'status ' . responseStatus($res));
if ($appointmentId) {
    probeResult('create stores location_id', Appointment::find($appointmentId)->location_id === $location->id);
    $show = $controller->getAppointment($appointmentId);
    probeResult('appointment show returns location', responseJson($show)['locationId'] === $location->id && responseJson($show)['location'] === $location->name);
    $r = $controller->assignClient(calendarReq('POST', '/calendar/appointments/' . $appointmentId . '/assign-client', ['client_id' => $clientB->id]), $appointmentId);
    probeResult('assign client', responseStatus($r) === 200 && Appointment::find($appointmentId)->client_id === $clientB->id, 'status ' . responseStatus($r));
    $events = responseJson($controller->getEvents(calendarReq('GET', '/calendar/events', ['start' => $monday->toDateString(), 'end' => $monday->copy()->endOfDay()->toDateTimeString(), 'location_id' => $location->id])));
    probeResult('events location filter returns matching appointment', collect($events)->contains('id', $appointmentId));
    $events = responseJson($controller->getEvents(calendarReq('GET', '/calendar/events', ['start' => $monday->toDateString(), 'end' => $monday->copy()->endOfDay()->toDateTimeString(), 'location_id' => $inactiveLocation->id])));
    probeResult('events location filter excludes other locations', !collect($events)->contains('id', $appointmentId));
    Appointment::find($appointmentId)->delete();
}

$validationCases = [
    ['missing staff', ['client_id' => $clientA->id, 'service_id' => $service->id, 'start_time' => $validStart, 'end_time' => $validEnd]],
    ['invalid status', ['staff_id' => $staff->id, 'client_id' => $clientA->id, 'service_id' => $service->id, 'start_time' => $monday->copy()->setTime(12, 0)->format('Y-m-d\TH:i:s'), 'end_time' => $monday->copy()->setTime(13, 0)->format('Y-m-d\TH:i:s'), 'status' => 'bad']],
    ['end before start', ['staff_id' => $staff->id, 'client_id' => $clientA->id, 'service_id' => $service->id, 'start_time' => $monday->copy()->setTime(12, 0)->format('Y-m-d\TH:i:s'), 'end_time' => $monday->copy()->setTime(11, 0)->format('Y-m-d\TH:i:s')]],
    ['same start end', ['staff_id' => $staff->id, 'client_id' => $clientA->id, 'service_id' => $service->id, 'start_time' => $monday->copy()->setTime(12, 0)->format('Y-m-d\TH:i:s'), 'end_time' => $monday->copy()->setTime(12, 0)->format('Y-m-d\TH:i:s')]],
];

foreach ($validationCases as [$name, $payload]) {
    try {
        $r = $controller->storeAppointment(calendarReq('POST', '/calendar/appointments', $payload));
        probeResult($name, responseStatus($r) === 422, 'status ' . responseStatus($r));
    } catch (Illuminate\Validation\ValidationException $e) {
        probeResult($name, true, 'validation exception');
    }
}

$timeCases = [
    ['09:00-10:00 reject', '09:00', '10:00', 422],
    ['09:30-10:30 reject', '09:30', '10:30', 422],
    ['10:00-11:00 allow', '10:00', '11:00', 201],
    ['17:00-18:00 allow', '17:00', '18:00', 201],
    ['17:30-18:30 reject', '17:30', '18:30', 422],
    ['18:00-19:00 reject', '18:00', '19:00', 422],
    ['12:00-13:00 allow', '12:00', '13:00', 201],
    ['12:30-13:30 reject', '12:30', '13:30', 422],
    ['13:00-14:00 reject', '13:00', '14:00', 422],
    ['13:15-13:45 reject', '13:15', '13:45', 422],
    ['13:30-14:30 reject', '13:30', '14:30', 422],
    ['14:00-15:00 allow', '14:00', '15:00', 201],
];

foreach ($timeCases as [$name, $start, $end, $expected]) {
    [$sh, $sm] = explode(':', $start);
    [$eh, $em] = explode(':', $end);
    $r = $controller->storeAppointment(calendarReq('POST', '/calendar/appointments', ['staff_id' => $staff->id, 'client_id' => $clientB->id, 'service_id' => $service->id, 'start_time' => $monday->copy()->setTime((int) $sh, (int) $sm)->format('Y-m-d\TH:i:s'), 'end_time' => $monday->copy()->setTime((int) $eh, (int) $em)->format('Y-m-d\TH:i:s'), 'status' => 'booked', 'notes' => 'CAL_TEST ' . $name]));
    probeResult('working/break ' . $name, responseStatus($r) === $expected, 'status ' . responseStatus($r));
    if (responseStatus($r) === 201) {
        Appointment::find(responseJson($r)['appointment']['id'])->delete();
    }
}

$base = Appointment::create(['staff_id' => $staff->id, 'client_id' => $clientA->id, 'service_id' => $service->id, 'start_time' => $monday->copy()->setTime(15, 0), 'end_time' => $monday->copy()->setTime(16, 0), 'status' => 'booked', 'notes' => 'CAL_TEST overlap base']);
$overlaps = [
    ['14:00-15:00 reject by incoming buffer', '14:00', '15:00', 422],
    ['14:30-15:30 reject', '14:30', '15:30', 422],
    ['15:00-16:00 reject', '15:00', '16:00', 422],
    ['15:30-16:30 reject', '15:30', '16:30', 422],
    ['16:00-17:00 reject by existing buffer', '16:00', '17:00', 422],
    ['16:15-17:15 allow after buffer', '16:15', '17:15', 201],
    ['14:00-17:00 reject', '14:00', '17:00', 422],
    ['15:15-15:45 reject', '15:15', '15:45', 422],
];

foreach ($overlaps as [$name, $start, $end, $expected]) {
    [$sh, $sm] = explode(':', $start);
    [$eh, $em] = explode(':', $end);
    $r = $controller->storeAppointment(calendarReq('POST', '/calendar/appointments', ['staff_id' => $staff->id, 'client_id' => $clientB->id, 'service_id' => $service->id, 'start_time' => $monday->copy()->setTime((int) $sh, (int) $sm)->format('Y-m-d\TH:i:s'), 'end_time' => $monday->copy()->setTime((int) $eh, (int) $em)->format('Y-m-d\TH:i:s')]));
    probeResult('overlap ' . $name, responseStatus($r) === $expected, 'status ' . responseStatus($r));
    if (responseStatus($r) === 201) {
        Appointment::find(responseJson($r)['appointment']['id'])->delete();
    }
}
$base->delete();

$cancelled = Appointment::create(['staff_id' => $staff->id, 'client_id' => $clientA->id, 'service_id' => $service->id, 'start_time' => $monday->copy()->setTime(15, 0), 'end_time' => $monday->copy()->setTime(16, 0), 'status' => 'cancelled', 'notes' => 'CAL_TEST cancelled']);
$r = $controller->storeAppointment(calendarReq('POST', '/calendar/appointments', ['staff_id' => $staff->id, 'client_id' => $clientB->id, 'service_id' => $service->id, 'start_time' => $monday->copy()->setTime(15, 0)->format('Y-m-d\TH:i:s'), 'end_time' => $monday->copy()->setTime(16, 0)->format('Y-m-d\TH:i:s')]));
probeResult('cancelled appointment excluded', responseStatus($r) === 201, 'status ' . responseStatus($r));
if (responseStatus($r) === 201) {
    Appointment::find(responseJson($r)['appointment']['id'])->delete();
}
$cancelled->delete();

$r = $controller->storeAppointment(calendarReq('POST', '/calendar/appointments', ['staff_id' => $staff->id, 'client_id' => $clientB->id, 'service_id' => $service->id, 'location_id' => $location->id, 'start_time' => $tuesday->copy()->setTime(10, 0)->format('Y-m-d\TH:i:s'), 'end_time' => $tuesday->copy()->setTime(10, 15)->format('Y-m-d\TH:i:s')]));
probeResult('service duration short appointment rejected', responseStatus($r) === 422, 'status ' . responseStatus($r));

$r = $controller->storeAppointment(calendarReq('POST', '/calendar/appointments', ['staff_id' => $staff->id, 'client_id' => $clientB->id, 'service_id' => $service->id, 'location_id' => $location->id, 'start_time' => $tuesday->copy()->setTime(10, 0)->format('Y-m-d\TH:i:s'), 'end_time' => $tuesday->copy()->addDay()->setTime(11, 0)->format('Y-m-d\TH:i:s')]));
probeResult('cross-day appointment rejected', responseStatus($r) === 422, 'status ' . responseStatus($r));

$bufferBase = Appointment::create(['staff_id' => $staff->id, 'client_id' => $clientA->id, 'service_id' => $service->id, 'location_id' => $location->id, 'start_time' => $tuesday->copy()->setTime(10, 0), 'end_time' => $tuesday->copy()->setTime(11, 0), 'status' => 'booked', 'notes' => 'CAL_TEST buffer base']);
$r = $controller->storeAppointment(calendarReq('POST', '/calendar/appointments', ['staff_id' => $staff->id, 'client_id' => $clientB->id, 'service_id' => $service->id, 'location_id' => $location->id, 'start_time' => $tuesday->copy()->setTime(11, 0)->format('Y-m-d\TH:i:s'), 'end_time' => $tuesday->copy()->setTime(12, 0)->format('Y-m-d\TH:i:s')]));
probeResult('buffer blocks immediate next appointment', responseStatus($r) === 422, 'status ' . responseStatus($r));
$r = $controller->storeAppointment(calendarReq('POST', '/calendar/appointments', ['staff_id' => $staff->id, 'client_id' => $clientB->id, 'service_id' => $service->id, 'location_id' => $location->id, 'start_time' => $tuesday->copy()->setTime(11, 15)->format('Y-m-d\TH:i:s'), 'end_time' => $tuesday->copy()->setTime(12, 15)->format('Y-m-d\TH:i:s')]));
probeResult('buffer boundary appointment allowed', responseStatus($r) === 201, 'status ' . responseStatus($r));
if (responseStatus($r) === 201) {
    Appointment::find(responseJson($r)['appointment']['id'])->delete();
}
$bufferBase->delete();

$otherLocation = Location::create(['name' => 'CAL_TEST_Other', 'timezone' => $tz, 'is_active' => true]);
$r = $controller->storeAppointment(calendarReq('POST', '/calendar/appointments', ['staff_id' => $staff->id, 'client_id' => $clientB->id, 'service_id' => $service->id, 'location_id' => $otherLocation->id, 'start_time' => $tuesday->copy()->setTime(14, 0)->format('Y-m-d\TH:i:s'), 'end_time' => $tuesday->copy()->setTime(15, 0)->format('Y-m-d\TH:i:s')]));
probeResult('staff-location mismatch rejected', responseStatus($r) === 422, 'status ' . responseStatus($r));
try {
    $inactiveLocationResponse = $controller->storeAppointment(calendarReq('POST', '/calendar/appointments', ['staff_id' => $staff->id, 'client_id' => $clientB->id, 'service_id' => $service->id, 'location_id' => $inactiveLocation->id, 'start_time' => $tuesday->copy()->setTime(15, 0)->format('Y-m-d\TH:i:s'), 'end_time' => $tuesday->copy()->setTime(16, 0)->format('Y-m-d\TH:i:s')]));
    probeResult('inactive location rejected', responseStatus($inactiveLocationResponse) === 422, 'status ' . responseStatus($inactiveLocationResponse));
} catch (Illuminate\Validation\ValidationException $e) {
    probeResult('inactive location rejected', true, 'validation exception');
}

$historicalInactive = Appointment::create(['staff_id' => $staff->id, 'client_id' => $clientA->id, 'service_id' => $service->id, 'location_id' => $inactiveLocation->id, 'start_time' => $tuesday->copy()->setTime(16, 30), 'end_time' => $tuesday->copy()->setTime(17, 30), 'status' => 'booked', 'notes' => 'CAL_TEST historical inactive location']);
$showHistorical = responseJson($controller->getAppointment($historicalInactive->id));
probeResult('historical inactive location displays safely', $showHistorical['locationId'] === $inactiveLocation->id && $showHistorical['location'] === $inactiveLocation->name);
$updateHistorical = $controller->updateAppointment(calendarReq('PUT', '/calendar/appointments/' . $historicalInactive->id, ['notes' => 'CAL_TEST historical updated']), $historicalInactive->id);
probeResult('historical inactive location notes-only update safe', responseStatus($updateHistorical) === 200, 'status ' . responseStatus($updateHistorical));
$historicalInactive->delete();

$dateCases = [
    ['date specific 10-11 reject', '10:00', '11:00', 422],
    ['date specific 12-13 allow', '12:00', '13:00', 201],
    ['date specific 15-16 allow', '15:00', '16:00', 201],
    ['date specific 16-17 reject', '16:00', '17:00', 422],
];

foreach ($dateCases as [$name, $start, $end, $expected]) {
    [$sh, $sm] = explode(':', $start);
    [$eh, $em] = explode(':', $end);
    $r = $controller->storeAppointment(calendarReq('POST', '/calendar/appointments', ['staff_id' => $staff->id, 'client_id' => $clientB->id, 'service_id' => $service->id, 'start_time' => $dateSpecific->copy()->setTime((int) $sh, (int) $sm)->format('Y-m-d\TH:i:s'), 'end_time' => $dateSpecific->copy()->setTime((int) $eh, (int) $em)->format('Y-m-d\TH:i:s')]));
    probeResult($name, responseStatus($r) === $expected, 'status ' . responseStatus($r));
    if (responseStatus($r) === 201) {
        Appointment::find(responseJson($r)['appointment']['id'])->delete();
    }
}

$appt = Appointment::create(['staff_id' => $staff->id, 'client_id' => $clientA->id, 'service_id' => $service->id, 'start_time' => $monday->copy()->setTime(10, 0), 'end_time' => $monday->copy()->setTime(11, 0), 'status' => 'booked', 'notes' => 'CAL_TEST update']);
$r = $controller->updateAppointment(calendarReq('PUT', '/calendar/appointments/' . $appt->id, ['start_time' => $monday->copy()->setTime(10, 0)->format('Y-m-d\TH:i:s'), 'end_time' => $monday->copy()->setTime(11, 0)->format('Y-m-d\TH:i:s')]), $appt->id);
probeResult('self-conflict-free update same time', responseStatus($r) === 200, 'status ' . responseStatus($r));
$r = $controller->updateAppointment(calendarReq('PUT', '/calendar/appointments/' . $appt->id, ['notes' => 'CAL_TEST notes only']), $appt->id);
probeResult('notes-only update', responseStatus($r) === 200, 'status ' . responseStatus($r));
$other = Appointment::create(['staff_id' => $staff->id, 'client_id' => $clientB->id, 'service_id' => $service->id, 'start_time' => $monday->copy()->setTime(11, 0), 'end_time' => $monday->copy()->setTime(12, 0), 'status' => 'booked', 'notes' => 'CAL_TEST occupied']);
$r = $controller->updateAppointment(calendarReq('PUT', '/calendar/appointments/' . $appt->id, ['start_time' => $monday->copy()->setTime(11, 0)->format('Y-m-d\TH:i:s'), 'end_time' => $monday->copy()->setTime(12, 0)->format('Y-m-d\TH:i:s')]), $appt->id);
probeResult('invalid reschedule occupied rejected', responseStatus($r) === 422, 'status ' . responseStatus($r));
$r = $controller->updateAppointment(calendarReq('PUT', '/calendar/appointments/' . $appt->id, ['start_time' => $monday->copy()->setTime(14, 0)->format('Y-m-d\TH:i:s'), 'end_time' => $monday->copy()->setTime(15, 0)->format('Y-m-d\TH:i:s')]), $appt->id);
probeResult('valid reschedule succeeds', responseStatus($r) === 200, 'status ' . responseStatus($r));
$appt->delete();
$other->delete();

$r = $controller->getEvents(calendarReq('GET', '/calendar/events', ['start' => $monday->toDateString(), 'end' => $monday->copy()->endOfDay()->toDateTimeString()]));
$events = responseJson($r);
probeResult('events endpoint json fields', is_array($events) && (count($events) === 0 || isset($events[0]['id'], $events[0]['start'], $events[0]['end'], $events[0]['status'], $events[0]['staffId'], $events[0]['serviceId'], $events[0]['clientId'], $events[0]['color'])), 'count ' . count($events));

$r = $controller->getStaffSchedules(calendarReq('GET', '/calendar/staff-schedules', ['start' => $monday->toDateString(), 'end' => $monday->copy()->addDays(6)->toDateString()]));
$schedules = responseJson($r);
$staffRows = collect($schedules['staff'] ?? []);
probeResult('staff schedules active output', $staffRows->contains('id', $staff->id) && !$staffRows->contains('id', $inactiveStaff->id), 'staff count ' . $staffRows->count());

$r = $controller->quickCreateClient(calendarReq('POST', '/calendar/quick-client', ['name' => 'CAL_TEST_Quick', 'email' => 'caltest_quick@example.com', 'phone' => '333']));
probeResult('quick client create', responseStatus($r) === 201 && isset(responseJson($r)['client']['id']), 'status ' . responseStatus($r));
try {
    $controller->quickCreateClient(calendarReq('POST', '/calendar/quick-client', ['name' => 'CAL_TEST_Quick2', 'email' => 'caltest_quick@example.com']));
    probeResult('quick client duplicate email rejected', false);
} catch (Illuminate\Validation\ValidationException $e) {
    probeResult('quick client duplicate email rejected', true, 'validation exception');
}

try {
    $r = $controller->storeAppointment(calendarReq('POST', '/calendar/appointments', ['staff_id' => $inactiveStaff->id, 'client_id' => $clientA->id, 'service_id' => $service->id, 'start_time' => $monday->copy()->setTime(10, 0)->format('Y-m-d\TH:i:s'), 'end_time' => $monday->copy()->setTime(11, 0)->format('Y-m-d\TH:i:s')]));
    probeResult('inactive staff rejected', responseStatus($r) === 422, 'actual status ' . responseStatus($r));
    if (responseStatus($r) === 201) {
        Appointment::find(responseJson($r)['appointment']['id'])->delete();
    }
} catch (Illuminate\Validation\ValidationException $e) {
    probeResult('inactive staff rejected', true, 'validation exception');
}

try {
    $r = $controller->storeAppointment(calendarReq('POST', '/calendar/appointments', ['staff_id' => $staff->id, 'client_id' => $clientA->id, 'service_id' => $inactiveService->id, 'start_time' => $monday->copy()->setTime(16, 0)->format('Y-m-d\TH:i:s'), 'end_time' => $monday->copy()->setTime(17, 0)->format('Y-m-d\TH:i:s')]));
    probeResult('inactive service rejected', responseStatus($r) === 422, 'actual status ' . responseStatus($r));
    if (responseStatus($r) === 201) {
        Appointment::find(responseJson($r)['appointment']['id'])->delete();
    }
} catch (Illuminate\Validation\ValidationException $e) {
    probeResult('inactive service rejected', true, 'validation exception');
}

cleanupCalendarProbe();
