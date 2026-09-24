<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Staff;
use App\Models\Location;
use App\Models\Service;
use App\Models\Client;
use App\Models\Appointment;
use App\Models\StaffSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ServiceColorWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Staff $adminStaff;
    protected Staff $staff;
    protected Location $location;
    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->location = Location::create([
            'name' => 'City Clinic',
            'is_active' => true,
        ]);

        $this->adminStaff = Staff::create([
            'name' => 'Admin Boss',
            'email' => 'admin_color@example.com',
            'role' => 'admin',
            'access_level' => 'admin',
            'is_active' => true,
            'password' => bcrypt('password123'),
            'location_id' => $this->location->id,
        ]);

        $this->staff = Staff::create([
            'name' => 'Practitioner One',
            'email' => 'practitioner@example.com',
            'role' => 'staff',
            'access_level' => 'staff',
            'is_active' => true,
            'password' => null,
            'location_id' => $this->location->id,
        ]);

        StaffSchedule::create([
            'staff_id' => $this->staff->id,
            'working_date' => '2026-09-15',
            'day_of_week' => 1,
            'start_time' => '08:00:00',
            'end_time' => '18:00:00',
            'is_working' => true,
            'recurrence_type' => 'one_time',
        ]);

        $this->client = Client::create([
            'name' => 'Alice ColorTest',
            'email' => 'alice@example.com',
            'phone' => '1112223333',
        ]);
    }

    /**
     * Requirement 2 & 6:
     * A. Create Service with color Red (#FF0000).
     * B. Create appointment using that service.
     * C. Calendar event must use Red.
     * D. Change service to Blue (#0000FF).
     * E. Update appointment.
     * F. Calendar event must become Blue.
     */
    public function test_service_color_workflow_create_and_update(): void
    {
        // A. Create Service with color Red (#FF0000)
        $serviceRed = Service::create([
            'name' => 'Physiotherapy Red',
            'color' => '#FF0000',
            'duration_minutes' => 30,
            'price' => 75,
            'is_active' => true,
        ]);

        $serviceBlue = Service::create([
            'name' => 'Massage Blue',
            'color' => '#0000FF',
            'duration_minutes' => 30,
            'price' => 90,
            'is_active' => true,
        ]);

        // B. Create appointment using Red service
        $resCreate = $this->actingAs($this->adminStaff, 'staff')
            ->postJson('/calendar/appointments', [
                'staff_id' => $this->staff->id,
                'client_id' => $this->client->id,
                'service_id' => $serviceRed->id,
                'location_id' => $this->location->id,
                'start_time' => '2026-09-15 10:00:00',
                'end_time' => '2026-09-15 10:30:00',
                'status' => 'booked',
            ]);

        $resCreate->assertStatus(201);
        $apptData = $resCreate->json('appointment');

        // C. Calendar event response must immediately use Red (#FF0000)
        $this->assertEquals('#FF0000', $apptData['color']);
        $this->assertEquals('#FF0000', $apptData['backgroundColor']);
        $this->assertEquals('#FF0000', $apptData['borderColor']);
        $this->assertEquals('#FF0000', $apptData['serviceColor']);

        $apptId = $apptData['id'];

        // Verify Week/Day view events endpoint returns Red
        $resEvents = $this->actingAs($this->adminStaff, 'staff')
            ->getJson('/calendar/events?start=2026-09-15&end=2026-09-15');
        $resEvents->assertStatus(200);
        $eventItem = collect($resEvents->json())->firstWhere('id', $apptId);
        $this->assertNotNull($eventItem);
        $this->assertEquals('#FF0000', $eventItem['color']);
        $this->assertEquals('#FF0000', $eventItem['serviceColor']);

        // D & E. Change service to Blue (#0000FF) and update appointment
        $resUpdate = $this->actingAs($this->adminStaff, 'staff')
            ->putJson("/calendar/appointments/{$apptId}", [
                'service_id' => $serviceBlue->id,
                'start_time' => '2026-09-15 10:00:00',
                'end_time' => '2026-09-15 10:30:00',
            ]);

        $resUpdate->assertStatus(200);
        $updatedData = $resUpdate->json('appointment');

        // F. Calendar event must become Blue (#0000FF)
        $this->assertEquals('#0000FF', $updatedData['color']);
        $this->assertEquals('#0000FF', $updatedData['backgroundColor']);
        $this->assertEquals('#0000FF', $updatedData['borderColor']);
        $this->assertEquals('#0000FF', $updatedData['serviceColor']);

        // G & H. Verify Calendar events endpoint now returns Blue
        $resEventsUpdated = $this->actingAs($this->adminStaff, 'staff')
            ->getJson('/calendar/events?start=2026-09-15&end=2026-09-15');
        $updatedEventItem = collect($resEventsUpdated->json())->firstWhere('id', $apptId);
        $this->assertEquals('#0000FF', $updatedEventItem['color']);
        $this->assertEquals('#0000FF', $updatedEventItem['serviceColor']);

        // I. Verify Month View receives updated service color
        $resMonth = $this->actingAs($this->adminStaff, 'staff')
            ->get('/calendar?view=month&month=2026-09');
        $resMonth->assertStatus(200);
        $resMonth->assertSee('#0000FF');
    }
}
