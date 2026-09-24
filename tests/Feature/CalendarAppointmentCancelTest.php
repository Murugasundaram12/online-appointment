<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Location;
use App\Models\Service;
use App\Models\Staff;
use App\Models\StaffSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CalendarAppointmentCancelTest extends TestCase
{
    use RefreshDatabase;

    private Staff $adminStaff;
    private Service $serviceWithColor;
    private Service $serviceWithoutColor;
    private Location $location;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->location = Location::create([
            'name' => 'Downtown Clinic',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        $this->adminStaff = Staff::create([
            'location_id' => $this->location->id,
            'name' => 'Dr. Jane Admin',
            'email' => 'jane_admin@example.com',
            'password' => Hash::make('password123'),
            'access_level' => 'admin',
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
        }

        // Service with orange service color
        $this->serviceWithColor = Service::create([
            'name' => 'Massage Therapy Treatment',
            'type' => 'in_person',
            'price' => 95,
            'duration_minutes' => 60,
            'buffer_minutes' => 0,
            'color' => '#ffac38',
            'is_active' => true,
        ]);

        // Service without custom color
        $this->serviceWithoutColor = Service::create([
            'name' => 'Standard Consultation',
            'type' => 'in_person',
            'price' => 50,
            'duration_minutes' => 30,
            'buffer_minutes' => 0,
            'color' => null,
            'is_active' => true,
        ]);

        $this->client = Client::create([
            'first_name' => 'Mark',
            'last_name' => 'Smith',
            'name' => 'Mark Smith',
            'email' => 'mark@example.com',
            'phone' => '5551234567',
        ]);
    }

    /**
     * 1. Cancelled appointment requires cancellation reason.
     */
    public function test_cancelled_appointment_requires_cancellation_reason(): void
    {
        $appt = Appointment::create([
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->serviceWithColor->id,
            'client_id' => $this->client->id,
            'start_time' => now()->addDays(1)->setTime(10, 0),
            'end_time' => now()->addDays(1)->setTime(11, 0),
            'status' => 'booked',
        ]);

        // Empty cancellation reason
        $res = $this->actingAs($this->adminStaff, 'staff')->putJson("/calendar/appointments/{$appt->id}", [
            'status' => 'cancelled',
            'cancellation_reason' => '',
        ]);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['cancellation_reason']);
        $this->assertEquals('booked', $appt->fresh()->status);

        // Missing cancellation reason
        $resMissing = $this->actingAs($this->adminStaff, 'staff')->putJson("/calendar/appointments/{$appt->id}", [
            'status' => 'cancelled',
        ]);

        $resMissing->assertStatus(422);
        $resMissing->assertJsonValidationErrors(['cancellation_reason']);
        $this->assertEquals('booked', $appt->fresh()->status);
    }

    /**
     * 2. Valid cancellation succeeds without any validation error.
     */
    public function test_valid_cancellation_succeeds_without_validation_error(): void
    {
        $appt = Appointment::create([
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->serviceWithColor->id,
            'client_id' => $this->client->id,
            'start_time' => now()->addDays(1)->setTime(10, 0),
            'end_time' => now()->addDays(1)->setTime(11, 0),
            'status' => 'booked',
        ]);

        $res = $this->actingAs($this->adminStaff, 'staff')->putJson("/calendar/appointments/{$appt->id}", [
            'status' => 'cancelled',
            'cancellation_reason' => 'Patient has urgent work meeting',
        ]);

        $res->assertStatus(200);
        $res->assertJson([
            'success' => true,
        ]);
        $this->assertArrayNotHasKey('errors', $res->json());

        $fresh = $appt->fresh();
        $this->assertEquals('cancelled', $fresh->status);
        $this->assertEquals('Patient has urgent work meeting', $fresh->cancellation_reason);
    }

    /**
     * 3. Cancelled appointment keeps service color (does NOT change to red #f64e60).
     */
    public function test_cancelled_appointment_keeps_service_color(): void
    {
        $appt = Appointment::create([
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->serviceWithColor->id,
            'client_id' => $this->client->id,
            'start_time' => now()->addDays(1)->setTime(10, 0),
            'end_time' => now()->addDays(1)->setTime(11, 0),
            'status' => 'booked',
        ]);

        // Cancellation update
        $res = $this->actingAs($this->adminStaff, 'staff')->putJson("/calendar/appointments/{$appt->id}", [
            'status' => 'cancelled',
            'cancellation_reason' => 'Schedule conflict',
        ]);

        $res->assertStatus(200);
        $apptPayload = $res->json('appointment');

        // Appointment payload must preserve the orange service color #ffac38, NOT #f64e60 (red)
        $this->assertEquals('#ffac38', $apptPayload['color']);
        $this->assertEquals('#ffac38', $apptPayload['backgroundColor']);
        $this->assertEquals('#ffac38', $apptPayload['borderColor']);
        $this->assertEquals('#ffac38', $apptPayload['serviceColor']);
        $this->assertNotEquals('#f64e60', $apptPayload['color']);

        // Check getEvents API
        $dateStr = now()->addDays(1)->format('Y-m-d');
        $eventsRes = $this->actingAs($this->adminStaff, 'staff')->getJson("/calendar/events?start={$dateStr}&end={$dateStr}");
        $eventsRes->assertStatus(200);
        $eventItem = collect($eventsRes->json())->firstWhere('id', $appt->id);
        $this->assertNotNull($eventItem);
        $this->assertEquals('#ffac38', $eventItem['color']);
        $this->assertEquals('#ffac38', $eventItem['serviceColor']);
        $this->assertNotEquals('#f64e60', $eventItem['color']);

        // Check Month view endpoint preserves service color
        $monthStr = now()->addDays(1)->format('Y-m');
        $monthRes = $this->actingAs($this->adminStaff, 'staff')->get("/calendar?view=month&month={$monthStr}");
        $monthRes->assertStatus(200);
        $monthRes->assertSee('#ffac38');
    }

    /**
     * 3b. If service has no color, use existing fallback color #3699ff, NOT status color #f64e60.
     */
    public function test_service_without_color_uses_fallback_color_when_cancelled(): void
    {
        $appt = Appointment::create([
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->serviceWithoutColor->id,
            'client_id' => $this->client->id,
            'start_time' => now()->addDays(1)->setTime(14, 0),
            'end_time' => now()->addDays(1)->setTime(14, 30),
            'status' => 'booked',
        ]);

        $res = $this->actingAs($this->adminStaff, 'staff')->putJson("/calendar/appointments/{$appt->id}", [
            'status' => 'cancelled',
            'cancellation_reason' => 'Doctor unavailable',
        ]);

        $res->assertStatus(200);
        $apptPayload = $res->json('appointment');
        $this->assertEquals('#3699ff', $apptPayload['color']);
        $this->assertNotEquals('#f64e60', $apptPayload['color']);
    }

    /**
     * 4. Booked, completed, cancelled status badge still displays correctly.
     */
    public function test_status_badges_display_correctly_for_all_statuses(): void
    {
        // 1. Booked
        $apptBooked = Appointment::create([
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->serviceWithColor->id,
            'client_id' => $this->client->id,
            'start_time' => now()->addDays(1)->setTime(8, 0),
            'end_time' => now()->addDays(1)->setTime(9, 0),
            'status' => 'booked',
        ]);

        // 2. Completed
        $apptCompleted = Appointment::create([
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->serviceWithColor->id,
            'client_id' => $this->client->id,
            'start_time' => now()->addDays(1)->setTime(9, 0),
            'end_time' => now()->addDays(1)->setTime(10, 0),
            'status' => 'completed',
        ]);

        // 3. Cancelled
        $apptCancelled = Appointment::create([
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->serviceWithColor->id,
            'client_id' => $this->client->id,
            'start_time' => now()->addDays(1)->setTime(11, 0),
            'end_time' => now()->addDays(1)->setTime(12, 0),
            'status' => 'cancelled',
            'cancellation_reason' => 'Patient ill',
        ]);

        $dateStr = now()->addDays(1)->format('Y-m-d');
        $res = $this->actingAs($this->adminStaff, 'staff')->getJson("/calendar/events?start={$dateStr}&end={$dateStr}");
        $res->assertStatus(200);
        $events = collect($res->json());

        $evBooked = $events->firstWhere('id', $apptBooked->id);
        $this->assertEquals('booked', $evBooked['status']);

        $evCompleted = $events->firstWhere('id', $apptCompleted->id);
        $this->assertEquals('completed', $evCompleted['status']);

        $evCancelled = $events->firstWhere('id', $apptCancelled->id);
        $this->assertEquals('cancelled', $evCancelled['status']);
    }
}
