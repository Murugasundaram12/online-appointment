<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ScheduleController;
use App\Http\Requests\StoreScheduleRequest;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Location;
use App\Models\Service;
use App\Models\Staff;
use App\Models\StaffSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

// Back()/with() on the redirect responses needs a session store in CLI context.
app()->instance('session.store', new \Illuminate\Session\Store('probe', new \Illuminate\Session\ArraySessionHandler(120)));

const SCHED_MSG_UNAVAILABLE = 'Staff is not available at the selected time.';
const SCHED_MSG_BOOKED = 'This time slot is already booked.';

function cleanupScheduleProbe(): void
{
    $staffIds = Staff::where('email', 'like', 'schedtest_%@example.com')->pluck('id');
    $serviceIds = Service::where('name', 'like', 'SCHED_TEST_%')->pluck('id');
    $locationIds = Location::where('name', 'like', 'SCHED_TEST_%')->pluck('id');
    $clientIds = Client::where('name', 'like', 'SCHED_TEST_%')->pluck('id');

    Appointment::whereIn('staff_id', $staffIds)
        ->orWhereIn('service_id', $serviceIds)
        ->orWhereIn('client_id', $clientIds)
        ->delete();
    StaffSchedule::whereIn('staff_id', $staffIds)->delete();
    Client::whereIn('id', $clientIds)->delete();
    Staff::whereIn('id', $staffIds)->delete();
    Service::whereIn('id', $serviceIds)->delete();
    Location::whereIn('id', $locationIds)->delete();
}

function schedReq(string $method, string $uri, array $data = []): Request
{
    return Request::create($uri, $method, $data);
}

function makeStoreRequest(array $data): StoreScheduleRequest
{
    $req = StoreScheduleRequest::create('/schedule', 'POST', $data);
    $req->setContainer(app());
    $req->setRedirector(app('redirect'));
    $req->validateResolved();
    return $req;
}

function responseStatus($response): int
{
    if (method_exists($response, 'status')) {
        return $response->status();
    }
    return method_exists($response, 'getStatusCode') ? $response->getStatusCode() : 200;
}

function responseJson($response): array
{
    return json_decode($response->getContent(), true) ?: [];
}

function probeResult(string $name, bool $pass, string $detail = ''): void
{
    echo ($pass ? 'PASS' : 'FAIL') . ' | ' . $name . ($detail ? ' | ' . $detail : '') . PHP_EOL;
}

function schedCount(int $staffId, string $from, string $to, ?string $recurrenceType = null): int
{
    $q = StaffSchedule::where('staff_id', $staffId)
        ->whereNotNull('working_date')
        ->whereBetween('working_date', [$from, $to]);
    if ($recurrenceType) {
        $q->where('recurrence_type', $recurrenceType);
    }
    return $q->count();
}

function book(CalendarController $controller, Staff $staff, Client $client, Service $service, Carbon $start, Carbon $end, string $label)
{
    return $controller->storeAppointment(schedReq('POST', '/calendar/appointments', [
        'staff_id' => $staff->id,
        'client_id' => $client->id,
        'service_id' => $service->id,
        'start_time' => $start->format('Y-m-d\TH:i:s'),
        'end_time' => $end->format('Y-m-d\TH:i:s'),
        'status' => 'booked',
        'notes' => 'SCHED_TEST ' . $label,
    ]));
}

function deleteBookedAppointment($response): void
{
    $id = responseJson($response)['appointment']['id'] ?? null;
    if ($id) {
        Appointment::find($id)?->delete();
    }
}

function assertCleanMessage($response, string $expectedMessage, string $name): void
{
    $data = responseJson($response);
    probeResult($name, ($data['message'] ?? '') === $expectedMessage, 'message: ' . ($data['message'] ?? 'none'));
}

cleanupScheduleProbe();

$tz = config('app.timezone');
$location = Location::create(['name' => 'SCHED_TEST_Main', 'address' => 'Test Address', 'timezone' => $tz, 'is_active' => true]);
$service = Service::create(['name' => 'SCHED_TEST_Service', 'type' => 'in_person', 'price' => 50, 'duration_minutes' => 60, 'buffer_minutes' => 0, 'is_active' => true]);
$staffA = Staff::create(['location_id' => $location->id, 'name' => 'SCHED_TEST_John', 'email' => 'schedtest_john@example.com', 'password' => Hash::make('secret123'), 'access_level' => 'staff', 'category' => 'Doctor', 'is_active' => true]);
$staffB = Staff::create(['location_id' => $location->id, 'name' => 'SCHED_TEST_Staff_B', 'email' => 'schedtest_b@example.com', 'password' => Hash::make('secret123'), 'access_level' => 'staff', 'category' => 'Nurse', 'is_active' => true]);
$staffC = Staff::create(['location_id' => $location->id, 'name' => 'SCHED_TEST_Staff_C', 'email' => 'schedtest_c@example.com', 'password' => Hash::make('secret123'), 'access_level' => 'staff', 'category' => 'Nurse', 'is_active' => true]);
$client = Client::create(['name' => 'SCHED_TEST_Client', 'phone' => '9999999999']);

$controller = app(CalendarController::class);
$scheduleController = app(ScheduleController::class);

