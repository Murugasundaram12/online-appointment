<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\OnlineBookingController;
use App\Models\Appointment;
use App\Models\BusinessSetting;
use App\Models\Client;
use App\Models\Location;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Staff;
use App\Models\StaffSchedule;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

function bkCleanup(): void
{
    $clientIds = Client::where('email', 'like', 'bkprobe_%@example.com')->pluck('id');
    $staffIds = Staff::where('email', 'like', 'bkprobe_%@example.com')->pluck('id');
    $serviceIds = Service::where('name', 'like', 'BKPROBE_%')->pluck('id');
    $categoryIds = ServiceCategory::where('name', 'like', 'BKPROBE_%')->pluck('id');
    $locationIds = Location::where('name', 'like', 'BKPROBE_%')->pluck('id');

    Appointment::whereIn('client_id', $clientIds)
        ->orWhereIn('staff_id', $staffIds)
        ->orWhereIn('service_id', $serviceIds)
        ->delete();
    StaffSchedule::whereIn('staff_id', $staffIds)->delete();
    Client::whereIn('id', $clientIds)->delete();
    Staff::whereIn('id', $staffIds)->delete();
    Service::whereIn('id', $serviceIds)->delete();
    ServiceCategory::whereIn('id', $categoryIds)->delete();
    Location::whereIn('id', $locationIds)->delete();
    Subscription::whereHas('plan', fn ($q) => $q->where('name', 'BKPROBE_Unlimited'))->delete();
    SubscriptionPlan::where('name', 'BKPROBE_Unlimited')->delete();
    BusinessSetting::where('key', 'appointment_interval')->where('value', 'BKPROBE')->delete();
}

function bkReq(string $method, string $uri, array $data = []): Request
{
    return Request::create($uri, $method, $data);
}

function bkJson($response): array
{
    return json_decode($response->getContent(), true) ?: [];
}

function bkResult(string $name, bool $pass, string $detail = ''): void
{
    echo ($pass ? 'PASS' : 'FAIL') . ' | ' . $name . ($detail ? ' | ' . $detail : '') . PHP_EOL;
}

bkCleanup();

