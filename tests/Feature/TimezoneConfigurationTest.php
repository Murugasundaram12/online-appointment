<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\BusinessSetting;
use App\Models\Client;
use App\Models\Location;
use App\Models\Service;
use App\Models\Staff;
use App\Models\StaffSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TimezoneConfigurationTest extends TestCase
{
    use RefreshDatabase;

    private Staff $adminStaff;
    private Staff $staff;
    private Location $location;
    private Service $service;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->location = Location::create([
            'name' => 'Toronto Clinic',
            'is_active' => true,
        ]);

        $this->adminStaff = Staff::create([
            'name' => 'Admin Staff',
            'email' => 'admin_tz@example.com',
            'role' => 'admin',
            'access_level' => 'admin',
            'is_active' => true,
            'password' => Hash::make('password123'),
            'location_id' => $this->location->id,
        ]);

        $this->staff = Staff::create([
            'name' => 'Dr. Toronto Practitioner',
            'email' => 'practitioner_tz@example.com',
            'password' => Hash::make('Password123'),
            'access_level' => 'admin',
            'role' => 'admin',
            'location_id' => $this->location->id,
            'is_active' => true,
        ]);

        $this->service = Service::create([
            'name' => 'Consultation',
            'type' => 'in_person',
            'price' => 100.00,
            'duration_minutes' => 60,
            'buffer_minutes' => 0,
            'color' => '#3699ff',
            'is_active' => true,
        ]);

        $this->client = Client::create([
            'name' => 'Jane Toronto',
            'email' => 'jane.toronto@example.com',
            'phone' => '416-555-0199',
        ]);
    }

    /**
     * 1. Application timezone is America/Toronto.
     */
    public function test_application_timezone_is_america_toronto(): void
    {
        $this->assertSame('America/Toronto', config('app.timezone'));
        $this->assertSame('America/Toronto', date_default_timezone_get());
        $this->assertSame('America/Toronto', now()->timezoneName);
        $this->assertSame('America/Toronto', Carbon::now()->timezoneName);
    }

    /**
     * 2. Appointment displays in Toronto local time.
     */
    public function test_appointment_displays_in_toronto_local_time(): void
    {
        $appointment = Appointment::create([
            'client_id' => $this->client->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'location_id' => $this->location->id,
            'start_time' => '2026-09-28 10:00:00',
            'end_time' => '2026-09-28 11:00:00',
            'status' => 'booked',
        ]);

        $this->actingAs($this->adminStaff, 'staff');

        $response = $this->getJson('/calendar/appointments/' . $appointment->id);
        $response->assertOk();

        // In September, Toronto is in Daylight Saving Time (EDT, UTC-4)
        $isoStart = $appointment->start_time->toIso8601String();
        $this->assertStringContainsString('2026-09-28T10:00:00-04:00', $isoStart);

        $payload = $response->json();
        $apptData = $payload['appointment'] ?? $payload;
        $this->assertSame('2026-09-28 10:00:00', Carbon::parse($apptData['start'])->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-28 11:00:00', Carbon::parse($apptData['end'])->format('Y-m-d H:i:s'));
    }

    /**
     * 3. Calendar Month/Week/Date use Toronto time.
     */
    public function test_calendar_views_use_toronto_time(): void
    {
        $appointment = Appointment::create([
            'client_id' => $this->client->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'location_id' => $this->location->id,
            'start_time' => '2026-10-15 14:30:00',
            'end_time' => '2026-10-15 15:30:00',
            'status' => 'booked',
        ]);

        $this->actingAs($this->adminStaff, 'staff');

        // Month view endpoint
        $responseMonth = $this->get('/calendar?view=month&month=2026-10');
        $responseMonth->assertOk();
        $responseMonth->assertSee('2026-10-15T14:30:00-04:00');

        // Week view events endpoint
        $responseWeek = $this->getJson('/calendar/events?start=2026-10-11&end=2026-10-18');
        $responseWeek->assertOk();
        $events = $responseWeek->json();
        $matching = collect($events)->firstWhere('id', $appointment->id);
        $this->assertNotNull($matching);
        $this->assertSame('2026-10-15T14:30:00-04:00', $matching['start']);
        $this->assertSame('2026-10-15T15:30:00-04:00', $matching['end']);

        // Day view events endpoint
        $responseDay = $this->getJson('/calendar/events?start=2026-10-15&end=2026-10-15');
        $responseDay->assertOk();
        $dayEvents = $responseDay->json();
        $this->assertNotEmpty(collect($dayEvents)->where('id', $appointment->id));
    }

    /**
     * 4. Staff availability uses Toronto time.
     */
    public function test_staff_availability_uses_toronto_time(): void
    {
        // Dr. Toronto works on Mondays (day 0) from 09:00 to 17:00 with lunch break 12:00 to 13:00
        StaffSchedule::create([
            'staff_id' => $this->staff->id,
            'day_of_week' => 0, // Monday
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'is_working' => true,
            'breaks' => [
                ['start' => '12:00:00', 'end' => '13:00:00'],
            ],
        ]);

        $this->actingAs($this->adminStaff, 'staff');

        // 2026-09-28 is a Monday. Test slot within working hours: 10:00 - 11:00
        $resAvailable = $this->getJson('/calendar/available-staff?' . http_build_query([
            'start_time' => '2026-09-28T10:00:00',
            'end_time' => '2026-09-28T11:00:00',
            'service_id' => $this->service->id,
        ]));
        $resAvailable->assertOk();
        $staffIds = collect($resAvailable->json('staff') ?? $resAvailable->json('available_staff'))->pluck('id')->all();
        $this->assertContains($this->staff->id, $staffIds);

        // Test slot overlapping lunch break: 12:30 - 13:30 (should NOT be available)
        $resBreak = $this->getJson('/calendar/available-staff?' . http_build_query([
            'start_time' => '2026-09-28T12:30:00',
            'end_time' => '2026-09-28T13:30:00',
            'service_id' => $this->service->id,
        ]));
        $resBreak->assertOk();
        $breakStaffIds = collect($resBreak->json('staff') ?? $resBreak->json('available_staff'))->pluck('id')->all();
        $this->assertNotContains($this->staff->id, $breakStaffIds);

        // Test slot before working hours: 07:00 - 08:00 (should NOT be available)
        $resEarly = $this->getJson('/calendar/available-staff?' . http_build_query([
            'start_time' => '2026-09-28T07:00:00',
            'end_time' => '2026-09-28T08:00:00',
            'service_id' => $this->service->id,
        ]));
        $resEarly->assertOk();
        $earlyStaffIds = collect($resEarly->json('staff') ?? $resEarly->json('available_staff'))->pluck('id')->all();
        $this->assertNotContains($this->staff->id, $earlyStaffIds);
    }

    /**
     * 5. Reminder calculation uses Toronto time (23h-25h window, duplicate protection).
     */
    public function test_reminder_calculation_uses_toronto_time(): void
    {
        Mail::fake();

        // Fix "now" in America/Toronto
        $knownNow = Carbon::create(2026, 9, 25, 10, 0, 0, 'America/Toronto');
        Carbon::setTestNow($knownNow);

        // Appointment exactly 24 hours ahead in Toronto time
        $appointment = Appointment::create([
            'client_id' => $this->client->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'location_id' => $this->location->id,
            'start_time' => $knownNow->copy()->addHours(24)->format('Y-m-d H:i:s'),
            'end_time' => $knownNow->copy()->addHours(25)->format('Y-m-d H:i:s'),
            'status' => 'booked',
        ]);

        $this->artisan('appointments:send-reminders')
            ->expectsOutputToContain('Found 1 appointment(s) to process.')
            ->assertSuccessful();

        $appointment->refresh();
        $this->assertNotNull($appointment->reminder_sent_at);

        // Duplicate protection: running again should not process this appointment
        $this->artisan('appointments:send-reminders')
            ->expectsOutputToContain('No appointments found requiring reminders.')
            ->assertSuccessful();

        Carbon::setTestNow();
    }

    /**
     * 6. No-show calculation evaluates using Toronto time and preserves policy.
     */
    public function test_no_show_calculation_uses_toronto_time(): void
    {
        $knownNow = Carbon::create(2026, 9, 25, 16, 0, 0, 'America/Toronto');
        Carbon::setTestNow($knownNow);

        // Past booked appointment
        $appointment = Appointment::create([
            'client_id' => $this->client->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'location_id' => $this->location->id,
            'start_time' => $knownNow->copy()->subHours(3)->format('Y-m-d H:i:s'),
            'end_time' => $knownNow->copy()->subHours(2)->format('Y-m-d H:i:s'),
            'status' => 'booked',
        ]);

        $this->artisan('appointments:mark-no-show')
            ->expectsOutputToContain('Evaluation time (America/Toronto): ' . $knownNow->toDateTimeString())
            ->expectsOutputToContain('Automatic transition to No Show is disabled.')
            ->assertSuccessful();

        // Appointment remains in booked status (policy preserved)
        $appointment->refresh();
        $this->assertSame('booked', $appointment->status);

        Carbon::setTestNow();
    }

    /**
     * 7. DST transition does not cause a one-hour scheduling error.
     */
    public function test_dst_transition_does_not_cause_scheduling_error(): void
    {
        // In Toronto, DST ends on Sunday Nov 1, 2026 at 2:00 AM (clocks move back 1 hour, EDT -> EST)
        // Appointment before DST end (EDT, UTC-4)
        $edtAppointment = Appointment::create([
            'client_id' => $this->client->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'location_id' => $this->location->id,
            'start_time' => '2026-10-31 10:00:00',
            'end_time' => '2026-10-31 11:00:00',
            'status' => 'booked',
        ]);

        // Appointment after DST end (EST, UTC-5)
        $estAppointment = Appointment::create([
            'client_id' => $this->client->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'location_id' => $this->location->id,
            'start_time' => '2026-11-02 10:00:00',
            'end_time' => '2026-11-02 11:00:00',
            'status' => 'booked',
        ]);

        // Both appointments must retain their exact 10:00 wall-clock start time
        $this->assertSame('10:00:00', $edtAppointment->start_time->format('H:i:s'));
        $this->assertSame('-04:00', $edtAppointment->start_time->format('P'));
        $this->assertSame(60, $edtAppointment->start_time->diffInMinutes($edtAppointment->end_time));

        $this->assertSame('10:00:00', $estAppointment->start_time->format('H:i:s'));
        $this->assertSame('-05:00', $estAppointment->start_time->format('P'));
        $this->assertSame(60, $estAppointment->start_time->diffInMinutes($estAppointment->end_time));

        // Spring forward DST transition: March 8, 2026 (EST -> EDT)
        $springAppointment = Appointment::create([
            'client_id' => $this->client->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'location_id' => $this->location->id,
            'start_time' => '2026-03-09 14:00:00',
            'end_time' => '2026-03-09 15:00:00',
            'status' => 'booked',
        ]);

        $this->assertSame('14:00:00', $springAppointment->start_time->format('H:i:s'));
        $this->assertSame('-04:00', $springAppointment->start_time->format('P'));
        $this->assertSame(60, $springAppointment->start_time->diffInMinutes($springAppointment->end_time));
    }

    /**
     * 8. Existing appointment database values remain unchanged.
     */
    public function test_existing_appointment_database_values_remain_unchanged(): void
    {
        $rawTimeString = '2026-09-28 15:45:00';
        $appointment = Appointment::create([
            'client_id' => $this->client->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'location_id' => $this->location->id,
            'start_time' => $rawTimeString,
            'end_time' => '2026-09-28 16:45:00',
            'status' => 'booked',
        ]);

        // Verify the raw stored value directly in the database without model casting
        $dbRaw = DB::table('appointments')->where('id', $appointment->id)->value('start_time');
        $this->assertSame($rawTimeString, $dbRaw);

        // When retrieved via Eloquent, wall-clock time matches raw DB string
        $fresh = Appointment::find($appointment->id);
        $this->assertSame($rawTimeString, $fresh->start_time->format('Y-m-d H:i:s'));
        $this->assertSame('America/Toronto', $fresh->start_time->timezoneName);
    }
}