// ---------------------------------------------------------------------------
// Test 1: One-time schedule + booking within/outside + single clean message
// ---------------------------------------------------------------------------
$oneTimeDate = Carbon::parse('2026-12-19'); // Saturday
$r = $scheduleController->store(makeStoreRequest([
    'staff_id' => $staffC->id,
    'recurrence_type' => 'one_time',
    'working_date' => $oneTimeDate->toDateString(),
    'start_time' => '09:00',
    'end_time' => '17:00',
]));
probeResult('one-time schedule created', responseStatus($r) === 302 && schedCount($staffC->id, '2026-12-19', '2026-12-19') === 1, 'status ' . responseStatus($r));

$r = book($controller, $staffC, $client, $service, $oneTimeDate->copy()->setTime(10, 0), $oneTimeDate->copy()->setTime(11, 0), 'one-time within');
probeResult('one-time booking within allowed', responseStatus($r) === 201, 'status ' . responseStatus($r));
deleteBookedAppointment($r);

$r = book($controller, $staffC, $client, $service, $oneTimeDate->copy()->setTime(18, 0), $oneTimeDate->copy()->setTime(19, 0), 'one-time outside');
probeResult('one-time booking outside rejected', responseStatus($r) === 422, 'status ' . responseStatus($r));
assertCleanMessage($r, SCHED_MSG_UNAVAILABLE, 'one-time outside single clean message');

// ---------------------------------------------------------------------------
// Test 2: Weekly recurring (Dr. John: every Sunday 2026-08-01 -> 2026-12-31)
// ---------------------------------------------------------------------------
$expectedSundays = 0;
$cursor = Carbon::parse('2026-08-01')->startOfDay();
$rangeEnd = Carbon::parse('2026-12-31')->endOfDay();
while ($cursor->lte($rangeEnd)) {
    if ($cursor->dayOfWeek === 0) {
        $expectedSundays++;
    }
    $cursor->addDay();
}

$r = $scheduleController->store(makeStoreRequest([
    'staff_id' => $staffA->id,
    'recurrence_type' => 'weekly',
    'start_time' => '09:00',
    'end_time' => '17:00',
    'start_date' => '2026-08-01',
    'end_date' => '2026-12-31',
    'weekly_days' => [0], // Sunday
]));
probeResult('weekly sunday schedule created for every sunday', responseStatus($r) === 302 && schedCount($staffA->id, '2026-08-01', '2026-12-31') === $expectedSundays, 'expected ' . $expectedSundays . ' got ' . schedCount($staffA->id, '2026-08-01', '2026-12-31'));

$drSunday = Carbon::parse('2026-12-27'); // Sunday in range, in the future
$r = book($controller, $staffA, $client, $service, $drSunday->copy()->setTime(10, 0), $drSunday->copy()->setTime(11, 0), 'weekly sunday within');
probeResult('weekly sunday booking 10-11 allowed', responseStatus($r) === 201, 'status ' . responseStatus($r));
deleteBookedAppointment($r);

$r = book($controller, $staffA, $client, $service, $drSunday->copy()->setTime(18, 0), $drSunday->copy()->setTime(19, 0), 'weekly sunday 18:00');
probeResult('weekly sunday booking 18:00 rejected', responseStatus($r) === 422, 'status ' . responseStatus($r));
assertCleanMessage($r, SCHED_MSG_UNAVAILABLE, 'weekly sunday 18:00 clean message');

$r = book($controller, $staffA, $client, $service, Carbon::parse('2026-12-28')->setTime(10, 0), Carbon::parse('2026-12-28')->setTime(11, 0), 'non-sunday');
probeResult('weekly schedule non-sunday rejected', responseStatus($r) === 422, 'status ' . responseStatus($r));
assertCleanMessage($r, SCHED_MSG_UNAVAILABLE, 'non-sunday clean message');

// ---------------------------------------------------------------------------
// Test 3: Monthly recurrence (every 15th)
// ---------------------------------------------------------------------------
$r = $scheduleController->store(makeStoreRequest([
    'staff_id' => $staffC->id,
    'recurrence_type' => 'monthly',
    'start_time' => '09:00',
    'end_time' => '17:00',
    'start_date' => '2026-12-01',
    'end_date' => '2027-03-31',
    'monthly_day' => 15,
]));
probeResult('monthly 15th schedule created', responseStatus($r) === 302 && schedCount($staffC->id, '2026-12-01', '2027-03-31', 'monthly') === 4, 'got ' . schedCount($staffC->id, '2026-12-01', '2027-03-31', 'monthly'));

$r = book($controller, $staffC, $client, $service, Carbon::parse('2027-01-15')->setTime(10, 0), Carbon::parse('2027-01-15')->setTime(11, 0), 'monthly 15th within');
probeResult('monthly booking on 15th allowed', responseStatus($r) === 201, 'status ' . responseStatus($r));
deleteBookedAppointment($r);

$r = book($controller, $staffC, $client, $service, Carbon::parse('2027-01-16')->setTime(10, 0), Carbon::parse('2027-01-16')->setTime(11, 0), 'monthly off-day');
probeResult('monthly booking off-day rejected', responseStatus($r) === 422, 'status ' . responseStatus($r));

