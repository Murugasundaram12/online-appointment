<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Location;
use App\Models\Service;
use App\Models\Staff;
use App\Models\StaffSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

class AppointmentNoShowRegressionTest extends TestCase
{
    use RefreshDatabase;

    private Staff $adminStaff;
    private Service $service;
    private Location $location;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->location = Location::create([
            'name' => 'Regression Clinic',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        $this->adminStaff = Staff::create([
            'location_id' => $this->location->id,
            'name' => 'Regression Staff',
            'email' => 'admin_noshow_reg@example.com',
            'password' => Hash::make('password123'),
            'access_level' => 'admin',
            'is_active' => true,
        ]);

        for ($day = 0; $day <= 6; $day++) {
            StaffSchedule::create([
                'staff_id' => $this->adminStaff->id,
                'day_of_week' => (string) $day,
                'start_time' => '08:00:00',
                'end_time' => '20:00:00',
                'is_working' => true,
            ]);
        }

        $this->service = Service::create([
            'name' => 'Consultation',
            'price' => 50.00,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $this->client = Client::create([
            'first_name' => 'Jane',
            'last_name' => 'Client',
            'email' => 'jane_client@example.com',
            'phone' => '1234567890',
        ]);
    }

    /**
     * Test expired booked appointment remains booked.
     */
    public function test_expired_booked_appointment_remains_booked(): void
    {
        $pastStart = Carbon::now()->subHours(5);
        $pastEnd = Carbon::now()->subHours(4);

        $appt = Appointment::create([
            'client_id' => $this->client->id,
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'location_id' => $this->location->id,
            'start_time' => $pastStart,
            'end_time' => $pastEnd,
            'status' => 'booked',
        ]);

        // Run the command that used to automatically mark no-show
        Artisan::call('appointments:mark-no-show');

        // Status must NEVER automatically change
        $this->assertEquals('booked', $appt->fresh()->status);

        // Fetching events on calendar also does not change status
        $response = $this->actingAs($this->adminStaff, 'staff')
            ->getJson('/calendar/events?start=' . $pastStart->copy()->subDay()->toISOString() . '&end=' . $pastEnd->copy()->addDay()->toISOString());
        $response->assertStatus(200);

        $this->assertEquals('booked', $appt->fresh()->status);
    }

    /**
     * Test expired confirmed appointment remains confirmed.
     */
    public function test_expired_confirmed_appointment_remains_confirmed(): void
    {
        $pastStart = Carbon::now()->subHours(3);
        $pastEnd = Carbon::now()->subHours(2);

        $appt = Appointment::create([
            'client_id' => $this->client->id,
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'location_id' => $this->location->id,
            'start_time' => $pastStart,
            'end_time' => $pastEnd,
            'status' => 'confirmed',
        ]);

        Artisan::call('appointments:mark-no-show');

        $this->assertEquals('confirmed', $appt->fresh()->status);
    }

    /**
     * Test expired pending appointment remains pending.
     */
    public function test_expired_pending_appointment_remains_pending(): void
    {
        $pastStart = Carbon::now()->subHours(6);
        $pastEnd = Carbon::now()->subHours(5);

        $appt = Appointment::create([
            'client_id' => $this->client->id,
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'location_id' => $this->location->id,
            'start_time' => $pastStart,
            'end_time' => $pastEnd,
            'status' => 'pending',
        ]);

        Artisan::call('appointments:mark-no-show');

        $this->assertEquals('pending', $appt->fresh()->status);
    }

    /**
     * Test reminder command continues working without altering appointment statuses.
     */
    public function test_reminder_command_continues_working_without_altering_statuses(): void
    {
        // An appointment in the reminder window (24h ahead)
        $futureStart = Carbon::now()->addHours(24);
        $futureEnd = Carbon::now()->addHours(25);

        $appt = Appointment::create([
            'client_id' => $this->client->id,
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'location_id' => $this->location->id,
            'start_time' => $futureStart,
            'end_time' => $futureEnd,
            'status' => 'booked',
        ]);

        // Also an expired appointment
        $pastAppt = Appointment::create([
            'client_id' => $this->client->id,
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::now()->subHours(4),
            'end_time' => Carbon::now()->subHours(3),
            'status' => 'confirmed',
        ]);

        $exitCode = Artisan::call('appointments:send-reminders', ['--dry-run' => true]);
        $this->assertEquals(0, $exitCode);

        // Neither appointment status has changed
        $this->assertEquals('booked', $appt->fresh()->status);
        $this->assertEquals('confirmed', $pastAppt->fresh()->status);
    }

    /**
     * Test explicit manual status update changes appointment to no_show.
     */
    public function test_explicit_manual_status_update_to_no_show_succeeds(): void
    {
        $pastStart = Carbon::now()->subHours(2);
        $pastEnd = Carbon::now()->subHours(1);

        $appt = Appointment::create([
            'client_id' => $this->client->id,
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'location_id' => $this->location->id,
            'start_time' => $pastStart,
            'end_time' => $pastEnd,
            'status' => 'booked',
        ]);

        // Explicit manual update to no_show
        $response = $this->actingAs($this->adminStaff, 'staff')
            ->putJson("/calendar/appointments/{$appt->id}", [
                'status' => 'no_show',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertEquals('no_show', $appt->fresh()->status);
    }

    /**
     * Test completed, cancelled, and no_show statuses remain terminal.
     */
    public function test_completed_cancelled_no_show_remain_unchanged(): void
    {
        $completed = Appointment::create([
            'client_id' => $this->client->id,
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::now()->subHours(10),
            'end_time' => Carbon::now()->subHours(9),
            'status' => 'completed',
        ]);

        $cancelled = Appointment::create([
            'client_id' => $this->client->id,
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::now()->subHours(8),
            'end_time' => Carbon::now()->subHours(7),
            'status' => 'cancelled',
        ]);

        $noShow = Appointment::create([
            'client_id' => $this->client->id,
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::now()->subHours(6),
            'end_time' => Carbon::now()->subHours(5),
            'status' => 'no_show',
        ]);

        Artisan::call('appointments:mark-no-show');

        $this->assertEquals('completed', $completed->fresh()->status);
        $this->assertEquals('cancelled', $cancelled->fresh()->status);
        $this->assertEquals('no_show', $noShow->fresh()->status);
    }
}
