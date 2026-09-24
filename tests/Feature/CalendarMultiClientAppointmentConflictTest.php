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

class CalendarMultiClientAppointmentConflictTest extends TestCase
{
    use RefreshDatabase;

    private Staff $adminStaff;
    private Staff $staff;
    private Location $location;
    private Service $serviceA;
    private Service $serviceB;
    private Client $clientA;
    private Client $clientB;
    private Carbon $wednesday;

    protected function setUp(): void
    {
        parent::setUp();

        $this->location = Location::create([
            'name' => 'Main Health Center',
            'is_active' => true,
        ]);

        $this->adminStaff = Staff::create([
            'name' => 'Admin User',
            'email' => 'admin_mc@example.com',
            'role' => 'admin',
            'access_level' => 'admin',
            'is_active' => true,
            'password' => Hash::make('password123'),
            'location_id' => $this->location->id,
        ]);

        $this->staff = Staff::create([
            'name' => 'Udhayakumar Natarajan',
            'email' => 'udhayakumar@example.com',
            'password' => Hash::make('Password123'),
            'access_level' => 'admin',
            'role' => 'admin',
            'category' => 'Cardiology',
            'location_id' => $this->location->id,
            'is_active' => true,
        ]);

        $this->serviceA = Service::create([
            'name' => 'Consultation A',
            'type' => 'in_person',
            'price' => 100.00,
            'duration_minutes' => 60,
            'buffer_minutes' => 0,
            'color' => '#10b981',
            'category' => 'Cardiology',
            'is_active' => true,
        ]);

        $this->serviceB = Service::create([
            'name' => 'Consultation B',
            'type' => 'in_person',
            'price' => 150.00,
            'duration_minutes' => 60,
            'buffer_minutes' => 0,
            'color' => '#6366f1',
            'category' => 'Cardiology',
            'is_active' => true,
        ]);

        $this->clientA = Client::create([
            'name' => 'muruga',
            'email' => 'muruga@example.com',
            'phone' => '555-0101',
            'client_since' => now()->toDateString(),
        ]);

        $this->clientB = Client::create([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'phone' => '555-0102',
            'client_since' => now()->toDateString(),
        ]);

        // Wednesday schedule: 09:00 - 17:00 with lunch break: 13:00 - 14:00
        $this->wednesday = Carbon::parse('next wednesday')->startOfDay();
        $wednesdayIsoDay = (string) ($this->wednesday->dayOfWeekIso - 1); // 0=Mon, 2=Wed

        StaffSchedule::create([
            'staff_id' => $this->staff->id,
            'day_of_week' => $wednesdayIsoDay,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'is_working' => true,
            'breaks' => [
                [
                    'start_time' => '13:00',
                    'end_time' => '14:00',
                    'description' => 'Lunch Break',
                ]
            ],
        ]);
    }

    /**
     * Requirement A: Staff scheduled + no existing appointment -> ALLOW.
     */
    public function test_a_staff_scheduled_no_existing_appointment_is_allowed(): void
    {
        $start = $this->wednesday->copy()->setTime(10, 15)->format('Y-m-d\TH:i:s');
        $end = $this->wednesday->copy()->setTime(11, 15)->format('Y-m-d\TH:i:s');

        $res = $this->actingAs($this->adminStaff, 'staff')
            ->postJson('/calendar/appointments', [
                'staff_id' => $this->staff->id,
                'client_id' => $this->clientA->id,
                'service_id' => $this->serviceA->id,
                'location_id' => $this->location->id,
                'start_time' => $start,
                'end_time' => $end,
                'status' => 'booked',
            ]);

        $res->assertStatus(201)->assertJson(['success' => true]);
        $this->assertDatabaseHas('appointments', [
            'staff_id' => $this->staff->id,
            'client_id' => $this->clientA->id,
            'start_time' => Carbon::parse($start)->toDateTimeString(),
        ]);
    }

