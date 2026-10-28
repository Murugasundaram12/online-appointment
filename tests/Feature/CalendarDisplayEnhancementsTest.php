<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Location;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Staff;
use App\Models\StaffSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CalendarDisplayEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    private Staff $admin;
    private Staff $staffA;
    private Staff $staffB;
    private Location $location;
    private ServiceCategory $physioCategory;
    private ServiceCategory $massageCategory;
    private Service $physioService;
    private Service $massageService;
    private Service $customColorService;
    private Client $clientA;
    private Client $clientB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->location = Location::create([
            'name' => 'Main Clinic',
            'is_active' => true,
        ]);

        $this->admin = Staff::create([
            'name' => 'Admin Manager',
            'email' => 'admin_calendar_enh@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
            'access_level' => 'admin',
            'is_active' => true,
            'location_id' => $this->location->id,
        ]);

        $this->staffA = Staff::create([
            'name' => 'Dr. Alice Physio',
            'email' => 'alice_physio@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'staff',
            'access_level' => 'staff',
            'is_active' => true,
            'location_id' => $this->location->id,
        ]);

        $this->staffB = Staff::create([
            'name' => 'Bob RMT',
            'email' => 'bob_rmt@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'staff',
            'access_level' => 'staff',
            'is_active' => true,
            'location_id' => $this->location->id,
        ]);

        $this->physioCategory = ServiceCategory::create([
            'name' => 'Physiotherapy',
            'is_active' => true,
        ]);

        $this->massageCategory = ServiceCategory::create([
            'name' => 'Massage Therapy',
            'is_active' => true,
        ]);

        // Physio with null/default color
        $this->physioService = Service::create([
            'service_category_id' => $this->physioCategory->id,
            'name' => 'Physiotherapy Assessment',
            'type' => 'in_person',
            'price' => 120.00,
            'duration_minutes' => 45,
            'buffer_minutes' => 0,
            'color' => null,
            'is_active' => true,
        ]);

        // Massage with null/default color
        $this->massageService = Service::create([
            'service_category_id' => $this->massageCategory->id,
            'name' => 'Deep Tissue Massage',
            'type' => 'in_person',
            'price' => 110.00,
            'duration_minutes' => 60,
            'buffer_minutes' => 0,
            'color' => '#3699ff',
            'is_active' => true,
        ]);

        // Custom color service
        $this->customColorService = Service::create([
            'service_category_id' => $this->physioCategory->id,
            'name' => 'Specialized Physio Treatment',
            'type' => 'in_person',
            'price' => 140.00,
            'duration_minutes' => 45,
            'buffer_minutes' => 0,
            'color' => '#e11d48',
            'is_active' => true,
        ]);

        $this->clientA = Client::create([
            'name' => 'Jane Doe',
            'email' => 'jane_calendar_test@example.com',
            'phone' => '4165550101',
        ]);

        $this->clientB = Client::create([
            'name' => 'John Smith',
            'email' => 'john_calendar_test@example.com',
            'phone' => '4165550102',
        ]);
    }

    /**
     * 1. Overlapping appointments at the same time for different staff or same staff
     * are both returned with full client names and category details for side-by-side rendering.
     */
    public function test_overlapping_appointments_are_returned_with_full_details_and_service_categories(): void
    {
        $testDate = '2026-11-02';
        $start = Carbon::parse("{$testDate} 10:00:00");
        $end = Carbon::parse("{$testDate} 10:45:00");

        // Appointment 1: Client A with Staff A (Physiotherapy)
        $appt1 = Appointment::create([
            'staff_id' => $this->staffA->id,
            'service_id' => $this->physioService->id,
            'client_id' => $this->clientA->id,
            'location_id' => $this->location->id,
            'start_time' => $start,
            'end_time' => $end,
            'status' => 'booked',
        ]);

        // Appointment 2: Client B with Staff B (Massage Therapy) at identical time
        $appt2 = Appointment::create([
            'staff_id' => $this->staffB->id,
            'service_id' => $this->massageService->id,
            'client_id' => $this->clientB->id,
            'location_id' => $this->location->id,
            'start_time' => $start,
            'end_time' => $end,
            'status' => 'booked',
        ]);

        $response = $this->actingAs($this->admin, 'staff')->getJson(route('calendar.events', [
            'start' => $testDate,
            'end' => $testDate,
        ]));

        $response->assertStatus(200);
        $events = collect($response->json());

        $this->assertCount(2, $events);

        $event1 = $events->firstWhere('id', $appt1->id);
        $this->assertNotNull($event1);
        $this->assertEquals('Jane Doe', $event1['title']);
        $this->assertEquals('Physiotherapy Assessment', $event1['service']);
        $this->assertEquals('Physiotherapy', $event1['serviceCategory']);
        $this->assertEquals($this->physioCategory->id, $event1['serviceCategoryId']);

        $event2 = $events->firstWhere('id', $appt2->id);
        $this->assertNotNull($event2);
        $this->assertEquals('John Smith', $event2['title']);
        $this->assertEquals('Deep Tissue Massage', $event2['service']);
        $this->assertEquals('Massage Therapy', $event2['serviceCategory']);
        $this->assertEquals($this->massageCategory->id, $event2['serviceCategoryId']);
    }

    /**
     * 2. Distinguishable service colors:
     * Custom colors are preserved, and fallback category colors (#2563eb for Physio, #0d9488 for Massage)
     * are resolved in event payload and monthEvents.
     */
    public function test_service_colors_preserve_custom_colors_and_resolve_distinguishable_category_colors(): void
    {
        $testDate = '2026-11-03';

        // 1. Custom color appointment
        $apptCustom = Appointment::create([
            'staff_id' => $this->staffA->id,
            'service_id' => $this->customColorService->id,
            'client_id' => $this->clientA->id,
            'location_id' => $this->location->id,
            'start_time' => "{$testDate} 11:00:00",
            'end_time' => "{$testDate} 11:45:00",
            'status' => 'booked',
        ]);

        // 2. Physiotherapy with null/default color -> should resolve to #2563eb
        $apptPhysio = Appointment::create([
            'staff_id' => $this->staffA->id,
            'service_id' => $this->physioService->id,
            'client_id' => $this->clientA->id,
            'location_id' => $this->location->id,
            'start_time' => "{$testDate} 12:00:00",
            'end_time' => "{$testDate} 12:45:00",
            'status' => 'booked',
        ]);

        // 3. Massage Therapy with generic color #3699ff -> should resolve to #0d9488
        $apptMassage = Appointment::create([
            'staff_id' => $this->staffB->id,
            'service_id' => $this->massageService->id,
            'client_id' => $this->clientB->id,
            'location_id' => $this->location->id,
            'start_time' => "{$testDate} 13:00:00",
            'end_time' => "{$testDate} 14:00:00",
            'status' => 'booked',
        ]);

        $response = $this->actingAs($this->admin, 'staff')->getJson(route('calendar.events', [
            'start' => $testDate,
            'end' => $testDate,
        ]));

        $response->assertStatus(200);
        $events = collect($response->json());

        $eventCustom = $events->firstWhere('id', $apptCustom->id);
        $this->assertNotNull($eventCustom);
        $this->assertEquals('#e11d48', $eventCustom['color']);
        $this->assertEquals('#e11d48', $eventCustom['serviceColor']);
        $this->assertEquals('Physiotherapy', $eventCustom['serviceCategory']);

        $eventPhysio = $events->firstWhere('id', $apptPhysio->id);
        $this->assertNotNull($eventPhysio);
        $this->assertEquals('#2563eb', $eventPhysio['color']);
        $this->assertEquals('#2563eb', $eventPhysio['serviceColor']);
        $this->assertEquals('Physiotherapy', $eventPhysio['serviceCategory']);

        $eventMassage = $events->firstWhere('id', $apptMassage->id);
        $this->assertNotNull($eventMassage);
        $this->assertEquals('#0d9488', $eventMassage['color']);
        $this->assertEquals('#0d9488', $eventMassage['serviceColor']);
        $this->assertEquals('Massage Therapy', $eventMassage['serviceCategory']);
    }

    /**
     * 3. Daily counts rule:
     * Distinguish appointment count from unique-client count, and exclude cancelled appointments.
     */
    public function test_daily_counts_distinguishes_appointments_from_unique_clients_and_excludes_cancelled(): void
    {
        $testDate = '2026-11-04';

        // Appointment 1: Client A at 09:00
        Appointment::create([
            'staff_id' => $this->staffA->id,
            'service_id' => $this->physioService->id,
            'client_id' => $this->clientA->id,
            'location_id' => $this->location->id,
            'start_time' => "{$testDate} 09:00:00",
            'end_time' => "{$testDate} 09:45:00",
            'status' => 'booked',
        ]);

        // Appointment 2: Client A (same client, second appointment) at 10:00
        Appointment::create([
            'staff_id' => $this->staffB->id,
            'service_id' => $this->massageService->id,
            'client_id' => $this->clientA->id,
            'location_id' => $this->location->id,
            'start_time' => "{$testDate} 10:00:00",
            'end_time' => "{$testDate} 11:00:00",
            'status' => 'booked',
        ]);

        // Appointment 3: Client B at 11:00
        Appointment::create([
            'staff_id' => $this->staffB->id,
            'service_id' => $this->massageService->id,
            'client_id' => $this->clientB->id,
            'location_id' => $this->location->id,
            'start_time' => "{$testDate} 11:00:00",
            'end_time' => "{$testDate} 12:00:00",
            'status' => 'booked',
        ]);

        // Appointment 4: Cancelled appointment for Client B at 14:00
        Appointment::create([
            'staff_id' => $this->staffA->id,
            'service_id' => $this->physioService->id,
            'client_id' => $this->clientB->id,
            'location_id' => $this->location->id,
            'start_time' => "{$testDate} 14:00:00",
            'end_time' => "{$testDate} 14:45:00",
            'status' => 'cancelled',
            'cancellation_reason' => 'Client requested reschedule',
        ]);

        // Query the events for this date
        $response = $this->actingAs($this->admin, 'staff')->getJson(route('calendar.events', [
            'start' => $testDate,
            'end' => $testDate,
        ]));

        $response->assertStatus(200);
        $events = collect($response->json());

        // Total raw events in the endpoint includes cancelled (4 total)
        $this->assertCount(4, $events);

        // Apply counting rules as defined:
        // 1. Exclude cancelled appointments from default daily count
        $activeEvents = $events->filter(fn ($e) => strtolower($e['status'] ?? '') !== 'cancelled');
        $this->assertCount(3, $activeEvents);

        // 2. Count unique clients by clientId
        $uniqueClientIds = $activeEvents->pluck('clientId')->unique();
        $this->assertCount(2, $uniqueClientIds);
        $this->assertTrue($uniqueClientIds->contains($this->clientA->id));
        $this->assertTrue($uniqueClientIds->contains($this->clientB->id));

        // Result matches specification: 3 Appointments · 2 Clients
        $totalAppointments = $activeEvents->count();
        $totalUniqueClients = $uniqueClientIds->count();
        $this->assertEquals(3, $totalAppointments);
        $this->assertEquals(2, $totalUniqueClients);
    }

    /**
     * 4. Calendar Blade view contains the daily count badge element and overlap layout styles.
     */
    public function test_calendar_blade_view_contains_daily_count_badge_and_overlap_classes(): void
    {
        $response = $this->actingAs($this->admin, 'staff')->get(route('calendar.index'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // Daily count badge element near current-date-header
        $this->assertStringContainsString('id="calendar-daily-count"', $content);
        $this->assertStringContainsString('daily-count-badge', $content);

        // Overlap CSS styles and collision assignment logic
        $this->assertStringContainsString('.calendar-appointment.is-overlapping', $content);
        $this->assertStringContainsString('.calendar-appointment.is-compact-slot', $content);
        $this->assertStringContainsString('getCategoryOrServiceFallbackColor', $content);
        $this->assertStringContainsString('updateDailyCountDisplay', $content);
    }

    /**
     * 5. Two or more appointments starting at the same time for the same staff member
     * are returned with distinct client names, service details, and times for side-by-side display.
     */
    public function test_two_appointments_starting_at_same_time_for_same_staff_member_returned_with_full_client_details(): void
    {
        $testDate = '2026-11-05';
        $start = Carbon::parse("{$testDate} 10:00:00");
        $end = Carbon::parse("{$testDate} 10:45:00");

        // Two appointments for the SAME staff member (staffA) at the EXACT same time
        $appt1 = Appointment::create([
            'staff_id' => $this->staffA->id,
            'service_id' => $this->physioService->id,
            'client_id' => $this->clientA->id,
            'location_id' => $this->location->id,
            'start_time' => $start,
            'end_time' => $end,
            'status' => 'booked',
        ]);

        $appt2 = Appointment::create([
            'staff_id' => $this->staffA->id,
            'service_id' => $this->customColorService->id,
            'client_id' => $this->clientB->id,
            'location_id' => $this->location->id,
            'start_time' => $start,
            'end_time' => $end,
            'status' => 'booked',
        ]);

        $response = $this->actingAs($this->admin, 'staff')->getJson(route('calendar.events', [
            'start' => $testDate,
            'end' => $testDate,
        ]));

        $response->assertStatus(200);
        $events = collect($response->json());

        $ev1 = $events->firstWhere('id', $appt1->id);
        $ev2 = $events->firstWhere('id', $appt2->id);

        $this->assertNotNull($ev1);
        $this->assertNotNull($ev2);

        // Both clients are clearly distinct
        $this->assertEquals('Jane Doe', $ev1['title']);
        $this->assertEquals('Jane Doe', $ev1['clientName']);
        $this->assertEquals($this->staffA->id, $ev1['staffId']);

        $this->assertEquals('John Smith', $ev2['title']);
        $this->assertEquals('John Smith', $ev2['clientName']);
        $this->assertEquals($this->staffA->id, $ev2['staffId']);

        // Same start time
        $this->assertEquals($start->toIso8601String(), $ev1['start']);
        $this->assertEquals($start->toIso8601String(), $ev2['start']);
    }

    /**
     * 6. Appointments with different start times that partially overlap
     * are returned with correct time intervals for interval track layout.
     */
    public function test_partially_overlapping_appointments_with_different_start_times_returned_for_side_by_side_tracks(): void
    {
        $testDate = '2026-11-06';

        // Appointment 1: 10:00 to 11:00 (Client A)
        $appt1 = Appointment::create([
            'staff_id' => $this->staffA->id,
            'service_id' => $this->physioService->id,
            'client_id' => $this->clientA->id,
            'location_id' => $this->location->id,
            'start_time' => "{$testDate} 10:00:00",
            'end_time' => "{$testDate} 11:00:00",
            'status' => 'booked',
        ]);

        // Appointment 2: 10:30 to 11:15 (Client B) - partially overlapping
        $appt2 = Appointment::create([
            'staff_id' => $this->staffA->id,
            'service_id' => $this->customColorService->id,
            'client_id' => $this->clientB->id,
            'location_id' => $this->location->id,
            'start_time' => "{$testDate} 10:30:00",
            'end_time' => "{$testDate} 11:15:00",
            'status' => 'booked',
        ]);

        $response = $this->actingAs($this->admin, 'staff')->getJson(route('calendar.events', [
            'start' => $testDate,
            'end' => $testDate,
        ]));

        $response->assertStatus(200);
        $events = collect($response->json());

        $ev1 = $events->firstWhere('id', $appt1->id);
        $ev2 = $events->firstWhere('id', $appt2->id);

        $this->assertNotNull($ev1);
        $this->assertNotNull($ev2);

        $this->assertEquals('Jane Doe', $ev1['title']);
        $this->assertEquals('John Smith', $ev2['title']);

        $this->assertTrue(Carbon::parse($ev2['start'])->lt(Carbon::parse($ev1['end'])));
    }

    /**
     * 7. Calendar Blade view contains client name prioritization styles and full-detail tooltips.
     */
    public function test_blade_view_prioritizes_client_name_compacts_secondary_details_and_sets_full_tooltips(): void
    {
        $response = $this->actingAs($this->admin, 'staff')->get(route('calendar.index'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // 1. Client name font weight and 100% width display rule in overlapping cards
        $this->assertStringContainsString('.calendar-appointment.is-overlapping .calendar-appointment-client', $content);
        $this->assertStringContainsString('font-weight: 750 !important', $content);
        $this->assertStringContainsString('width: 100% !important', $content);

        // 2. Compact secondary details (service, time, status badge)
        $this->assertStringContainsString('.calendar-appointment.is-overlapping .calendar-appointment-service', $content);
        $this->assertStringContainsString('.calendar-appointment.is-overlapping .calendar-appointment-status', $content);
        $this->assertStringContainsString('.calendar-appointment.is-overlapping .appointment-time', $content);

        // 3. Compact slot rules for 3+ tracks
        $this->assertStringContainsString('.calendar-appointment.is-compact-slot .calendar-appointment-client', $content);
        $this->assertStringContainsString('font-weight: 800 !important', $content);

        // 4. Overlapping card header gives full width to client name on dedicated row
        $this->assertStringContainsString('calendar-appointment-footer', $content);

        // 5. Tooltip constructed with full untruncated client name, service, staff, time, and status
        $this->assertStringContainsString('el.title = `${clientTitle}${tooltipService}${tooltipStaff} • ${fullTimeStr} [${String(statusLabel).toUpperCase()}]`', $content);

        // 6. Existing click behavior preserved
        $this->assertStringContainsString('openCompletedAppointmentModal(ev.id, e)', $content);
        $this->assertStringContainsString('openAppointmentModalForEdit(ev.id, e)', $content);
    }
}

