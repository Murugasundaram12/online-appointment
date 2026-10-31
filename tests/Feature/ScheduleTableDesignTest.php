<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Staff;
use App\Models\StaffSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ScheduleTableDesignTest extends TestCase
{
    use RefreshDatabase;

    protected Location $location;
    protected Staff $admin;
    protected Staff $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->location = Location::create([
            'name' => 'Main Clinic',
            'is_active' => true,
        ]);

        $this->admin = Staff::create([
            'name' => 'Admin Manager',
            'email' => 'admin_table@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
            'access_level' => 'admin',
            'is_active' => true,
            'color' => '#2563eb',
            'location_id' => $this->location->id,
        ]);

        $this->staff = Staff::create([
            'name' => 'Dr. Alexander Hamilton',
            'email' => 'alex_table@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'staff',
            'access_level' => 'staff',
            'is_active' => true,
            'color' => '#3b82f6',
            'location_id' => $this->location->id,
        ]);
    }

    public function test_schedule_table_renders_modern_dashboard_card_and_elements(): void
    {
        $targetMonday = Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        $targetSunday = Carbon::now()->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');

        // Create weekly recurring entry
        StaffSchedule::create([
            'staff_id' => $this->staff->id,
            'location_id' => $this->location->id,
            'day_of_week' => 1,
            'working_date' => $targetMonday,
            'recurrence_type' => 'weekly',
            'start_date' => $targetMonday,
            'end_date' => $targetSunday,
            'weekly_days' => [1],
            'start_time' => '08:30:00',
            'end_time' => '16:30:00',
            'is_working' => true,
            'recurrence_group_id' => 'group-table-test-1',
        ]);

        $response = $this->actingAs($this->admin, 'staff')->get('/schedule?staff_id=' . $this->staff->id);
        $response->assertStatus(200);

        // Card header & toolbar
        $response->assertSee('schedule-entries-section', false);
        $response->assertSee('schedule-entries-card', false);
        $response->assertSee('Schedule Entries', false);
        $response->assertSee('id="schedule-entries-count"', false);
        $response->assertSee('id="schedule-table-search-input"', false);
        $response->assertSee('placeholder="Search staff, category, type..."', false);

        // Table headers
        $response->assertSee('schedule-table', false);
        $response->assertSee('Staff', false);
        $response->assertSee('Type', false);
        $response->assertSee('Recurrence', false);
        $response->assertSee('Working Date', false);
        $response->assertSee('Start', false);
        $response->assertSee('End', false);
        $response->assertSee('From', false);
        $response->assertSee('To', false);
        $response->assertSee('Status', false);
        $response->assertSee('Actions', false);

        // Staff row details
        $response->assertSee('staff-row-name', false);
        $response->assertSee('Dr. Alexander Hamilton', false);
        $response->assertSee('avatar-initials', false);
        $response->assertSee('DA', false);

        // Type & status badges
        $response->assertSee('schedule-type-badge', false);
        $response->assertSee('Weekly', false);
        $response->assertSee('schedule-time-pill', false);
        $response->assertSee('08:30', false);
        $response->assertSee('16:30', false);
        $response->assertSee('badge-status-pill', false);
        $response->assertSee('Working', false);

        // Custom action dropdowns
        $response->assertSee('custom-dropdown', false);
        $response->assertSee('custom-dropdown-trigger', false);
        $response->assertSee('custom-dropdown-menu', false);

        // Client filter empty state row exists
        $response->assertSee('id="no-staff-schedules-row"', false);
    }

    public function test_schedule_table_renders_empty_state_when_no_schedules(): void
    {
        $response = $this->actingAs($this->admin, 'staff')->get('/schedule?staff_id=' . $this->staff->id);
        $response->assertStatus(200);

        // Server empty state row
        $response->assertSee('id="no-schedules-server-row"', false);
        $response->assertSee('No schedules found', false);
    }
}
