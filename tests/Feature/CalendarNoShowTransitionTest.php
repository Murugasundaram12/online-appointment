<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\Service;
use App\Models\Staff;
use App\Models\StaffSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CalendarNoShowTransitionTest extends TestCase
{
    use RefreshDatabase;

    private Staff $adminStaff;
    private Staff $otherStaff;
    private Service $service;
    private Location $location;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->location = Location::create([
            'name' => 'Main Center',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        $this->adminStaff = Staff::create([
            'location_id' => $this->location->id,
            'name' => 'Admin Staff',
            'email' => 'admin_sched@example.com',
            'password' => Hash::make('Secret123!'),
            'access_level' => 'admin',
            'is_active' => true,
        ]);

        $this->otherStaff = Staff::create([
            'location_id' => $this->location->id,
            'name' => 'Other Staff',
            'email' => 'other_sched@example.com',
            'password' => Hash::make('Secret123!'),
            'access_level' => 'staff',
            'is_active' => true,
        ]);

        for ($day = 0; $day <= 6; $day++) {
            StaffSchedule::create([
                'staff_id' => $this->adminStaff->id,
                'day_of_week' => (string) $day,
                'start_time' => '08:00:00',
                'end_time' => '18:00:00',
                'is_working' => true,
            ]);

            StaffSchedule::create([
                'staff_id' => $this->otherStaff->id,
                'day_of_week' => (string) $day,
                'start_time' => '08:00:00',
                'end_time' => '18:00:00',
                'is_working' => true,
            ]);
        }

        $this->service = Service::create([
            'name' => 'Consultation',
            'type' => 'in_person',
            'price' => 75,
            'duration_minutes' => 60,
            'buffer_minutes' => 0,
            'is_active' => true,
        ]);

        $this->client = Client::create([
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'name' => 'Alice Smith',
            'email' => 'alice@example.com',
            'phone' => '1234567890',
        ]);
    }

    private function createNoShowAppointment(array $overrides = []): Appointment
    {
        return Appointment::create(array_merge([
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'location_id' => $this->location->id,
            'start_time' => now()->addDays(1)->setTime(10, 0),
            'end_time' => now()->addDays(1)->setTime(11, 0),
            'status' => 'no_show',
        ], $overrides));
    }

    /**
     * 1. no_show -> booked succeeds.
     */
    public function test_no_show_to_booked_succeeds(): void
    {
        $appt = $this->createNoShowAppointment();

        $res = $this->actingAs($this->adminStaff, 'staff')->putJson("/calendar/appointments/{$appt->id}", [
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'start_time' => $appt->start_time->format('Y-m-d\TH:i:s'),
            'end_time' => $appt->end_time->format('Y-m-d\TH:i:s'),
            'status' => 'booked',
        ]);

        $res->assertStatus(200);
        $res->assertJsonPath('success', true);
        $res->assertJsonPath('appointment.status', 'booked');
        $this->assertEquals('booked', $appt->fresh()->status);
    }

    /**
     * 2. pending and confirmed statuses are rejected in backend.
     */
    public function test_pending_and_confirmed_statuses_are_rejected(): void
    {
        $appt = $this->createNoShowAppointment();

        // Pending rejected on update
        $resPending = $this->actingAs($this->adminStaff, 'staff')->putJson("/calendar/appointments/{$appt->id}", [
            'status' => 'pending',
        ]);
        $resPending->assertStatus(422);
        $resPending->assertJsonValidationErrors(['status']);

        // Confirmed rejected on update
        $resConfirmed = $this->actingAs($this->adminStaff, 'staff')->putJson("/calendar/appointments/{$appt->id}", [
            'status' => 'confirmed',
        ]);
        $resConfirmed->assertStatus(422);
        $resConfirmed->assertJsonValidationErrors(['status']);

        // Pending rejected on create
        $resCreatePending = $this->actingAs($this->adminStaff, 'staff')->postJson("/calendar/appointments", [
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'start_time' => now()->addDays(2)->setTime(10, 0)->format('Y-m-d\TH:i:s'),
            'end_time' => now()->addDays(2)->setTime(11, 0)->format('Y-m-d\TH:i:s'),
            'status' => 'pending',
        ]);
        $resCreatePending->assertStatus(422);
        $resCreatePending->assertJsonValidationErrors(['status']);

        // Confirmed rejected on create
        $resCreateConfirmed = $this->actingAs($this->adminStaff, 'staff')->postJson("/calendar/appointments", [
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'start_time' => now()->addDays(2)->setTime(10, 0)->format('Y-m-d\TH:i:s'),
            'end_time' => now()->addDays(2)->setTime(11, 0)->format('Y-m-d\TH:i:s'),
            'status' => 'confirmed',
        ]);
        $resCreateConfirmed->assertStatus(422);
        $resCreateConfirmed->assertJsonValidationErrors(['status']);
    }

    /**
     * 4. no_show -> cancelled still requires cancellation reason.
     */
    public function test_no_show_to_cancelled_requires_cancellation_reason(): void
    {
        $appt = $this->createNoShowAppointment();

        // Without reason -> rejected with 422
        $resFail = $this->actingAs($this->adminStaff, 'staff')->putJson("/calendar/appointments/{$appt->id}", [
            'status' => 'cancelled',
        ]);

        $resFail->assertStatus(422);
        $resFail->assertJsonValidationErrors(['cancellation_reason']);
        $this->assertEquals('no_show', $appt->fresh()->status);

        // With reason -> succeeds
        $resPass = $this->actingAs($this->adminStaff, 'staff')->putJson("/calendar/appointments/{$appt->id}", [
            'status' => 'cancelled',
            'cancellation_reason' => 'Client called to cancel after missing slot',
        ]);

        $resPass->assertStatus(200);
        $resPass->assertJsonPath('success', true);
        $this->assertEquals('cancelled', $appt->fresh()->status);
        $this->assertEquals('Client called to cancel after missing slot', $appt->fresh()->cancellation_reason);
    }

    /**
     * 5. no_show -> completed succeeds and respects business rules (auto invoice).
     */
    public function test_no_show_to_completed_succeeds_and_creates_invoice(): void
    {
        $appt = $this->createNoShowAppointment();

        $res = $this->actingAs($this->adminStaff, 'staff')->putJson("/calendar/appointments/{$appt->id}", [
            'status' => 'completed',
        ]);

        $res->assertStatus(200);
        $res->assertJsonPath('success', true);
        $this->assertEquals('completed', $appt->fresh()->status);

        $this->assertDatabaseHas('invoices', [
            'appointment_id' => $appt->id,
            'client_id' => $this->client->id,
            'staff_id' => $this->adminStaff->id,
        ]);
    }

    /**
     * 6. Automatic no-show remains disabled and does not change booked/pending/confirmed.
     */
    public function test_automatic_no_show_remains_disabled_and_leaves_appointments_intact(): void
    {
        $pastBooked = Appointment::create([
            'client_id' => $this->client->id,
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::now()->subHours(5),
            'end_time' => Carbon::now()->subHours(4),
            'status' => 'booked',
        ]);

        $pastPending = Appointment::create([
            'client_id' => $this->client->id,
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::now()->subHours(4),
            'end_time' => Carbon::now()->subHours(3),
            'status' => 'pending',
        ]);

        $pastConfirmed = Appointment::create([
            'client_id' => $this->client->id,
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::now()->subHours(3),
            'end_time' => Carbon::now()->subHours(2),
            'status' => 'confirmed',
        ]);

        Artisan::call('appointments:mark-no-show');

        $this->assertEquals('booked', $pastBooked->fresh()->status);
        $this->assertEquals('pending', $pastPending->fresh()->status);
        $this->assertEquals('confirmed', $pastConfirmed->fresh()->status);
    }

    /**
     * 7. Invalid status values are still rejected.
     */
    public function test_invalid_status_values_are_rejected(): void
    {
        $appt = $this->createNoShowAppointment();

        $res = $this->actingAs($this->adminStaff, 'staff')->putJson("/calendar/appointments/{$appt->id}", [
            'status' => 'bogus_status',
        ]);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['status']);
        $this->assertEquals('no_show', $appt->fresh()->status);
    }

    /**
     * 8. Existing authorization rules remain enforced.
     */
    public function test_unauthorized_staff_cannot_update_no_show_appointment(): void
    {
        $appt = $this->createNoShowAppointment();

        // otherStaff is non-admin and not assigned to this appointment
        $res = $this->actingAs($this->otherStaff, 'staff')->putJson("/calendar/appointments/{$appt->id}", [
            'status' => 'booked',
        ]);

        $res->assertStatus(403);
        $this->assertEquals('no_show', $appt->fresh()->status);
    }

    /**
     * 9. Existing appointment validation remains enforced on reschedule / update.
     */
    public function test_existing_appointment_validation_remains_enforced(): void
    {
        $appt = $this->createNoShowAppointment();

        // 1. End time before start time is rejected
        $resTime = $this->actingAs($this->adminStaff, 'staff')->putJson("/calendar/appointments/{$appt->id}", [
            'start_time' => now()->addDays(1)->setTime(14, 0)->format('Y-m-d\TH:i:s'),
            'end_time' => now()->addDays(1)->setTime(13, 0)->format('Y-m-d\TH:i:s'),
            'status' => 'booked',
        ]);
        $resTime->assertStatus(422);

        // 2. Outside staff schedule hours is rejected
        $resHours = $this->actingAs($this->adminStaff, 'staff')->putJson("/calendar/appointments/{$appt->id}", [
            'start_time' => now()->addDays(1)->setTime(22, 0)->format('Y-m-d\TH:i:s'),
            'end_time' => now()->addDays(1)->setTime(23, 0)->format('Y-m-d\TH:i:s'),
            'status' => 'booked',
        ]);
        $resHours->assertStatus(422);
    }

    /**
     * 10. Frontend calendar view contains all required status options and safe response parser.
     */
    public function test_frontend_status_dropdown_and_safe_response_parser(): void
    {
        $res = $this->actingAs($this->adminStaff, 'staff')->get(route('calendar.index'));
        $res->assertStatus(200);

        // Exactly the 4 required status options are present in the dropdown
        $res->assertSee('<option value="booked">Booked</option>', false);
        $res->assertSee('<option value="completed">Completed</option>', false);
        $res->assertSee('<option value="cancelled">Canceled</option>', false);
        $res->assertSee('<option value="no_show">No Show</option>', false);

        // Pending and Confirmed are NOT present in the status options
        $res->assertDontSee('<option value="confirmed">', false);
        $res->assertDontSee('<option value="pending">', false);

        // Safe single-read response parser is in place
        $res->assertSee('parseResponsePayload(response)', false);
        $res->assertSee('extractErrorMessage(parsed', false);
    }
}