// ---------------------------------------------------------------------------
// Test 4: Yearly recurrence (every Jan 10)
// ---------------------------------------------------------------------------
$r = $scheduleController->store(makeStoreRequest([
    'staff_id' => $staffC->id,
    'recurrence_type' => 'yearly',
    'start_time' => '09:00',
    'end_time' => '17:00',
    'start_date' => '2026-12-01',
    'end_date' => '2028-12-31',
    'yearly_month' => 1,
    'yearly_day' => 10,
]));
probeResult('yearly Jan 10 schedule created', responseStatus($r) === 302 && schedCount($staffC->id, '2026-12-01', '2028-12-31', 'yearly') === 2, 'got ' . schedCount($staffC->id, '2026-12-01', '2028-12-31', 'yearly'));

$r = book($controller, $staffC, $client, $service, Carbon::parse('2028-01-10')->setTime(10, 0), Carbon::parse('2028-01-10')->setTime(11, 0), 'yearly within');
probeResult('yearly booking on Jan 10 allowed', responseStatus($r) === 201, 'status ' . responseStatus($r));
deleteBookedAppointment($r);

$r = book($controller, $staffC, $client, $service, Carbon::parse('2028-01-11')->setTime(10, 0), Carbon::parse('2028-01-11')->setTime(11, 0), 'yearly off-day');
probeResult('yearly booking off-day rejected', responseStatus($r) === 422, 'status ' . responseStatus($r));

// ---------------------------------------------------------------------------
// Tests 5-8, 11: Adjacent schedules, boundary rules, overlap rejection
// (staffA already has the Dr. John Sunday weekly; 2026-12-19 is a Saturday)
// ---------------------------------------------------------------------------
$r = $scheduleController->store(makeStoreRequest([
    'staff_id' => $staffA->id,
    'recurrence_type' => 'one_time',
    'working_date' => '2026-12-19',
    'start_time' => '09:00',
    'end_time' => '13:00',
]));
probeResult('one-time morning segment created', responseStatus($r) === 302 && schedCount($staffA->id, '2026-12-19', '2026-12-19') === 1, 'got ' . schedCount($staffA->id, '2026-12-19', '2026-12-19'));

$r = $scheduleController->store(makeStoreRequest([
    'staff_id' => $staffA->id,
    'recurrence_type' => 'one_time',
    'working_date' => '2026-12-19',
    'start_time' => '13:00',
    'end_time' => '17:00',
]));
probeResult('adjacent schedule allowed (no overlap)', responseStatus($r) === 302 && schedCount($staffA->id, '2026-12-19', '2026-12-19') === 2, 'got ' . schedCount($staffA->id, '2026-12-19', '2026-12-19'));

$r = $controller->getStaffSchedules(schedReq('GET', '/calendar/staff-schedules', ['start' => '2026-12-19', 'end' => '2026-12-19']));
$schedJson = responseJson($r);
$staffARow = collect($schedJson['staff'] ?? [])->firstWhere('id', $staffA->id);
$segments = is_array($staffARow['schedules_by_date']['2026-12-19'] ?? null) ? $staffARow['schedules_by_date']['2026-12-19'] : [];
probeResult('calendar returns both adjacent segments', is_array($segments) && count($segments) === 2 && $segments[0]['start_time'] === '09:00:00' && $segments[1]['start_time'] === '13:00:00', 'segments ' . count($segments));

$dec19 = Carbon::parse('2026-12-19');
$r = book($controller, $staffA, $client, $service, $dec19->copy()->setTime(9, 0), $dec19->copy()->setTime(10, 0), 'boundary start');
probeResult('booking starting exactly at schedule start allowed', responseStatus($r) === 201, 'status ' . responseStatus($r));
deleteBookedAppointment($r);

$r = book($controller, $staffA, $client, $service, $dec19->copy()->setTime(16, 0), $dec19->copy()->setTime(17, 0), 'boundary end');
probeResult('booking ending exactly at schedule end allowed', responseStatus($r) === 201, 'status ' . responseStatus($r));
deleteBookedAppointment($r);

$r = book($controller, $staffA, $client, $service, $dec19->copy()->setTime(12, 0), $dec19->copy()->setTime(13, 0), 'boundary between segments');
probeResult('booking ending at boundary between adjacent schedules allowed', responseStatus($r) === 201, 'status ' . responseStatus($r));
deleteBookedAppointment($r);

$r = book($controller, $staffA, $client, $service, $dec19->copy()->setTime(13, 0), $dec19->copy()->setTime(14, 0), 'boundary next segment');
probeResult('booking starting at boundary between adjacent schedules allowed', responseStatus($r) === 201, 'status ' . responseStatus($r));
deleteBookedAppointment($r);

$r = book($controller, $staffA, $client, $service, $dec19->copy()->setTime(8, 30), $dec19->copy()->setTime(9, 30), 'before start');
probeResult('booking starting before schedule start rejected', responseStatus($r) === 422, 'status ' . responseStatus($r));
assertCleanMessage($r, SCHED_MSG_UNAVAILABLE, 'before start clean message');

$r = book($controller, $staffA, $client, $service, $dec19->copy()->setTime(16, 30), $dec19->copy()->setTime(17, 30), 'after end');
probeResult('booking ending after schedule end rejected', responseStatus($r) === 422, 'status ' . responseStatus($r));
assertCleanMessage($r, SCHED_MSG_UNAVAILABLE, 'after end clean message');

