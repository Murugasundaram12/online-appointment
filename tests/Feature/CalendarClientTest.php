<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\PaymentRecord;
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

    public function test_appointment_details_endpoint_returns_client_details_for_edit_modal(): void
    {
        $client = Client::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'name' => 'Jane Doe',
            'phone' => '555-0987',
            'email' => 'jane.doe@example.com',
        ]);

        $appointment = Appointment::create([
            'staff_id' => $this->staffA->id,
            'service_id' => $this->service->id,
            'client_id' => $client->id,
            'location_id' => $this->location->id,
            'start_time' => now()->addDay()->setHour(14)->setMinute(0),
            'end_time' => now()->addDay()->setHour(15)->setMinute(0),
            'status' => 'booked',
        ]);

        $response = $this->actingAs($this->staffA, 'staff')
            ->getJson("/calendar/appointments/{$appointment->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $appointment->id,
                'clientId' => $client->id,
                'clientName' => 'Jane Doe',
                'clientPhone' => '555-0987',
                'clientEmail' => 'jane.doe@example.com',
                'title' => 'Jane Doe',
                'staffId' => $this->staffA->id,
                'serviceId' => $this->service->id,
                'locationId' => $this->location->id,
                'status' => 'booked',
            ]);
    }

    public function test_calendar_view_contains_appointment_client_synchronization_logic(): void
    {
        $response = $this->actingAs($this->staffA, 'staff')
            ->get('/calendar');

        $response->assertStatus(200);
        $response->assertSee('setAppointmentClient', false);
        $response->assertSee('ensureClientSelectOption', false);
        $response->assertSee('id="appt-client"', false);
    }

    public function test_assign_client_is_rejected_for_completed_appointment(): void
    {
        $clientA = Client::create([
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'name' => 'Alice Smith',
            'email' => 'alice@example.com',
            'phone' => '555-0101',
        ]);

        $clientB = Client::create([
            'first_name' => 'Bob',
            'last_name' => 'Jones',
            'name' => 'Bob Jones',
            'email' => 'bob@example.com',
            'phone' => '555-0102',
        ]);

        $appointment = Appointment::create([
            'staff_id' => $this->staffA->id,
            'service_id' => $this->service->id,
            'client_id' => $clientA->id,
            'location_id' => $this->location->id,
            'start_time' => now()->subDay()->setHour(10)->setMinute(0),
            'end_time' => now()->subDay()->setHour(11)->setMinute(0),
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->staffA, 'staff')
            ->postJson("/calendar/appointments/{$appointment->id}/assign-client", [
                'client_id' => $clientB->id,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Completed appointments cannot be modified.',
            ]);

        $this->assertEquals($clientA->id, $appointment->fresh()->client_id);
    }

    public function test_assign_client_succeeds_for_eligible_non_completed_appointment(): void
    {
        $clientA = Client::create([
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'name' => 'Alice Smith',
            'email' => 'alice2@example.com',
            'phone' => '555-0201',
        ]);

        $clientB = Client::create([
            'first_name' => 'Bob',
            'last_name' => 'Jones',
            'name' => 'Bob Jones',
            'email' => 'bob2@example.com',
            'phone' => '555-0202',
        ]);

        $appointment = Appointment::create([
            'staff_id' => $this->staffA->id,
            'service_id' => $this->service->id,
            'client_id' => $clientA->id,
            'location_id' => $this->location->id,
            'start_time' => now()->addDay()->setHour(10)->setMinute(0),
            'end_time' => now()->addDay()->setHour(11)->setMinute(0),
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($this->staffA, 'staff')
            ->postJson("/calendar/appointments/{$appointment->id}/assign-client", [
                'client_id' => $clientB->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Client assigned successfully',
            ]);

        $this->assertEquals($clientB->id, $appointment->fresh()->client_id);
    }

    public function test_rejected_assign_client_leaves_appointment_invoice_and_payment_records_unchanged(): void
    {
        $clientA = Client::create([
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'name' => 'Alice Smith',
            'email' => 'alice3@example.com',
            'phone' => '555-0301',
        ]);

        $clientB = Client::create([
            'first_name' => 'Bob',
            'last_name' => 'Jones',
            'name' => 'Bob Jones',
            'email' => 'bob3@example.com',
            'phone' => '555-0302',
        ]);

        $appointment = Appointment::create([
            'staff_id' => $this->staffA->id,
            'service_id' => $this->service->id,
            'client_id' => $clientA->id,
            'location_id' => $this->location->id,
            'start_time' => now()->subDay()->setHour(10)->setMinute(0),
            'end_time' => now()->subDay()->setHour(11)->setMinute(0),
            'status' => 'completed',
        ]);

        $invoice = Invoice::create([
            'appointment_id'  => $appointment->id,
            'client_id'       => $clientA->id,
            'staff_id'        => $this->staffA->id,
            'invoice_number'  => 'INV-REJECT-001',
            'subtotal'        => 50.00,
            'discount_amount' => 0.00,
            'tax_amount'      => 0.00,
            'total_amount'    => 50.00,
            'paid_amount'     => 50.00,
            'status'          => 'paid',
            'issued_date'     => now()->toDateString(),
        ]);

        $payment = PaymentRecord::create([
            'invoice_id'     => $invoice->id,
            'amount'         => 50.00,
            'payment_method' => 'cash',
            'payment_date'   => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->staffA, 'staff')
            ->postJson("/calendar/appointments/{$appointment->id}/assign-client", [
                'client_id' => $clientB->id,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Completed appointments cannot be modified.',
            ]);

        // Verify appointment was NOT modified
        $this->assertEquals($clientA->id, $appointment->fresh()->client_id);

        // Verify invoice was NOT modified
        $freshInvoice = $invoice->fresh();
        $this->assertEquals($clientA->id, $freshInvoice->client_id);
        $this->assertEquals($appointment->id, $freshInvoice->appointment_id);
        $this->assertEquals('paid', $freshInvoice->status);

        // Verify payment record was NOT modified
        $freshPayment = $payment->fresh();
        $this->assertEquals($invoice->id, $freshPayment->invoice_id);
        $this->assertEquals('50.00', number_format((float) $freshPayment->amount, 2, '.', ''));
    }
}