$plan = SubscriptionPlan::create([
    'name' => 'BKPROBE_Unlimited',
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

$controller = app(OnlineBookingController::class);

$probePhone = '555' . random_int(10000, 99999);

$locA = Location::create(['name' => 'BKPROBE_Main', 'timezone' => config('app.timezone'), 'is_active' => true]);
$locB = Location::create(['name' => 'BKPROBE_Other', 'timezone' => config('app.timezone'), 'is_active' => true]);

$cat = ServiceCategory::create(['name' => 'BKPROBE_Massage']);
$catOther = ServiceCategory::create(['name' => 'BKPROBE_Physio']);

$serviceMassage = Service::create(['name' => 'BKPROBE_Massage', 'service_category_id' => $cat->id, 'type' => 'in_person', 'price' => 50, 'duration_minutes' => 60, 'buffer_minutes' => 0, 'is_active' => true]);
$serviceNoCat = Service::create(['name' => 'BKPROBE_General', 'type' => 'in_person', 'price' => 40, 'duration_minutes' => 30, 'buffer_minutes' => 0, 'is_active' => true]);

$staffA = Staff::create(['location_id' => $locA->id, 'name' => 'BKPROBE_Staff_Massage', 'email' => 'bkprobe_a@example.com', 'password' => Hash::make('secret123'), 'access_level' => 'staff', 'category' => 'BKPROBE_Massage', 'is_active' => true]);
$staffB = Staff::create(['location_id' => $locB->id, 'name' => 'BKPROBE_Staff_Physio', 'email' => 'bkprobe_b@example.com', 'password' => Hash::make('secret123'), 'access_level' => 'staff', 'category' => 'BKPROBE_Physio', 'is_active' => true]);

$day = Carbon::parse('next monday')->addWeek();
$idx = $day->dayOfWeekIso - 1;
StaffSchedule::create(['staff_id' => $staffA->id, 'day_of_week' => (string) $idx, 'start_time' => '09:00', 'end_time' => '17:00', 'is_working' => true, 'breaks' => []]);
StaffSchedule::create(['staff_id' => $staffB->id, 'day_of_week' => (string) $idx, 'start_time' => '09:00', 'end_time' => '17:00', 'is_working' => true, 'breaks' => []]);

// index() must expose location_id on staff for client-side location filtering
$indexView = $controller->index();
$staffView = $indexView->getData()['staff'] ?? collect();
$probeStaff = $staffView->whereIn('id', [$staffA->id, $staffB->id]);
$locAttrOk = $probeStaff->count() === 2
    && (int) $staffView->where('id', $staffA->id)->first()->getAttribute('location_id') === $locA->id
    && (int) $staffView->where('id', $staffB->id)->first()->getAttribute('location_id') === $locB->id;
bkResult('booking index exposes staff location_id', (bool) $locAttrOk, 'count ' . $staffView->count());

$dateStr = $day->toDateString();

// slots: category filter keeps only matching staff (physio staff must be excluded; uncategorized staff are unrestricted)
$slots = bkJson($controller->slots(bkReq('GET', '/online-booking/slots', ['service_id' => $serviceMassage->id, 'date' => $dateStr])));
$slotsStaff = collect($slots)->pluck('staff_id')->unique()->map(fn ($id) => (int) $id);
bkResult('slots massage includes matching staff', $slotsStaff->contains((int) $staffA->id), 'staff ' . $slotsStaff->implode(','));
bkResult('slots massage excludes physio staff', !$slotsStaff->contains((int) $staffB->id), 'staff ' . $slotsStaff->implode(','));

// slots: location filter
$slots = bkJson($controller->slots(bkReq('GET', '/online-booking/slots', ['service_id' => $serviceMassage->id, 'date' => $dateStr, 'location_id' => $locB->id])));
bkResult('slots location filter excludes other-location staff', count($slots) === 0, 'count ' . count($slots));

// slots: uncategorized service is available to all staff
$slots = bkJson($controller->slots(bkReq('GET', '/online-booking/slots', ['service_id' => $serviceNoCat->id, 'date' => $dateStr])));
$slotsStaff = collect($slots)->pluck('staff_id')->unique();
bkResult('slots uncategorized service includes all staff', $slotsStaff->contains($staffA->id) && $slotsStaff->contains($staffB->id), 'staff ' . $slotsStaff->implode(','));

// store: valid booking
$start = $day->copy()->setTime(10, 0)->format('Y-m-d\TH:i:s');
$end = $day->copy()->setTime(11, 0)->format('Y-m-d\TH:i:s');
$req = bkReq('POST', '/online-booking', [
    'location_id' => $locA->id,
    'service_id' => $serviceMassage->id,
    'staff_id' => $staffA->id,
    'start_time' => $start,
    'end_time' => $end,
    'client_name' => 'BKPROBE Customer',
    'client_email' => 'bkprobe_cust@example.com',
    'client_phone' => $probePhone,
]);
try {
    $res = $controller->store($req);
    $appt = Appointment::where('client_id', Client::where('email', 'bkprobe_cust@example.com')->first()->id)
        ->where('staff_id', $staffA->id)->where('service_id', $serviceMassage->id)->first();
    bkResult('booking store creates appointment', (bool) $appt && $appt->location_id === $locA->id, 'redirect ok');
} catch (ValidationException $e) {
    bkResult('booking store creates appointment', false, 'validation: ' . implode(';', array_map(fn ($v) => implode(',', $v), $e->errors())));
}

// store: category mismatch rejected
$req = bkReq('POST', '/online-booking', [
    'location_id' => $locB->id,
    'service_id' => $serviceMassage->id,
    'staff_id' => $staffB->id,
    'start_time' => $start,
    'end_time' => $end,
    'client_name' => 'BKPROBE Bad',
    'client_email' => 'bkprobe_bad@example.com',
]);
try {
    $controller->store($req);
    bkResult('booking store category mismatch rejected', false, 'no exception');
} catch (ValidationException $e) {
    bkResult('booking store category mismatch rejected', array_key_exists('staff_id', $e->errors()));
}

// store: occupied slot rejected
$req = bkReq('POST', '/online-booking', [
    'location_id' => $locA->id,
    'service_id' => $serviceMassage->id,
    'staff_id' => $staffA->id,
    'start_time' => $start,
    'end_time' => $end,
    'client_name' => 'BKPROBE Dup',
    'client_email' => 'bkprobe_dup@example.com',
]);
try {
    $controller->store($req);
    bkResult('booking store occupied slot rejected', false, 'no exception');
} catch (ValidationException $e) {
    bkResult('booking store occupied slot rejected', array_key_exists('start_time', $e->errors()));
}

bkCleanup();