$before = schedCount($staffA->id, '2026-12-19', '2026-12-19');
$r = $scheduleController->store(makeStoreRequest([
    'staff_id' => $staffA->id,
    'recurrence_type' => 'one_time',
    'working_date' => '2026-12-19',
    'start_time' => '12:00',
    'end_time' => '16:00',
]));
probeResult('overlapping schedule rejected for same staff', responseStatus($r) === 302 && schedCount($staffA->id, '2026-12-19', '2026-12-19') === $before, 'status ' . responseStatus($r) . ' count ' . schedCount($staffA->id, '2026-12-19', '2026-12-19'));

// ---------------------------------------------------------------------------
// Test 9: Recurring vs one-time overlap rejected
// ---------------------------------------------------------------------------
$r = $scheduleController->store(makeStoreRequest([
    'staff_id' => $staffA->id,
    'recurrence_type' => 'one_time',
    'working_date' => '2026-12-14', // Monday
    'start_time' => '10:00',
    'end_time' => '11:00',
]));
probeResult('one-time monday before recurring created', responseStatus($r) === 302 && schedCount($staffA->id, '2026-12-14', '2026-12-14') === 1, 'status ' . responseStatus($r));

$before = schedCount($staffA->id, '2026-12-07', '2026-12-28');
$r = $scheduleController->store(makeStoreRequest([
    'staff_id' => $staffA->id,
    'recurrence_type' => 'weekly',
    'start_time' => '09:00',
    'end_time' => '17:00',
    'start_date' => '2026-12-07',
    'end_date' => '2026-12-28',
    'weekly_days' => [1], // Monday
]));
probeResult('recurring vs one-time overlap rejected', responseStatus($r) === 302 && schedCount($staffA->id, '2026-12-07', '2026-12-28') === $before, 'count ' . schedCount($staffA->id, '2026-12-07', '2026-12-28'));

// ---------------------------------------------------------------------------
// Test 10: Recurring vs recurring overlap rejected
// ---------------------------------------------------------------------------
$r = $scheduleController->store(makeStoreRequest([
    'staff_id' => $staffB->id,
    'recurrence_type' => 'weekly',
    'start_time' => '09:00',
    'end_time' => '17:00',
    'start_date' => '2026-12-07',
    'end_date' => '2026-12-28',
    'weekly_days' => [1], // Monday
]));
probeResult('first recurring monday schedule created', responseStatus($r) === 302 && schedCount($staffB->id, '2026-12-07', '2026-12-28') === 4, 'got ' . schedCount($staffB->id, '2026-12-07', '2026-12-28'));

$before = schedCount($staffB->id, '2026-12-07', '2026-12-28');
$r = $scheduleController->store(makeStoreRequest([
    'staff_id' => $staffB->id,
    'recurrence_type' => 'weekly',
    'start_time' => '10:00',
    'end_time' => '12:00',
    'start_date' => '2026-12-07',
    'end_date' => '2026-12-28',
    'weekly_days' => [1],
]));
probeResult('recurring vs recurring overlap rejected', responseStatus($r) === 302 && schedCount($staffB->id, '2026-12-07', '2026-12-28') === $before, 'count ' . schedCount($staffB->id, '2026-12-07', '2026-12-28'));

// ---------------------------------------------------------------------------
// Test 12: Multiple staff in day-view payload with working segments
// ---------------------------------------------------------------------------
$r = $controller->getStaffSchedules(schedReq('GET', '/calendar/staff-schedules', ['start' => '2026-12-21', 'end' => '2026-12-27']));
$weekJson = responseJson($r);
$weekStaff = collect($weekJson['staff'] ?? []);
$rowA = $weekStaff->firstWhere('id', $staffA->id);
$rowB = $weekStaff->firstWhere('id', $staffB->id);
$segA = $rowA['schedules_by_date']['2026-12-27'] ?? [];
$segB = $rowB['schedules_by_date']['2026-12-21'] ?? [];
probeResult('day view payload lists both working staff', $weekStaff->contains('id', $staffA->id) && $weekStaff->contains('id', $staffB->id), 'staff count ' . $weekStaff->count());
probeResult('staff A has working sunday segment', is_array($segA) && count($segA) === 1 && $segA[0]['is_working'] === true, 'segments ' . (is_array($segA) ? count($segA) : 'n/a'));
probeResult('staff B has working monday segment', is_array($segB) && count($segB) === 1 && $segB[0]['is_working'] === true, 'segments ' . (is_array($segB) ? count($segB) : 'n/a'));

// ---------------------------------------------------------------------------
// Booking conflict -> single clean "already booked" message
// ---------------------------------------------------------------------------
$r = book($controller, $staffA, $client, $service, $drSunday->copy()->setTime(10, 0), $drSunday->copy()->setTime(11, 0), 'conflict base');
$conflictBase = responseJson($r)['appointment']['id'] ?? null;
$r2 = book($controller, $staffA, $client, $service, $drSunday->copy()->setTime(10, 30), $drSunday->copy()->setTime(11, 30), 'conflict second');
probeResult('overlapping appointment rejected', responseStatus($r2) === 422, 'status ' . responseStatus($r2));
assertCleanMessage($r2, SCHED_MSG_BOOKED, 'overlapping appointment clean message');
if ($conflictBase) {
    Appointment::find($conflictBase)?->delete();
}

