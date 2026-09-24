<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Location;
use App\Models\Service;
use App\Models\Staff;
use App\Models\StaffSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CalendarSlotAvailabilityAndFastSaveTest extends TestCase
{
    use RefreshDatabase;

    private Staff $adminStaff;
    private Staff $staff;
    private Location $location;
    private Service $service;
    private Client $client;
    private Carbon $monday;

    protected function setUp(): void
    {
        parent::setUp();

        $this->location = Location::create([
            'name' => 'Main Health Center',
            'is_active' => true,
        ]);

        $this->adminStaff = Staff::create([
            'name' => 'Admin User',
            'email' => 'admin_sched@example.com',
            'role' => 'admin',
            'access_level' => 'admin',
            'is_active' => true,
            'password' => Hash::make('password123'),
            'location_id' => $this->location->id,
        ]);

        $this->staff = Staff::create([
            'name' => 'Dr. Robert Specialist',
            'email' => 'robert@example.com',
            'password' => Hash::make('Password123'),
            'access_level' => 'admin',
            'role' => 'admin',
            'category' => 'Physiotherapy',
            'location_id' => $this->location->id,
            'is_active' => true,
        ]);

        $this->service = Service::create([
            'name' => 'Physical Therapy Session',
            'type' => 'in_person',
            'price' => 120.00,
            'duration_minutes' => 15,
            'buffer_minutes' => 0,
            'color' => '#10b981',
            'category' => 'Physiotherapy',
            'is_active' => true,
        ]);

        $this->client = Client::create([
            'name' => 'John Patient',
            'email' => 'patient@example.com',
            'phone' => '555-0199',
            'client_since' => now()->toDateString(),
        ]);

        // Next Monday schedule: 09:00 - 17:00 with lunch break: 12:30 - 13:30
        $this->monday = Carbon::parse('next monday')->startOfDay();
        $mondayIsoDay = (string) ($this->monday->dayOfWeekIso - 1);

        StaffSchedule::create([
            'staff_id' => $this->staff->id,
            'day_of_week' => $mondayIsoDay,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'is_working' => true,
            'breaks' => [
                [
                    'start_time' => '12:30',
                    'end_time' => '13:30',
                    'description' => 'Lunch Break',
                ]
            ],
        ]);
    }

    /**
     * Case A: Scheduled 15-minute slot returns available staff count > 0 (modal allowed to open).
     */
    public function test_case_a_scheduled_15_minute_slot_returns_available_staff(): void
    {
        $start = $this->monday->copy()->setTime(10, 0)->format('Y-m-d\TH:i:s');
        $end = $this->monday->copy()->setTime(10, 15)->format('Y-m-d\TH:i:s');

        $response = $this->actingAs($this->adminStaff, 'staff')
            ->getJson("/calendar/available-staff?start_time={$start}&end_time={$end}&location_id={$this->location->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $staffList = $response->json('staff');
        $this->assertNotEmpty($staffList);
        $this->assertEquals($this->staff->id, $staffList[0]['id']);
    }

    /**
     * Case B: Unscheduled 15-minute slot (e.g. 08:30 before schedule or 20:00 after schedule) returns 0 staff.
     */
    public function test_case_b_unscheduled_15_minute_slot_returns_zero_staff(): void
    {
        // 08:30 - 08:45 is before 09:00 start
        $start = $this->monday->copy()->setTime(8, 30)->format('Y-m-d\TH:i:s');
        $end = $this->monday->copy()->setTime(8, 45)->format('Y-m-d\TH:i:s');

        $response = $this->actingAs($this->adminStaff, 'staff')
            ->getJson("/calendar/available-staff?start_time={$start}&end_time={$end}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'staff' => [],
            ]);
    }

    /**
     * Case C: Non-working date (e.g. Sunday) returns 0 staff.
     */
    public function test_case_c_non_working_date_returns_zero_staff(): void
    {
        $sunday = $this->monday->copy()->addDays(6); // Sunday
        $start = $sunday->copy()->setTime(10, 0)->format('Y-m-d\TH:i:s');
        $end = $sunday->copy()->setTime(10, 15)->format('Y-m-d\TH:i:s');

        $response = $this->actingAs($this->adminStaff, 'staff')
            ->getJson("/calendar/available-staff?start_time={$start}&end_time={$end}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'staff' => [],
            ]);
    }

    /**
     * Case D: Valid scheduled slot returns the correct staff member with id, name, and category.
     */
    public function test_case_d_valid_scheduled_slot_returns_correct_staff_details(): void
    {
        $start = $this->monday->copy()->setTime(11, 0)->format('Y-m-d\TH:i:s');
        $end = $this->monday->copy()->setTime(11, 15)->format('Y-m-d\TH:i:s');

        $response = $this->actingAs($this->adminStaff, 'staff')
            ->getJson("/calendar/available-staff?start_time={$start}&end_time={$end}");

        $response->assertStatus(200);
        $staff = $response->json('staff.0');
        $this->assertEquals($this->staff->id, $staff['id']);
        $this->assertEquals($this->staff->name, $staff['name']);
        $this->assertEquals('Physiotherapy', $staff['category']);
    }

    /**
     * Case E & G & H: Save valid appointment gives fast AJAX JSON response with service color and formatted appointment.
     */
    public function test_case_e_g_h_save_valid_appointment_gives_fast_json_with_service_color(): void
    {
        $start = $this->monday->copy()->setTime(10, 0)->format('Y-m-d\TH:i:s');
        $end = $this->monday->copy()->setTime(10, 15)->format('Y-m-d\TH:i:s');

        $response = $this->actingAs($this->adminStaff, 'staff')
            ->postJson('/calendar/appointments', [
                'staff_id' => $this->staff->id,
                'client_id' => $this->client->id,
                'service_id' => $this->service->id,
                'location_id' => $this->location->id,
                'start_time' => $start,
                'end_time' => $end,
                'status' => 'booked',
                'notes' => 'Test fast AJAX save with service color.',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        $appointmentData = $response->json('appointment');
        $this->assertNotNull($appointmentData);
        $this->assertEquals($this->client->name, $appointmentData['title']);
        $this->assertEquals('#10b981', $appointmentData['serviceColor']);
        $this->assertEquals('#10b981', $appointmentData['color']);
    }

    /**
     * Case I: Invalid time (end before start or equal) is blocked with 422.
     */
    public function test_case_i_invalid_time_save_is_blocked(): void
    {
        $start = $this->monday->copy()->setTime(10, 15)->format('Y-m-d\TH:i:s');
        $end = $this->monday->copy()->setTime(10, 0)->format('Y-m-d\TH:i:s'); // before start!

        $response = $this->actingAs($this->adminStaff, 'staff')
            ->postJson('/calendar/appointments', [
                'staff_id' => $this->staff->id,
                'client_id' => $this->client->id,
                'service_id' => $this->service->id,
                'location_id' => $this->location->id,
                'start_time' => $start,
                'end_time' => $end,
            ]);

        $response->assertStatus(422);
    }

    /**
     * Case J: Existing booked slot for Client A allows booking for Client B, available-staff returns staff, but blocks same Client A.
     */
    public function test_case_j_existing_booked_slot_allows_different_client_blocks_same_client(): void
    {
        $start = $this->monday->copy()->setTime(14, 0)->format('Y-m-d\TH:i:s');
        $end = $this->monday->copy()->setTime(14, 30)->format('Y-m-d\TH:i:s');

        // Create first appointment for client A
        Appointment::create([
            'staff_id' => $this->staff->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service->id,
            'location_id' => $this->location->id,
            'start_time' => $start,
            'end_time' => $end,
            'status' => 'booked',
        ]);

        // Check availability for colliding slot 14:15 - 14:30 => staff is still available!
        $colStart = $this->monday->copy()->setTime(14, 15)->format('Y-m-d\TH:i:s');
        $colEnd = $this->monday->copy()->setTime(14, 30)->format('Y-m-d\TH:i:s');

        $availRes = $this->actingAs($this->adminStaff, 'staff')
            ->getJson("/calendar/available-staff?start_time={$colStart}&end_time={$colEnd}");

        $availRes->assertStatus(200);
        $staffIds = collect($availRes->json('staff'))->pluck('id')->all();
        $this->assertContains($this->staff->id, $staffIds);

        // Client B booking at overlapping time is ALLOWED
        $clientB = Client::create([
            'name' => 'Alice Secondary',
            'email' => 'alice@example.com',
            'phone' => '555-0200',
            'client_since' => now()->toDateString(),
        ]);

        $saveResB = $this->actingAs($this->adminStaff, 'staff')
            ->postJson('/calendar/appointments', [
                'staff_id' => $this->staff->id,
                'client_id' => $clientB->id,
                'service_id' => $this->service->id,
                'location_id' => $this->location->id,
                'start_time' => $colStart,
                'end_time' => $colEnd,
                'status' => 'booked',
            ]);

        $saveResB->assertStatus(201)
            ->assertJson(['success' => true]);

        // Attempting to save colliding appointment for the SAME client A is blocked
        $saveResA = $this->actingAs($this->adminStaff, 'staff')
            ->postJson('/calendar/appointments', [
                'staff_id' => $this->staff->id,
                'client_id' => $this->client->id,
                'service_id' => $this->service->id,
                'location_id' => $this->location->id,
                'start_time' => $colStart,
                'end_time' => $colEnd,
                'status' => 'booked',
            ]);

        $saveResA->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'This time slot is already booked.'
            ]);
    }

    /**
     * Test: Edit existing appointment without self-conflict is allowed.
     */
    public function test_edit_existing_appointment_without_self_conflict(): void
    {
        $start = $this->monday->copy()->setTime(11, 0)->format('Y-m-d\TH:i:s');
        $end = $this->monday->copy()->setTime(11, 15)->format('Y-m-d\TH:i:s');

        $appt = Appointment::create([
            'staff_id' => $this->staff->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service->id,
            'location_id' => $this->location->id,
            'start_time' => $start,
            'end_time' => $end,
            'status' => 'booked',
        ]);

        $newEnd = $this->monday->copy()->setTime(11, 30)->format('Y-m-d\TH:i:s');

        $res = $this->actingAs($this->adminStaff, 'staff')
            ->putJson("/calendar/appointments/{$appt->id}", [
                'staff_id' => $this->staff->id,
                'client_id' => $this->client->id,
                'service_id' => $this->service->id,
                'location_id' => $this->location->id,
                'start_time' => $start,
                'end_time' => $newEnd,
                'status' => 'booked',
            ]);

        $res->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /**
     * Case K: Appointment crossing schedule boundary (e.g. 16:45 - 17:15 when end is 17:00) is blocked.
     */
    public function test_case_k_appointment_crossing_schedule_boundary_is_blocked(): void
    {
        $start = $this->monday->copy()->setTime(16, 45)->format('Y-m-d\TH:i:s');
        $end = $this->monday->copy()->setTime(17, 15)->format('Y-m-d\TH:i:s'); // crosses 17:00!

        // Availability returns 0 staff
        $availRes = $this->actingAs($this->adminStaff, 'staff')
            ->getJson("/calendar/available-staff?start_time={$start}&end_time={$end}");

        $availRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'staff' => [],
            ]);

        // Save is rejected
        $saveRes = $this->actingAs($this->adminStaff, 'staff')
            ->postJson('/calendar/appointments', [
                'staff_id' => $this->staff->id,
                'client_id' => $this->client->id,
                'service_id' => $this->service->id,
                'location_id' => $this->location->id,
                'start_time' => $start,
                'end_time' => $end,
                'status' => 'booked',
            ]);

        $saveRes->assertStatus(422);
    }

    /**
     * Case L: Break overlap (e.g. 12:45 - 13:00 during 12:30 - 13:30 lunch) is blocked.
     */
    public function test_case_l_break_overlap_is_blocked(): void
    {
        $start = $this->monday->copy()->setTime(12, 45)->format('Y-m-d\TH:i:s');
        $end = $this->monday->copy()->setTime(13, 0)->format('Y-m-d\TH:i:s');

        // Availability returns 0 staff
        $availRes = $this->actingAs($this->adminStaff, 'staff')
            ->getJson("/calendar/available-staff?start_time={$start}&end_time={$end}");

        $availRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'staff' => [],
            ]);

        // Save is rejected
        $saveRes = $this->actingAs($this->adminStaff, 'staff')
            ->postJson('/calendar/appointments', [
                'staff_id' => $this->staff->id,
                'client_id' => $this->client->id,
                'service_id' => $this->service->id,
                'location_id' => $this->location->id,
                'start_time' => $start,
                'end_time' => $end,
                'status' => 'booked',
            ]);

        $saveRes->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }
}
