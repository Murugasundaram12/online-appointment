<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ScheduleController;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Location;
use App\Models\Service;
use App\Models\Staff;
use App\Models\StaffSchedule;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;

function csResult(string $name, bool $pass, string $detail = ''): void
{
    echo ($pass ? 'PASS' : 'FAIL') . ' | ' . $name . ($detail ? ' | ' . $detail : '') . PHP_EOL;
}

function csCleanup(): void
{
    $clientIds = Client::where('email', 'like', 'csprobe_%@example.com')->pluck('id');
    $staffIds = Staff::where('email', 'like', 'csprobe_%@example.com')->pluck('id');
    $serviceIds = Service::where('name', 'like', 'CSPROBE_%')->pluck('id');
    $locationIds = Location::where('name', 'like', 'CSPROBE_%')->pluck('id');

    Appointment::whereIn('client_id', $clientIds)
        ->orWhereIn('staff_id', $staffIds)
        ->orWhereIn('service_id', $serviceIds)
        ->delete();
    StaffSchedule::whereIn('staff_id', $staffIds)->delete();
    Client::whereIn('id', $clientIds)->delete();
    Staff::whereIn('id', $staffIds)->delete();
    Service::whereIn('id', $serviceIds)->delete();
    Location::whereIn('id', $locationIds)->delete();
    Subscription::whereHas('plan', fn ($query) => $query->where('name', 'CSPROBE_Unlimited'))->delete();
    SubscriptionPlan::where('name', 'CSPROBE_Unlimited')->delete();
}

function csJson($response): array
{
    return json_decode($response->getContent(), true) ?: [];
}

function csReq(string $method, string $uri, array $data = []): Request
{
    return Request::create($uri, $method, $data);
}

csCleanup();
Mail::fake();
View::share('errors', new ViewErrorBag());

SubscriptionPlan::create([
    'name' => 'CSPROBE_Unlimited',
    'price' => 0,
    'billing_cycle' => 'monthly',
    'staff_limit' => null,
    'location_limit' => null,
    'appointment_limit' => null,
    'is_active' => true,
]);
Subscription::create([
    'subscription_plan_id' => SubscriptionPlan::where('name', 'CSPROBE_Unlimited')->value('id'),
    'start_date' => now()->subDay()->toDateString(),
    'end_date' => now()->addYear()->toDateString(),
    'status' => 'active',
    'payment_status' => 'paid',
]);

$loc = Location::create(['name' => 'CSPROBE_Main', 'timezone' => config('app.timezone'), 'is_active' => true]);
$staffA = Staff::create(['location_id' => $loc->id, 'name' => 'CSPROBE_Staff_A', 'email' => 'csprobe_a@example.com', 'password' => Hash::make('Password123'), 'access_level' => 'staff', 'is_active' => true]);
$staffB = Staff::create(['location_id' => $loc->id, 'name' => 'CSPROBE_Staff_B', 'email' => 'csprobe_b@example.com', 'password' => Hash::make('Password123'), 'access_level' => 'staff', 'is_active' => true]);
$staffC = Staff::create(['location_id' => $loc->id, 'name' => 'CSPROBE_Staff_C', 'email' => 'csprobe_c@example.com', 'password' => Hash::make('Password123'), 'access_level' => 'staff', 'is_active' => true]);
$service = Service::create(['name' => 'CSPROBE_Service', 'type' => 'in_person', 'price' => 50, 'duration_minutes' => 60, 'buffer_minutes' => 0, 'is_active' => true]);
$client = Client::create(['name' => 'CSPROBE_Client', 'email' => 'csprobe_client@example.com', 'phone' => '7770001']);

$monday = Carbon::parse('next monday')->startOfDay();
$dayIdx = (string) ($monday->dayOfWeekIso - 1); // 0=Mon ... 6=Sun

StaffSchedule::create(['staff_id' => $staffA->id, 'day_of_week' => $dayIdx, 'working_date' => $monday->toDateString(), 'start_time' => '09:00', 'end_time' => '17:00', 'is_working' => true]);
StaffSchedule::create(['staff_id' => $staffB->id, 'day_of_week' => $dayIdx, 'working_date' => $monday->toDateString(), 'start_time' => '09:00', 'end_time' => '17:00', 'is_working' => true]);