// ---------------------------------------------------------------------------
// Hard cap: never flood the DB with thousands of rows
// ---------------------------------------------------------------------------
$before = StaffSchedule::where('staff_id', $staffC->id)->count();
$r = $scheduleController->store(makeStoreRequest([
    'staff_id' => $staffC->id,
    'recurrence_type' => 'daily',
    'start_time' => '09:00',
    'end_time' => '17:00',
    'start_date' => '2026-01-01',
    'end_date' => '2028-12-31',
]));
probeResult('oversized range rejected (no flood)', responseStatus($r) === 302 && StaffSchedule::where('staff_id', $staffC->id)->count() === $before, 'count ' . StaffSchedule::where('staff_id', $staffC->id)->count());

// ---------------------------------------------------------------------------
// Recurring schedule must not silently overlap a day_of_week template
// ---------------------------------------------------------------------------
StaffSchedule::create(['staff_id' => $staffC->id, 'day_of_week' => '0', 'start_time' => '09:00', 'end_time' => '17:00', 'is_working' => true, 'breaks' => []]);
$before = StaffSchedule::where('staff_id', $staffC->id)->count();
$r = $scheduleController->store(makeStoreRequest([
    'staff_id' => $staffC->id,
    'recurrence_type' => 'weekly',
    'start_time' => '10:00',
    'end_time' => '12:00',
    'start_date' => '2026-12-07',
    'end_date' => '2026-12-28',
    'weekly_days' => [1],
]));
probeResult('recurring vs day-of-week template overlap rejected', responseStatus($r) === 302 && StaffSchedule::where('staff_id', $staffC->id)->count() === $before, 'count ' . StaffSchedule::where('staff_id', $staffC->id)->count());

// ---------------------------------------------------------------------------
// storeApi overlap still rejected (schedule grid quick-create)
// ---------------------------------------------------------------------------
$r = $scheduleController->storeApi(schedReq('POST', '/schedule-api/create', ['staff_id' => $staffC->id, 'working_date' => '2026-12-19', 'start_time' => '12:00', 'end_time' => '16:00']));
probeResult('storeApi overlap rejected', responseStatus($r) === 422 && responseJson($r)['message'] === 'Schedule overlaps with an existing schedule for this date.', 'status ' . responseStatus($r));

// ---------------------------------------------------------------------------
// Test 15: Different staff at the same time allowed (schedule + booking)
// ---------------------------------------------------------------------------
$r = $scheduleController->store(makeStoreRequest([
    'staff_id' => $staffC->id,
    'recurrence_type' => 'one_time',
    'working_date' => '2026-12-06', // Sunday
    'start_time' => '09:00',
    'end_time' => '13:00',
]));
probeResult('different staff same-day schedule allowed', responseStatus($r) === 302 && schedCount($staffC->id, '2026-12-06', '2026-12-06') === 1, 'status ' . responseStatus($r));

$dec06 = Carbon::parse('2026-12-06');
$r = book($controller, $staffA, $client, $service, $dec06->copy()->setTime(10, 0), $dec06->copy()->setTime(11, 0), 'staffA same slot');
$r2 = book($controller, $staffC, $client, $service, $dec06->copy()->setTime(10, 0), $dec06->copy()->setTime(11, 0), 'staffC same slot');
probeResult('two staff booked at same time both allowed', responseStatus($r) === 201 && responseStatus($r2) === 201, 'A=' . responseStatus($r) . ' C=' . responseStatus($r2));
deleteBookedAppointment($r);
deleteBookedAppointment($r2);

// ---------------------------------------------------------------------------
// Test 16: Edit (preserve recurrence + group id, no duplicates)
// ---------------------------------------------------------------------------
$weeklyRows = StaffSchedule::where('staff_id', $staffA->id)
    ->where('recurrence_type', 'weekly')
    ->whereBetween('working_date', ['2026-12-07', '2026-12-28'])
    ->orderBy('working_date')->get();
$editTarget = $weeklyRows->first();
$countBefore = $weeklyRows->count();
$groupIdBefore = $editTarget->recurrence_group_id;

$r = $scheduleController->store(makeStoreRequest([
    'schedule_id' => $editTarget->id,
    'staff_id' => $staffA->id,
    'recurrence_type' => 'weekly',
    'start_time' => '10:00',
    'end_time' => '16:00',
    'start_date' => '2026-12-07',
    'end_date' => '2026-12-28',
    'weekly_days' => [0],
]));

$afterEdit = StaffSchedule::where('staff_id', $staffA->id)
    ->where('recurrence_type', 'weekly')
    ->whereBetween('working_date', ['2026-12-07', '2026-12-28'])
    ->get();
probeResult('edit keeps same row count (no duplicates)', responseStatus($r) === 302 && $afterEdit->count() === $countBefore, 'before ' . $countBefore . ' after ' . $afterEdit->count());
probeResult('edit preserves recurrence group id', $afterEdit->pluck('recurrence_group_id')->unique()->count() === 1 && $afterEdit->first()->recurrence_group_id === $groupIdBefore, 'group ' . ($afterEdit->first()->recurrence_group_id ?? 'null'));
probeResult('edit applies new times to all rows', $afterEdit->every(fn ($x) => $x->start_time === '10:00:00' && $x->end_time === '16:00:00'), implode(',', $afterEdit->pluck('start_time')->all()));

