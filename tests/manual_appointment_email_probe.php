<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\CalendarController;
use App\Mail\AppointmentBookedMail;
use App\Mail\AppointmentCancelledMail;
use App\Mail\AppointmentCompletedMail;
use App\Mail\AppointmentUpdatedMail;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Location;
use App\Models\Service;
use App\Models\Staff;
use App\Models\StaffSchedule;
use App\Services\AppointmentEmailService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

function cleanupAppointmentEmailProbe(): void
{
    $clientIds = Client::where('email', 'like', 'emailprobe_%@example.com')
        ->orWhere('name', 'like', 'EMAIL_PROBE_%')
        ->pluck('id');
    $staffIds = Staff::where('email', 'like', 'emailprobe_%@example.com')->pluck('id');
    $serviceIds = Service::where('name', 'like', 'EMAIL_PROBE_%')->pluck('id');
    $locationIds = Location::where('name', 'like', 'EMAIL_PROBE_%')->pluck('id');

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

function emailReq(string $method, string $uri, array $data = []): Request
{
    return Request::create($uri, $method, $data);
}

function emailResult(string $name, bool $pass, string $detail = ''): void
{
    echo ($pass ? 'PASS' : 'FAIL') . ' | ' . $name . ($detail ? ' | ' . $detail : '') . PHP_EOL;
}

function jsonContent($response): array
{
    return json_decode($response->getContent(), true) ?: [];
}

function sentMailCount(string $mailable): int
{
    return Mail::getFacadeRoot()->sent($mailable)->count();
}

cleanupAppointmentEmailProbe();
Mail::fake();

$monday = Carbon::parse('next monday')->startOfDay();
$location = Location::create(['name' => 'EMAIL_PROBE_Main', 'address' => '100 Email Probe Road', 'timezone' => config('app.timezone'), 'is_active' => true]);
$staff = Staff::create(['location_id' => $location->id, 'name' => 'EMAIL_PROBE_Staff', 'email' => 'emailprobe_staff@example.com', 'password' => Hash::make('Password123'), 'access_level' => 'admin', 'is_active' => true]);
$service = Service::create(['name' => 'EMAIL_PROBE_Service', 'type' => 'in_person', 'price' => 50, 'duration_minutes' => 60, 'buffer_minutes' => 0, 'is_active' => true]);
$client = Client::create(['name' => 'EMAIL_PROBE_Client', 'email' => 'emailprobe_client@example.com', 'phone' => '111']);
$invalidEmailClient = Client::create(['name' => 'EMAIL_PROBE_Invalid_Email_Client', 'email' => 'not-valid']);

StaffSchedule::create(['staff_id' => $staff->id, 'day_of_week' => (string) ($monday->dayOfWeekIso - 1), 'start_time' => '09:00', 'end_time' => '18:00', 'is_working' => true, 'breaks' => []]);

$controller = app(CalendarController::class);

$createResponse = $controller->storeAppointment(emailReq('POST', '/calendar/appointments', [
    'staff_id' => $staff->id,
    'client_id' => $client->id,
    'service_id' => $service->id,
    'location_id' => $location->id,
    'start_time' => $monday->copy()->setTime(10, 0)->format('Y-m-d\TH:i:s'),
    'end_time' => $monday->copy()->setTime(11, 0)->format('Y-m-d\TH:i:s'),
    'status' => 'booked',
]));
$appointmentId = jsonContent($createResponse)['appointment']['id'] ?? null;
emailResult('Appointment create succeeds with email result', $createResponse->status() === 201 && (jsonContent($createResponse)['email']['sent'] ?? false) === true);
emailResult('Booked mail sent once', sentMailCount(AppointmentBookedMail::class) === 1);

$notesOnlyResponse = $controller->updateAppointment(emailReq('PUT', '/calendar/appointments/' . $appointmentId, [
    'notes' => 'Notes only should not send update mail',
]), $appointmentId);
emailResult('Notes-only update succeeds without update mail', $notesOnlyResponse->status() === 200);
emailResult('Notes-only update email skipped', sentMailCount(AppointmentUpdatedMail::class) === 0);

$rescheduleResponse = $controller->updateAppointment(emailReq('PUT', '/calendar/appointments/' . $appointmentId, [
    'start_time' => $monday->copy()->setTime(12, 0)->format('Y-m-d\TH:i:s'),
    'end_time' => $monday->copy()->setTime(13, 0)->format('Y-m-d\TH:i:s'),
]), $appointmentId);
emailResult('Reschedule update succeeds', $rescheduleResponse->status() === 200);
emailResult('Reschedule mail sent once', sentMailCount(AppointmentUpdatedMail::class) === 1);

$cancelResponse = $controller->updateAppointment(emailReq('PUT', '/calendar/appointments/' . $appointmentId, [
    'status' => 'cancelled',
]), $appointmentId);
emailResult('Cancel update succeeds', $cancelResponse->status() === 200);
emailResult('Cancelled mail sent once', sentMailCount(AppointmentCancelledMail::class) === 1);

$repeatCancelResponse = $controller->updateAppointment(emailReq('PUT', '/calendar/appointments/' . $appointmentId, [
    'status' => 'cancelled',
    'notes' => 'Repeated save should not duplicate cancellation mail',
]), $appointmentId);
emailResult('Repeated cancel save succeeds', $repeatCancelResponse->status() === 200);
emailResult('Repeated cancellation mail not duplicated', sentMailCount(AppointmentCancelledMail::class) === 1);

$completedResponse = $controller->storeAppointment(emailReq('POST', '/calendar/appointments', [
    'staff_id' => $staff->id,
    'client_id' => $client->id,
    'service_id' => $service->id,
    'location_id' => $location->id,
    'start_time' => $monday->copy()->setTime(14, 0)->format('Y-m-d\TH:i:s'),
    'end_time' => $monday->copy()->setTime(15, 0)->format('Y-m-d\TH:i:s'),
    'status' => 'booked',
]));
$completedId = jsonContent($completedResponse)['appointment']['id'] ?? null;
$completeUpdate = $controller->updateAppointment(emailReq('PUT', '/calendar/appointments/' . $completedId, [
    'status' => 'completed',
]), $completedId);
emailResult('Completed update succeeds', $completeUpdate->status() === 200);
emailResult('Completed mail sent once', sentMailCount(AppointmentCompletedMail::class) === 1);

$invalidEmailAppointment = Appointment::create([
    'client_id' => $invalidEmailClient->id,
    'staff_id' => $staff->id,
    'service_id' => $service->id,
    'location_id' => $location->id,
    'start_time' => $monday->copy()->setTime(16, 0),
    'end_time' => $monday->copy()->setTime(17, 0),
    'status' => 'booked',
]);
$invalidEmailResult = app(AppointmentEmailService::class)->sendBooked($invalidEmailAppointment);
emailResult('Invalid client email safely skipped', ($invalidEmailResult['attempted'] ?? true) === false);

$smtpFailureAppointment = Appointment::create([
    'client_id' => $client->id,
    'staff_id' => $staff->id,
    'service_id' => $service->id,
    'location_id' => $location->id,
    'start_time' => $monday->copy()->setTime(17, 0),
    'end_time' => $monday->copy()->setTime(18, 0),
    'status' => 'booked',
]);
Mail::swap(new class {
    public function to(string $email): self
    {
        throw new RuntimeException('SMTP probe failure');
    }
});
$smtpFailureResult = app(AppointmentEmailService::class)->sendBooked($smtpFailureAppointment);
emailResult('SMTP failure safely caught', ($smtpFailureResult['attempted'] ?? false) === true && ($smtpFailureResult['sent'] ?? true) === false);

cleanupAppointmentEmailProbe();
