<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Staff;
use App\Models\Location;
use App\Models\Service;
use App\Models\Client;
use App\Models\Appointment;
use App\Models\StaffSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StaffScheduleRequirementsTest extends TestCase
{
    use RefreshDatabase;

    protected Staff $adminStaff;
    protected Staff $staff;
    protected Location $location;
    protected Service $service1h;
    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->location = Location::create([
            'name' => 'Main Clinic',
            'is_active' => true,
            'address' => '100 Medical Way',
        ]);

        $this->adminStaff = Staff::create([
            'name' => 'Admin User',
            'email' => 'admin_sched@example.com',
            'role' => 'admin',
            'access_level' => 'admin',
            'is_active' => true,
            'password' => bcrypt('password123'),
            'location_id' => $this->location->id,
        ]);

        $this->staff = Staff::create([
            'name' => 'Dr. Monday Specialist',
            'email' => 'dr_monday@example.com',
            'role' => 'staff',
            'access_level' => 'staff',
            'is_active' => true,
            'password' => null,
            'location_id' => $this->location->id,
        ]);

        $this->service1h = Service::create([
            'name' => 'Consultation 60m',
            'duration_minutes' => 60,
            'price' => 120,
            'color' => '#FF5733',
            'is_active' => true,
        ]);

        $this->client = Client::create([
            'name' => 'John Patient',
            'email' => 'john_patient@example.com',
            'phone' => '5551234567',
        ]);

        // Staff schedule: Monday (2026-09-14) 09:00 - 17:00
        StaffSchedule::create([
            'staff_id' => $this->staff->id,
            'working_date' => '2026-09-14',
            'day_of_week' => 0, // Monday
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'is_working' => true,
            'recurrence_type' => 'one_time',
        ]);
    }

    /**
     * Test A: Staff has schedule Monday 09:00-17:00, Appointment Monday 10:00-11:00 => ALLOW
     */
    public function test_a_create_appointment_within_schedule_is_allowed(): void
    {
        $res = $this->actingAs($this->adminStaff, 'staff')
            ->postJson('/calendar/appointments', [
                'staff_id' => $this->staff->id,
                'client_id' => $this->client->id,
                'service_id' => $this->service1h->id,
                'location_id' => $this->location->id,
                'start_time' => '2026-09-14 10:00:00',
                'end_time' => '2026-09-14 11:00:00',
                'status' => 'booked',
            ]);

        $res->assertStatus(201);
        $res->assertJsonPath('success', true);
        $this->assertDatabaseHas('appointments', [
            'staff_id' => $this->staff->id,
            'start_time' => '2026-09-14 10:00:00',
            'end_time' => '2026-09-14 11:00:00',
        ]);
    }

    public function test_a_update_appointment_within_schedule_is_allowed(): void
    {
        $appt = Appointment::create([
            'staff_id' => $this->staff->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service1h->id,
            'location_id' => $this->location->id,
            'start_time' => '2026-09-14 09:00:00',
            'end_time' => '2026-09-14 10:00:00',
            'status' => 'booked',
        ]);

        $res = $this->actingAs($this->adminStaff, 'staff')
            ->putJson("/calendar/appointments/{$appt->id}", [
                'staff_id' => $this->staff->id,
                'service_id' => $this->service1h->id,
                'start_time' => '2026-09-14 10:00:00',
                'end_time' => '2026-09-14 11:00:00',
            ]);

        $res->assertStatus(200);
        $res->assertJsonPath('success', true);
    }

    /**
     * Test B: Staff has no schedule on selected date => BLOCK
     */
    public function test_b_create_appointment_on_unscheduled_date_is_blocked(): void
    {
        // 2026-09-16 is Wednesday (no schedule for this staff)
        $res = $this->actingAs($this->adminStaff, 'staff')
            ->postJson('/calendar/appointments', [
                'staff_id' => $this->staff->id,
                'client_id' => $this->client->id,
                'service_id' => $this->service1h->id,
                'location_id' => $this->location->id,
                'start_time' => '2026-09-16 10:00:00',
                'end_time' => '2026-09-16 11:00:00',
                'status' => 'booked',
            ]);

        $res->assertStatus(422);
        $res->assertJson([
            'success' => false,
            'message' => 'Selected staff member is not scheduled for this date and time.'
        ]);
    }

    public function test_b_update_appointment_to_unscheduled_date_is_blocked(): void
    {
        $appt = Appointment::create([
            'staff_id' => $this->staff->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service1h->id,
            'location_id' => $this->location->id,
            'start_time' => '2026-09-14 10:00:00',
            'end_time' => '2026-09-14 11:00:00',
            'status' => 'booked',
        ]);

        $res = $this->actingAs($this->adminStaff, 'staff')
            ->putJson("/calendar/appointments/{$appt->id}", [
                'start_time' => '2026-09-16 10:00:00',
                'end_time' => '2026-09-16 11:00:00',
            ]);

        $res->assertStatus(422);
        $res->assertJson([
            'success' => false,
            'message' => 'Selected staff member is not scheduled for this date and time.'
        ]);
    }

    /**
     * Test C: Staff has schedule on Monday but appointment selected Tuesday => BLOCK
     */
    public function test_c_create_appointment_on_different_date_is_blocked(): void
    {
        // 2026-09-15 is Tuesday
        $res = $this->actingAs($this->adminStaff, 'staff')
            ->postJson('/calendar/appointments', [
                'staff_id' => $this->staff->id,
                'client_id' => $this->client->id,
                'service_id' => $this->service1h->id,
                'location_id' => $this->location->id,
                'start_time' => '2026-09-15 10:00:00',
                'end_time' => '2026-09-15 11:00:00',
                'status' => 'booked',
            ]);

        $res->assertStatus(422);
        $res->assertJson([
            'success' => false,
            'message' => 'Selected staff member is not scheduled for this date and time.'
        ]);
    }

    public function test_c_update_appointment_to_tuesday_is_blocked(): void
    {
        $appt = Appointment::create([
            'staff_id' => $this->staff->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service1h->id,
            'location_id' => $this->location->id,
            'start_time' => '2026-09-14 10:00:00',
            'end_time' => '2026-09-14 11:00:00',
            'status' => 'booked',
        ]);

        $res = $this->actingAs($this->adminStaff, 'staff')
            ->putJson("/calendar/appointments/{$appt->id}", [
                'start_time' => '2026-09-15 10:00:00',
                'end_time' => '2026-09-15 11:00:00',
            ]);

        $res->assertStatus(422);
        $res->assertJson([
            'success' => false,
            'message' => 'Selected staff member is not scheduled for this date and time.'
        ]);
    }

    /**
     * Test D: Appointment before scheduled start (Schedule 09:00-17:00, Appt 08:00-09:00) => BLOCK
     */
    public function test_d_create_appointment_before_scheduled_start_is_blocked(): void
    {
        $res = $this->actingAs($this->adminStaff, 'staff')
            ->postJson('/calendar/appointments', [
                'staff_id' => $this->staff->id,
                'client_id' => $this->client->id,
                'service_id' => $this->service1h->id,
                'location_id' => $this->location->id,
                'start_time' => '2026-09-14 08:00:00',
                'end_time' => '2026-09-14 09:00:00',
                'status' => 'booked',
            ]);

        $res->assertStatus(422);
        $res->assertJson([
            'success' => false,
            'message' => 'Selected staff member is not available during this time.'
        ]);
    }

    public function test_d_update_appointment_before_scheduled_start_is_blocked(): void
    {
        $appt = Appointment::create([
            'staff_id' => $this->staff->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service1h->id,
            'location_id' => $this->location->id,
            'start_time' => '2026-09-14 10:00:00',
            'end_time' => '2026-09-14 11:00:00',
            'status' => 'booked',
        ]);

        $res = $this->actingAs($this->adminStaff, 'staff')
            ->putJson("/calendar/appointments/{$appt->id}", [
                'start_time' => '2026-09-14 08:00:00',
                'end_time' => '2026-09-14 09:00:00',
            ]);

        $res->assertStatus(422);
        $res->assertJson([
            'success' => false,
            'message' => 'Selected staff member is not available during this time.'
        ]);
    }

    /**
     * Test E: Appointment after scheduled end (Schedule 09:00-17:00, Appt 17:00-18:00) => BLOCK
     */
    public function test_e_create_appointment_after_scheduled_end_is_blocked(): void
    {
        $res = $this->actingAs($this->adminStaff, 'staff')
            ->postJson('/calendar/appointments', [
                'staff_id' => $this->staff->id,
                'client_id' => $this->client->id,
                'service_id' => $this->service1h->id,
                'location_id' => $this->location->id,
                'start_time' => '2026-09-14 17:00:00',
                'end_time' => '2026-09-14 18:00:00',
                'status' => 'booked',
            ]);

        $res->assertStatus(422);
        $res->assertJson([
            'success' => false,
            'message' => 'Selected staff member is not available during this time.'
        ]);
    }

    public function test_e_update_appointment_after_scheduled_end_is_blocked(): void
    {
        $appt = Appointment::create([
            'staff_id' => $this->staff->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service1h->id,
            'location_id' => $this->location->id,
            'start_time' => '2026-09-14 10:00:00',
            'end_time' => '2026-09-14 11:00:00',
            'status' => 'booked',
        ]);

        $res = $this->actingAs($this->adminStaff, 'staff')
            ->putJson("/calendar/appointments/{$appt->id}", [
                'start_time' => '2026-09-14 17:00:00',
                'end_time' => '2026-09-14 18:00:00',
            ]);

        $res->assertStatus(422);
        $res->assertJson([
            'success' => false,
            'message' => 'Selected staff member is not available during this time.'
        ]);
    }

    /**
     * Test F: Appointment crossing schedule boundary (Schedule 09:00-17:00, Appt 16:30-17:30) => BLOCK
     */
    public function test_f_create_appointment_crossing_boundary_is_blocked(): void
    {
        $res = $this->actingAs($this->adminStaff, 'staff')
            ->postJson('/calendar/appointments', [
                'staff_id' => $this->staff->id,
                'client_id' => $this->client->id,
                'service_id' => $this->service1h->id,
                'location_id' => $this->location->id,
                'start_time' => '2026-09-14 16:30:00',
                'end_time' => '2026-09-14 17:30:00',
                'status' => 'booked',
            ]);

        $res->assertStatus(422);
        $res->assertJson([
            'success' => false,
            'message' => 'Selected staff member is not available during this time.'
        ]);
    }

    public function test_f_update_appointment_crossing_boundary_is_blocked(): void
    {
        $appt = Appointment::create([
            'staff_id' => $this->staff->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service1h->id,
            'location_id' => $this->location->id,
            'start_time' => '2026-09-14 10:00:00',
            'end_time' => '2026-09-14 11:00:00',
            'status' => 'booked',
        ]);

        $res = $this->actingAs($this->adminStaff, 'staff')
            ->putJson("/calendar/appointments/{$appt->id}", [
                'start_time' => '2026-09-14 16:30:00',
                'end_time' => '2026-09-14 17:30:00',
            ]);

        $res->assertStatus(422);
        $res->assertJson([
            'success' => false,
            'message' => 'Selected staff member is not available during this time.'
        ]);
    }

    /**
     * Test G: Appointment exactly inside: 09:00-17:00 (Schedule 09:00-17:00) => ALLOW
     */
    public function test_g_create_appointment_exact_boundary_equality_is_allowed(): void
    {
        $res = $this->actingAs($this->adminStaff, 'staff')
            ->postJson('/calendar/appointments', [
                'staff_id' => $this->staff->id,
                'client_id' => $this->client->id,
                'service_id' => $this->service1h->id,
                'location_id' => $this->location->id,
                'start_time' => '2026-09-14 09:00:00',
                'end_time' => '2026-09-14 17:00:00',
                'status' => 'booked',
            ]);

        $res->assertStatus(201);
        $res->assertJsonPath('success', true);
    }

    public function test_g_update_appointment_exact_boundary_equality_is_allowed(): void
    {
        $appt = Appointment::create([
            'staff_id' => $this->staff->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service1h->id,
            'location_id' => $this->location->id,
            'start_time' => '2026-09-14 10:00:00',
            'end_time' => '2026-09-14 11:00:00',
            'status' => 'booked',
        ]);

        $res = $this->actingAs($this->adminStaff, 'staff')
            ->putJson("/calendar/appointments/{$appt->id}", [
                'start_time' => '2026-09-14 09:00:00',
                'end_time' => '2026-09-14 17:00:00',
            ]);

        $res->assertStatus(200);
        $res->assertJsonPath('success', true);
    }

    /**
     * Test H: Existing appointment overlap => BLOCK because of appointment conflict
     */
    public function test_h_create_appointment_overlapping_existing_appointment_is_blocked(): void
    {
        Appointment::create([
            'staff_id' => $this->staff->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service1h->id,
            'location_id' => $this->location->id,
            'start_time' => '2026-09-14 10:00:00',
            'end_time' => '2026-09-14 11:00:00',
            'status' => 'booked',
        ]);

        $res = $this->actingAs($this->adminStaff, 'staff')
            ->postJson('/calendar/appointments', [
                'staff_id' => $this->staff->id,
                'client_id' => $this->client->id,
                'service_id' => $this->service1h->id,
                'location_id' => $this->location->id,
                'start_time' => '2026-09-14 10:00:00',
                'end_time' => '2026-09-14 11:00:00',
                'status' => 'booked',
            ]);

        $res->assertStatus(422);
        $res->assertJson([
            'success' => false,
            'message' => 'This time slot is already booked.'
        ]);
    }

    public function test_h_update_appointment_to_overlapping_slot_is_blocked(): void
    {
        $existing = Appointment::create([
            'staff_id' => $this->staff->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service1h->id,
            'location_id' => $this->location->id,
            'start_time' => '2026-09-14 10:00:00',
            'end_time' => '2026-09-14 11:00:00',
            'status' => 'booked',
        ]);

        $second = Appointment::create([
            'staff_id' => $this->staff->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service1h->id,
            'location_id' => $this->location->id,
            'start_time' => '2026-09-14 14:00:00',
            'end_time' => '2026-09-14 15:00:00',
            'status' => 'booked',
        ]);

        $res = $this->actingAs($this->adminStaff, 'staff')
            ->putJson("/calendar/appointments/{$second->id}", [
                'start_time' => '2026-09-14 10:00:00',
                'end_time' => '2026-09-14 11:00:00',
            ]);

        $res->assertStatus(422);
        $res->assertJson([
            'success' => false,
            'message' => 'This time slot is already booked.'
        ]);
    }

    /**
     * Test I: Valid scheduled appointment with no overlap => ALLOW
     */
    public function test_i_valid_scheduled_appointment_without_overlap_is_allowed(): void
    {
        Appointment::create([
            'staff_id' => $this->staff->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service1h->id,
            'location_id' => $this->location->id,
            'start_time' => '2026-09-14 10:00:00',
            'end_time' => '2026-09-14 11:00:00',
            'status' => 'booked',
        ]);

        $res = $this->actingAs($this->adminStaff, 'staff')
            ->postJson('/calendar/appointments', [
                'staff_id' => $this->staff->id,
                'client_id' => $this->client->id,
                'service_id' => $this->service1h->id,
                'location_id' => $this->location->id,
                'start_time' => '2026-09-14 11:00:00',
                'end_time' => '2026-09-14 12:00:00',
                'status' => 'booked',
            ]);

        $res->assertStatus(201);
        $res->assertJsonPath('success', true);
    }
}
