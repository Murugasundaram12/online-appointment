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
use App\Services\QuotationCreationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class QuotationShowTest extends TestCase
{
    use RefreshDatabase;

    private Location $locationA;
    private Location $locationB;
    private Staff $admin;
    private Staff $staffA;
    private Staff $staffB;
    private Client $client;
    private ServiceCategory $category;
    private Service $serviceA;
    private Service $serviceB;
    private Appointment $appointment;
    private QuotationCreationService $quotationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->locationA = Location::create([
            'name'      => 'Downtown Clinic',
            'timezone'  => 'America/Toronto',
            'is_active' => true,
        ]);

        $this->locationB = Location::create([
            'name'      => 'Uptown Clinic',
            'timezone'  => 'America/Toronto',
            'is_active' => true,
        ]);

        $this->admin = Staff::create([
            'location_id'  => $this->locationA->id,
            'name'         => 'Director Admin',
            'email'        => 'admin@clinic.test',
            'password'     => Hash::make('password123'),
            'access_level' => 'admin',
            'is_active'    => true,
        ]);

        $this->staffA = Staff::create([
            'location_id'  => $this->locationA->id,
            'name'         => 'Dr. Alice Downtown',
            'email'        => 'alice@clinic.test',
            'password'     => Hash::make('password123'),
            'access_level' => 'staff',
            'is_active'    => true,
        ]);

        $this->staffB = Staff::create([
            'location_id'  => $this->locationB->id,
            'name'         => 'Dr. Bob Uptown',
            'email'        => 'bob@clinic.test',
            'password'     => Hash::make('password123'),
            'access_level' => 'staff',
            'is_active'    => true,
        ]);

        $this->client = Client::create([
            'name'          => 'Jane Doe Patient',
            'email'         => 'jane.doe@example.com',
            'phone'         => '555-0199',
            'address_line1' => '123 Main St',
            'city'          => 'Toronto',
            'status'        => 'active',
        ]);

        $this->category = ServiceCategory::create(['name' => 'Consultations']);

        $this->serviceA = Service::create([
            'service_category_id' => $this->category->id,
            'name'                => 'Dental Checkup',
            'price'               => 100.00,
            'duration_minutes'    => 30,
            'is_active'           => true,
        ]);

        $this->serviceB = Service::create([
            'service_category_id' => $this->category->id,
            'name'                => 'Physiotherapy Session',
            'price'               => 80.00,
            'duration_minutes'    => 45,
            'is_active'           => true,
        ]);

        $this->appointment = Appointment::create([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staffA->id,
            'service_id'  => $this->serviceA->id,
            'location_id' => $this->locationA->id,
            'start_time'  => Carbon::tomorrow()->setTime(10, 0),
            'end_time'    => Carbon::tomorrow()->setTime(10, 30),
            'status'      => 'confirmed',
        ]);

        $this->quotationService = app(QuotationCreationService::class);
    }

    /** Test 1 — Show renders with core quotation details */
    public function test_show_renders_with_core_quotation_details(): void
    {
        $quotation = $this->quotationService->createQuotation([
            'client_id'      => $this->client->id,
            'staff_id'       => $this->staffA->id,
            'appointment_id' => $this->appointment->id,
            'issued_date'    => '2026-10-06',
            'valid_until'    => '2026-11-05',
            'status'         => 'draft',
            'items'          => [
                [
                    'service_id'  => $this->serviceA->id,
                    'description' => 'Initial Assessment',
                    'quantity'    => 1,
                    'unit_price'  => 100.00,
                ],
            ],
        ]);

        $response = $this->actingAs($this->admin, 'staff')
            ->get(route('quotations.show', $quotation));

        $response->assertStatus(200);
        $response->assertViewIs('quotations.show');
        $response->assertSee('QUOTATION');
        $response->assertSee($quotation->quotation_number);
        $response->assertSee($this->client->name);
        $response->assertSee('Issued Date:');
        $response->assertSee('06 Oct 2026');
        $response->assertSee('Valid Until:');
        $response->assertSee('05 Nov 2026');
        $response->assertSee('Status:');
        $response->assertSee('Draft');
    }

    /** Test 2 — Multi-item rendering */
    public function test_multi_item_rendering_displays_all_financial_columns(): void
    {
        $quotation = $this->quotationService->createQuotation([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staffA->id,
            'issued_date' => '2026-10-06',
            'valid_until' => '2026-11-05',
            'items'       => [
                [
                    'service_id'      => $this->serviceA->id,
                    'description'     => 'Service A Item',
                    'quantity'        => 2,
                    'unit_price'      => 50.00,
                    'discount_amount' => 10.00,
                    'tax_rate'        => 10.00,
                ],
                [
                    'service_id'      => $this->serviceB->id,
                    'description'     => 'Service B Item',
                    'quantity'        => 1.5,
                    'unit_price'      => 40.00,
                    'discount_amount' => 0.00,
                    'tax_rate'        => 0.00,
                ],
            ],
        ]);

        $response = $this->actingAs($this->admin, 'staff')
            ->get(route('quotations.show', $quotation));

        $response->assertStatus(200);

        // Descriptions and Services
        $response->assertSee('Service A Item');
        $response->assertSee('Service B Item');

        // Quantities
        $response->assertSee('2');
        $response->assertSee('1.5');

        // Rates
        $response->assertSee('50.00');
        $response->assertSee('40.00');

        // Line totals
        $response->assertSee('99.00');
        $response->assertSee('60.00');

        // Header totals
        $response->assertSee('160.00'); // Subtotal
        $response->assertSee('10.00');  // Discount
        $response->assertSee('9.00');   // Tax
        $response->assertSee('159.00'); // Grand total
    }

    /** Test 3 — Historical snapshot */
    public function test_historical_snapshot_displays_persisted_rate_not_current_catalog_price(): void
    {
        // Service price is set to 62.15
        $this->serviceA->update(['price' => 62.15]);

        // Quotation created with distinct unit_price 101.70
        $quotation = $this->quotationService->createQuotation([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staffA->id,
            'issued_date' => '2026-10-06',
            'valid_until' => '2026-11-05',
            'items'       => [
                [
                    'service_id'  => $this->serviceA->id,
                    'description' => 'Snapshot Test Item',
                    'quantity'    => 1,
                    'unit_price'  => 101.70,
                ],
            ],
        ]);

        // Service catalog price is modified afterwards
        $this->serviceA->update(['price' => 45.00]);

        $response = $this->actingAs($this->admin, 'staff')
            ->get(route('quotations.show', $quotation));

        $response->assertStatus(200);
        $response->assertSee('101.70');
        $response->assertDontSee('62.15');
        $response->assertDontSee('45.00');
    }

    /** Test 4 — Optional appointment */
    public function test_optional_appointment_standalone_quotation_renders_successfully(): void
    {
        $quotation = $this->quotationService->createQuotation([
            'client_id'      => $this->client->id,
            'staff_id'       => $this->staffA->id,
            'appointment_id' => null,
            'issued_date'    => '2026-10-06',
            'valid_until'    => '2026-11-05',
            'items'          => [
                [
                    'service_id'  => $this->serviceA->id,
                    'description' => 'Consultation Without Appointment',
                    'quantity'    => 1,
                    'unit_price'  => 75.00,
                ],
            ],
        ]);

        $this->assertNull($quotation->appointment_id);

        $response = $this->actingAs($this->admin, 'staff')
            ->get(route('quotations.show', $quotation));

        $response->assertStatus(200);
        $response->assertSee('Standalone Quotation');
        $response->assertDontSee('Appointment Date');
        $response->assertDontSee('Appointment Time');
    }

    /** Test 5 — Notes and terms */
    public function test_notes_and_terms_rendered_when_present_and_omitted_when_empty(): void
    {
        // 5a: With notes and terms
        $quotationWithNotes = $this->quotationService->createQuotation([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staffA->id,
            'issued_date' => '2026-10-06',
            'valid_until' => '2026-11-05',
            'notes'       => 'Special care requested by patient.',
            'terms'       => 'Estimate valid for 30 calendar days.',
            'items'       => [
                [
                    'service_id'  => $this->serviceA->id,
                    'quantity'    => 1,
                    'unit_price'  => 50.00,
                ],
            ],
        ]);

        $responseA = $this->actingAs($this->admin, 'staff')
            ->get(route('quotations.show', $quotationWithNotes));

        $responseA->assertStatus(200);
        $responseA->assertSee('Notes');
        $responseA->assertSee('Special care requested by patient.');
        $responseA->assertSee('Terms &amp; Conditions', false);
        $responseA->assertSee('Estimate valid for 30 calendar days.');

        // 5b: Without notes and terms
        $quotationWithoutNotes = $this->quotationService->createQuotation([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staffA->id,
            'issued_date' => '2026-10-06',
            'valid_until' => '2026-11-05',
            'notes'       => null,
            'terms'       => null,
            'items'       => [
                [
                    'service_id'  => $this->serviceA->id,
                    'quantity'    => 1,
                    'unit_price'  => 50.00,
                ],
            ],
        ]);

        $responseB = $this->actingAs($this->admin, 'staff')
            ->get(route('quotations.show', $quotationWithoutNotes));

        $responseB->assertStatus(200);
        $responseB->assertDontSee('<h2>Notes</h2>', false);
        $responseB->assertDontSee('<h2>Terms &amp; Conditions</h2>', false);
    }

    /** Test 6 — Authorization */
    public function test_unauthorized_location_scoped_staff_cannot_view_other_location_quotation(): void
    {
        // Quotation created for staffB at locationB
        $quotationB = $this->quotationService->createQuotation([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staffB->id,
            'created_by'  => $this->staffB->id,
            'issued_date' => '2026-10-06',
            'valid_until' => '2026-11-05',
            'items'       => [
                [
                    'service_id'  => $this->serviceB->id,
                    'quantity'    => 1,
                    'unit_price'  => 80.00,
                ],
            ],
        ]);

        // StaffA belongs strictly to locationA -> should receive 403 Forbidden
        $responseUnauthorized = $this->actingAs($this->staffA, 'staff')
            ->get(route('quotations.show', $quotationB));

        $responseUnauthorized->assertStatus(403);

        // Admin retains global access -> should receive 200
        $responseAdmin = $this->actingAs($this->admin, 'staff')
            ->get(route('quotations.show', $quotationB));

        $responseAdmin->assertStatus(200);

        // StaffB belongs to locationB -> should receive 200
        $responseAuthorized = $this->actingAs($this->staffB, 'staff')
            ->get(route('quotations.show', $quotationB));

        $responseAuthorized->assertStatus(200);
    }

    /** Test 7 — No payment information */
    public function test_no_payment_information_or_balance_or_conversion_exposed(): void
    {
        $quotation = $this->quotationService->createQuotation([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staffA->id,
            'issued_date' => '2026-10-06',
            'valid_until' => '2026-11-05',
            'items'       => [
                [
                    'service_id'  => $this->serviceA->id,
                    'quantity'    => 1,
                    'unit_price'  => 50.00,
                ],
            ],
        ]);

        $response = $this->actingAs($this->admin, 'staff')
            ->get(route('quotations.show', $quotation));

        $response->assertStatus(200);
        $response->assertDontSee('Payment Records');
        $response->assertDontSee('Payment History');
        $response->assertDontSee('Amount Paid');
        $response->assertDontSee('Balance Due');
        $response->assertDontSee('Add payment');
        $response->assertDontSee('invoices.download', false);
        $response->assertDontSee('Convert to Invoice');
    }

    /** Test 8 — Empty items safety */
    public function test_empty_items_safety_renders_without_crashing(): void
    {
        $quotation = Quotation::create([
            'quotation_number' => 'QUO-EMPTY-0001',
            'client_id'        => $this->client->id,
            'staff_id'         => $this->staffA->id,
            'status'           => 'draft',
            'issued_date'      => '2026-10-06',
            'valid_until'      => '2026-11-05',
            'subtotal'         => 0.00,
            'discount_amount'  => 0.00,
            'tax_amount'       => 0.00,
            'total_amount'     => 0.00,
        ]);

        $response = $this->actingAs($this->admin, 'staff')
            ->get(route('quotations.show', $quotation));

        $response->assertStatus(200);
        $response->assertSee('No quotation items recorded.');
    }

    /** Test 9 — Step 6: Convert button visibility strictly depends on accepted status */
    public function test_convert_button_visibility_strictly_depends_on_accepted_status(): void
    {
        $quotation = $this->quotationService->createQuotation([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staffA->id,
            'issued_date' => '2026-10-06',
            'valid_until' => '2026-11-05',
            'status'      => 'draft',
            'items'       => [
                [
                    'service_id'  => $this->serviceA->id,
                    'quantity'    => 1,
                    'unit_price'  => 50.00,
                ],
            ],
        ]);

        // draft -> hidden
        $responseDraft = $this->actingAs($this->admin, 'staff')->get(route('quotations.show', $quotation));
        $responseDraft->assertDontSee('Convert to Invoice');

        // sent -> hidden
        $quotation->update(['status' => 'sent']);
        $responseSent = $this->actingAs($this->admin, 'staff')->get(route('quotations.show', $quotation));
        $responseSent->assertDontSee('Convert to Invoice');

        // accepted -> visible
        $quotation->update(['status' => 'accepted']);
        $responseAccepted = $this->actingAs($this->admin, 'staff')->get(route('quotations.show', $quotation));
        $responseAccepted->assertSee('Convert to Invoice');
        $responseAccepted->assertSee(route('quotations.convert', $quotation));

        // rejected -> hidden
        $quotation->update(['status' => 'rejected']);
        $responseRejected = $this->actingAs($this->admin, 'staff')->get(route('quotations.show', $quotation));
        $responseRejected->assertDontSee('Convert to Invoice');

        // expired -> hidden
        $quotation->update(['status' => 'expired']);
        $responseExpired = $this->actingAs($this->admin, 'staff')->get(route('quotations.show', $quotation));
        $responseExpired->assertDontSee('Convert to Invoice');

        // converted -> hidden
        $quotation->update(['status' => 'converted']);
        $responseConverted = $this->actingAs($this->admin, 'staff')->get(route('quotations.show', $quotation));
        $responseConverted->assertDontSee('Convert to Invoice');
    }
}
