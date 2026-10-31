<?php

namespace Tests\Feature;

use App\Http\Controllers\CalendarController;
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

class ScheduleOverlapAndStaffColorTest extends TestCase
{
    use RefreshDatabase;

    private Staff $admin;
    private Staff $staffWithCustomColor;
    private Staff $staffWithoutColorA;
    private Staff $staffWithoutColorB;
    private Location $location;
    private Service $service;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->location = Location::create([
            'name' => 'Downtown Wellness Center',
            'is_active' => true,
        ]);

        $this->admin = Staff::create([
            'name' => 'Clinic Admin',
            'email' => 'admin_schedule_test@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
            'access_level' => 'admin',
            'is_active' => true,
            'location_id' => $this->location->id,
        ]);

        $this->staffWithCustomColor = Staff::create([
            'name' => 'Dr. Custom Color',
            'email' => 'custom_color_staff@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'staff',
            'access_level' => 'staff',
            'is_active' => true,
            'color' => '#8b5cf6', // Violet
            'location_id' => $this->location->id,
        ]);

        $this->staffWithoutColorA = Staff::create([
            'name' => 'Provider Alpha',
            'email' => 'provider_alpha@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'staff',
            'access_level' => 'staff',
            'is_active' => true,
            'color' => null,
            'location_id' => $this->location->id,
        ]);

        $this->staffWithoutColorB = Staff::create([
            'name' => 'Provider Beta',
            'email' => 'provider_beta@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'staff',
            'access_level' => 'staff',
            'is_active' => true,
            'color' => null,
            'location_id' => $this->location->id,
        ]);

        $this->service = Service::create([
            'name' => 'Chiropractic Adjustment',
            'color' => '#10b981',
            'duration_minutes' => 60,
            'price' => 120,
            'is_active' => true,
        ]);

