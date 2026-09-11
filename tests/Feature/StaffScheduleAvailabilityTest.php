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

class StaffScheduleAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    protected Staff $adminStaff;
    protected Staff $reena;
    protected Staff $fridayStaff;
    protected Location $location;
    protected Service $service30m;
    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->location = Location::firstOrCreate(
            ['name' => 'DK Rehab'],
            ['is_active' => true, 'address' => '123 Health Ave']
        );

        $this->adminStaff = Staff::firstOrCreate(
            ['email' => 'admin_avail_test@example.com'],
            [
                'name' => 'Admin Tester',
                'role' => 'admin',
                'access_level' => 'admin',
                'is_active' => true,
                'password' => bcrypt('password'),
                'location_id' => $this->location->id,
            ]
        );

        // Reena with Tuesday & Thursday 16:00-20:00
        $this->reena = Staff::where('name', 'like', '%Reena%')->first() ?? Staff::create([
            'name' => 'Reena',
            'email' => 'reena_avail_test@example.com',
            'role' => 'staff',
            'is_active' => true,
            'password' => bcrypt('password'),
            'location_id' => $this->location->id,
        ]);

        // Ensure Reena's schedules for testing (Tue 2026-09-08 16:00-20:00, Thu 2026-09-10 16:00-20:00)
        StaffSchedule::firstOrCreate([
            'staff_id' => $this->reena->id,
            'working_date' => '2026-09-08',
        ], [
            'day_of_week' => 2,
            'start_time' => '16:00:00',
            'end_time' => '20:00:00',
            'is_working' => true,
            'recurrence_type' => 'weekly',
        ]);

        StaffSchedule::firstOrCreate([
            'staff_id' => $this->reena->id,
            'working_date' => '2026-09-10',
        ], [
            'day_of_week' => 4,
            'start_time' => '16:00:00',
            'end_time' => '20:00:00',
            'is_working' => true,
            'recurrence_type' => 'weekly',
        ]);

        // Staff with Friday 09:00-17:00 schedule
        $this->fridayStaff = Staff::firstOrCreate(
            ['email' => 'friday_staff_test@example.com'],
            [
                'name' => 'Friday Staff',
                'role' => 'staff',
                'is_active' => true,
                'password' => bcrypt('password'),
                'location_id' => $this->location->id,
            ]
        );

        StaffSchedule::updateOrCreate([
            'staff_id' => $this->fridayStaff->id,
            'working_date' => '2026-09-11',
        ], [
            'day_of_week' => 5,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'is_working' => true,
            'recurrence_type' => 'one_time',
        ]);

        $this->service30m = Service::firstOrCreate(
            ['name' => '30 Min Consultation'],
            [
                'duration_minutes' => 30,
                'price' => 50,
                'is_active' => true,
            ]
        );

        $this->client = Client::firstOrCreate(
            ['email' => 'client_avail_test@example.com'],
            [
                'name' => 'Client Avail Test',
                'phone' => '1234567890',
            ]
        );
    }

    /**
     * Test Case 1: Reena, Tuesday, 16:00–17:00 => SHOW
     */
    public function test_case_1_reena_tuesday_16_to_17_is_available(): void
    {
        $res = $this->actingAs($this->adminStaff, 'staff')
            ->getJson('/calendar/available-staff?' . http_build_query([
                'start_time' => '2026-09-08 16:00:00',
                'end_time' => '2026-09-08 17:00:00',
                'location_id' => $this->location->id,
            ]));

        $res->assertStatus(200);
        $res->assertJsonPath('success', true);
        $staffIds = collect($res->json('staff'))->pluck('id')->all();
        $this->assertContains($this->reena->id, $staffIds);
    }

    /**
     * Test Case 2: Reena, Thursday, 16:00–17:00 => SHOW
     */
    public function test_case_2_reena_thursday_16_to_17_is_available(): void
    {
        $res = $this->actingAs($this->adminStaff, 'staff')
            ->getJson('/calendar/available-staff?' . http_build_query([
                'start_time' => '2026-09-10 16:00:00',
                'end_time' => '2026-09-10 17:00:00',
                'location_id' => $this->location->id,
            ]));

        $res->assertStatus(200);
        $staffIds = collect($res->json('staff'))->pluck('id')->all();
        $this->assertContains($this->reena->id, $staffIds);
    }

    /**
     * Test Case 3: Reena, Friday, 10:00–10:30 => HIDE
     */
    public function test_case_3_reena_friday_10_to_10_30_is_not_available(): void
    {
        $res = $this->actingAs($this->adminStaff, 'staff')
            ->getJson('/calendar/available-staff?' . http_build_query([
                'start_time' => '2026-09-11 10:00:00',
                'end_time' => '2026-09-11 10:30:00',
                'location_id' => $this->location->id,
            ]));

        $res->assertStatus(200);
        $staffIds = collect($res->json('staff'))->pluck('id')->all();
        $this->assertNotContains($this->reena->id, $staffIds);
    }

    /**
     * Test Case 4: Reena, Thursday, 10:00–10:30 => HIDE
     */
    public function test_case_4_reena_thursday_10_to_10_30_is_not_available(): void
    {
        $res = $this->actingAs($this->adminStaff, 'staff')
            ->getJson('/calendar/available-staff?' . http_build_query([
                'start_time' => '2026-09-10 10:00:00',
                'end_time' => '2026-09-10 10:30:00',
                'location_id' => $this->location->id,
            ]));

        $res->assertStatus(200);
        $staffIds = collect($res->json('staff'))->pluck('id')->all();
        $this->assertNotContains($this->reena->id, $staffIds);
    }

    /**
     * Test Case 5: Reena, Thursday, 19:30–20:30 => HIDE
     */
    public function test_case_5_reena_thursday_19_30_to_20_30_is_not_available(): void
    {
        $res = $this->actingAs($this->adminStaff, 'staff')
            ->getJson('/calendar/available-staff?' . http_build_query([
                'start_time' => '2026-09-10 19:30:00',
                'end_time' => '2026-09-10 20:30:00',
                'location_id' => $this->location->id,
            ]));

        $res->assertStatus(200);
        $staffIds = collect($res->json('staff'))->pluck('id')->all();
        $this->assertNotContains($this->reena->id, $staffIds);
    }

    /**
     * Test Case 6: Staff with Friday 09:00–17:00, Friday 10:00–10:30 => SHOW
     */
    public function test_case_6_friday_staff_is_available(): void
    {
        $res = $this->actingAs($this->adminStaff, 'staff')
            ->getJson('/calendar/available-staff?' . http_build_query([
                'start_time' => '2026-09-11 10:00:00',
                'end_time' => '2026-09-11 10:30:00',
                'location_id' => $this->location->id,
            ]));

        $res->assertStatus(200);
        $staffIds = collect($res->json('staff'))->pluck('id')->all();
        $this->assertContains($this->fridayStaff->id, $staffIds);
    }

    /**
     * Test Case 7: Staff with Friday 09:00–17:00, Friday 16:30–17:30 => HIDE
     */
    public function test_case_7_friday_staff_outside_hours_is_not_available(): void
    {
        $res = $this->actingAs($this->adminStaff, 'staff')
            ->getJson('/calendar/available-staff?' . http_build_query([
                'start_time' => '2026-09-11 16:30:00',
                'end_time' => '2026-09-11 17:30:00',
                'location_id' => $this->location->id,
            ]));

        $res->assertStatus(200);
        $staffIds = collect($res->json('staff'))->pluck('id')->all();
        $this->assertNotContains($this->fridayStaff->id, $staffIds);
    }

    /**
     * Test Case 8: Staff with Friday 09:00–17:00 but conflicting appointment, Friday 10:00–10:30 => HIDE
     */
    public function test_case_8_friday_staff_with_conflicting_appointment_is_not_available(): void
    {
        // Create an overlapping appointment for fridayStaff: 10:15 - 11:00
        Appointment::create([
            'staff_id' => $this->fridayStaff->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service30m->id,
            'location_id' => $this->location->id,
            'start_time' => '2026-09-11 10:15:00',
            'end_time' => '2026-09-11 11:00:00',
            'status' => 'booked',
        ]);

        $res = $this->actingAs($this->adminStaff, 'staff')
            ->getJson('/calendar/available-staff?' . http_build_query([
                'start_time' => '2026-09-11 10:00:00',
                'end_time' => '2026-09-11 10:30:00',
                'location_id' => $this->location->id,
            ]));

        $res->assertStatus(200);
        $staffIds = collect($res->json('staff'))->pluck('id')->all();
        $this->assertNotContains($this->fridayStaff->id, $staffIds);
    }

    /**
     * Test Case 9: Try manually submitting an unavailable staff_id => Backend rejects
     */
    public function test_case_9_submitting_unavailable_staff_is_rejected(): void
    {
        $res = $this->actingAs($this->adminStaff, 'staff')
            ->postJson('/calendar/appointments', [
                'staff_id' => $this->reena->id,
                'client_id' => $this->client->id,
                'service_id' => $this->service30m->id,
                'location_id' => $this->location->id,
                'start_time' => '2026-09-11 10:00:00',
                'end_time' => '2026-09-11 10:30:00',
                'status' => 'booked',
            ]);

        $res->assertStatus(422);
        $res->assertJson([
            'success' => false,
            'message' => 'The selected staff member is not available for this date and time.'
        ]);
    }
}
