<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Location;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class QuotationCreateUiTest extends TestCase
{
    use RefreshDatabase;

    private Location $location;
    private Staff $admin;
    private Client $client;
    private ServiceCategory $category;
    private Service $serviceA;
    private Service $serviceB;
    private Appointment $appointment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->location = Location::create([
            'name'      => 'Central Clinic',
            'timezone'  => 'America/Toronto',
            'is_active' => true,
        ]);

        $this->admin = Staff::create([
            'location_id'  => $this->location->id,
            'name'         => 'Dr. Smith Admin',
            'email'        => 'admin@clinic.test',
            'password'     => Hash::make('secret123'),
            'access_level' => 'admin',
            'is_active'    => true,
        ]);

        $this->client = Client::create([
            'name'   => 'Jane Doe Patient',
            'email'  => 'jane@patient.test',
            'phone'  => '555-0199',
            'status' => 'active',
        ]);

        $this->category = ServiceCategory::create(['name' => 'Consultations']);

        $this->serviceA = Service::create([
            'service_category_id' => $this->category->id,
            'name'                => 'Initial Consultation',
            'price'               => 100.00,
            'duration_minutes'    => 60,
            'is_active'           => true,
        ]);

        $this->serviceB = Service::create([
            'service_category_id' => $this->category->id,
            'name'                => 'Follow-up Therapy',
            'price'               => 60.00,
            'duration_minutes'    => 45,
            'is_active'           => true,
        ]);

        $this->appointment = Appointment::create([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->admin->id,
            'service_id'  => $this->serviceA->id,
            'location_id' => $this->location->id,
            'start_time'  => Carbon::tomorrow()->setTime(9, 30),
            'end_time'    => Carbon::tomorrow()->setTime(10, 30),
            'status'      => 'booked',
        ]);
    }

    /** Test A — Create page renders with all required form fields */
    public function test_quotation_create_page_renders_with_all_elements(): void
    {
        $response = $this->actingAs($this->admin, 'staff')
            ->get(route('quotations.create'));

        $response->assertStatus(200);
        $response->assertViewIs('quotations.create');

        // Form attributes
        $response->assertSee('id="quotation-create-form"', false);
        $response->assertSee(route('quotations.store'), false);
        $response->assertSee('method="POST"', false);

        // Core fields
        $response->assertSee('name="client_id"', false);
        $response->assertSee('name="staff_id"', false);
        $response->assertSee('name="appointment_id"', false);
        $response->assertSee('(Optional)', false);
        $response->assertSee('name="issued_date"', false);
        $response->assertSee('name="valid_until"', false);

        // Multi-item builder components
        $response->assertSee('id="btn-add-item"', false);
        $response->assertSee('id="items-table"', false);
        $response->assertSee('items[0][service_id]', false);
        $response->assertSee('items[0][description]', false);
        $response->assertSee('items[0][quantity]', false);
        $response->assertSee('items[0][unit_price]', false);
        $response->assertSee('items[0][discount_amount]', false);
        $response->assertSee('items[0][tax_rate]', false);

        // Summary elements
        $response->assertSee('id="summary-subtotal"', false);
        $response->assertSee('id="summary-discount"', false);
        $response->assertSee('id="summary-tax"', false);
        $response->assertSee('id="summary-total"', false);

        // Notes and terms
        $response->assertSee('name="notes"', false);
        $response->assertSee('name="terms"', false);

        // Next quotation number preview
        $response->assertSee('QUO-');
        $response->assertSee('Draft');
    }

    /** Test B — Service data and client data available to the view */
    public function test_active_services_and_clients_available_in_view(): void
    {
        $response = $this->actingAs($this->admin, 'staff')
            ->get(route('quotations.create'));

        $response->assertStatus(200);
        $response->assertViewHas('services', function ($services) {
            return $services->contains($this->serviceA) && $services->contains($this->serviceB);
        });
        $response->assertViewHas('clients', function ($clients) {
            return $clients->contains($this->client);
        });
        $response->assertViewHas('staff', function ($staff) {
            return $staff->contains($this->admin);
        });
        $response->assertViewHas('appointments', function ($appointments) {
            return $appointments->contains($this->appointment);
        });

        // HTML contains service options
        $response->assertSee('Initial Consultation');
        $response->assertSee('Follow-up Therapy');
    }

    /** Test C — Validation errors on empty or invalid submission */
    public function test_invalid_quotation_submission_shows_validation_errors(): void
    {
        $response = $this->actingAs($this->admin, 'staff')
            ->from(route('quotations.create'))
            ->post(route('quotations.store'), [
                'client_id'   => '',
                'staff_id'    => '',
                'issued_date' => '',
                'valid_until' => '',
                'items'       => [],
            ]);

        $response->assertRedirect(route('quotations.create'));
        $response->assertSessionHasErrors([
            'client_id',
            'staff_id',
            'issued_date',
            'valid_until',
            'items',
        ]);

        $this->assertDatabaseCount('quotations', 0);
        $this->assertDatabaseCount('quotation_items', 0);
    }

    /** Test D — Multi-item submission through full controller to DB stack */
    public function test_multi_item_submission_persists_full_quotation(): void
    {
        $payload = [
            'client_id'      => $this->client->id,
            'staff_id'       => $this->admin->id,
            'appointment_id' => $this->appointment->id,
            'issued_date'    => now()->toDateString(),
            'valid_until'    => now()->addDays(30)->toDateString(),
            'notes'          => 'Patient requested customized treatment plan',
            'terms'          => 'Valid for 30 calendar days from issue date',
            'items'          => [
                [
                    'service_id'      => $this->serviceA->id,
                    'staff_id'        => $this->admin->id,
                    'description'     => 'Initial Consultation Special',
                    'quantity'        => 2.0,
                    'unit_price'      => 50.00,
                    'discount_amount' => 10.00,
                    'tax_rate'        => 10.00,
                    'sort_order'      => 1,
                ],
                [
                    'service_id'      => $this->serviceB->id,
                    'staff_id'        => $this->admin->id,
                    'description'     => 'Follow-up Therapy Session',
                    'quantity'        => 1.5,
                    'unit_price'      => 40.00,
                    'discount_amount' => 0.00,
                    'tax_rate'        => 0.00,
                    'sort_order'      => 2,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin, 'staff')
            ->from(route('quotations.create'))
            ->post(route('quotations.store'), $payload);

        $response->assertRedirect(route('quotations.create'));
        $response->assertSessionHas('success');

        $this->assertDatabaseCount('quotations', 1);
        $this->assertDatabaseCount('quotation_items', 2);

        $quotation = Quotation::first();
        $this->assertEquals($this->client->id, $quotation->client_id);
        $this->assertEquals($this->admin->id, $quotation->staff_id);
        $this->assertEquals($this->appointment->id, $quotation->appointment_id);
        $this->assertEquals('draft', $quotation->status);

        // Mathematical parity check:
        // Item 1: 2 * 50 = 100, taxable = 90, tax 10% = 9.00, line_total = 99.00
        // Item 2: 1.5 * 40 = 60, taxable = 60, tax 0% = 0.00, line_total = 60.00
        // Subtotal = 160.00, Discount = 10.00, Tax = 9.00, Total = 159.00
        $this->assertEquals(160.00, (float) $quotation->subtotal);
        $this->assertEquals(10.00, (float) $quotation->discount_amount);
        $this->assertEquals(9.00, (float) $quotation->tax_amount);
        $this->assertEquals(159.00, (float) $quotation->total_amount);

        $items = $quotation->items()->orderBy('sort_order')->get();
        $this->assertCount(2, $items);
        $this->assertEquals('Initial Consultation Special', $items[0]->description);
        $this->assertEquals(99.00, (float) $items[0]->line_total);
        $this->assertEquals('Follow-up Therapy Session', $items[1]->description);
        $this->assertEquals(60.00, (float) $items[1]->line_total);
    }

    /** Test E — Old input retention preserves multi-items after validation failure */
    public function test_old_input_retention_preserves_rows(): void
    {
        $payload = [
            'client_id'      => $this->client->id,
            'staff_id'       => $this->admin->id,
            'issued_date'    => now()->toDateString(),
            'valid_until'    => now()->subDays(5)->toDateString(), // Invalid: before issued_date
            'notes'          => 'Retained note',
            'items'          => [
                [
                    'service_id'      => $this->serviceA->id,
                    'description'     => 'Preserved Row 1 Description',
                    'quantity'        => 2.5,
                    'unit_price'      => 75.00,
                    'discount_amount' => 5.00,
                    'tax_rate'        => 5.00,
                ],
                [
                    'service_id'      => null,
                    'description'     => 'Preserved Row 2 Description',
                    'quantity'        => 1.0,
                    'unit_price'      => 30.00,
                    'discount_amount' => 0.00,
                    'tax_rate'        => 0.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin, 'staff')
            ->from(route('quotations.create'))
            ->post(route('quotations.store'), $payload);

        $response->assertRedirect(route('quotations.create'));
        $response->assertSessionHasErrors(['valid_until']);

        // Check re-rendering with old input
        $followUp = $this->actingAs($this->admin, 'staff')
            ->withSession(['_old_input' => $payload])
            ->get(route('quotations.create'));

        $followUp->assertStatus(200);
        $followUp->assertSee('Preserved Row 1 Description');
        $followUp->assertSee('Preserved Row 2 Description');
        $followUp->assertSee('Retained note');
    }
}
