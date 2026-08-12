<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Location;
use App\Models\Service;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CalendarClientTest extends TestCase
{
    use RefreshDatabase;

    private Staff $adminStaff;
    private Staff $staffA;
    private Staff $staffB;
    private Service $service;
    private Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->location = Location::create(['name' => 'Test Clinic', 'timezone' => 'UTC', 'is_active' => true]);
        $this->adminStaff = Staff::create([
            'location_id' => $this->location->id,
            'name' => 'Admin Staff',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'access_level' => 'admin',
            'is_active' => true
        ]);
        $this->staffA = Staff::create([
            'location_id' => $this->location->id,
            'name' => 'Staff A',
            'email' => 'staffa@example.com',
            'password' => Hash::make('password123'),
            'access_level' => 'staff',
            'is_active' => true
        ]);
        $this->staffB = Staff::create([
            'location_id' => $this->location->id,
            'name' => 'Staff B',
            'email' => 'staffb@example.com',
            'password' => Hash::make('password123'),
            'access_level' => 'staff',
            'is_active' => true
        ]);
        $this->service = Service::create([
            'name' => 'Test Service',
            'type' => 'in_person',
            'price' => 50,
            'duration_minutes' => 60,
            'buffer_minutes' => 0,
            'is_active' => true
        ]);
    }

    public function test_quick_create_client_without_email_succeeds(): void
    {
        $response = $this->actingAs($this->adminStaff, 'staff')
            ->postJson('/calendar/quick-client', [
                'first_name' => 'John',
                'last_name' => 'NoEmail',
                'phone' => '555-0001',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'client' => [
                    'name' => 'John NoEmail',
                    'first_name' => 'John',
                    'last_name' => 'NoEmail',
                    'phone' => '555-0001',
                    'email' => null,
                ]
            ]);

        $this->assertDatabaseHas('clients', [
            'first_name' => 'John',
            'last_name' => 'NoEmail',
            'name' => 'John NoEmail',
            'phone' => '555-0001',
            'email' => null,
        ]);
    }

    public function test_quick_create_client_with_full_details_persists(): void
    {
        $response = $this->actingAs($this->adminStaff, 'staff')
            ->postJson('/calendar/quick-client', [
                'first_name' => 'Alice',
                'last_name' => 'Full',
                'email' => 'alice@example.com',
                'phone' => '555-0002',
                'gender' => 'female',
                'dob' => '1995-04-12',
                'address_line1' => '100 Main St',
                'city' => 'Metropolis',
                'state' => 'NY',
                'postal_code' => '10002',
                'notes' => 'VIP Client',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('clients', [
            'first_name' => 'Alice',
            'last_name' => 'Full',
            'email' => 'alice@example.com',
            'city' => 'Metropolis',
            'notes' => 'VIP Client',
        ]);
    }

    public function test_duplicate_phone_and_email_rejected(): void
    {
        Client::create([
            'first_name' => 'Existing',
            'last_name' => 'Client',
            'name' => 'Existing Client',
            'phone' => '555-EXIST',
            'email' => 'existing@example.com',
        ]);

        $responsePhone = $this->actingAs($this->adminStaff, 'staff')
            ->postJson('/calendar/quick-client', [
                'first_name' => 'New',
                'last_name' => 'User',
                'phone' => '555-EXIST',
            ]);

        $responsePhone->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);

        $responseEmail = $this->actingAs($this->adminStaff, 'staff')
            ->postJson('/calendar/quick-client', [
                'first_name' => 'New2',
                'last_name' => 'User2',
                'phone' => '555-NEW2',
                'email' => 'existing@example.com',
            ]);

        $responseEmail->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_unauthorized_update_and_assign_rejected(): void
    {
        $client = Client::create([
            'first_name' => 'Client',
            'last_name' => 'One',
            'name' => 'Client One',
            'phone' => '555-C1',
        ]);

        $appointment = Appointment::create([
            'staff_id' => $this->staffA->id,
            'service_id' => $this->service->id,
            'client_id' => $client->id,
            'location_id' => $this->location->id,
            'start_time' => now()->addDay()->setHour(10)->setMinute(0),
            'end_time' => now()->addDay()->setHour(11)->setMinute(0),
            'status' => 'booked',
        ]);

        // Staff B attempts to update Staff A's appointment
        $unauthUpdate = $this->actingAs($this->staffB, 'staff')
            ->putJson("/calendar/appointments/{$appointment->id}", [
                'notes' => 'Hacked',
            ]);

        $unauthUpdate->assertStatus(403);

        // Staff B attempts to assign client to Staff A's appointment
        $unauthAssign = $this->actingAs($this->staffB, 'staff')
            ->postJson("/calendar/appointments/{$appointment->id}/assign-client", [
                'client_id' => $client->id,
            ]);

        $unauthAssign->assertStatus(403);

        // Assigned Staff A updates Staff A's appointment
        $authUpdate = $this->actingAs($this->staffA, 'staff')
            ->putJson("/calendar/appointments/{$appointment->id}", [
                'notes' => 'Authorized update',
            ]);

        $authUpdate->assertStatus(200);
    }
}