    /**
     * Requirement B & C: Staff scheduled + existing appointment for Client A + new Client B same time -> ALLOW; both exist.
     */
    public function test_b_and_c_staff_scheduled_existing_appointment_for_client_a_new_client_b_same_time_both_exist(): void
    {
        $start = $this->wednesday->copy()->setTime(10, 15)->format('Y-m-d\TH:i:s');
        $end = $this->wednesday->copy()->setTime(11, 15)->format('Y-m-d\TH:i:s');

        // Existing appointment for Client A
        $resA = $this->actingAs($this->adminStaff, 'staff')
            ->postJson('/calendar/appointments', [
                'staff_id' => $this->staff->id,
                'client_id' => $this->clientA->id,
                'service_id' => $this->serviceA->id,
                'location_id' => $this->location->id,
                'start_time' => $start,
                'end_time' => $end,
                'status' => 'booked',
            ]);
        $resA->assertStatus(201);

        // New appointment for Client B at the exact same staff and time -> MUST BE ALLOWED
        $resB = $this->actingAs($this->adminStaff, 'staff')
            ->postJson('/calendar/appointments', [
                'staff_id' => $this->staff->id,
                'client_id' => $this->clientB->id,
                'service_id' => $this->serviceB->id,
                'location_id' => $this->location->id,
                'start_time' => $start,
                'end_time' => $end,
                'status' => 'booked',
            ]);
        $resB->assertStatus(201)->assertJson(['success' => true]);

        // Verify both appointments exist in DB
        $this->assertDatabaseHas('appointments', [
            'staff_id' => $this->staff->id,
            'client_id' => $this->clientA->id,
            'service_id' => $this->serviceA->id,
        ]);
        $this->assertDatabaseHas('appointments', [
            'staff_id' => $this->staff->id,
            'client_id' => $this->clientB->id,
            'service_id' => $this->serviceB->id,
        ]);

        $this->assertEquals(2, Appointment::where('staff_id', $this->staff->id)->count());
    }

    /**
     * Requirement D: Same staff + different clients + overlapping duration -> ALLOW.
     */
    public function test_d_same_staff_different_clients_overlapping_duration_allowed(): void
    {
        $startA = $this->wednesday->copy()->setTime(10, 0)->format('Y-m-d\TH:i:s');
        $endA = $this->wednesday->copy()->setTime(11, 0)->format('Y-m-d\TH:i:s');

        $this->actingAs($this->adminStaff, 'staff')
            ->postJson('/calendar/appointments', [
                'staff_id' => $this->staff->id,
                'client_id' => $this->clientA->id,
                'service_id' => $this->serviceA->id,
                'location_id' => $this->location->id,
                'start_time' => $startA,
                'end_time' => $endA,
                'status' => 'booked',
            ])->assertStatus(201);

        // Client B overlaps 10:30 - 11:30
        $startB = $this->wednesday->copy()->setTime(10, 30)->format('Y-m-d\TH:i:s');
        $endB = $this->wednesday->copy()->setTime(11, 30)->format('Y-m-d\TH:i:s');

        $resB = $this->actingAs($this->adminStaff, 'staff')
            ->postJson('/calendar/appointments', [
                'staff_id' => $this->staff->id,
                'client_id' => $this->clientB->id,
                'service_id' => $this->serviceB->id,
                'location_id' => $this->location->id,
                'start_time' => $startB,
                'end_time' => $endB,
                'status' => 'booked',
            ]);

        $resB->assertStatus(201)->assertJson(['success' => true]);
    }

