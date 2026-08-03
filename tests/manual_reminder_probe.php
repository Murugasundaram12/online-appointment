<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Mail\AppointmentReminderMail;
use App\Models\Appointment;
use App\Models\BusinessSetting;
use App\Models\Client;
use App\Models\Location;
use App\Models\Service;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

function rmCleanup(): void
{
    $clientIds = Client::where('email', 'like', 'rmprobe_%@example.com')
        ->orWhere('name', 'like', 'RMPROBE_%')
        ->pluck('id');
    $staffIds = Staff::where('email', 'like', 'rmprobe_%@example.com')->pluck('id');
    $serviceIds = Service::where('name', 'like', 'RMPROBE_%')->pluck('id');
    $locationIds = Location::where('name', 'like', 'RMPROBE_%')->pluck('id');
    Appointment::whereIn('client_id', $clientIds)->delete();
    Client::whereIn('id', $clientIds)->delete();
    Staff::whereIn('id', $staffIds)->delete();
    Service::whereIn('id', $serviceIds)->delete();
    Location::whereIn('id', $locationIds)->delete();
    BusinessSetting::where('key', 'timezone')->where('value', 'RMPROBE')->delete();
}

function rmResult(string $name, bool $pass, string $detail = ''): void
{
    echo ($pass ? 'PASS' : 'FAIL') . ' | ' . $name . ($detail ? ' | ' . $detail : '') . PHP_EOL;
}

rmCleanup();

$loc = Location::create(['name' => 'RMPROBE_Clinic', 'timezone' => 'UTC', 'is_active' => true]);
$staff = Staff::create(['location_id' => $loc->id, 'name' => 'RMPROBE_Staff', 'email' => 'rmprobe_staff@example.com', 'password' => Hash::make('secret123'), 'access_level' => 'staff', 'is_active' => true]);
$service = Service::create(['name' => 'RMPROBE_Checkup', 'type' => 'in_person', 'price' => 50, 'duration_minutes' => 60, 'buffer_minutes' => 0, 'is_active' => true]);
$client = Client::create(['name' => 'RMPROBE_Patient', 'email' => 'rmprobe_patient@example.com', 'phone' => '555']);

$start = now()->addHours(24)->startOfMinute();
$appt = Appointment::create([
    'client_id' => $client->id,
    'staff_id' => $staff->id,
    'service_id' => $service->id,
    'location_id' => $loc->id,
    'start_time' => $start,
    'end_time' => $start->copy()->addMinutes(60),
    'status' => 'pending',
]);

$exit = Artisan::call('appointments:send-reminders', ['--dry-run' => true]);
$output = Artisan::output();
rmResult('reminder dry-run finds 24h appointment', str_contains($output, "appointment {$appt->id}") && str_contains($output, '1 sent'), trim(preg_replace('/\s+/', ' ', $output)));

// booked appointments are eligible for reminders
$appt->update(['reminder_sent_at' => null, 'status' => 'booked']);
$exit = Artisan::call('appointments:send-reminders', ['--dry-run' => true]);
$output = Artisan::output();
rmResult('reminder finds booked appointment', str_contains($output, "appointment {$appt->id}") && str_contains($output, '1 sent'), trim(preg_replace('/\s+/', ' ', $output)));
$appt->update(['status' => 'pending']);

// already-reminded appointments are not re-picked
$appt->update(['reminder_sent_at' => now()]);
$exit = Artisan::call('appointments:send-reminders', ['--dry-run' => true]);
$output = Artisan::output();
rmResult('reminder skips already-reminded', !str_contains($output, "appointment {$appt->id}"), '0 found expected');

// client without email is skipped safely
$appt->update(['reminder_sent_at' => null]);
$client->update(['email' => null]);
$exit = Artisan::call('appointments:send-reminders', ['--dry-run' => true]);
$output = Artisan::output();
rmResult('reminder skips client without email', str_contains($output, 'Skipping appointment ' . $appt->id) && str_contains($output, '1 skipped'), 'skip path ok');
$client->update(['email' => 'rmprobe_patient@example.com']);

// mailable view renders without errors
$business = ['name' => 'RMPROBE_Clinic', 'timezone' => 'UTC', 'email' => 'rmprobe@example.com', 'phone' => '555', 'address' => '1 Test St'];
$mail = new AppointmentReminderMail($appt, $business, 'APT-TEST1234');
$html = $mail->render();
rmResult('reminder mail view renders', str_contains($html, 'Appointment reminder') && str_contains($html, 'RMPROBE_Patient') && str_contains($html, 'Booking Reference'), 'len ' . strlen($html));

// invalid email does not break dry-run
$client->update(['email' => 'not-an-email']);
$exit = Artisan::call('appointments:send-reminders', ['--dry-run' => true]);
$output = Artisan::output();
rmResult('reminder skips invalid email safely', str_contains($output, 'skipped'), '');

rmCleanup();