$controller = app(CalendarController::class);

function csBook(CalendarController $controller, Staff $staff, Carbon $start, Carbon $end, Client $client, Service $service, Location $loc)
{
    return $controller->storeAppointment(csReq('POST', '/calendar/appointments', [
        'staff_id' => $staff->id,
        'client_id' => $client->id,
        'service_id' => $service->id,
        'location_id' => $loc->id,
        'start_time' => $start->format('Y-m-d\TH:i:s'),
        'end_time' => $end->format('Y-m-d\TH:i:s'),
        'status' => 'booked',
    ]));
}

$r1 = csBook($controller, $staffA, $monday->copy()->setTime(10, 0), $monday->copy()->setTime(11, 0), $client, $service, $loc);
csResult('staff A books 10-11 | status 201', $r1->status() === 201);

$r2 = csBook($controller, $staffB, $monday->copy()->setTime(10, 0), $monday->copy()->setTime(11, 0), $client, $service, $loc);
csResult('staff B books same 10-11 slot | status 201 (other staff bookable)', $r2->status() === 201);

$r3 = csBook($controller, $staffA, $monday->copy()->setTime(10, 0), $monday->copy()->setTime(11, 0), $client, $service, $loc);
csResult('staff A re-book same slot rejected | status 422', $r3->status() === 422);
csResult('staff A re-book single clean message', (csJson($r3)['message'] ?? '') === 'This time slot is already booked.');

$r4 = csBook($controller, $staffA, $monday->copy()->setTime(12, 0), $monday->copy()->setTime(13, 0), $client, $service, $loc);
csResult('staff A books 12-13 | status 201', $r4->status() === 201);

$r5 = csBook($controller, $staffB, $monday->copy()->setTime(12, 0), $monday->copy()->setTime(13, 0), $client, $service, $loc);
csResult('staff B books 12-13 too | status 201', $r5->status() === 201);

$r6 = csBook($controller, $staffC, $monday->copy()->setTime(10, 0), $monday->copy()->setTime(11, 0), $client, $service, $loc);
csResult('unavailable staff C rejected | status 422', $r6->status() === 422);
csResult('unavailable staff C single clean message', (csJson($r6)['message'] ?? '') === 'Staff is not available at the selected time.');

$payload = csJson($controller->getStaffSchedules(csReq('GET', '/calendar/staff-schedules', [
    'start' => $monday->toDateString(),
    'end' => $monday->toDateString(),
])));

$staffPayload = collect($payload['staff'] ?? []);
$segA = $staffPayload->firstWhere('id', $staffA->id);
$segB = $staffPayload->firstWhere('id', $staffB->id);
$segC = $staffPayload->firstWhere('id', $staffC->id);
$dateKey = $monday->toDateString();

csResult('payload lists all three staff', $segA !== null && $segB !== null && $segC !== null);
csResult('staff A has working segment on monday', isset($segA['schedules_by_date'][$dateKey]) && $segA['schedules_by_date'][$dateKey][0]['is_working'] === true);
csResult('staff B has working segment on monday', isset($segB['schedules_by_date'][$dateKey]) && $segB['schedules_by_date'][$dateKey][0]['is_working'] === true);
csResult('staff C has empty effective array on monday', isset($segC['schedules_by_date'][$dateKey]) && $segC['schedules_by_date'][$dateKey] === []);

$scheduleIndex = app(ScheduleController::class)->index(csReq('GET', '/schedule', ['staff_id' => $staffA->id, 'range' => 'this_month']));
csResult('schedule index view renders', str_contains($scheduleIndex->render(), 'Schedule'));

$scheduleCreate = app(ScheduleController::class)->create(csReq('GET', '/schedule/create', ['staff_id' => $staffA->id]));
csResult('schedule create view renders', str_contains($scheduleCreate->render(), 'Recurrence') || str_contains($scheduleCreate->render(), 'recurrence'));

$calendarIndex = app(CalendarController::class)->index(csReq('GET', '/calendar?view=day'));
$html = $calendarIndex->render();
csResult('calendar day view renders', str_contains($html, 'calendar-grid') && str_contains($html, 'day'));
csResult('day view renders staff scheduled hours per column', str_contains($html, 'format12Hour(s.start_time)') && str_contains($html, 'staff-schedule-item small'));

csCleanup();
