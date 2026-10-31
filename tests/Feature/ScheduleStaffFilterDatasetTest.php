<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Staff;
use App\Models\StaffSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ScheduleStaffFilterDatasetTest extends TestCase
{
    use RefreshDatabase;

    protected Location $locationA;
    protected Location $locationB;
    protected Staff $admin;
    protected Staff $staffA;
    protected Staff $staffB;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2026, 9, 16, 10, 0, 0)); // Wednesday in 2026-09-14 week

        $this->locationA = Location::create([
            'name' => 'Downtown Clinic',
            'is_active' => true,
        ]);

        $this->locationB = Location::create([
            'name' => 'Uptown Clinic',
            'is_active' => true,
        ]);

        $this->admin = Staff::create([
            'name' => 'Clinic Admin',
            'email' => 'admin_filter@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
            'access_level' => 'admin',
            'is_active' => true,
            'color' => '#2563eb',
            'location_id' => $this->locationA->id,
        ]);

        $this->staffA = Staff::create([
            'name' => 'Dr. Alice',
            'email' => 'alice_filter@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'staff',
            'access_level' => 'staff',
            'is_active' => true,
            'color' => '#10b981',
            'location_id' => $this->locationA->id,
        ]);

        $this->staffB = Staff::create([
            'name' => 'Dr. Bob',
            'email' => 'bob_filter@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'staff',
            'access_level' => 'staff',
            'is_active' => true,
            'color' => '#f59e0b',
            'location_id' => $this->locationA->id,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * 1. When opening /schedule?staff_id={id}&week_date=..., the complete weekly dataset is returned.
     */
    public function test_individual_staff_url_receives_complete_weekly_dataset(): void
    {
        $targetMonday = Carbon::parse('2026-09-14');

        // Staff A schedule
        StaffSchedule::create([
            'staff_id' => $this->staffA->id,
            'working_date' => $targetMonday->toDateString(),
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'is_working' => true,
            'recurrence_type' => 'one_time',
        ]);

        // Staff B schedule
        StaffSchedule::create([
            'staff_id' => $this->staffB->id,
            'working_date' => $targetMonday->copy()->addDay()->toDateString(),
            'day_of_week' => 2,
            'start_time' => '13:00:00',
            'end_time' => '17:00:00',
            'is_working' => true,
            'recurrence_type' => 'one_time',
        ]);

        $response = $this->actingAs($this->admin, 'staff')
            ->get('/schedule?staff_id=' . $this->staffB->id . '&week_date=2026-09-14');

        $response->assertOk();

        // Selected staff is Staff B
        $this->assertEquals($this->staffB->id, $response->viewData('currentStaffId'));
        $this->assertEquals($this->staffB->id, $response->viewData('currentStaff')->id);
        $this->assertFalse($response->viewData('isAllStaff'));

        // Complete dataset for the week contains BOTH staff schedules
        $schedules = collect($response->viewData('schedules'));
        $this->assertCount(2, $schedules);
        $this->assertTrue($schedules->contains('staff_id', $this->staffA->id));
        $this->assertTrue($schedules->contains('staff_id', $this->staffB->id));

        // Frontend script receives full dataset in JSON
        $content = $response->getContent();
        $this->assertStringContainsString('activeStaffFilter = isAllStaff ? \'all\' : (currentStaffId', $content);
        $this->assertStringContainsString('filterScheduleTableRows()', $content);
        $this->assertStringContainsString('class="schedule-entry-row" data-staff-id="' . $this->staffA->id . '"', $content);
        $this->assertStringContainsString('class="schedule-entry-row" data-staff-id="' . $this->staffB->id . '"', $content);
    }

    /**
     * 2. All Staff request receives all schedules for the week and sets currentStaffId to 'all'.
     */
    public function test_all_staff_request_receives_all_schedules_and_sets_all_flag(): void
    {
        $targetMonday = Carbon::parse('2026-09-14');

        StaffSchedule::create([
            'staff_id' => $this->staffA->id,
            'working_date' => $targetMonday->toDateString(),
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'is_working' => true,
            'recurrence_type' => 'one_time',
        ]);

        StaffSchedule::create([
            'staff_id' => $this->staffB->id,
            'working_date' => $targetMonday->toDateString(),
            'day_of_week' => 1,
            'start_time' => '13:00:00',
            'end_time' => '17:00:00',
            'is_working' => true,
            'recurrence_type' => 'one_time',
        ]);

        $response = $this->actingAs($this->admin, 'staff')
            ->get('/schedule?staff_id=all&week_date=2026-09-14');

        $response->assertOk();
        $this->assertTrue($response->viewData('isAllStaff'));
        $this->assertEquals('all', $response->viewData('currentStaffId'));
        $this->assertNull($response->viewData('currentStaff'));

        $schedules = collect($response->viewData('schedules'));
        $this->assertCount(2, $schedules);

        $content = $response->getContent();
        // Hidden input has value="all"
        $this->assertStringContainsString('<input type="hidden" name="staff_id" value="all">', $content);
    }

    /**
     * 3. Filter form preserves staff_id=all on location and status filter changes.
     */
    public function test_filter_form_preserves_staff_id_all(): void
    {
        $response = $this->actingAs($this->admin, 'staff')
            ->get('/schedule?staff_id=all');

        $response->assertOk();
        $content = $response->getContent();

        // The hidden staff_id input must have value="all" when All Staff is selected
        $this->assertStringContainsString('<input type="hidden" name="staff_id" value="all">', $content);

        // Submitting with location filter and staff_id=all
        $resLocation = $this->actingAs($this->admin, 'staff')
            ->get('/schedule?staff_id=all&location_id=' . $this->locationA->id);

        $resLocation->assertOk();
        $this->assertTrue($resLocation->viewData('isAllStaff'));
        $this->assertEquals('all', $resLocation->viewData('currentStaffId'));
    }

    /**
     * 4. Filter form preserves individual staff_id on filter changes.
     */
    public function test_filter_form_preserves_individual_staff_id(): void
    {
        $response = $this->actingAs($this->admin, 'staff')
            ->get('/schedule?staff_id=' . $this->staffA->id);

        $response->assertOk();
        $content = $response->getContent();

        // The hidden staff_id input must have value of staffA id
        $this->assertStringContainsString('<input type="hidden" name="staff_id" value="' . $this->staffA->id . '">', $content);

        // Submitting with status filter
        $resStatus = $this->actingAs($this->admin, 'staff')
            ->get('/schedule?staff_id=' . $this->staffA->id . '&status=working');

        $resStatus->assertOk();
        $this->assertEquals($this->staffA->id, $resStatus->viewData('currentStaffId'));
    }

    /**
     * 5. Week navigation preserves staff_id=all and active staff selection.
     */
    public function test_week_navigation_preserves_staff_selection(): void
    {
        $responseAll = $this->actingAs($this->admin, 'staff')
            ->get('/schedule?staff_id=all');
        $responseAll->assertOk();
        $contentAll = $responseAll->getContent();

        // Check navigation listeners use activeStaffFilter
        $this->assertStringContainsString("if (activeStaffFilter) qs.set('staff_id', String(activeStaffFilter));", $contentAll);

        // Navigating with week_date and staff_id=all
        $resNext = $this->actingAs($this->admin, 'staff')
            ->get('/schedule?staff_id=all&week_date=2026-09-21');
        $resNext->assertOk();
        $this->assertTrue($resNext->viewData('isAllStaff'));
        $this->assertEquals('all', $resNext->viewData('currentStaffId'));
    }

    /**
     * 6. Does not load schedules outside the requested week date range.
     */
    public function test_does_not_load_schedules_outside_requested_week(): void
    {
        $inWeekDate = Carbon::parse('2026-09-15'); // inside 2026-09-14 week
        $outsideWeekDate = Carbon::parse('2026-09-25'); // outside 2026-09-14 week

        StaffSchedule::create([
            'staff_id' => $this->staffA->id,
            'working_date' => $inWeekDate->toDateString(),
            'day_of_week' => 2,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'is_working' => true,
            'recurrence_type' => 'one_time',
        ]);

        StaffSchedule::create([
            'staff_id' => $this->staffB->id,
            'working_date' => $outsideWeekDate->toDateString(),
            'day_of_week' => 5,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'is_working' => true,
            'recurrence_type' => 'one_time',
        ]);

        $response = $this->actingAs($this->admin, 'staff')
            ->get('/schedule?week_date=2026-09-14');

        $response->assertOk();
        $schedules = collect($response->viewData('schedules'));

        $this->assertCount(1, $schedules);
        $this->assertEquals($this->staffA->id, $schedules->first()['staff_id']);
    }
}

