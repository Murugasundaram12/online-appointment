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
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

$failures = 0;
function ok(string $label, bool $cond): void
{
    global $failures;
    if (!$cond) $failures++;
    echo ($cond ? 'PASS' : 'FAIL') . ' | ' . $label . PHP_EOL;
}

function cleanupPhaseBLeftovers(): void
{
    $clientIds = Client::where('email', 'like', 'probe_c_%@example.com')->pluck('id');
    $staffIds = Staff::where('email', 'like', 'probe_s_%@example.com')->pluck('id');
    $serviceIds = Service::where('name', 'like', 'PROBE_SVC_%')->pluck('id');
    $locationIds = Location::where('name', 'like', 'PROBE_LOC_%')->pluck('id');
    Appointment::whereIn('client_id', $clientIds)
        ->orWhereIn('staff_id', $staffIds)
        ->orWhereIn('service_id', $serviceIds)
        ->orWhereIn('location_id', $locationIds)
        ->delete();
    Client::whereIn('id', $clientIds)->delete();
    Staff::whereIn('id', $staffIds)->delete();
    Service::whereIn('id', $serviceIds)->delete();
    Location::whereIn('id', $locationIds)->delete();
}

cleanupPhaseBLeftovers();

$suffix = uniqid('_', false);
$staff1 = Staff::create(['name' => 'PROBE_S_' . $suffix, 'email' => 'probe_s_' . $suffix . '@example.com', 'password' => \Illuminate\Support\Facades\Hash::make('probe'), 'is_active' => true, 'location_id' => null, 'category' => 'doctor']);
$staff2 = Staff::create(['name' => 'PROBE_S2_' . $suffix, 'email' => 'probe_s2_' . $suffix . '@example.com', 'password' => \Illuminate\Support\Facades\Hash::make('probe'), 'is_active' => true, 'location_id' => null, 'category' => 'doctor']);
$service = Service::create(['name' => 'PROBE_SVC_' . $suffix, 'is_active' => true, 'duration_minutes' => 30, 'price' => 10, 'service_category_id' => null]);
$client = Client::create(['name' => 'PROBE_C_' . $suffix, 'email' => 'probe_c_' . $suffix . '@example.com']);
$loc = Location::create(['name' => 'PROBE_LOC_' . $suffix, 'is_active' => true]);

$day = Carbon::today()->addDays(10);
Appointment::create(['client_id' => $client->id, 'staff_id' => $staff1->id, 'service_id' => $service->id, 'location_id' => $loc->id, 'start_time' => $day->copy()->setTime(9, 0), 'end_time' => $day->copy()->setTime(9, 30), 'status' => 'pending']);
Appointment::create(['client_id' => $client->id, 'staff_id' => $staff1->id, 'service_id' => $service->id, 'location_id' => $loc->id, 'start_time' => $day->copy()->setTime(10, 0), 'end_time' => $day->copy()->setTime(10, 30), 'status' => 'booked']);
Appointment::create(['client_id' => $client->id, 'staff_id' => $staff2->id, 'service_id' => $service->id, 'location_id' => $loc->id, 'start_time' => $day->copy()->setTime(11, 0), 'end_time' => $day->copy()->setTime(11, 30), 'status' => 'completed']);

// 1. events endpoint respects status filter
$req = Request::create('/calendar/events?start=' . $day->toDateString() . '&end=' . $day->toDateString() . '&status=pending', 'GET', []);
$res = app(CalendarController::class)->getEvents($req);
$data = $res->getData(true);
ok('events status=pending only pending', collect($data)->where('status', 'pending')->count() >= 1 && collect($data)->where('status', '!=', 'pending')->count() === 0);

$req2 = Request::create('/calendar/events?start=' . $day->toDateString() . '&end=' . $day->toDateString() . '&status=booked', 'GET', []);
$data2 = app(CalendarController::class)->getEvents($req2)->getData(true);
ok('events status=booked only booked', collect($data2)->where('status', 'booked')->count() >= 1 && collect($data2)->where('status', '!=', 'booked')->count() === 0);

$req3 = Request::create('/calendar/events?start=' . $day->toDateString() . '&end=' . $day->toDateString() . '&status=completed&staff_id=' . $staff2->id, 'GET', []);
$data3 = app(CalendarController::class)->getEvents($req3)->getData(true);
ok('events status+staff combined', collect($data3)->count() >= 1 && collect($data3)->every(fn($e) => $e['status'] === 'completed' && $e['staffId'] == $staff2->id));

// 2. index page honors filters for month view
$req4 = Request::create('/calendar?view=month&month=' . $day->format('Y-m') . '&status=pending&location_id=' . $loc->id, 'GET', []);
$html = app(CalendarController::class)->index($req4)->render();
ok('month view status=pending renders selected status', str_contains($html, 'value="pending" selected'));
ok('month view location filter pre-selected', str_contains($html, 'value="' . $loc->id . '" selected'));
ok('month view monthEvents contain only pending', substr_count($html, '"status":"pending"') >= 1 && substr_count($html, '"status":"booked"') === 0 && substr_count($html, '"status":"completed"') === 0);
ok('reset filters button present', str_contains($html, 'calendar-reset-filters'));

// 3. index page default week view unfiltered
$req5 = Request::create('/calendar?view=week', 'GET', []);
$html5 = app(CalendarController::class)->index($req5)->render();
ok('default week view has all statuses in monthEvents', substr_count($html5, '"status":"pending"') >= 1 && substr_count($html5, '"status":"booked"') >= 1 && substr_count($html5, '"status":"completed"') >= 1);

// 4. invalid status value does not break index
$req6 = Request::create('/calendar?view=month&month=' . $day->format('Y-m') . '&status=banana', 'GET', []);
$html6 = app(CalendarController::class)->index($req6)->render();
ok('invalid status param renders safely', str_contains($html6, 'monthEvents') && !str_contains($html6, 'Undefined variable') && !str_contains($html6, 'whoops'));

// cleanup
Appointment::where('client_id', $client->id)->delete();
$client->delete();
$staff1->delete();
$staff2->delete();
$service->delete();
$loc->delete();

echo PHP_EOL . ($failures === 0 ? 'ALL PASS' : $failures . ' FAILURES') . PHP_EOL;
exit($failures === 0 ? 0 : 1);