    /**
     * Requirement E: Staff not scheduled -> BLOCK.
     */
    public function test_e_staff_not_scheduled_blocked(): void
    {
        // Thursday has no schedule for this staff
        $thursday = $this->wednesday->copy()->addDay();
        $start = $thursday->setTime(10, 0)->format('Y-m-d\TH:i:s');
        $end = $thursday->copy()->setTime(11, 0)->format('Y-m-d\TH:i:s');

        $res = $this->actingAs($this->adminStaff, 'staff')
            ->postJson('/calendar/appointments', [
                'staff_id' => $this->staff->id,
                'client_id' => $this->clientA->id,
                'service_id' => $this->serviceA->id,
                'location_id' => $this->location->id,
                'start_time' => $start,
                'end_time' => $end,
                'status' => 'booked',
            ]);

        $res->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Selected staff member is not scheduled for this date and time.'
            ]);
    }

    /**
     * Requirement F: Non-working date -> BLOCK.
     */
    public function test_f_non_working_date_blocked(): void
    {
        $sunday = $this->wednesday->copy()->addDays(4);
        $start = $sunday->setTime(10, 0)->format('Y-m-d\TH:i:s');
        $end = $sunday->copy()->setTime(11, 0)->format('Y-m-d\TH:i:s');

        $res = $this->actingAs($this->adminStaff, 'staff')
            ->postJson('/calendar/appointments', [
                'staff_id' => $this->staff->id,
                'client_id' => $this->clientA->id,
                'service_id' => $this->serviceA->id,
                'location_id' => $this->location->id,
                'start_time' => $start,
                'end_time' => $end,
                'status' => 'booked',
            ]);

        $res->assertStatus(422);
    }

    /**
     * Requirement G: Outside working hours -> BLOCK.
     */
    public function test_g_outside_working_hours_blocked(): void
    {
        // Staff works 09:00 - 17:00; test 07:00 - 08:00
        $start = $this->wednesday->copy()->setTime(7, 0)->format('Y-m-d\TH:i:s');
        $end = $this->wednesday->copy()->setTime(8, 0)->format('Y-m-d\TH:i:s');

        $res = $this->actingAs($this->adminStaff, 'staff')
            ->postJson('/calendar/appointments', [
                'staff_id' => $this->staff->id,
                'client_id' => $this->clientA->id,
                'service_id' => $this->serviceA->id,
                'location_id' => $this->location->id,
                'start_time' => $start,
                'end_time' => $end,
                'status' => 'booked',
            ]);

        $res->assertStatus(422);
    }

    /**
     * Requirement H: Crosses schedule boundary -> BLOCK.
     */
    public function test_h_crosses_schedule_boundary_blocked(): void
    {
        // Staff works until 17:00; test 16:30 - 17:30
        $start = $this->wednesday->copy()->setTime(16, 30)->format('Y-m-d\TH:i:s');
        $end = $this->wednesday->copy()->setTime(17, 30)->format('Y-m-d\TH:i:s');

        $res = $this->actingAs($this->adminStaff, 'staff')
            ->postJson('/calendar/appointments', [
                'staff_id' => $this->staff->id,
                'client_id' => $this->clientA->id,
                'service_id' => $this->serviceA->id,
                'location_id' => $this->location->id,
                'start_time' => $start,
                'end_time' => $end,
                'status' => 'booked',
            ]);

        $res->assertStatus(422);
    }

    /**
     * Requirement I: Break overlap -> BLOCK.
     */
    public function test_i_break_overlap_blocked(): void
    {
        // Lunch break is 13:00 - 14:00; test 60-minute appointment 12:30 - 13:30 which overlaps break
        $start = $this->wednesday->copy()->setTime(12, 30)->format('Y-m-d\TH:i:s');
        $end = $this->wednesday->copy()->setTime(13, 30)->format('Y-m-d\TH:i:s');

        $res = $this->actingAs($this->adminStaff, 'staff')
            ->postJson('/calendar/appointments', [
                'staff_id' => $this->staff->id,
                'client_id' => $this->clientA->id,
                'service_id' => $this->serviceA->id,
                'location_id' => $this->location->id,
                'start_time' => $start,
                'end_time' => $end,
                'status' => 'booked',
            ]);

        $res->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Selected staff member is not available during this time.'
            ]);
    }

    /**
     * Requirement J: Edit existing appointment without self-conflict -> ALLOW.
     */
    public function test_j_edit_existing_appointment_without_self_conflict_allowed(): void
    {
        $start = $this->wednesday->copy()->setTime(10, 0)->format('Y-m-d\TH:i:s');
        $end = $this->wednesday->copy()->setTime(11, 0)->format('Y-m-d\TH:i:s');

        $appt = Appointment::create([
            'staff_id' => $this->staff->id,
            'client_id' => $this->clientA->id,
            'service_id' => $this->serviceA->id,
            'location_id' => $this->location->id,
            'start_time' => $start,
            'end_time' => $end,
            'status' => 'booked',
        ]);

        // Edit notes or duration without error
        $newEnd = $this->wednesday->copy()->setTime(11, 15)->format('Y-m-d\TH:i:s');

        $res = $this->actingAs($this->adminStaff, 'staff')
            ->putJson("/calendar/appointments/{$appt->id}", [
                'staff_id' => $this->staff->id,
                'client_id' => $this->clientA->id,
                'service_id' => $this->serviceA->id,
                'location_id' => $this->location->id,
                'start_time' => $start,
                'end_time' => $newEnd,
                'status' => 'booked',
            ]);

        $res->assertStatus(200)->assertJson(['success' => true]);
    }

    /**
     * Requirement K: Available staff endpoint still returns the staff even when another client already has an appointment.
     */
    public function test_k_available_staff_endpoint_still_returns_staff_with_existing_appointment(): void
    {
        $start = $this->wednesday->copy()->setTime(10, 15)->format('Y-m-d\TH:i:s');
        $end = $this->wednesday->copy()->setTime(11, 15)->format('Y-m-d\TH:i:s');

        // Existing appointment for Client A
        Appointment::create([
            'staff_id' => $this->staff->id,
            'client_id' => $this->clientA->id,
            'service_id' => $this->serviceA->id,
            'location_id' => $this->location->id,
            'start_time' => $start,
            'end_time' => $end,
            'status' => 'booked',
        ]);

        // Query available staff for that exact time
        $res = $this->actingAs($this->adminStaff, 'staff')
            ->getJson("/calendar/available-staff?start_time={$start}&end_time={$end}");

        $res->assertStatus(200);
        $staffIds = collect($res->json('staff'))->pluck('id')->all();
        $this->assertContains($this->staff->id, $staffIds);
    }

    /**
     * Requirement L & M & N: Both appointments appear in calendar events, service colors remain correct, fast AJAX save.
     */
    public function test_l_m_n_both_appointments_in_calendar_events_with_service_colors(): void
    {
        $start = $this->wednesday->copy()->setTime(10, 15)->format('Y-m-d\TH:i:s');
        $end = $this->wednesday->copy()->setTime(11, 15)->format('Y-m-d\TH:i:s');

        $resA = $this->actingAs($this->adminStaff, 'staff')
            ->postJson('/calendar/appointments', [
                'staff_id' => $this->staff->id,
                'client_id' => $this->clientA->id,
                'service_id' => $this->serviceA->id,
                'location_id' => $this->location->id,
                'start_time' => $start,
                'end_time' => $end,
                'status' => 'booked',
            ]);
        $resA->assertStatus(201);
        $this->assertEquals('#10b981', $resA->json('appointment.serviceColor'));

        $resB = $this->actingAs($this->adminStaff, 'staff')
            ->postJson('/calendar/appointments', [
                'staff_id' => $this->staff->id,
                'client_id' => $this->clientB->id,
                'service_id' => $this->serviceB->id,
                'location_id' => $this->location->id,
                'start_time' => $start,
                'end_time' => $end,
                'status' => 'booked',
            ]);
        $resB->assertStatus(201);
        $this->assertEquals('#6366f1', $resB->json('appointment.serviceColor'));

        // Load calendar events
        $eventsRes = $this->actingAs($this->adminStaff, 'staff')
            ->getJson('/calendar/events?start=' . $this->wednesday->toDateString() . '&end=' . $this->wednesday->copy()->endOfDay()->toDateString());

        $eventsRes->assertStatus(200);
        $events = $eventsRes->json();
        $this->assertCount(2, $events);

        $titles = collect($events)->pluck('title')->all();
        $this->assertContains('muruga', $titles);
        $this->assertContains('Alice', $titles);

        $colors = collect($events)->pluck('serviceColor')->all();
        $this->assertContains('#10b981', $colors);
        $this->assertContains('#6366f1', $colors);
    }

    /**
     * Client duplicate protection: SAME client booking colliding time is BLOCKED.
     */
    public function test_same_client_duplicate_appointment_is_blocked(): void
    {
        $start = $this->wednesday->copy()->setTime(10, 15)->format('Y-m-d\TH:i:s');
        $end = $this->wednesday->copy()->setTime(11, 15)->format('Y-m-d\TH:i:s');

        Appointment::create([
            'staff_id' => $this->staff->id,
            'client_id' => $this->clientA->id,
            'service_id' => $this->serviceA->id,
            'location_id' => $this->location->id,
            'start_time' => $start,
            'end_time' => $end,
            'status' => 'booked',
        ]);

        $res = $this->actingAs($this->adminStaff, 'staff')
            ->postJson('/calendar/appointments', [
                'staff_id' => $this->staff->id,
                'client_id' => $this->clientA->id,
                'service_id' => $this->serviceA->id,
                'location_id' => $this->location->id,
                'start_time' => $start,
                'end_time' => $end,
                'status' => 'booked',
            ]);

        $res->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'This time slot is already booked.'
            ]);
    }
}