// ---------------------------------------------------------------------------
// Test 17: Edit must not self-overlap, but must reject real overlaps
// ---------------------------------------------------------------------------
$r = $scheduleController->store(makeStoreRequest([
    'schedule_id' => $afterEdit->first()->id,
    'staff_id' => $staffA->id,
    'recurrence_type' => 'weekly',
    'start_time' => '10:30',
    'end_time' => '15:00',
    'start_date' => '2026-12-07',
    'end_date' => '2026-12-28',
    'weekly_days' => [0],
]));
$afterResize = StaffSchedule::where('staff_id', $staffA->id)
    ->where('recurrence_type', 'weekly')
    ->whereBetween('working_date', ['2026-12-07', '2026-12-28'])
    ->count();
probeResult('edit resizing own group allowed (no false self-overlap)', responseStatus($r) === 302 && $afterResize === $countBefore, 'count ' . $afterResize);

// The resize edit recreated the group, so the previous $afterEdit ids are stale.
$currentRows = StaffSchedule::where('staff_id', $staffA->id)
    ->where('recurrence_type', 'weekly')
    ->whereBetween('working_date', ['2026-12-07', '2026-12-28'])
    ->get();

// staffA already has a one-time Monday (10:00-11:00) on 2026-12-14 (test 9).
// Editing the recurring group to also cover Mondays in that window must be rejected.
$countBeforeOverlapEdit = StaffSchedule::where('staff_id', $staffA->id)
    ->whereBetween('working_date', ['2026-12-07', '2026-12-28'])->count();
$r = $scheduleController->store(makeStoreRequest([
    'schedule_id' => $currentRows->first()->id,
    'staff_id' => $staffA->id,
    'recurrence_type' => 'weekly',
    'start_time' => '09:00',
    'end_time' => '17:00',
    'start_date' => '2026-12-07',
    'end_date' => '2026-12-28',
    'weekly_days' => [0, 1], // adds Mondays -> collides with 12-14 one-time
]));
$countAfterOverlapEdit = StaffSchedule::where('staff_id', $staffA->id)
    ->whereBetween('working_date', ['2026-12-07', '2026-12-28'])->count();
probeResult('edit overlapping another schedule rejected', responseStatus($r) === 302 && $countAfterOverlapEdit === $countBeforeOverlapEdit, 'before ' . $countBeforeOverlapEdit . ' after ' . $countAfterOverlapEdit);

// ---------------------------------------------------------------------------
// Test 18: Staff change during edit (no orphans, move group)
// ---------------------------------------------------------------------------
$moveRow = StaffSchedule::where('staff_id', $staffA->id)
    ->where('recurrence_type', 'weekly')
    ->whereBetween('working_date', ['2026-12-07', '2026-12-28'])->first();
$oldGroup = $moveRow->recurrence_group_id;
$r = $scheduleController->store(makeStoreRequest([
    'schedule_id' => $moveRow->id,
    'staff_id' => $staffC->id,
    'recurrence_type' => 'weekly',
    'start_time' => '10:30',
    'end_time' => '15:00',
    'start_date' => '2026-12-07',
    'end_date' => '2026-12-28',
    'weekly_days' => [0],
]));
$orphansA = StaffSchedule::where('staff_id', $staffA->id)->where('recurrence_group_id', $oldGroup)->count();
$newRowsC = StaffSchedule::where('staff_id', $staffC->id)
    ->where('recurrence_group_id', $oldGroup)
    ->whereBetween('working_date', ['2026-12-07', '2026-12-28'])->get();
probeResult('staff change removes old group rows (no orphans)', $orphansA === 0, 'orphans ' . $orphansA);
probeResult('staff change creates rows for new staff', responseStatus($r) === 302 && $newRowsC->count() === $countBefore, 'new staff rows ' . $newRowsC->count());

// ---------------------------------------------------------------------------
// Test 21: Calendar payload reflects a group edit (availability controls booking)
// ---------------------------------------------------------------------------
$r = $controller->getStaffSchedules(schedReq('GET', '/calendar/staff-schedules', ['start' => '2026-12-13', 'end' => '2026-12-13']));
$weekJson = responseJson($r);
$staffC13 = collect($weekJson['staff'] ?? [])->firstWhere('id', $staffC->id);
$seg13 = isset($staffC13['schedules_by_date']['2026-12-13']) ? $staffC13['schedules_by_date']['2026-12-13'] : [];
probeResult('calendar shows moved schedule window (10:30-15:00)', is_array($seg13) && count($seg13) === 1 && $seg13[0]['start_time'] === '10:30:00' && $seg13[0]['end_time'] === '15:00:00', 'segments ' . json_encode($seg13));

$r = book($controller, $staffC, $client, $service, Carbon::parse('2026-12-13')->setTime(9, 30), Carbon::parse('2026-12-13')->setTime(10, 30), 'outside 10:30-15 window');
probeResult('booking outside moved window rejected', responseStatus($r) === 422 && (responseJson($r)['message'] ?? '') === SCHED_MSG_UNAVAILABLE, 'status ' . responseStatus($r));