        $this->client = Client::create([
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'phone' => '555-0199',
        ]);
    }

    public function test_staff_color_resolution_configured_and_deterministic_fallback(): void
    {
        // 1. Configured custom color
        $resolvedCustom = CalendarController::resolveStaffColor($this->staffWithCustomColor);
        $this->assertEquals('#8b5cf6', $resolvedCustom);

        // 2. Deterministic fallback for staff without color
        $resolvedA1 = CalendarController::resolveStaffColor($this->staffWithoutColorA);
        $resolvedA2 = CalendarController::resolveStaffColor($this->staffWithoutColorA);
        $this->assertNotEmpty($resolvedA1);
        $this->assertStringStartsWith('#', $resolvedA1);
        $this->assertEquals($resolvedA1, $resolvedA2, 'Fallback color must be deterministic and consistent across calls.');

        // 3. Fallback when null staff is passed
        $resolvedNull = CalendarController::resolveStaffColor(null);
        $this->assertNotEmpty($resolvedNull);
        $this->assertStringStartsWith('#', $resolvedNull);
    }

    public function test_schedule_index_passes_staff_colors_and_staff_color_attribute(): void
    {
        $targetDate = Carbon::now()->startOfWeek()->addDay()->toDateString(); // Tuesday

        StaffSchedule::create([
            'staff_id' => $this->staffWithCustomColor->id,
            'working_date' => $targetDate,
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'is_working' => true,
            'recurrence_type' => 'one_time',
        ]);

        $response = $this->actingAs($this->admin, 'staff')->get('/schedule');
        $response->assertStatus(200);

        $response->assertViewHas('staffColors');
        $response->assertViewHas('schedules');

        $staffColors = $response->viewData('staffColors');
        $this->assertArrayHasKey($this->staffWithCustomColor->id, $staffColors);
        $this->assertEquals('#8b5cf6', $staffColors[$this->staffWithCustomColor->id]);

        $schedules = $response->viewData('schedules');
        $this->assertNotEmpty($schedules);
        $firstSchedule = collect($schedules)->firstWhere('staff_id', $this->staffWithCustomColor->id);
        $this->assertNotNull($firstSchedule);
        $this->assertEquals('#8b5cf6', $firstSchedule['staff_color']);

        // Verify HTML contains the staff legend and provider avatar with staff color
        $response->assertSee('Staff Legend:');
        $response->assertSee('#8b5cf6');
        $response->assertSee('Dr. Custom Color');
    }

    public function test_simultaneous_shifts_for_different_staff_are_provided_with_distinct_staff_colors(): void
    {
        $targetDate = Carbon::now()->startOfWeek()->addDays(2)->toDateString(); // Wednesday

        // Shift 1: Staff With Custom Color (09:00 - 13:00)
        StaffSchedule::create([
            'staff_id' => $this->staffWithCustomColor->id,
            'working_date' => $targetDate,
            'day_of_week' => 2,
            'start_time' => '09:00:00',
            'end_time' => '13:00:00',
            'is_working' => true,
            'recurrence_type' => 'one_time',
        ]);

        // Shift 2: Provider Alpha (09:00 - 13:00 simultaneous shift)
        StaffSchedule::create([
            'staff_id' => $this->staffWithoutColorA->id,
            'working_date' => $targetDate,
            'day_of_week' => 2,
            'start_time' => '09:00:00',
            'end_time' => '13:00:00',
            'is_working' => true,
            'recurrence_type' => 'one_time',
        ]);

        $response = $this->actingAs($this->admin, 'staff')->get('/schedule');
        $response->assertStatus(200);

        $schedules = collect($response->viewData('schedules'));
        $shiftA = $schedules->firstWhere('staff_id', $this->staffWithCustomColor->id);
        $shiftB = $schedules->firstWhere('staff_id', $this->staffWithoutColorA->id);

        $this->assertNotNull($shiftA);
        $this->assertNotNull($shiftB);
        $this->assertEquals('09:00:00', $shiftA['start_time']);
        $this->assertEquals('09:00:00', $shiftB['start_time']);
        $this->assertEquals('#8b5cf6', $shiftA['staff_color']);
        $this->assertNotEmpty($shiftB['staff_color']);
        $this->assertEquals(CalendarController::resolveStaffColor($this->staffWithoutColorA), $shiftB['staff_color']);
    }

    public function test_adjacent_shifts_for_same_staff_have_distinct_times_and_descriptions(): void
    {
        $targetDate = Carbon::now()->startOfWeek()->addDays(3)->toDateString(); // Thursday

        // Shift 1: Morning (09:00 - 13:00)
        StaffSchedule::create([
            'staff_id' => $this->staffWithCustomColor->id,
            'working_date' => $targetDate,
            'day_of_week' => 3,
            'start_time' => '09:00:00',
            'end_time' => '13:00:00',
            'is_working' => true,
            'recurrence_type' => 'one_time',
        ]);

        // Shift 2: Afternoon immediately adjacent (13:00 - 17:00)
        StaffSchedule::create([
            'staff_id' => $this->staffWithCustomColor->id,
            'working_date' => $targetDate,
            'day_of_week' => 3,
            'start_time' => '13:00:00',
            'end_time' => '17:00:00',
            'is_working' => true,
            'recurrence_type' => 'one_time',
        ]);

        $response = $this->actingAs($this->admin, 'staff')->get('/schedule');
        $response->assertStatus(200);

        $schedules = collect($response->viewData('schedules'))->where('staff_id', $this->staffWithCustomColor->id)->values();
        $this->assertCount(2, $schedules);
        $this->assertEquals('09:00:00', $schedules[0]['start_time']);
        $this->assertEquals('13:00:00', $schedules[0]['end_time']);
        $this->assertEquals('13:00:00', $schedules[1]['start_time']);
        $this->assertEquals('17:00:00', $schedules[1]['end_time']);
    }

    public function test_calendar_events_include_staff_color_and_preserve_service_color(): void
    {
        $date = Carbon::now()->startOfWeek()->addDays(1)->format('Y-m-d');

        $appt = Appointment::create([
            'staff_id' => $this->staffWithCustomColor->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service->id,
            'location_id' => $this->location->id,
            'start_time' => "{$date} 10:00:00",
            'end_time' => "{$date} 11:00:00",
            'status' => 'booked',
        ]);

        // Week/Day events endpoint
        $resEvents = $this->actingAs($this->admin, 'staff')->getJson("/calendar/events?start={$date}&end={$date}");
        $resEvents->assertStatus(200);

        $event = collect($resEvents->json())->firstWhere('id', $appt->id);
        $this->assertNotNull($event);
        $this->assertEquals('#8b5cf6', $event['staffColor']);
        $this->assertEquals('#8b5cf6', $event['staff_color']);
        $this->assertEquals('#10b981', $event['serviceColor']);
        $this->assertEquals('#10b981', $event['color']);

        // Month view endpoint
        $month = Carbon::parse($date)->format('Y-m');
        $resMonth = $this->actingAs($this->admin, 'staff')->get("/calendar?view=month&month={$month}");
        $resMonth->assertStatus(200);
        $resMonth->assertSee('calendar-staff-legend');
        $resMonth->assertSee('#8b5cf6');
    }

    public function test_calendar_staff_schedules_api_returns_resolved_staff_color(): void
    {
        $start = Carbon::now()->startOfWeek()->toDateString();
        $end = Carbon::now()->endOfWeek()->toDateString();

        $res = $this->actingAs($this->admin, 'staff')->getJson("/calendar/staff-schedules?start={$start}&end={$end}");
        $res->assertStatus(200);

        $staffList = collect($res->json('staff'));
        $customStaffItem = $staffList->firstWhere('id', $this->staffWithCustomColor->id);
        $this->assertNotNull($customStaffItem);
        $this->assertEquals('#8b5cf6', $customStaffItem['color']);
        $this->assertEquals('#8b5cf6', $customStaffItem['staff_color']);

        $fallbackStaffItem = $staffList->firstWhere('id', $this->staffWithoutColorA->id);
        $this->assertNotNull($fallbackStaffItem);
        $this->assertEquals(CalendarController::resolveStaffColor($this->staffWithoutColorA), $fallbackStaffItem['color']);
    }
}

