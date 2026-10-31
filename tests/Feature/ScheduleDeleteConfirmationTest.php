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

class ScheduleDeleteConfirmationTest extends TestCase
{
    use RefreshDatabase;

    protected Location $location;
    protected Staff $admin;
    protected Staff $staff;
    protected Client $client;
    protected Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->location = Location::create([
            'name' => 'City Clinic',
            'is_active' => true,
        ]);

        $this->admin = Staff::create([
            'name' => 'Admin User',
            'email' => 'admin_del@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
            'access_level' => 'admin',
            'is_active' => true,
            'location_id' => $this->location->id,
        ]);

        $this->staff = Staff::create([
            'name' => 'Dr. Robert Davis',
            'email' => 'robert_del@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'staff',
            'access_level' => 'staff',
            'is_active' => true,
            'color' => '#10b981',
            'location_id' => $this->location->id,
        ]);

        $this->client = Client::create([
            'name' => 'Jane Patient',
            'email' => 'jane@example.com',
            'phone' => '1234567890',
        ]);

        $this->service = Service::create([
            'name' => 'General Consultation',
            'duration_minutes' => 30,
            'price' => 50,
            'is_active' => true,
        ]);
    }

    public function test_schedule_delete_forms_use_bootstrap_modal_confirm_instead_of_native_dialog(): void
    {
        $targetMonday = Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        $targetSunday = Carbon::now()->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');

        // 1. Recurring weekly schedule occurrence
        StaffSchedule::create([
            'staff_id' => $this->staff->id,
            'location_id' => $this->location->id,
            'day_of_week' => 1,
            'working_date' => $targetMonday,
            'recurrence_type' => 'weekly',
            'start_date' => $targetMonday,
            'end_date' => $targetSunday,
            'weekly_days' => [1, 2, 3],
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'is_working' => true,
            'recurrence_group_id' => 'group-uuid-12345',
        ]);

        // 2. Non-recurring schedule
        StaffSchedule::create([
            'staff_id' => $this->staff->id,
            'location_id' => $this->location->id,
            'day_of_week' => 1,
            'working_date' => $targetMonday,
            'recurrence_type' => 'one_time',
            'start_time' => '18:00:00',
            'end_time' => '20:00:00',
            'is_working' => true,
        ]);

        $response = $this->actingAs($this->admin, 'staff')->get('/schedule?staff_id=' . $this->staff->id);
        $response->assertStatus(200);

        // Native confirm must not be present in schedule delete actions
        $response->assertDontSee("confirm('Delete this occurrence only?')", false);
        $response->assertDontSee("confirm('Delete entire recurring schedule group?')", false);
        $response->assertDontSee("confirm('Delete this schedule?')", false);
        $response->assertDontSee("confirm('Skip this occurrence?", false);

        // Bootstrap 5 modal confirm attributes for "Delete This Occurrence"
        $response->assertSee('data-confirm="Are you sure you want to delete this schedule occurrence?"', false);
        $response->assertSee('data-confirm-title="Confirm Schedule Deletion"', false);
        $response->assertSee('data-confirm-text="Yes, Delete"', false);
        $response->assertSee('data-confirm-class="btn-danger"', false);

        // Bootstrap 5 modal confirm attributes for "Delete Entire Schedule"
        $response->assertSee('data-confirm="Are you sure you want to delete the entire recurring schedule? All occurrences in this recurring group will be affected."', false);
        $response->assertSee('data-confirm-text="Yes, Delete Entire Schedule"', false);

        // Record info with staff name and date
        $response->assertSee('Staff: Dr. Robert Davis', false);

        // Non-recurring schedule delete
        $response->assertSee('data-confirm="Are you sure you want to delete this schedule?"', false);
    }

    public function test_delete_occurrence_deletes_only_single_occurrence_and_preserves_group(): void
    {
        $targetMonday = Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        $targetTuesday = Carbon::now()->startOfWeek(Carbon::MONDAY)->addDay()->format('Y-m-d');
        $groupId = 'group-uuid-test-999';

        $occurrence1 = StaffSchedule::create([
            'staff_id' => $this->staff->id,
            'location_id' => $this->location->id,
            'day_of_week' => 1,
            'working_date' => $targetMonday,
            'recurrence_type' => 'weekly',
            'start_date' => $targetMonday,
            'end_date' => $targetTuesday,
            'weekly_days' => [1, 2],
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'is_working' => true,
            'recurrence_group_id' => $groupId,
        ]);

        $occurrence2 = StaffSchedule::create([
            'staff_id' => $this->staff->id,
            'location_id' => $this->location->id,
            'day_of_week' => 2,
            'working_date' => $targetTuesday,
            'recurrence_type' => 'weekly',
            'start_date' => $targetMonday,
            'end_date' => $targetTuesday,
            'weekly_days' => [1, 2],
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'is_working' => true,
            'recurrence_group_id' => $groupId,
        ]);

        $response = $this->actingAs($this->admin, 'staff')->delete(route('schedule.destroy', $occurrence1->id), [
            'scope' => 'occurrence',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('staff_schedules', ['id' => $occurrence1->id]);
        $this->assertDatabaseHas('staff_schedules', ['id' => $occurrence2->id]);
    }

    public function test_delete_group_deletes_all_occurrences_in_recurring_schedule(): void
    {
        $targetMonday = Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        $targetTuesday = Carbon::now()->startOfWeek(Carbon::MONDAY)->addDay()->format('Y-m-d');
        $groupId = 'group-uuid-all-delete';

        $occurrence1 = StaffSchedule::create([
            'staff_id' => $this->staff->id,
            'location_id' => $this->location->id,
            'day_of_week' => 1,
            'working_date' => $targetMonday,
            'recurrence_type' => 'weekly',
            'start_date' => $targetMonday,
            'end_date' => $targetTuesday,
            'weekly_days' => [1, 2],
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'is_working' => true,
            'recurrence_group_id' => $groupId,
        ]);

        $occurrence2 = StaffSchedule::create([
            'staff_id' => $this->staff->id,
            'location_id' => $this->location->id,
            'day_of_week' => 2,
            'working_date' => $targetTuesday,
            'recurrence_type' => 'weekly',
            'start_date' => $targetMonday,
            'end_date' => $targetTuesday,
            'weekly_days' => [1, 2],
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'is_working' => true,
            'recurrence_group_id' => $groupId,
        ]);

        $response = $this->actingAs($this->admin, 'staff')->delete(route('schedule.destroy', $occurrence1->id), [
            'scope' => 'group',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('staff_schedules', ['id' => $occurrence1->id]);
        $this->assertDatabaseMissing('staff_schedules', ['id' => $occurrence2->id]);
    }

    public function test_delete_is_blocked_when_conflicting_appointment_exists(): void
    {
        $targetMonday = Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');

        $schedule = StaffSchedule::create([
            'staff_id' => $this->staff->id,
            'location_id' => $this->location->id,
            'day_of_week' => 1,
            'working_date' => $targetMonday,
            'recurrence_type' => 'one_time',
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'is_working' => true,
        ]);

        // Create conflicting appointment
        Appointment::create([
            'staff_id' => $this->staff->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service->id,
            'location_id' => $this->location->id,
            'start_time' => $targetMonday . ' 10:00:00',
            'end_time' => $targetMonday . ' 10:30:00',
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($this->admin, 'staff')->delete(route('schedule.destroy', $schedule->id));

        $response->assertSessionHas('error', 'This schedule cannot be removed because appointments already exist during this time.');
        $this->assertDatabaseHas('staff_schedules', ['id' => $schedule->id]);
    }
}