// ---------------------------------------------------------------------------
// Test 19: Delete removes the whole group, not another staff's schedule
// ---------------------------------------------------------------------------
$staffBOwnScheduleCount = StaffSchedule::where('staff_id', $staffB->id)->count();
$deleteTarget = $newRowsC->first();
$r = $scheduleController->destroy((string) $deleteTarget->id);
$groupAfterDelete = StaffSchedule::where('recurrence_group_id', $oldGroup)->count();
$staffBAfterDelete = StaffSchedule::where('staff_id', $staffB->id)->count();
probeResult('delete removes whole recurring group', $groupAfterDelete === 0, 'left ' . $groupAfterDelete);
probeResult('delete never touches another staff schedule', $staffBAfterDelete === $staffBOwnScheduleCount, 'staffB ' . $staffBAfterDelete . '/' . $staffBOwnScheduleCount);

// ---------------------------------------------------------------------------
// Test 20: Break validation + availability during break
// ---------------------------------------------------------------------------
$r = $scheduleController->store(makeStoreRequest([
    'staff_id' => $staffC->id,
    'recurrence_type' => 'one_time',
    'working_date' => '2026-12-22', // Tuesday (avoid Monday day_of_week template)
    'start_time' => '09:00',
    'end_time' => '17:00',
    'break_start' => '12:00',
    'break_end' => '13:00',
]));
$breakRow = StaffSchedule::where('staff_id', $staffC->id)
    ->where('working_date', '2026-12-22')->where('recurrence_type', 'one_time')->first();
$breakStored = $breakRow && is_array($breakRow->breaks) && count($breakRow->breaks) === 1
    && $breakRow->breaks[0]['start'] === '12:00' && $breakRow->breaks[0]['end'] === '13:00';
probeResult('break start/end stored as breaks json', responseStatus($r) === 302 && $breakStored, 'breaks ' . json_encode($breakRow->breaks ?? null));

$dec22 = Carbon::parse('2026-12-22');
$r = book($controller, $staffC, $client, $service, $dec22->copy()->setTime(11, 0), $dec22->copy()->setTime(12, 0), 'before break');
probeResult('booking before break allowed', responseStatus($r) === 201, 'status ' . responseStatus($r));
deleteBookedAppointment($r);
$r = book($controller, $staffC, $client, $service, $dec22->copy()->setTime(12, 30), $dec22->copy()->setTime(13, 30), 'during break');
probeResult('booking during break rejected', responseStatus($r) === 422, 'status ' . responseStatus($r));
assertCleanMessage($r, SCHED_MSG_UNAVAILABLE, 'during break clean message');

$r = $scheduleController->store(makeStoreRequest([
    'staff_id' => $staffC->id,
    'recurrence_type' => 'one_time',
    'working_date' => '2026-12-23', // Wednesday
    'start_time' => '09:00',
    'end_time' => '17:00',
    'break_start' => '08:00', // outside working hours
    'break_end' => '09:00',
]));
$outsideBreakRow = StaffSchedule::where('staff_id', $staffC->id)->where('working_date', '2026-12-23')->first();
probeResult('break outside working hours rejected', responseStatus($r) === 302 && !$outsideBreakRow, 'row created: ' . ($outsideBreakRow ? 'yes' : 'no'));

// ---------------------------------------------------------------------------
// Test 22: Edit single occurrence vs edit entire recurring group
// ---------------------------------------------------------------------------
StaffSchedule::where('staff_id', $staffC->id)->delete();
$r = $scheduleController->store(makeStoreRequest([
    'staff_id' => $staffC->id,
    'recurrence_type' => 'weekly',
    'start_time' => '09:00',
    'end_time' => '17:00',
    'start_date' => '2027-01-03',
    'end_date' => '2027-01-31',
    'weekly_days' => [0], // Sundays: Jan 3, 10, 17, 24, 31
]));
$janRows = StaffSchedule::where('staff_id', $staffC->id)
    ->where('recurrence_type', 'weekly')
    ->whereBetween('working_date', ['2027-01-03', '2027-01-31'])
    ->orderBy('working_date')->get();
probeResult('weekly recurring schedule created for Jan 2027', $janRows->count() === 5, 'count ' . $janRows->count());

// Edit occurrence only (Jan 10)
$occRow = $janRows->first(fn($x) => $x->working_date?->toDateString() === '2027-01-10');
$r = $scheduleController->store(makeStoreRequest([
    'schedule_id' => $occRow->id,
    'edit_scope' => 'occurrence',
    'staff_id' => $staffC->id,
    'recurrence_type' => 'one_time',
    'working_date' => '2027-01-10',
    'start_time' => '11:00',
    'end_time' => '15:00',
]));
$updatedJan10 = StaffSchedule::where('staff_id', $staffC->id)->where('working_date', '2027-01-10')->first();
$otherJanRows = StaffSchedule::where('staff_id', $staffC->id)
    ->where('recurrence_group_id', $janRows->first()->recurrence_group_id)
    ->get();

