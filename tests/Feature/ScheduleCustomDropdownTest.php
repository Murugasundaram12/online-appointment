<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Staff;
use App\Models\StaffSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ScheduleCustomDropdownTest extends TestCase
{
    use RefreshDatabase;

    protected Location $location;
    protected Staff $admin;
    protected Staff $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->location = Location::create([
            'name' => 'Main Health Clinic',
            'is_active' => true,
        ]);

        $this->admin = Staff::create([
            'name' => 'Clinic Admin',
            'email' => 'admin_dropdown@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
            'access_level' => 'admin',
            'is_active' => true,
            'color' => '#2563eb',
            'location_id' => $this->location->id,
        ]);

        $this->staff = Staff::create([
            'name' => 'Dr. Jessica Taylor',
            'email' => 'jessica_dropdown@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'staff',
            'access_level' => 'staff',
            'is_active' => true,
            'color' => '#8b5cf6',
            'location_id' => $this->location->id,
        ]);
    }

    public function test_custom_dropdown_markup_renders_for_recurring_entries(): void
    {
        $targetMonday = Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        $targetTuesday = Carbon::now()->startOfWeek(Carbon::MONDAY)->addDay()->format('Y-m-d');
        $targetSunday = Carbon::now()->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');

        // Create recurring schedule group with multiple occurrences (first, middle, last rows)
        $groupId = 'group-uuid-test-dropdown';
        StaffSchedule::create([
            'staff_id' => $this->staff->id,
            'location_id' => $this->location->id,
            'day_of_week' => 1,
            'working_date' => $targetMonday,
            'recurrence_type' => 'weekly',
            'start_date' => $targetMonday,
            'end_date' => $targetSunday,
            'weekly_days' => [1, 2],
            'start_time' => '09:00:00',
            'end_time' => '13:00:00',
            'is_working' => true,
            'recurrence_group_id' => $groupId,
        ]);

        StaffSchedule::create([
            'staff_id' => $this->staff->id,
            'location_id' => $this->location->id,
            'day_of_week' => 2,
            'working_date' => $targetTuesday,
            'recurrence_type' => 'weekly',
            'start_date' => $targetMonday,
            'end_date' => $targetSunday,
            'weekly_days' => [1, 2],
            'start_time' => '14:00:00',
            'end_time' => '18:00:00',
            'is_working' => true,
            'recurrence_group_id' => $groupId,
        ]);

        $response = $this->actingAs($this->admin, 'staff')->get('/schedule?staff_id=' . $this->staff->id);
        $response->assertStatus(200);

        // Assert custom dropdown classes are present
        $response->assertSee('custom-dropdown', false);
        $response->assertSee('custom-dropdown-trigger', false);
        $response->assertSee('custom-dropdown-menu', false);

        // Edit dropdown items
        $response->assertSee('Edit this occurrence only', false);
        $response->assertSee('Edit entire recurring schedule', false);
        $response->assertSee('scope=occurrence', false);
        $response->assertSee('scope=group', false);

        // Delete dropdown items
        $response->assertSee('Delete this occurrence', false);
        $response->assertSee('Skip this occurrence', false);
        $response->assertSee('Delete entire schedule', false);

        // Icons
        $response->assertSee('bx-calendar-event', false);
        $response->assertSee('bx-repeat', false);
        $response->assertSee('bx-calendar-x', false);
        $response->assertSee('bx-block', false);
        $response->assertSee('bx-trash', false);

        // Dropdown chevrons
        $response->assertSee('bx-chevron-down', false);

        // Ensure Bootstrap dropdown toggle attributes are NOT used on Schedule Entries table
        $response->assertDontSee('data-bs-boundary="viewport"', false);
        $response->assertDontSee('btn-light border dropdown-toggle', false);
        $response->assertDontSee('btn-outline-danger dropdown-toggle', false);

        // Ensure JavaScript dropdown manager is loaded
        $response->assertSee('initCustomDropdowns()', false);
    }
}
