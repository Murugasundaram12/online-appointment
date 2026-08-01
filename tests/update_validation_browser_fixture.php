<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

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
use Illuminate\Support\Facades\Hash;

$clientIds = Client::where('email', 'like', 'uv_browser_%@example.com')->pluck('id');
$staffIds = Staff::where('email', 'like', 'uv_browser_%@example.com')->pluck('id');
$locationIds = Location::where('name', 'like', 'UV_BROWSER_%')->pluck('id');
$serviceIds = Service::where('name', 'like', 'UV_BROWSER_%')->pluck('id');
$packageIds = Package::where('name', 'like', 'UV_BROWSER_%')->pluck('id');
$invoiceIds = Invoice::whereIn('client_id', $clientIds)->orWhereIn('staff_id', $staffIds)->pluck('id');

PaymentRecord::whereIn('invoice_id', $invoiceIds)->delete();
Invoice::whereIn('id', $invoiceIds)->delete();
Payroll::whereIn('staff_id', $staffIds)->delete();
StaffSchedule::whereIn('staff_id', $staffIds)->delete();
Package::whereIn('id', $packageIds)->delete();
Service::whereIn('id', $serviceIds)->delete();
Staff::whereIn('id', $staffIds)->delete();
Client::whereIn('id', $clientIds)->delete();
Location::whereIn('id', $locationIds)->delete();
Subscription::whereHas('plan', fn ($query) => $query->where('name', 'UV_BROWSER_Unlimited'))->delete();
SubscriptionPlan::where('name', 'UV_BROWSER_Unlimited')->delete();

$plan = SubscriptionPlan::create([
    'name' => 'UV_BROWSER_Unlimited',
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

$location = Location::create(['name' => 'UV_BROWSER_Main', 'timezone' => config('app.timezone'), 'is_active' => true]);
$clientA = Client::create(['name' => 'UV_BROWSER_Client_A', 'email' => 'uv_browser_a@example.com', 'phone' => '810001', 'city' => 'Alpha']);
$clientB = Client::create(['name' => 'UV_BROWSER_Client_B', 'email' => 'uv_browser_b@example.com', 'phone' => '810002', 'city' => 'Beta']);
$staff = Staff::create([
    'location_id' => $location->id,
    'name' => 'UV_BROWSER_Staff',
    'email' => 'uv_browser_staff@example.com',
    'password' => Hash::make('Password123'),
    'access_level' => 'admin',
    'salary' => 100,
    'is_active' => true,
]);
$service = Service::create(['name' => 'UV_BROWSER_Service', 'type' => 'in_person', 'price' => 50, 'duration_minutes' => 60, 'is_active' => true]);
$package = Package::create(['name' => 'UV_BROWSER_Package', 'price' => 75, 'is_active' => true]);

file_put_contents(__DIR__ . '/update_validation_browser_fixture.json', json_encode([
    'clientA' => $clientA->id,
    'clientB' => $clientB->id,
    'staff' => $staff->id,
    'location' => $location->id,
    'service' => $service->id,
    'package' => $package->id,
    'plan' => $plan->id,
], JSON_PRETTY_PRINT));

echo "fixture ready\n";
