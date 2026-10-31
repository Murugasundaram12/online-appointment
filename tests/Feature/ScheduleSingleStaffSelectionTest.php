<?php

namespace Tests\Feature;

use App\Http\Controllers\CalendarController;
use App\Models\Location;
use App\Models\Staff;
use App\Models\StaffSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ScheduleSingleStaffSelectionTest extends TestCase
{
    use RefreshDatabase;

    protected Location $location;
    protected Staff $admin;
    protected Staff $staffA;
    protected Staff $staffB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->location = Location::create([
            'name' => 'Main Health Clinic',
            'is_active' => true,
        ]);

        $this->admin = Staff::create([
            'name' => 'Clinic Admin',
            'email' => 'admin_single_staff@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
            'access_level' => 'admin',
            'is_active' => true,
            'color' => '#2563eb', // Blue
            'location_id' => $this->location->id,
        ]);

        $this->staffA = Staff::create([
            'name' => 'Dr. Alice Smith',
            'email' => 'alice_staff@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'staff',
            'access_level' => 'staff',
            'is_active' => true,
            'color' => '#8b5cf6', // Violet
            'location_id' => $this->location->id,
        ]);

        $this->staffB = Staff::create([
            'name' => 'Dr. Bob Jones',
            'email' => 'bob_staff@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'staff',
            'access_level' => 'staff',
            'is_active' => true,
            'color' => '#ea580c', // Orange
            'location_id' => $this->location->id,
        ]);
    }

    /**
     * 1. Initial Schedule page selects one default Staff member.
     */
    public function test_initial_schedule_page_selects_one_default_staff(): void
    {
        $response = $this->actingAs($this->admin, 'staff')->get('/schedule');
        $response->assertStatus(200);

        // Expect single staff selection
        $currentStaff = $response->viewData('currentStaff');
        $currentStaffId = $response->viewData('currentStaffId');
        $isAllStaff = $response->viewData('isAllStaff');

        $this->assertNotNull($currentStaff);
        // Default staff is the first active staff member ($this->admin)
        $this->assertEquals($this->admin->id, $currentStaff->id);
        $this->assertEquals($this->admin->id, $currentStaffId);
        $this->assertFalse($isAllStaff);

        $content = $response->getContent();
        // In the sidebar, default staff item is active
        $this->assertStringContainsString('data-staff-id="' . $this->admin->id . '"', $content);
        $this->assertStringContainsString('selectStaffFilter', $content);
    }

    /**
     * 2. Initial view does not display every Staff schedule (filters to selected single staff).
     */
    public function test_initial_view_does_not_display_every_staff_schedule(): void
    {
        $response = $this->actingAs($this->admin, 'staff')->get('/schedule');
        $response->assertStatus(200);

        $content = $response->getContent();

        // Check client-side filter logic in blade view
        $this->assertStringContainsString("if (activeStaffFilter !== 'all' && String(sch.staff_id) !== String(activeStaffFilter))", $content);
        // Default filter initialized to single staff ID, not 'all'
        $this->assertStringContainsString('activeStaffFilter = isAllStaff ? \'all\' : (currentStaffId', $content);
    }

    /**
     * 3. Selecting another Staff updates the visible schedule and highlights them.
     */
    public function test_selecting_another_staff_updates_visible_schedule(): void
    {
        $response = $this->actingAs($this->admin, 'staff')->get('/schedule?staff_id=' . $this->staffA->id);
        $response->assertStatus(200);

        $currentStaff = $response->viewData('currentStaff');
        $currentStaffId = $response->viewData('currentStaffId');
        $isAllStaff = $response->viewData('isAllStaff');

        $this->assertNotNull($currentStaff);
        $this->assertEquals($this->staffA->id, $currentStaff->id);
        $this->assertEquals($this->staffA->id, $currentStaffId);
        $this->assertFalse($isAllStaff);

        $content = $response->getContent();
        // Staff A is highlighted active
        $this->assertStringContainsString('data-staff-id="' . $this->staffA->id . '"', $content);
        $this->assertMatchesRegularExpression('/provider-item\s+active[^"]*"\s+data-staff-id="' . $this->staffA->id . '"/', $content);
    }

    /**
     * 4. All Staff option displays all schedules and sets active state on All Staff item.
     */
    public function test_all_staff_option_displays_all_schedules(): void
    {
        $targetDate = Carbon::now()->startOfWeek()->addDay()->toDateString();

        // Schedule for Staff A
        StaffSchedule::create([
            'staff_id' => $this->staffA->id,
            'working_date' => $targetDate,
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'is_working' => true,
            'recurrence_type' => 'one_time',
        ]);

        // Schedule for Staff B
        StaffSchedule::create([
            'staff_id' => $this->staffB->id,
            'working_date' => $targetDate,
            'day_of_week' => 1,
            'start_time' => '13:00:00',
            'end_time' => '17:00:00',
            'is_working' => true,
            'recurrence_type' => 'one_time',
        ]);

        $response = $this->actingAs($this->admin, 'staff')->get('/schedule?staff_id=all');
        $response->assertStatus(200);

        $isAllStaff = $response->viewData('isAllStaff');
        $currentStaffId = $response->viewData('currentStaffId');
        $schedules = collect($response->viewData('schedules'));

        $this->assertTrue($isAllStaff);
        $this->assertEquals('all', $currentStaffId);
        $this->assertCount(2, $schedules);
    }

    /**
     * 5. Day, Week and Month view toggles preserve the selected Staff filter.
     */
    public function test_day_week_and_month_preserve_selected_staff(): void
    {
        // 1. Day view with Staff B
        $resDay = $this->actingAs($this->admin, 'staff')->get('/schedule?staff_id=' . $this->staffB->id . '&range=today');
        $resDay->assertStatus(200);
        $this->assertEquals($this->staffB->id, $resDay->viewData('currentStaffId'));
        $this->assertEquals('today', $resDay->viewData('selectedRange'));

        // 2. Month view with Staff B
        $resMonth = $this->actingAs($this->admin, 'staff')->get('/schedule?staff_id=' . $this->staffB->id . '&range=this_month');
        $resMonth->assertStatus(200);
        $this->assertEquals($this->staffB->id, $resMonth->viewData('currentStaffId'));
        $this->assertEquals('this_month', $resMonth->viewData('selectedRange'));

        // 3. View toggle links preserve staff_id
        $content = $resMonth->getContent();
        $this->assertStringContainsString('staff_id=' . $this->staffB->id, $content);
    }

    /**
     * 6. Date navigation preserves selected Staff filter.
     */
    public function test_date_navigation_preserves_selected_staff(): void
    {
        $targetWeek = Carbon::now()->addWeeks(2)->startOfWeek()->toDateString();
        $response = $this->actingAs($this->admin, 'staff')->get('/schedule?staff_id=' . $this->staffA->id . '&week_date=' . $targetWeek);
        $response->assertStatus(200);

        $this->assertEquals($this->staffA->id, $response->viewData('currentStaffId'));
        $content = $response->getContent();

        // Client-side date buttons update preserving activeStaffFilter
        $this->assertStringContainsString("if (activeStaffFilter) qs.set('staff_id', String(activeStaffFilter));", $content);
    }

    /**
     * 7. Staff-specific colors remain correct for each staff member.
     */
    public function test_staff_specific_colors_remain_correct(): void
    {
        $targetDate = Carbon::now()->startOfWeek()->addDay()->toDateString();

        StaffSchedule::create([
            'staff_id' => $this->staffA->id,
            'working_date' => $targetDate,
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'is_working' => true,
            'recurrence_type' => 'one_time',
        ]);

        $response = $this->actingAs($this->admin, 'staff')->get('/schedule?staff_id=' . $this->staffA->id);
        $response->assertStatus(200);

        $staffColors = $response->viewData('staffColors');
        $this->assertEquals('#8b5cf6', $staffColors[$this->staffA->id]);
        $this->assertEquals('#ea580c', $staffColors[$this->staffB->id]);

        $schedules = collect($response->viewData('schedules'));
        $first = $schedules->first();
        $this->assertNotNull($first);
        $this->assertEquals('#8b5cf6', $first['staff_color']);
    }

    /**
     * 8. Existing schedule filters and workflows continue working.
     */
    public function test_existing_schedule_filters_and_workflows_continue_working(): void
    {
        $targetDate = Carbon::now()->startOfWeek()->addDay()->toDateString();

        // Working schedule
        StaffSchedule::create([
            'staff_id' => $this->staffA->id,
            'working_date' => $targetDate,
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'is_working' => true,
            'recurrence_type' => 'one_time',
        ]);

        // Day off schedule
        StaffSchedule::create([
            'staff_id' => $this->staffA->id,
            'working_date' => $targetDate,
            'day_of_week' => 1,
            'start_time' => null,
            'end_time' => null,
            'is_working' => false,
            'recurrence_type' => 'one_time',
        ]);

        // Filter by working only
        $resWorking = $this->actingAs($this->admin, 'staff')->get('/schedule?staff_id=' . $this->staffA->id . '&status=working');
        $resWorking->assertStatus(200);
        $this->assertCount(1, $resWorking->viewData('schedules'));
        $this->assertTrue($resWorking->viewData('schedules')[0]['is_working']);

        // Filter by off only
        $resOff = $this->actingAs($this->admin, 'staff')->get('/schedule?staff_id=' . $this->staffA->id . '&status=off');
        $resOff->assertStatus(200);
        $this->assertCount(1, $resOff->viewData('schedules'));
        $this->assertFalse($resOff->viewData('schedules')[0]['is_working']);
    }
}

