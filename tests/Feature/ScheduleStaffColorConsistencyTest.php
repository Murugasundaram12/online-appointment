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

class ScheduleStaffColorConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected Location $location;
    protected Staff $admin;
    protected Staff $staffWithCustomColor;
    protected Staff $staff59Fallback;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2026, 10, 28, 10, 0, 0));

        $this->location = Location::create([
            'name' => 'Main Health Clinic',
            'is_active' => true,
        ]);

        $this->admin = Staff::create([
            'name' => 'Clinic Admin',
            'email' => 'admin_color_test@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
            'access_level' => 'admin',
            'is_active' => true,
            'color' => '#2563eb',
            'location_id' => $this->location->id,
        ]);

        $this->staffWithCustomColor = Staff::create([
            'name' => 'Dr. Emerald Green',
            'email' => 'emerald@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'staff',
            'access_level' => 'staff',
            'is_active' => true,
            'color' => '#10b981', // Emerald green
            'location_id' => $this->location->id,
        ]);

        // Create staff with null color to test deterministic fallback
        $this->staff59Fallback = Staff::create([
            'name' => 'Udhayakumar Natarajan',
            'email' => 'udhayakumar@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'staff',
            'access_level' => 'staff',
            'is_active' => true,
            'color' => null, // null custom color
            'location_id' => $this->location->id,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * 1. Staff with configured color uses exact staff color across all payload fields.
     */
    public function test_staff_with_custom_configured_color_uses_exact_color_across_all_blocks(): void
    {
        $now = Carbon::now();
        $monday = $now->copy()->startOfWeek();

        StaffSchedule::create([
            'staff_id' => $this->staffWithCustomColor->id,
            'day_of_week' => 1,
            'working_date' => $monday->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'is_working' => true,
            'recurrence_type' => 'one_time',
        ]);

        $response = $this->actingAs($this->admin, 'staff')
            ->get(route('schedule.index', ['staff_id' => $this->staffWithCustomColor->id]));

        $response->assertOk();
        $response->assertViewHas('currentStaffColor', '#10b981');

        $schedules = $response->viewData('schedules');
        $this->assertNotEmpty($schedules);

        foreach ($schedules as $entry) {
            $this->assertEquals('#10b981', $entry['staff_color']);
            $this->assertEquals('#10b981', $entry['color']);
            $this->assertEquals('#10b981', $entry['backgroundColor']);
            $this->assertEquals('#10b981', $entry['borderColor']);
            $this->assertEquals('#10b981', $entry['staffColor']);
        }

        $staffColors = $response->viewData('staffColors');
        $this->assertEquals('#10b981', $staffColors[$this->staffWithCustomColor->id]);
    }

    /**
     * 2. Multi-day weekday and weekend shifts consistently receive the configured staff color.
     */
    public function test_multi_day_weekday_and_weekend_shifts_use_same_staff_color(): void
    {
        $now = Carbon::now();
        $monday = $now->copy()->startOfWeek();
        $wednesday = $monday->copy()->addDays(2);
        $saturday = $monday->copy()->addDays(5);
        $sunday = $monday->copy()->addDays(6);

        $days = [$monday, $wednesday, $saturday, $sunday];
        foreach ($days as $idx => $d) {
            StaffSchedule::create([
                'staff_id' => $this->staffWithCustomColor->id,
                'day_of_week' => $d->dayOfWeekIso,
                'working_date' => $d->toDateString(),
                'start_time' => '10:00:00',
                'end_time' => '16:00:00',
                'is_working' => true,
                'recurrence_type' => 'one_time',
            ]);
        }

        $response = $this->actingAs($this->admin, 'staff')
            ->get(route('schedule.index', ['staff_id' => $this->staffWithCustomColor->id]));

        $response->assertOk();
        $schedules = $response->viewData('schedules');
        $this->assertCount(4, $schedules);

        foreach ($schedules as $entry) {
            $this->assertEquals('#10b981', $entry['staff_color']);
        }
    }

    /**
     * 3. Staff without custom color uses deterministic fallback consistently.
     */
    public function test_staff_without_custom_color_uses_deterministic_fallback_consistently(): void
    {
        $now = Carbon::now();
        $tuesday = $now->copy()->startOfWeek()->addDays(1);
        $saturday = $now->copy()->startOfWeek()->addDays(5);

        $expectedColor = CalendarController::resolveStaffColor($this->staff59Fallback);
        $this->assertNotEmpty($expectedColor);

        StaffSchedule::create([
            'staff_id' => $this->staff59Fallback->id,
            'day_of_week' => 2,
            'working_date' => $tuesday->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '14:00:00',
            'is_working' => true,
            'recurrence_type' => 'one_time',
        ]);

        StaffSchedule::create([
            'staff_id' => $this->staff59Fallback->id,
            'day_of_week' => 6,
            'working_date' => $saturday->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'is_working' => true,
            'recurrence_type' => 'one_time',
        ]);

        $response = $this->actingAs($this->admin, 'staff')
            ->get(route('schedule.index', ['staff_id' => $this->staff59Fallback->id]));

        $response->assertOk();
        $response->assertViewHas('currentStaffColor', $expectedColor);

        $schedules = $response->viewData('schedules');
        $this->assertCount(2, $schedules);

        foreach ($schedules as $entry) {
            $this->assertEquals($expectedColor, $entry['staff_color']);
            $this->assertEquals($expectedColor, $entry['color']);
            $this->assertEquals($expectedColor, $entry['backgroundColor']);
            $this->assertEquals($expectedColor, $entry['borderColor']);
        }
    }

    /**
     * 4. All-staff view preserves each staff member's distinct color.
     */
    public function test_all_staff_view_preserves_distinct_colors_for_each_staff(): void
    {
        $now = Carbon::now();
        $thursday = $now->copy()->startOfWeek()->addDays(3);

        StaffSchedule::create([
            'staff_id' => $this->staffWithCustomColor->id,
            'day_of_week' => 4,
            'working_date' => $thursday->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '13:00:00',
            'is_working' => true,
            'recurrence_type' => 'one_time',
        ]);

        StaffSchedule::create([
            'staff_id' => $this->staff59Fallback->id,
            'day_of_week' => 4,
            'working_date' => $thursday->toDateString(),
            'start_time' => '14:00:00',
            'end_time' => '18:00:00',
            'is_working' => true,
            'recurrence_type' => 'one_time',
        ]);

        $response = $this->actingAs($this->admin, 'staff')
            ->get(route('schedule.index', ['staff_id' => 'all']));

        $response->assertOk();
        $schedules = $response->viewData('schedules');
        $this->assertCount(2, $schedules);

        $entryCustom = collect($schedules)->firstWhere('staff_id', $this->staffWithCustomColor->id);
        $entryFallback = collect($schedules)->firstWhere('staff_id', $this->staff59Fallback->id);

        $this->assertEquals('#10b981', $entryCustom['staff_color']);
        $this->assertEquals(
            CalendarController::resolveStaffColor($this->staff59Fallback),
            $entryFallback['staff_color']
        );
    }

    /**
     * 5. Day, Week, and Month views maintain selected staff color.
     */
    public function test_day_week_and_month_views_maintain_staff_color_consistency(): void
    {
        $now = Carbon::now();

        StaffSchedule::create([
            'staff_id' => $this->staffWithCustomColor->id,
            'day_of_week' => $now->dayOfWeekIso,
            'working_date' => $now->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '15:00:00',
            'is_working' => true,
            'recurrence_type' => 'one_time',
        ]);

        foreach (['today', 'this_week', 'this_month'] as $range) {
            $response = $this->actingAs($this->admin, 'staff')
                ->get(route('schedule.index', [
                    'staff_id' => $this->staffWithCustomColor->id,
                    'range' => $range,
                ]));

            $response->assertOk();
            $response->assertViewHas('currentStaffColor', '#10b981');
            $schedules = $response->viewData('schedules');
            $this->assertNotEmpty($schedules);
            $this->assertEquals('#10b981', $schedules[0]['staff_color']);
        }
    }

    /**
     * 6. Legitimate status indicators (Off badge and All Staff legend indicator) are preserved.
     */
    public function test_legitimate_status_indicators_are_preserved(): void
    {
        $now = Carbon::now();

        StaffSchedule::create([
            'staff_id' => $this->staffWithCustomColor->id,
            'day_of_week' => $now->dayOfWeekIso,
            'working_date' => $now->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'is_working' => false, // Non-working / Off
            'recurrence_type' => 'one_time',
        ]);

        $response = $this->actingAs($this->admin, 'staff')
            ->get(route('schedule.index', [
                'staff_id' => $this->staffWithCustomColor->id,
                'status' => 'off',
            ]));

        $response->assertOk();
        $response->assertSee('badge bg-secondary', false);
        $response->assertSee('Off', false);
    }

    /**
     * 7. View script and CSS prevent gray/blue overrides and enforce staff color with priority.
     */
    public function test_view_script_and_css_prevent_unintended_overrides(): void
    {
        $response = $this->actingAs($this->admin, 'staff')
            ->get(route('schedule.index', ['staff_id' => $this->staffWithCustomColor->id]));

        $response->assertOk();
        $html = $response->getContent();

        // Check window.CURRENT_STAFF_COLOR is set in Blade
        $this->assertStringContainsString('window.CURRENT_STAFF_COLOR = currentStaffColor;', $html);

        // Check CSS does not contain malformed stray closing brace
        $this->assertStringNotContainsString("filter: saturate(0.85);\n        }\n        }", $html);

        // Check inline style enforcement with !important
        $this->assertStringContainsString("setProperty('background-color', staffColor, 'important')", $html);
        $this->assertStringContainsString("setProperty('border-color', staffColor, 'important')", $html);
    }
}
