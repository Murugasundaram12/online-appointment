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

class StaffManualColorPickerTest extends TestCase
{
    use RefreshDatabase;

    protected Staff $adminStaff;
    protected Location $location;
    protected Service $service;
    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->location = Location::create([
            'name' => 'Metro Health Clinic',
            'is_active' => true,
        ]);

        $this->adminStaff = Staff::create([
            'name' => 'Clinic Admin',
            'email' => 'admin_color_picker@example.com',
            'access_level' => 'admin',
            'is_active' => true,
            'password' => Hash::make('password123'),
            'location_id' => $this->location->id,
        ]);

        $this->service = Service::create([
            'name' => 'Acupuncture',
            'color' => '#f59e0b',
            'duration_minutes' => 45,
            'price' => 85,
            'is_active' => true,
        ]);

        $this->client = Client::create([
            'name' => 'Sarah Connor',
            'email' => 'sarah@example.com',
            'phone' => '555-4321',
        ]);
    }

    /**
     * 1. Creating Staff with a manually selected color.
     */
    public function test_create_staff_with_manually_selected_color(): void
    {
        $response = $this->actingAs($this->adminStaff, 'staff')
            ->post(route('staff.store'), [
                'name' => 'Dr. Violet Bloom',
                'email' => 'violet@example.com',
                'color' => '#8b5cf6',
                'location_id' => $this->location->id,
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('staff.index'));

        $this->assertDatabaseHas('staff', [
            'email' => 'violet@example.com',
            'name' => 'Dr. Violet Bloom',
            'color' => '#8b5cf6',
        ]);

        $staff = Staff::where('email', 'violet@example.com')->first();
        $this->assertEquals('#8b5cf6', CalendarController::resolveStaffColor($staff));
    }

    /**
     * 2. Updating an existing Staff color.
     */
    public function test_update_existing_staff_color(): void
    {
        $staff = Staff::create([
            'name' => 'Dr. Emerald',
            'email' => 'emerald@example.com',
            'color' => '#10b981',
            'location_id' => $this->location->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminStaff, 'staff')
            ->put(route('staff.update', $staff->id), [
                'name' => 'Dr. Emerald Updated',
                'email' => 'emerald@example.com',
                'color' => '#ec4899', // Change to pink
                'location_id' => $this->location->id,
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('staff.index'));

        $staff->refresh();
        $this->assertEquals('#ec4899', $staff->color);
        $this->assertEquals('#ec4899', CalendarController::resolveStaffColor($staff));
    }

    /**
     * 3. Existing Staff color is displayed in the Edit form view.
     */
    public function test_existing_staff_color_is_displayed_in_edit_form(): void
    {
        $staff = Staff::create([
            'name' => 'Dr. Cyan Wave',
            'email' => 'cyan@example.com',
            'color' => '#06b6d4',
            'location_id' => $this->location->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminStaff, 'staff')
            ->get(route('staff.edit', $staff->id));

        $response->assertStatus(200);
        $response->assertSee('value="#06b6d4"', false);
        $response->assertSee('id="edit_staff_color_picker"', false);
        $response->assertSee('Staff Color');
    }

    /**
     * 4. Invalid color values are rejected.
     */
    public function test_invalid_color_values_are_rejected(): void
    {
        $invalidColors = ['not-a-color', '#12345', '123456', '#1234567', 'blue'];

        foreach ($invalidColors as $invalid) {
            $response = $this->actingAs($this->adminStaff, 'staff')
                ->post(route('staff.store'), [
                    'name' => 'Invalid Color Staff',
                    'email' => "invalid_{$invalid}@example.com",
                    'color' => $invalid,
                ]);

            $response->assertSessionHasErrors(['color']);
            $this->assertDatabaseMissing('staff', ['email' => "invalid_{$invalid}@example.com"]);
        }
    }

    /**
     * 5. Staff without a custom color uses the existing deterministic fallback.
     */
    public function test_staff_without_custom_color_uses_deterministic_fallback(): void
    {
        $response = $this->actingAs($this->adminStaff, 'staff')
            ->post(route('staff.store'), [
                'name' => 'Dr. No Color',
                'email' => 'nocolor@example.com',
                'color' => '', // Left blank
                'location_id' => $this->location->id,
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('staff', [
            'email' => 'nocolor@example.com',
            'color' => null,
        ]);

        $staff = Staff::where('email', 'nocolor@example.com')->first();
        $this->assertNull($staff->color);

        $resolved = CalendarController::resolveStaffColor($staff);
        $this->assertNotEmpty($resolved);
        $this->assertStringStartsWith('#', $resolved);
        $this->assertEquals($resolved, CalendarController::resolveStaffColor($staff));
    }

    /**
     * 6. Manually selected colors override fallback colors.
     */
    public function test_manually_selected_color_overrides_fallback_palette(): void
    {
        $staff = Staff::create([
            'name' => 'Dr. Override Test',
            'email' => 'override@example.com',
            'color' => null,
            'location_id' => $this->location->id,
            'is_active' => true,
        ]);

        $fallbackColor = CalendarController::resolveStaffColor($staff);
        $this->assertNotEmpty($fallbackColor);

        // Explicitly set a different custom color
        $customColor = ($fallbackColor === '#e11d48') ? '#0284c7' : '#e11d48';
        $staff->update(['color' => $customColor]);

        $this->assertEquals($customColor, CalendarController::resolveStaffColor($staff));
        $this->assertNotEquals($fallbackColor, CalendarController::resolveStaffColor($staff));
    }

    /**
     * 7. Schedule uses the saved custom color.
     */
    public function test_schedule_uses_saved_custom_color(): void
    {
        $customStaff = Staff::create([
            'name' => 'Dr. Schedule Custom',
            'email' => 'sched_custom@example.com',
            'color' => '#7c3aed',
            'location_id' => $this->location->id,
            'is_active' => true,
        ]);

        $targetDate = Carbon::now()->startOfWeek()->addDay()->toDateString();

        StaffSchedule::create([
            'staff_id' => $customStaff->id,
            'working_date' => $targetDate,
            'day_of_week' => 1,
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'is_working' => true,
            'recurrence_type' => 'one_time',
        ]);

        $response = $this->actingAs($this->adminStaff, 'staff')->get('/schedule');
        $response->assertStatus(200);

        $staffColors = $response->viewData('staffColors');
        $this->assertEquals('#7c3aed', $staffColors[$customStaff->id]);

        $schedules = collect($response->viewData('schedules'));
        $shift = $schedules->firstWhere('staff_id', $customStaff->id);
        $this->assertNotNull($shift);
        $this->assertEquals('#7c3aed', $shift['staff_color']);
    }

    /**
     * 8. Calendar uses the same saved custom color.
     */
    public function test_calendar_uses_same_saved_custom_color(): void
    {
        $customStaff = Staff::create([
            'name' => 'Dr. Calendar Custom',
            'email' => 'cal_custom@example.com',
            'color' => '#ea580c',
            'location_id' => $this->location->id,
            'is_active' => true,
        ]);

        $targetDate = Carbon::now()->startOfWeek()->addDay()->format('Y-m-d');

        $appt = Appointment::create([
            'staff_id' => $customStaff->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service->id,
            'location_id' => $this->location->id,
            'start_time' => "{$targetDate} 10:00:00",
            'end_time' => "{$targetDate} 10:45:00",
            'status' => 'booked',
        ]);

        $resEvents = $this->actingAs($this->adminStaff, 'staff')
            ->getJson("/calendar/events?start={$targetDate}&end={$targetDate}");
        $resEvents->assertStatus(200);

        $event = collect($resEvents->json())->firstWhere('id', $appt->id);
        $this->assertNotNull($event);
        $this->assertEquals('#ea580c', $event['staffColor']);
        $this->assertEquals('#ea580c', $event['staff_color']);
    }

    /**
     * 9. Existing Staff creation and editing workflows remain functional.
     */
    public function test_existing_staff_creation_and_editing_workflows_remain_functional(): void
    {
        // Create staff via normal form without color
        $createRes = $this->actingAs($this->adminStaff, 'staff')
            ->post(route('staff.store'), [
                'name' => 'Standard Practitioner',
                'email' => 'standard@example.com',
                'phone' => '1234567890',
                'designation' => 'Practitioner',
                'location_id' => $this->location->id,
            ]);

        $createRes->assertSessionHasNoErrors();
        $staff = Staff::where('email', 'standard@example.com')->first();
        $this->assertNotNull($staff);
        $this->assertEquals('Standard Practitioner', $staff->name);

        // Edit staff and add color
        $updateRes = $this->actingAs($this->adminStaff, 'staff')
            ->put(route('staff.update', $staff->id), [
                'name' => 'Standard Practitioner Renamed',
                'email' => 'standard@example.com',
                'color' => '#0d9488',
                'location_id' => $this->location->id,
            ]);

        $updateRes->assertSessionHasNoErrors();
        $staff->refresh();
        $this->assertEquals('Standard Practitioner Renamed', $staff->name);
        $this->assertEquals('#0d9488', $staff->color);
    }

    /**
     * 10. Existing Service color workflow remains unchanged.
     */
    public function test_existing_service_color_workflow_remains_unchanged(): void
    {
        $customStaff = Staff::create([
            'name' => 'Dr. Service Check',
            'email' => 'service_check@example.com',
            'color' => '#8b5cf6',
            'location_id' => $this->location->id,
            'is_active' => true,
        ]);

        $targetDate = Carbon::now()->startOfWeek()->addDay()->format('Y-m-d');

        $appt = Appointment::create([
            'staff_id' => $customStaff->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service->id, // Acupuncture (#f59e0b)
            'location_id' => $this->location->id,
            'start_time' => "{$targetDate} 14:00:00",
            'end_time' => "{$targetDate} 14:45:00",
            'status' => 'booked',
        ]);

        $resEvents = $this->actingAs($this->adminStaff, 'staff')
            ->getJson("/calendar/events?start={$targetDate}&end={$targetDate}");
        $resEvents->assertStatus(200);

        $event = collect($resEvents->json())->firstWhere('id', $appt->id);
        $this->assertNotNull($event);
        // Service color is preserved
        $this->assertEquals('#f59e0b', $event['serviceColor']);
        $this->assertEquals('#f59e0b', $event['color']);
        // Staff color is also present and accurate
        $this->assertEquals('#8b5cf6', $event['staffColor']);
    }
}

