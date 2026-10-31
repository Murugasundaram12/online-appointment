<?php

namespace Tests\Feature;

use App\Http\Controllers\CalendarController;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Location;
use App\Models\Service;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CalendarStaffBackgroundColorTest extends TestCase
{
    use RefreshDatabase;

    protected Staff $adminStaff;
    protected Staff $staffA;
    protected Staff $staffB;
    protected Staff $staffC;
    protected Location $location;
    protected Service $serviceRed;
    protected Service $serviceGreen;
    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->location = Location::create([
            'name' => 'Care Clinic',
            'is_active' => true,
        ]);

        $this->adminStaff = Staff::create([
            'name' => 'Clinic Admin',
            'email' => 'admin_bg_test@example.com',
            'access_level' => 'admin',
            'is_active' => true,
            'password' => Hash::make('password123'),
            'location_id' => $this->location->id,
        ]);

        // Staff A with Blue
        $this->staffA = Staff::create([
            'name' => 'Dr. Staff Blue',
            'email' => 'staff_a_blue@example.com',
            'access_level' => 'staff',
            'is_active' => true,
            'color' => '#3b82f6',
            'location_id' => $this->location->id,
        ]);

        // Staff B with Purple
        $this->staffB = Staff::create([
            'name' => 'Dr. Staff Purple',
            'email' => 'staff_b_purple@example.com',
            'access_level' => 'staff',
            'is_active' => true,
            'color' => '#8b5cf6',
            'location_id' => $this->location->id,
        ]);

        // Staff C without custom color (null)
        $this->staffC = Staff::create([
            'name' => 'Dr. Staff Natural',
            'email' => 'staff_c_natural@example.com',
            'access_level' => 'staff',
            'is_active' => true,
            'color' => null,
            'location_id' => $this->location->id,
        ]);

        // Services
        $this->serviceRed = Service::create([
            'name' => 'Physiotherapy',
            'color' => '#ef4444',
            'duration_minutes' => 30,
            'price' => 80,
            'is_active' => true,
        ]);

        $this->serviceGreen = Service::create([
            'name' => 'Massage Therapy',
            'color' => '#10b981',
            'duration_minutes' => 60,
            'price' => 110,
            'is_active' => true,
        ]);

        $this->client = Client::create([
            'name' => 'Alice Wonder',
            'email' => 'alice@example.com',
            'phone' => '555-9876',
        ]);
    }

    /**
     * 1. Staff A appointment uses Staff A custom color.
     */
    public function test_staff_a_appointment_uses_staff_a_custom_color(): void
    {
        $date = Carbon::now()->startOfWeek()->addDay()->format('Y-m-d');

        $apptA = Appointment::create([
            'staff_id' => $this->staffA->id,
            'client_id' => $this->client->id,
            'service_id' => $this->serviceRed->id,
            'location_id' => $this->location->id,
            'start_time' => "{$date} 09:00:00",
            'end_time' => "{$date} 09:30:00",
            'status' => 'booked',
        ]);

        $res = $this->actingAs($this->adminStaff, 'staff')
            ->getJson("/calendar/events?start={$date}&end={$date}");
        $res->assertStatus(200);

        $eventA = collect($res->json())->firstWhere('id', $apptA->id);
        $this->assertNotNull($eventA);
        $this->assertEquals('#3b82f6', $eventA['staffColor']);
        $this->assertEquals('#3b82f6', $eventA['staff_color']);
    }

    /**
     * 2. Staff B appointment uses Staff B custom color.
     */
    public function test_staff_b_appointment_uses_staff_b_custom_color(): void
    {
        $date = Carbon::now()->startOfWeek()->addDay()->format('Y-m-d');

        $apptB = Appointment::create([
            'staff_id' => $this->staffB->id,
            'client_id' => $this->client->id,
            'service_id' => $this->serviceGreen->id,
            'location_id' => $this->location->id,
            'start_time' => "{$date} 10:00:00",
            'end_time' => "{$date} 11:00:00",
            'status' => 'booked',
        ]);

        $res = $this->actingAs($this->adminStaff, 'staff')
            ->getJson("/calendar/events?start={$date}&end={$date}");
        $res->assertStatus(200);

        $eventB = collect($res->json())->firstWhere('id', $apptB->id);
        $this->assertNotNull($eventB);
        $this->assertEquals('#8b5cf6', $eventB['staffColor']);
        $this->assertEquals('#8b5cf6', $eventB['staff_color']);
    }

    /**
     * 3. Staff without custom color uses deterministic fallback.
     */
    public function test_staff_without_custom_color_uses_deterministic_fallback(): void
    {
        $date = Carbon::now()->startOfWeek()->addDay()->format('Y-m-d');

        $apptC = Appointment::create([
            'staff_id' => $this->staffC->id,
            'client_id' => $this->client->id,
            'service_id' => $this->serviceRed->id,
            'location_id' => $this->location->id,
            'start_time' => "{$date} 11:30:00",
            'end_time' => "{$date} 12:00:00",
            'status' => 'booked',
        ]);

        $res = $this->actingAs($this->adminStaff, 'staff')
            ->getJson("/calendar/events?start={$date}&end={$date}");
        $res->assertStatus(200);

        $eventC = collect($res->json())->firstWhere('id', $apptC->id);
        $this->assertNotNull($eventC);

        $expectedFallback = CalendarController::resolveStaffColor($this->staffC);
        $this->assertNotEmpty($expectedFallback);
        $this->assertEquals($expectedFallback, $eventC['staffColor']);
    }

    /**
     * 4. Unassigned Staff appointment uses safe fallback.
     */
    public function test_unassigned_staff_appointment_uses_safe_fallback(): void
    {
        $safeFallback = CalendarController::resolveStaffColor(null);
        $this->assertNotEmpty($safeFallback);
        $this->assertStringStartsWith('#', $safeFallback);
    }

    /**
     * 5. Week and Day View rendering uses Staff background color in client script.
     */
    public function test_calendar_blade_view_renders_staff_color_as_full_background(): void
    {
        $res = $this->actingAs($this->adminStaff, 'staff')->get('/calendar');
        $res->assertStatus(200);

        $content = $res->getContent();
        // Check for getAppointmentStaffColor helper
        $this->assertStringContainsString('function getAppointmentStaffColor(ev)', $content);
        // Check for staffColor as full appointment card background
        $this->assertStringContainsString('const staffColor = getAppointmentStaffColor(ev);', $content);
        $this->assertStringContainsString('el.style.background = staffColor;', $content);
        $this->assertStringContainsString('el.style.backgroundColor = staffColor;', $content);
        $this->assertStringContainsString('el.style.borderColor = staffColor;', $content);
        // Check for high-contrast text color
        $this->assertStringContainsString('const textColor = getContrastColor(staffColor);', $content);
        $this->assertStringContainsString('el.style.color = textColor;', $content);
    }

    /**
     * 6. Month View FullCalendar events use Staff color as background.
     */
    public function test_month_view_uses_staff_color_as_background(): void
    {
        $date = Carbon::now()->startOfWeek()->addDay()->format('Y-m-d');
        $month = Carbon::parse($date)->format('Y-m');

        Appointment::create([
            'staff_id' => $this->staffA->id,
            'client_id' => $this->client->id,
            'service_id' => $this->serviceRed->id,
            'location_id' => $this->location->id,
            'start_time' => "{$date} 14:00:00",
            'end_time' => "{$date} 14:30:00",
            'status' => 'booked',
        ]);

        $res = $this->actingAs($this->adminStaff, 'staff')->get("/calendar?view=month&month={$month}");
        $res->assertStatus(200);

        $content = $res->getContent();
        // Month view eventDidMount sets staffColor
        $this->assertStringContainsString('info.el.style.backgroundColor = staffColor;', $content);
        $this->assertStringContainsString('info.el.style.borderColor = staffColor;', $content);
        // HTML contains Staff A color in staffColor payload
        $this->assertStringContainsString('#3b82f6', $content);
    }

    /**
     * 7. Existing appointment interactions and Service workflows remain functional.
     */
    public function test_existing_service_workflow_and_client_details_preserved(): void
    {
        $date = Carbon::now()->startOfWeek()->addDay()->format('Y-m-d');

        $appt = Appointment::create([
            'staff_id' => $this->staffA->id,
            'client_id' => $this->client->id,
            'service_id' => $this->serviceRed->id,
            'location_id' => $this->location->id,
            'start_time' => "{$date} 15:00:00",
            'end_time' => "{$date} 15:30:00",
            'status' => 'booked',
        ]);

        $res = $this->actingAs($this->adminStaff, 'staff')
            ->getJson("/calendar/events?start={$date}&end={$date}");
        $res->assertStatus(200);

        $event = collect($res->json())->firstWhere('id', $appt->id);
        $this->assertNotNull($event);
        // Client details preserved
        $this->assertEquals('Alice Wonder', $event['clientName']);
        // Service details preserved
        $this->assertEquals('Physiotherapy', $event['service']);
        $this->assertEquals('#ef4444', $event['serviceColor']);
        // Staff color is primary staff background
        $this->assertEquals('#3b82f6', $event['staffColor']);
        // Status preserved
        $this->assertEquals('booked', $event['status']);
    }

    /**
     * 8. Service color does not override Staff background color.
     */
    public function test_service_color_does_not_override_staff_background(): void
    {
        $date = Carbon::now()->startOfWeek()->addDay()->format('Y-m-d');

        // Staff A is Blue (#3b82f6); Service Green is Green (#10b981)
        $appt = Appointment::create([
            'staff_id' => $this->staffA->id,
            'client_id' => $this->client->id,
            'service_id' => $this->serviceGreen->id,
            'location_id' => $this->location->id,
            'start_time' => "{$date} 11:00:00",
            'end_time' => "{$date} 12:00:00",
            'status' => 'booked',
        ]);

        $res = $this->actingAs($this->adminStaff, 'staff')
            ->getJson("/calendar/events?start={$date}&end={$date}");
        $res->assertStatus(200);

        $event = collect($res->json())->firstWhere('id', $appt->id);
        $this->assertNotNull($event);
        // Staff color is resolved to Staff A's configured color (#3b82f6)
        $this->assertEquals('#3b82f6', $event['staffColor']);
        $this->assertEquals('#3b82f6', $event['staff_color']);
        // Service color is preserved for service metadata
        $this->assertEquals('#10b981', $event['serviceColor']);

        // Check Calendar view script to ensure getAppointmentStaffColor is used for el.style.background
        $resView = $this->actingAs($this->adminStaff, 'staff')->get('/calendar');
        $resView->assertStatus(200);
        $content = $resView->getContent();
        $this->assertStringContainsString('const staffColor = getAppointmentStaffColor(ev);', $content);
        $this->assertStringContainsString('el.style.background = staffColor;', $content);
        $this->assertStringContainsString("el.style.setProperty('background', staffColor, 'important');", $content);
    }

    /**
     * 9. Calendar views and schedule items have no hardcoded green background or thin left border.
     */
    public function test_calendar_views_and_schedule_items_have_no_hardcoded_green_background_or_thin_left_border(): void
    {
        $res = $this->actingAs($this->adminStaff, 'staff')->get('/calendar');
        $res->assertStatus(200);

        $content = $res->getContent();
        // Ensure hardcoded rgb(11, 128, 67) green is completely removed from calendar styles
        $this->assertStringNotContainsString('rgb(11, 128, 67)', $content);
        // Ensure staff schedule items in column header do not use thin left border
        $this->assertStringNotContainsString('item.style.borderLeft = `3px solid ${staffCol}`;', $content);
        $this->assertStringContainsString("item.style.setProperty('border-left', 'none', 'important');", $content);
        // Ensure staff schedule items use full staffCol background
        $this->assertStringContainsString("item.style.setProperty('background', staffCol, 'important');", $content);
        $this->assertStringContainsString("item.style.setProperty('background-color', staffCol, 'important');", $content);
        // Ensure appointment cards enforce full staffColor background with important
        $this->assertStringContainsString("el.style.setProperty('background', staffColor, 'important');", $content);
        $this->assertStringContainsString("el.style.setProperty('background-color', staffColor, 'important');", $content);
    }
}

