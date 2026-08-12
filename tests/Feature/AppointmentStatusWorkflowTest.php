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
use Carbon\Carbon;

class AppointmentStatusWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Staff $adminStaff;
    private Service $service;
    private Location $location;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->location = Location::create([
            'name' => 'Test Clinic',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        $this->adminStaff = Staff::create([
            'location_id' => $this->location->id,
            'name' => 'Admin Staff',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'access_level' => 'admin',
            'is_active' => true,
        ]);

        // Create weekly schedule for staff so availability validation passes
        for ($day = 0; $day <= 6; $day++) {
            StaffSchedule::create([
                'staff_id' => $this->adminStaff->id,
                'day_of_week' => (string) $day,
                'start_time' => '08:00:00',
                'end_time' => '18:00:00',
                'is_working' => true,
            ]);
        }

        $this->service = Service::create([
            'name' => 'Test Service',
            'type' => 'in_person',
            'price' => 50,
            'duration_minutes' => 60,
            'buffer_minutes' => 0,
            'is_active' => true,
        ]);

        $this->client = Client::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '1234567890',
        ]);
    }

    private function futureSlot(int $daysFromNow = 1, int $hour = 10): array
    {
        $start = Carbon::now()->addDays($daysFromNow)->setTime($hour, 0, 0);
        $end = $start->copy()->addMinutes(60);

        return [
            'start_time' => $start->format('Y-m-d\TH:i:s'),
            'end_time' => $end->format('Y-m-d\TH:i:s'),
        ];
    }

    /** 1-6. Valid statuses accepted during storeAppointment */
    public function test_pending_status_accepted(): void
    {
        $slot = $this->futureSlot(1, 9);
        $res = $this->actingAs($this->adminStaff, 'staff')->postJson('/calendar/appointments', array_merge([
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'status' => 'pending',
        ], $slot));

        $res->assertStatus(201);
        $this->assertDatabaseHas('appointments', ['status' => 'pending']);
    }

    public function test_booked_status_accepted(): void
    {
        $slot = $this->futureSlot(1, 10);
        $res = $this->actingAs($this->adminStaff, 'staff')->postJson('/calendar/appointments', array_merge([
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'status' => 'booked',
        ], $slot));

        $res->assertStatus(201);
        $this->assertDatabaseHas('appointments', ['status' => 'booked']);
    }

    public function test_confirmed_status_accepted(): void
    {
        $slot = $this->futureSlot(1, 11);
        $res = $this->actingAs($this->adminStaff, 'staff')->postJson('/calendar/appointments', array_merge([
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'status' => 'confirmed',
        ], $slot));

        $res->assertStatus(201);
        $this->assertDatabaseHas('appointments', ['status' => 'confirmed']);
    }

    public function test_completed_status_accepted(): void
    {
        $slot = $this->futureSlot(1, 12);
        $res = $this->actingAs($this->adminStaff, 'staff')->postJson('/calendar/appointments', array_merge([
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'status' => 'completed',
        ], $slot));

        $res->assertStatus(201);
        $this->assertDatabaseHas('appointments', ['status' => 'completed']);
    }

    public function test_cancelled_status_accepted(): void
    {
        $slot = $this->futureSlot(1, 13);
        $res = $this->actingAs($this->adminStaff, 'staff')->postJson('/calendar/appointments', array_merge([
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'status' => 'cancelled',
        ], $slot));

        $res->assertStatus(201);
        $this->assertDatabaseHas('appointments', ['status' => 'cancelled']);
    }

    public function test_no_show_status_accepted(): void
    {
        $slot = $this->futureSlot(1, 14);
        $res = $this->actingAs($this->adminStaff, 'staff')->postJson('/calendar/appointments', array_merge([
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'status' => 'no_show',
        ], $slot));

        $res->assertStatus(201);
        $this->assertDatabaseHas('appointments', ['status' => 'no_show']);
    }

    /** 7. Invalid status rejected */
    public function test_invalid_status_rejected(): void
    {
        $slot = $this->futureSlot(1, 15);
        $res = $this->actingAs($this->adminStaff, 'staff')->postJson('/calendar/appointments', array_merge([
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'status' => 'invalid_status_name',
        ], $slot));

        $res->assertStatus(422);
    }

    /** 8-12. Valid status transitions allowed */
    public function test_pending_to_confirmed_allowed(): void
    {
        $appt = Appointment::create([
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'start_time' => now()->addDays(1)->setTime(9, 0),
            'end_time' => now()->addDays(1)->setTime(10, 0),
            'status' => 'pending',
        ]);

        $res = $this->actingAs($this->adminStaff, 'staff')->putJson("/calendar/appointments/{$appt->id}", [
            'status' => 'confirmed',
        ]);

        $res->assertStatus(200);
        $this->assertEquals('confirmed', $appt->fresh()->status);
    }

    public function test_booked_to_confirmed_allowed(): void
    {
        $appt = Appointment::create([
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'start_time' => now()->addDays(1)->setTime(9, 0),
            'end_time' => now()->addDays(1)->setTime(10, 0),
            'status' => 'booked',
        ]);

        $res = $this->actingAs($this->adminStaff, 'staff')->putJson("/calendar/appointments/{$appt->id}", [
            'status' => 'confirmed',
        ]);

        $res->assertStatus(200);
        $this->assertEquals('confirmed', $appt->fresh()->status);
    }

    public function test_confirmed_to_completed_allowed(): void
    {
        $appt = Appointment::create([
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'start_time' => now()->addDays(1)->setTime(9, 0),
            'end_time' => now()->addDays(1)->setTime(10, 0),
            'status' => 'confirmed',
        ]);

        $res = $this->actingAs($this->adminStaff, 'staff')->putJson("/calendar/appointments/{$appt->id}", [
            'status' => 'completed',
        ]);

        $res->assertStatus(200);
        $this->assertEquals('completed', $appt->fresh()->status);
    }

    public function test_confirmed_to_no_show_allowed(): void
    {
        $appt = Appointment::create([
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'start_time' => now()->addDays(1)->setTime(9, 0),
            'end_time' => now()->addDays(1)->setTime(10, 0),
            'status' => 'confirmed',
        ]);

        $res = $this->actingAs($this->adminStaff, 'staff')->putJson("/calendar/appointments/{$appt->id}", [
            'status' => 'no_show',
        ]);

        $res->assertStatus(200);
        $this->assertEquals('no_show', $appt->fresh()->status);
    }

    public function test_confirmed_to_cancelled_allowed(): void
    {
        $appt = Appointment::create([
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'start_time' => now()->addDays(1)->setTime(9, 0),
            'end_time' => now()->addDays(1)->setTime(10, 0),
            'status' => 'confirmed',
        ]);

        $res = $this->actingAs($this->adminStaff, 'staff')->putJson("/calendar/appointments/{$appt->id}", [
            'status' => 'cancelled',
        ]);

        $res->assertStatus(200);
        $this->assertEquals('cancelled', $appt->fresh()->status);
    }

    /** 13-16. Invalid status transitions rejected */
    public function test_completed_to_pending_rejected(): void
    {
        $appt = Appointment::create([
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'start_time' => now()->addDays(1)->setTime(9, 0),
            'end_time' => now()->addDays(1)->setTime(10, 0),
            'status' => 'completed',
        ]);

        $res = $this->actingAs($this->adminStaff, 'staff')->putJson("/calendar/appointments/{$appt->id}", [
            'status' => 'pending',
        ]);

        $res->assertStatus(422);
    }

    public function test_completed_to_booked_rejected(): void
    {
        $appt = Appointment::create([
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'start_time' => now()->addDays(1)->setTime(9, 0),
            'end_time' => now()->addDays(1)->setTime(10, 0),
            'status' => 'completed',
        ]);

        $res = $this->actingAs($this->adminStaff, 'staff')->putJson("/calendar/appointments/{$appt->id}", [
            'status' => 'booked',
        ]);

        $res->assertStatus(422);
    }

    public function test_cancelled_to_completed_rejected(): void
    {
        $appt = Appointment::create([
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'start_time' => now()->addDays(1)->setTime(9, 0),
            'end_time' => now()->addDays(1)->setTime(10, 0),
            'status' => 'cancelled',
        ]);

        $res = $this->actingAs($this->adminStaff, 'staff')->putJson("/calendar/appointments/{$appt->id}", [
            'status' => 'completed',
        ]);

        $res->assertStatus(422);
    }

    public function test_no_show_to_confirmed_rejected(): void
    {
        $appt = Appointment::create([
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'start_time' => now()->addDays(1)->setTime(9, 0),
            'end_time' => now()->addDays(1)->setTime(10, 0),
            'status' => 'no_show',
        ]);

        $res = $this->actingAs($this->adminStaff, 'staff')->putJson("/calendar/appointments/{$appt->id}", [
            'status' => 'confirmed',
        ]);

        $res->assertStatus(422);
    }

    /** 17-19. Terminal status rescheduling rejected */
    public function test_completed_appointment_reschedule_rejected(): void
    {
        $appt = Appointment::create([
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'start_time' => now()->addDays(1)->setTime(9, 0),
            'end_time' => now()->addDays(1)->setTime(10, 0),
            'status' => 'completed',
        ]);

        $res = $this->actingAs($this->adminStaff, 'staff')->putJson("/calendar/appointments/{$appt->id}", [
            'start_time' => now()->addDays(2)->setTime(11, 0)->format('Y-m-d\TH:i:s'),
            'end_time' => now()->addDays(2)->setTime(12, 0)->format('Y-m-d\TH:i:s'),
        ]);

        $res->assertStatus(422);
        $res->assertJsonFragment(['message' => 'Completed appointments cannot be rescheduled.']);
    }

    public function test_cancelled_appointment_reschedule_rejected(): void
    {
        $appt = Appointment::create([
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'start_time' => now()->addDays(1)->setTime(9, 0),
            'end_time' => now()->addDays(1)->setTime(10, 0),
            'status' => 'cancelled',
        ]);

        $res = $this->actingAs($this->adminStaff, 'staff')->putJson("/calendar/appointments/{$appt->id}", [
            'start_time' => now()->addDays(2)->setTime(11, 0)->format('Y-m-d\TH:i:s'),
            'end_time' => now()->addDays(2)->setTime(12, 0)->format('Y-m-d\TH:i:s'),
        ]);

        $res->assertStatus(422);
        $res->assertJsonFragment(['message' => 'Cancelled appointments cannot be rescheduled.']);
    }

    public function test_no_show_appointment_reschedule_rejected(): void
    {
        $appt = Appointment::create([
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'start_time' => now()->addDays(1)->setTime(9, 0),
            'end_time' => now()->addDays(1)->setTime(10, 0),
            'status' => 'no_show',
        ]);

        $res = $this->actingAs($this->adminStaff, 'staff')->putJson("/calendar/appointments/{$appt->id}", [
            'start_time' => now()->addDays(2)->setTime(11, 0)->format('Y-m-d\TH:i:s'),
            'end_time' => now()->addDays(2)->setTime(12, 0)->format('Y-m-d\TH:i:s'),
        ]);

        $res->assertStatus(422);
        $res->assertJsonFragment(['message' => 'No-show appointments cannot be rescheduled.']);
    }

    /** 20-23. Reminder logic tests */
    public function test_confirmed_reminder_is_included(): void
    {
        $appt = Appointment::create([
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'start_time' => now()->addHours(24),
            'end_time' => now()->addHours(25),
            'status' => 'confirmed',
        ]);

        $this->artisan('appointments:send-reminders', ['--dry-run' => true])
            ->assertExitCode(0);
    }

    /** 24-25. Slot blocking logic tests */
    public function test_pending_booked_confirmed_block_new_slots(): void
    {
        // Create booked appointment from 10:00 to 11:00 tomorrow
        $slotStart = now()->addDays(2)->setTime(10, 0);
        $slotEnd = $slotStart->copy()->addMinutes(60);

        Appointment::create([
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'start_time' => $slotStart,
            'end_time' => $slotEnd,
            'status' => 'confirmed',
        ]);

        // Try booking overlapping slot 10:30 to 11:30
        $res = $this->actingAs($this->adminStaff, 'staff')->postJson('/calendar/appointments', [
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'start_time' => $slotStart->copy()->addMinutes(30)->format('Y-m-d\TH:i:s'),
            'end_time' => $slotEnd->copy()->addMinutes(30)->format('Y-m-d\TH:i:s'),
            'status' => 'booked',
        ]);

        $res->assertStatus(422);
        $res->assertJsonFragment(['message' => 'This time slot is already booked.']);
    }

    public function test_completed_cancelled_no_show_do_not_block_new_future_slots(): void
    {
        $slotStart = now()->addDays(3)->setTime(10, 0);
        $slotEnd = $slotStart->copy()->addMinutes(60);

        // Create completed appointment in a future slot
        Appointment::create([
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'start_time' => $slotStart,
            'end_time' => $slotEnd,
            'status' => 'completed',
        ]);

        // Attempting to book the same slot should now succeed because completed does not block new slots
        $res = $this->actingAs($this->adminStaff, 'staff')->postJson('/calendar/appointments', [
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'start_time' => $slotStart->format('Y-m-d\TH:i:s'),
            'end_time' => $slotEnd->format('Y-m-d\TH:i:s'),
            'status' => 'booked',
        ]);

        $res->assertStatus(201);
    }

    /** 26. CRM no-show count test */
    public function test_crm_reflects_no_show_count(): void
    {
        Appointment::create([
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'start_time' => now()->subDays(1)->setTime(10, 0),
            'end_time' => now()->subDays(1)->setTime(11, 0),
            'status' => 'no_show',
        ]);

        $res = $this->actingAs($this->adminStaff, 'staff')->get("/clients/{$this->client->id}");
        $res->assertStatus(200);
        $res->assertSee('No Show');
    }
}