probeResult('edit occurrence only updates only target date', $updatedJan10 && $updatedJan10->start_time === '11:00:00' && $updatedJan10->end_time === '15:00:00' && $updatedJan10->recurrence_type === 'one_time', 'time ' . ($updatedJan10->start_time ?? 'null'));
probeResult('edit occurrence only leaves other group dates unchanged', $otherJanRows->count() === 4 && $otherJanRows->every(fn($x) => $x->start_time === '09:00:00'), 'other count ' . $otherJanRows->count());

// ---------------------------------------------------------------------------
// Test 23: Delete occurrence only vs skip occurrence vs delete group
// ---------------------------------------------------------------------------
try {
    // Skip occurrence (Jan 17)
    $jan17Row = StaffSchedule::where('staff_id', $staffC->id)->whereDate('working_date', '2027-01-17')->first();
    if ($jan17Row) {
        request()->merge(['scope' => 'skip']);
        $r = $scheduleController->destroy((string) $jan17Row->id);
        request()->replace([]);
        $skippedRow = StaffSchedule::where('staff_id', $staffC->id)->where('working_date', '2027-01-17')->first();
        probeResult('skip occurrence sets target date to day off (is_working=false)', $skippedRow && $skippedRow->is_working === false, 'is_working ' . var_export($skippedRow?->is_working, true));
    } else {
        probeResult('skip occurrence sets target date to day off (is_working=false)', false, 'jan17 row not found');
    }

    // Delete single occurrence (Jan 24)
    $jan24Row = StaffSchedule::where('staff_id', $staffC->id)->whereDate('working_date', '2027-01-24')->first();
    if ($jan24Row) {
        request()->merge(['scope' => 'occurrence']);
        $r = $scheduleController->destroy((string) $jan24Row->id);
        request()->replace([]);
        $deletedJan24 = StaffSchedule::where('staff_id', $staffC->id)->where('working_date', '2027-01-24')->first();
        probeResult('delete occurrence only removes target row', $deletedJan24 === null, 'jan24 exists: ' . ($deletedJan24 ? 'yes' : 'no'));
    } else {
        probeResult('delete occurrence only removes target row', false, 'jan24 row not found');
    }

    // Delete entire group
    $jan31Row = StaffSchedule::where('staff_id', $staffC->id)->whereDate('working_date', '2027-01-31')->first();
    if ($jan31Row) {
        request()->merge(['scope' => 'group']);
        $r = $scheduleController->destroy((string) $jan31Row->id);
        request()->replace([]);
        $remainingGroupRows = StaffSchedule::where('staff_id', $staffC->id)
            ->whereNotNull('recurrence_group_id')
            ->count();
        probeResult('delete entire schedule removes rest of group', $remainingGroupRows === 0, 'remaining group count ' . $remainingGroupRows);
    } else {
        probeResult('delete entire schedule removes rest of group', false, 'jan31 row not found');
    }
} catch (\Throwable $ex) {
    echo "EX IN TEST 23: " . $ex->getMessage() . " at " . $ex->getFile() . ":" . $ex->getLine() . PHP_EOL;
}

// ---------------------------------------------------------------------------
// Test 24: Appointment protection on schedule deletion & modification
// ---------------------------------------------------------------------------
// Create working schedule for Feb 14
$r = $scheduleController->store(makeStoreRequest([
    'staff_id' => $staffC->id,
    'recurrence_type' => 'one_time',
    'working_date' => '2027-02-14',
    'start_time' => '10:00',
    'end_time' => '16:00',
]));
$feb14Schedule = StaffSchedule::where('staff_id', $staffC->id)->where('working_date', '2027-02-14')->first();

// Book an appointment during this schedule
$rBook = book($controller, $staffC, $client, $service, Carbon::parse('2027-02-14')->setTime(11, 0), Carbon::parse('2027-02-14')->setTime(12, 0), 'prot test');
$aptId = responseJson($rBook)['appointment']['id'] ?? null;
probeResult('appointment booked on Feb 14', responseStatus($rBook) === 201 && $aptId !== null, 'status ' . responseStatus($rBook));

// Try to delete schedule when appointment exists
request()->merge(['scope' => 'occurrence']);
$rDel = $scheduleController->destroy((string) $feb14Schedule->id);
request()->replace([]);
$afterDelCheck = StaffSchedule::where('id', $feb14Schedule->id)->first();
probeResult('delete schedule rejected when appointment exists', $afterDelCheck !== null, 'schedule deleted: ' . ($afterDelCheck ? 'no' : 'yes'));

// Try to edit schedule to hours that exclude existing appointment (e.g. 13:00 - 17:00)
$rEdit = $scheduleController->store(makeStoreRequest([
    'schedule_id' => $feb14Schedule->id,
    'staff_id' => $staffC->id,
    'recurrence_type' => 'one_time',
    'working_date' => '2027-02-14',
    'start_time' => '13:00',
    'end_time' => '17:00',
]));
$afterEditCheck = StaffSchedule::where('id', $feb14Schedule->id)->first();
probeResult('edit schedule rejected when new hours exclude existing appointment', $afterEditCheck->start_time === '10:00:00', 'start_time ' . $afterEditCheck->start_time);

if ($aptId) {
    Appointment::find($aptId)?->delete();
}

$staffC->forceDelete();
$staffA->forceDelete();
$staffB->forceDelete();

cleanupScheduleProbe();
