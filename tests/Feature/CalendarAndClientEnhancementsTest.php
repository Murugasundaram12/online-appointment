<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Location;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Staff;
use App\Models\StaffSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CalendarAndClientEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    private Staff $admin;
    private Staff $staff;
    private Location $location;
    private ServiceCategory $category;
    private Service $service;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->location = Location::create([
            'name' => 'Main Clinic',
            'is_active' => true,
        ]);

        $this->admin = Staff::create([
            'name' => 'Admin User',
            'email' => 'admin_enhancement@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
            'access_level' => 'admin',
            'is_active' => true,
            'location_id' => $this->location->id,
        ]);

        $this->staff = Staff::create([
            'name' => 'Doctor John',
            'email' => 'doctor_john@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'staff',
            'access_level' => 'staff',
            'is_active' => true,
            'location_id' => $this->location->id,
        ]);

        $this->category = ServiceCategory::create([
            'name' => 'General Wellness',
            'is_active' => true,
        ]);

        $this->service = Service::create([
            'service_category_id' => $this->category->id,
            'name' => 'Massage Therapy',
            'price' => 80.00,
            'duration_minutes' => 60,
            'buffer_minutes' => 0,
            'color' => '#FF5733',
            'is_active' => true,
        ]);

        $this->client = Client::create([
            'name' => 'Alice Wonder',
            'first_name' => 'Alice',
            'last_name' => 'Wonder',
            'email' => 'alice@example.com',
            'phone' => '1234567890',
        ]);
    }

    // =========================================================================
    // 1. CALENDAR - STAFF SCHEDULE & APPOINTMENT CREATION / UPDATE
    // =========================================================================

    public function test_appointment_can_be_created_inside_staff_scheduled_working_hours()
    {
        // Monday 2026-09-28: Staff schedule 09:00 - 17:00
        $monday = Carbon::parse('2026-09-28');
        StaffSchedule::create([
            'staff_id' => $this->staff->id,
            'working_date' => $monday->toDateString(),
            'day_of_week' => (string) $monday->dayOfWeek,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'is_working' => true,
        ]);

        $response = $this->actingAs($this->admin, 'staff')->postJson(route('calendar.store'), [
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'location_id' => $this->location->id,
            'start_time' => '2026-09-28T10:00:00',
            'end_time' => '2026-09-28T11:00:00',
            'status' => 'booked',
        ]);

        $response->assertStatus(201);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('appointments', [
            'staff_id' => $this->staff->id,
            'client_id' => $this->client->id,
            'status' => 'booked',
        ]);
    }

    public function test_appointment_can_be_created_with_weekly_template_schedule()
    {
        // Weekly schedule on Monday without working_date
        StaffSchedule::create([
            'staff_id' => $this->staff->id,
            'working_date' => null,
            'day_of_week' => 'monday',
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'is_working' => true,
        ]);

        $response = $this->actingAs($this->admin, 'staff')->postJson(route('calendar.store'), [
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'location_id' => $this->location->id,
            'start_time' => '2026-09-28T14:00:00',
            'end_time' => '2026-09-28T15:00:00',
            'status' => 'booked',
        ]);

        $response->assertStatus(201);
        $response->assertJson(['success' => true]);
    }

    public function test_appointment_outside_staff_working_hours_is_rejected()
    {
        $monday = Carbon::parse('2026-09-28');
        StaffSchedule::create([
            'staff_id' => $this->staff->id,
            'working_date' => $monday->toDateString(),
            'day_of_week' => (string) $monday->dayOfWeek,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'is_working' => true,
        ]);

        // Attempt appointment at 07:00 - 08:00 (before schedule)
        $responseBefore = $this->actingAs($this->admin, 'staff')->postJson(route('calendar.store'), [
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'location_id' => $this->location->id,
            'start_time' => '2026-09-28T07:00:00',
            'end_time' => '2026-09-28T08:00:00',
            'status' => 'booked',
        ]);
        $responseBefore->assertStatus(422);
        $responseBefore->assertJson(['success' => false]);

        // Attempt appointment at 18:00 - 19:00 (after schedule)
        $responseAfter = $this->actingAs($this->admin, 'staff')->postJson(route('calendar.store'), [
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'location_id' => $this->location->id,
            'start_time' => '2026-09-28T18:00:00',
            'end_time' => '2026-09-28T19:00:00',
            'status' => 'booked',
        ]);
        $responseAfter->assertStatus(422);
        $responseAfter->assertJson(['success' => false]);
    }

    public function test_appointment_conflict_protection_is_preserved()
    {
        $monday = Carbon::parse('2026-09-28');
        StaffSchedule::create([
            'staff_id' => $this->staff->id,
            'working_date' => $monday->toDateString(),
            'day_of_week' => (string) $monday->dayOfWeek,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'is_working' => true,
        ]);

        // Existing appointment at 10:00 - 11:00
        Appointment::create([
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'location_id' => $this->location->id,
            'start_time' => '2026-09-28 10:00:00',
            'end_time' => '2026-09-28 11:00:00',
            'status' => 'booked',
        ]);

        // Attempt overlapping appointment at 10:30 - 11:30
        $response = $this->actingAs($this->admin, 'staff')->postJson(route('calendar.store'), [
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'location_id' => $this->location->id,
            'start_time' => '2026-09-28T10:30:00',
            'end_time' => '2026-09-28T11:30:00',
            'status' => 'booked',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false, 'message' => 'This time slot is already booked.']);
    }

    public function test_appointment_on_off_day_is_rejected()
    {
        $monday = Carbon::parse('2026-09-28');
        StaffSchedule::create([
            'staff_id' => $this->staff->id,
            'working_date' => $monday->toDateString(),
            'day_of_week' => (string) $monday->dayOfWeek,
            'start_time' => null,
            'end_time' => null,
            'is_working' => false,
        ]);

        $response = $this->actingAs($this->admin, 'staff')->postJson(route('calendar.store'), [
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'location_id' => $this->location->id,
            'start_time' => '2026-09-28T10:00:00',
            'end_time' => '2026-09-28T11:00:00',
            'status' => 'booked',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    }

    public function test_appointment_can_be_updated_inside_valid_schedule()
    {
        $monday = Carbon::parse('2026-09-28');
        StaffSchedule::create([
            'staff_id' => $this->staff->id,
            'working_date' => $monday->toDateString(),
            'day_of_week' => (string) $monday->dayOfWeek,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'is_working' => true,
        ]);

        $appointment = Appointment::create([
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'location_id' => $this->location->id,
            'start_time' => '2026-09-28 10:00:00',
            'end_time' => '2026-09-28 11:00:00',
            'status' => 'booked',
        ]);

        // Reschedule to 14:00 - 15:00
        $response = $this->actingAs($this->admin, 'staff')->putJson(route('calendar.update', $appointment->id), [
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'start_time' => '2026-09-28T14:00:00',
            'end_time' => '2026-09-28T15:00:00',
            'status' => 'confirmed',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'confirmed',
        ]);
    }

    // =========================================================================
    // 2. CALENDAR - SERVICE COLOR DISPLAY
    // =========================================================================

    public function test_calendar_events_endpoint_returns_service_color()
    {
        $appointment = Appointment::create([
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'location_id' => $this->location->id,
            'start_time' => '2026-09-28 10:00:00',
            'end_time' => '2026-09-28 11:00:00',
            'status' => 'booked',
        ]);

        $response = $this->actingAs($this->admin, 'staff')->getJson(route('calendar.events', [
            'start' => '2026-09-28',
            'end' => '2026-09-28',
        ]));

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertNotEmpty($data);
        $event = collect($data)->firstWhere('id', $appointment->id);
        $this->assertNotNull($event);
        $this->assertEquals('#FF5733', $event['color']);
        $this->assertEquals('#FF5733', $event['backgroundColor']);
        $this->assertEquals('#FF5733', $event['borderColor']);
        $this->assertEquals('#FF5733', $event['serviceColor']);
    }

    // =========================================================================
    // 3. SERVICES - ADD / EDIT COLOR PERSISTENCE
    // =========================================================================

    public function test_service_color_can_be_saved_and_updated()
    {
        // Add service with custom color
        $response = $this->actingAs($this->admin, 'staff')->post(route('services.store'), [
            'service_category_id' => $this->category->id,
            'name' => 'Aromatherapy',
            'type' => 'in_person',
            'price' => 95.00,
            'duration_minutes' => 45,
            'buffer_minutes' => 5,
            'color' => '#10B981',
            'is_active' => 1,
        ]);
        $response->assertSessionHasNoErrors();

        $created = Service::where('name', 'Aromatherapy')->first();
        $this->assertNotNull($created);
        $this->assertEquals('#10B981', $created->color);

        // Edit service color
        $updateResponse = $this->actingAs($this->admin, 'staff')->put(route('services.update', $created->id), [
            'service_category_id' => $this->category->id,
            'name' => 'Aromatherapy Deluxe',
            'type' => 'in_person',
            'price' => 110.00,
            'duration_minutes' => 60,
            'buffer_minutes' => 5,
            'color' => '#8B5CF6',
            'is_active' => 1,
        ]);
        $updateResponse->assertSessionHasNoErrors();

        $created->refresh();
        $this->assertEquals('#8B5CF6', $created->color);
    }

    // =========================================================================
    // 4. CLIENT - EMAIL AND PHONE OPTIONAL
    // =========================================================================

    public function test_client_can_be_created_without_email_and_phone()
    {
        $response = $this->actingAs($this->admin, 'staff')->post(route('clients.store'), [
            'first_name' => 'Bob',
            'last_name' => 'NoContact',
            'email' => '',
            'phone' => '',
            'gender' => 'other',
        ]);

        $response->assertSessionHasNoErrors();
        $created = Client::where('first_name', 'Bob')->first();
        $this->assertNotNull($created);
        $this->assertNull($created->email);
        $this->assertNull($created->phone);
    }

    public function test_client_can_be_updated_to_clear_email_and_phone()
    {
        $client = Client::create([
            'name' => 'Charlie Existing',
            'first_name' => 'Charlie',
            'last_name' => 'Existing',
            'email' => 'charlie@example.com',
            'phone' => '9876543210',
        ]);

        $response = $this->actingAs($this->admin, 'staff')->put(route('clients.update', $client->id), [
            'first_name' => 'Charlie',
            'last_name' => 'Existing',
            'email' => '',
            'phone' => '',
        ]);

        $response->assertSessionHasNoErrors();
        $client->refresh();
        $this->assertNull($client->email);
        $this->assertNull($client->phone);
    }

    public function test_client_with_invalid_email_fails_validation()
    {
        $response = $this->actingAs($this->admin, 'staff')->post(route('clients.store'), [
            'first_name' => 'Invalid',
            'last_name' => 'Email',
            'email' => 'not-a-valid-email',
            'phone' => '',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_quick_client_from_calendar_allows_empty_email_and_phone()
    {
        $response = $this->actingAs($this->admin, 'staff')->postJson(route('calendar.quickClient'), [
            'first_name' => 'Quick',
            'last_name' => 'Client',
            'email' => '',
            'phone' => '',
        ]);

        $response->assertStatus(201);
        $response->assertJson(['success' => true]);
        $client = Client::where('first_name', 'Quick')->first();
        $this->assertNotNull($client);
        $this->assertNull($client->email);
        $this->assertNull($client->phone);
    }

    // =========================================================================
    // 5. CLIENT - PHONE PLACEHOLDERS & PROVINCE LABEL
    // =========================================================================

    public function test_client_form_displays_province_and_x_placeholders()
    {
        $response = $this->actingAs($this->admin, 'staff')->get(route('clients.create'));
        $response->assertStatus(200);

        // Province label
        $response->assertSee('Province');
        $response->assertSee('Enter province');

        // Phone placeholders with (xxx) xxx-xxxx
        $response->assertSee('placeholder="(xxx) xxx-xxxx"', false);
    }
}
