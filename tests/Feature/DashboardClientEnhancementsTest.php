<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Form;
use App\Models\FormRecord;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\PaymentRecord;
use App\Models\Service;
use App\Models\Staff;
use App\Models\StaffSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DashboardClientEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    private Staff $adminStaff;
    private Staff $staffMember;
    private Service $service;
    private Location $location;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->location = Location::create([
            'name' => 'Main Clinic',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        $this->adminStaff = Staff::create([
            'location_id' => $this->location->id,
            'name' => 'Admin Staff',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'access_level' => 'admin',
            'is_active' => true,
        ]);

        $this->staffMember = Staff::create([
            'location_id' => $this->location->id,
            'name' => 'Dr. Jane Smith',
            'email' => 'jane@example.com',
            'password' => Hash::make('password123'),
            'access_level' => 'staff',
            'is_active' => true,
        ]);

        for ($day = 0; $day <= 6; $day++) {
            StaffSchedule::create([
                'staff_id' => $this->adminStaff->id,
                'day_of_week' => (string) $day,
                'start_time' => '08:00:00',
                'end_time' => '18:00:00',
                'is_working' => true,
            ]);
            StaffSchedule::create([
                'staff_id' => $this->staffMember->id,
                'day_of_week' => (string) $day,
                'start_time' => '08:00:00',
                'end_time' => '18:00:00',
                'is_working' => true,
            ]);
        }

        $this->service = Service::create([
            'name' => 'General Checkup',
            'type' => 'in_person',
            'price' => 100,
            'duration_minutes' => 60,
            'buffer_minutes' => 0,
            'is_active' => true,
        ]);

        $this->client = Client::create([
            'first_name' => 'Alice',
            'last_name' => 'Wonderland',
            'name' => 'Alice Wonderland',
            'email' => 'alice@example.com',
            'phone' => '9876543210',
            'dob' => '1990-05-15',
            'is_vip' => true,
            'notes' => 'Patient has allergy to latex.',
        ]);
    }

    /** 1. Dashboard renders with today stats & staff workload */
    public function test_dashboard_renders_with_today_stats_and_workload(): void
    {
        $today = Carbon::today();

        // Create appointments for today
        Appointment::create([
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'location_id' => $this->location->id,
            'start_time' => $today->copy()->setTime(9, 0),
            'end_time' => $today->copy()->setTime(10, 0),
            'status' => 'confirmed',
        ]);

        Appointment::create([
            'staff_id' => $this->staffMember->id,
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'location_id' => $this->location->id,
            'start_time' => $today->copy()->setTime(11, 0),
            'end_time' => $today->copy()->setTime(12, 0),
            'status' => 'completed',
        ]);

        $res = $this->actingAs($this->adminStaff, 'staff')->get('/');
        $res->assertStatus(200);
        $res->assertSee('Today Summary');
        $res->assertSee('Staff daily workload');
        $res->assertSee('Create invoice');
    }

    /** 2. Client snapshot API returns detailed stats */
    public function test_client_snapshot_api_returns_detailed_stats(): void
    {
        Appointment::create([
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'start_time' => now()->subDays(2)->setTime(10, 0),
            'end_time' => now()->subDays(2)->setTime(11, 0),
            'status' => 'completed',
        ]);

        Appointment::create([
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'start_time' => now()->subDays(1)->setTime(10, 0),
            'end_time' => now()->subDays(1)->setTime(11, 0),
            'status' => 'no_show',
        ]);

        $invoice = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-99001',
            'issued_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'total_amount' => 150.00,
            'paid_amount' => 50.00,
            'status' => 'partial',
        ]);

        $res = $this->actingAs($this->adminStaff, 'staff')->getJson("/calendar/clients/{$this->client->id}/snapshot");
        $res->assertStatus(200);
        $res->assertJsonPath('success', true);
        $res->assertJsonPath('client.name', 'Alice Wonderland');
        $res->assertJsonPath('client.is_vip', true);
        $res->assertJsonPath('client.no_show_count', 1);
        $res->assertJsonPath('client.total_appointments', 2);
        $res->assertJsonPath('client.outstanding_amount', 100);
        $res->assertJsonPath('client.notes', 'Patient has allergy to latex.');
    }

    /** 3. Client 360 profile renders age and timeline events */
    public function test_client_profile_renders_age_and_timeline_events(): void
    {
        $invoice = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-99002',
            'issued_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'total_amount' => 200.00,
            'paid_amount' => 200.00,
            'status' => 'paid',
        ]);

        PaymentRecord::create([
            'invoice_id' => $invoice->id,
            'payment_date' => now()->toDateString(),
            'amount' => 200.00,
            'payment_method' => 'card',
        ]);

        $form = Form::create([
            'name' => 'Medical History Intake',
            'fields' => json_encode([]),
            'is_active' => true,
        ]);

        FormRecord::create([
            'form_id' => $form->id,
            'client_id' => $this->client->id,
            'submitted_at' => now(),
            'submitted_data' => json_encode(['allergies' => 'Latex']),
        ]);

        $res = $this->actingAs($this->adminStaff, 'staff')->get("/clients/{$this->client->id}");
        $res->assertStatus(200);
        $res->assertSee('Age:');
        $res->assertSee('Date of Birth');
        $res->assertSee('Payment Received');
        $res->assertSee('Form Submitted: Medical History Intake');
        $res->assertSee('Client Note Recorded');
    }

    /** 4. Calendar appointment show endpoint returns quick link fields */
    public function test_appointment_show_returns_quick_link_fields(): void
    {
        $appt = Appointment::create([
            'staff_id' => $this->adminStaff->id,
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'start_time' => now()->addDays(1)->setTime(10, 0),
            'end_time' => now()->addDays(1)->setTime(11, 0),
            'status' => 'booked',
        ]);

        $invoice = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'appointment_id' => $appt->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-99003',
            'issued_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'total_amount' => 100.00,
            'paid_amount' => 0,
            'status' => 'unpaid',
        ]);

        $res = $this->actingAs($this->adminStaff, 'staff')->getJson("/calendar/appointments/{$appt->id}");
        $res->assertStatus(200);
        $res->assertJsonPath('invoiceId', $invoice->id);
        $res->assertJsonPath('invoiceNumber', 'INV-99003');
    }

    /** 5. Empty states render cleanly without errors */
    public function test_empty_dashboard_and_client_states_render_cleanly(): void
    {
        $emptyClient = Client::create([
            'first_name' => 'Bob',
            'last_name' => 'Builder',
            'name' => 'Bob Builder',
            'email' => 'bob@example.com',
            'phone' => '1122334455',
        ]);

        $dashRes = $this->actingAs($this->adminStaff, 'staff')->get('/');
        $dashRes->assertStatus(200);
        $dashRes->assertSee('No appointments scheduled for today.');

        $clientRes = $this->actingAs($this->adminStaff, 'staff')->get("/clients/{$emptyClient->id}");
        $clientRes->assertStatus(200);
        $clientRes->assertSee('No appointments found for this client.');
    }
}
