<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Staff;
use App\Services\QuotationConversionService;
use App\Services\QuotationCreationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Tests\TestCase;

class QuotationConversionTest extends TestCase
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
    private QuotationConversionService $conversionService;

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
        $this->conversionService = app(QuotationConversionService::class);
    }

    /** 1. Accepted quotation converts successfully */
    public function test_accepted_quotation_converts_successfully(): void
    {
        $quotation = $this->quotationService->createQuotation([
            'client_id'      => $this->client->id,
            'staff_id'       => $this->staffA->id,
            'appointment_id' => $this->appointment->id,
            'status'         => 'draft',
            'items'          => [
                [
                    'service_id'  => $this->serviceA->id,
                    'description' => 'Dental Checkup Item',
                    'quantity'    => 1,
                    'unit_price'  => 100.00,
                ],
            ],
        ]);

        $quotation->update(['status' => 'accepted']);

        $invoice = $this->conversionService->convertToInvoice($quotation);

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertDatabaseHas('invoices', [
            'id'           => $invoice->id,
            'quotation_id' => $quotation->id,
            'status'       => 'outstanding',
        ]);
        $this->assertEquals('converted', $quotation->fresh()->status);
    }

    /** 2. Draft quotation rejected */
    public function test_draft_quotation_conversion_is_rejected(): void
    {
        $quotation = $this->quotationService->createQuotation([
            'client_id' => $this->client->id,
            'staff_id'  => $this->staffA->id,
            'status'    => 'draft',
            'items'     => [
                [
                    'service_id'  => $this->serviceA->id,
                    'quantity'    => 1,
                    'unit_price'  => 100.00,
                ],
            ],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only accepted quotations can be converted to an invoice. Current status: draft.');

        $this->conversionService->convertToInvoice($quotation);
    }

    /** 3. Sent quotation rejected */
    public function test_sent_quotation_conversion_is_rejected(): void
    {
        $quotation = $this->quotationService->createQuotation([
            'client_id' => $this->client->id,
            'staff_id'  => $this->staffA->id,
            'status'    => 'draft',
            'items'     => [
                [
                    'service_id'  => $this->serviceA->id,
                    'quantity'    => 1,
                    'unit_price'  => 100.00,
                ],
            ],
        ]);
        $quotation->update(['status' => 'sent']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only accepted quotations can be converted to an invoice. Current status: sent.');

        $this->conversionService->convertToInvoice($quotation);
    }

    /** 4. Rejected quotation rejected */
    public function test_rejected_quotation_conversion_is_rejected(): void
    {
        $quotation = $this->quotationService->createQuotation([
            'client_id' => $this->client->id,
            'staff_id'  => $this->staffA->id,
            'status'    => 'draft',
            'items'     => [
                [
                    'service_id'  => $this->serviceA->id,
                    'quantity'    => 1,
                    'unit_price'  => 100.00,
                ],
            ],
        ]);
        $quotation->update(['status' => 'rejected']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only accepted quotations can be converted to an invoice. Current status: rejected.');

        $this->conversionService->convertToInvoice($quotation);
    }

    /** 5. Expired quotation rejected */
    public function test_expired_quotation_conversion_is_rejected(): void
    {
        $quotation = $this->quotationService->createQuotation([
            'client_id' => $this->client->id,
            'staff_id'  => $this->staffA->id,
            'status'    => 'draft',
            'items'     => [
                [
                    'service_id'  => $this->serviceA->id,
                    'quantity'    => 1,
                    'unit_price'  => 100.00,
                ],
            ],
        ]);
        $quotation->update(['status' => 'expired']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only accepted quotations can be converted to an invoice. Current status: expired.');

        $this->conversionService->convertToInvoice($quotation);
    }

    /** 6. Converted quotation rejected */
    public function test_converted_quotation_conversion_is_rejected(): void
    {
        $quotation = $this->quotationService->createQuotation([
            'client_id' => $this->client->id,
            'staff_id'  => $this->staffA->id,
            'status'    => 'draft',
            'items'     => [
                [
                    'service_id'  => $this->serviceA->id,
                    'quantity'    => 1,
                    'unit_price'  => 100.00,
                ],
            ],
        ]);
        $quotation->update(['status' => 'converted']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only accepted quotations can be converted to an invoice. Current status: converted.');

        $this->conversionService->convertToInvoice($quotation);
    }

    /** 7. Quotation ID copied to invoice */
    public function test_quotation_id_is_populated_on_converted_invoice(): void
    {
        $quotation = $this->quotationService->createQuotation([
            'client_id' => $this->client->id,
            'staff_id'  => $this->staffA->id,
            'status'    => 'draft',
            'items'     => [
                [
                    'service_id'  => $this->serviceA->id,
                    'quantity'    => 1,
                    'unit_price'  => 100.00,
                ],
            ],
        ]);
        $quotation->update(['status' => 'accepted']);

        $invoice = $this->conversionService->convertToInvoice($quotation);

        $this->assertEquals($quotation->id, $invoice->quotation_id);
        $this->assertEquals($quotation->id, $invoice->quotation->id);
    }

    /** 8. Quotation item IDs copied to invoice items */
    public function test_quotation_item_ids_are_populated_on_converted_invoice_items(): void
    {
        $quotation = $this->quotationService->createQuotation([
            'client_id' => $this->client->id,
            'staff_id'  => $this->staffA->id,
            'status'    => 'draft',
            'items'     => [
                [
                    'service_id'  => $this->serviceA->id,
                    'description' => 'First Quote Item',
                    'quantity'    => 1,
                    'unit_price'  => 100.00,
                ],
                [
                    'service_id'  => $this->serviceB->id,
                    'description' => 'Second Quote Item',
                    'quantity'    => 2,
                    'unit_price'  => 80.00,
                ],
            ],
        ]);
        $quotation->update(['status' => 'accepted']);

        $invoice = $this->conversionService->convertToInvoice($quotation);

        $quotationItemIds = $quotation->items->pluck('id')->all();
        $invoiceItemQuotationIds = $invoice->items->pluck('quotation_item_id')->all();

        $this->assertCount(2, $invoiceItemQuotationIds);
        $this->assertEquals($quotationItemIds, $invoiceItemQuotationIds);
    }

    /** 9. Historical unit prices preserved */
    public function test_historical_unit_prices_preserved_from_quotation_items(): void
    {
        $quotation = $this->quotationService->createQuotation([
            'client_id' => $this->client->id,
            'staff_id'  => $this->staffA->id,
            'status'    => 'draft',
            'items'     => [
                [
                    'service_id'  => $this->serviceA->id,
                    'description' => 'Discounted Consultation',
                    'quantity'    => 1,
                    'unit_price'  => 77.50,
                ],
            ],
        ]);
        $quotation->update(['status' => 'accepted']);

        // Alter catalog price before conversion
        $this->serviceA->update(['price' => 199.99]);

        $invoice = $this->conversionService->convertToInvoice($quotation);

        $this->assertEquals(77.50, (float) $invoice->items->first()->unit_price);
        $this->assertNotEquals(199.99, (float) $invoice->items->first()->unit_price);
    }

    /** 10. Multi-item mapping */
    public function test_multi_item_quotation_mapping(): void
    {
        $quotation = $this->quotationService->createQuotation([
            'client_id' => $this->client->id,
            'staff_id'  => $this->staffA->id,
            'status'    => 'draft',
            'items'     => [
                [
                    'service_id'      => $this->serviceA->id,
                    'description'     => 'Service Item A',
                    'quantity'        => 2,
                    'unit_price'      => 50.00,
                    'discount_amount' => 10.00,
                    'tax_rate'        => 10.00,
                ],
                [
                    'service_id'      => $this->serviceB->id,
                    'description'     => 'Service Item B',
                    'quantity'        => 1.5,
                    'unit_price'      => 40.00,
                    'discount_amount' => 0.00,
                    'tax_rate'        => 0.00,
                ],
            ],
        ]);
        $quotation->update(['status' => 'accepted']);

        $invoice = $this->conversionService->convertToInvoice($quotation);

        $this->assertCount(2, $invoice->items);

        $item1 = $invoice->items->get(0);
        $this->assertEquals('Service Item A', $item1->description);
        $this->assertEquals(2.0, (float) $item1->quantity);
        $this->assertEquals(50.00, (float) $item1->unit_price);
        $this->assertEquals(10.00, (float) $item1->discount_amount);
        $this->assertEquals(10.00, (float) $item1->tax_rate);
        $this->assertEquals(9.00, (float) $item1->tax_amount);
        $this->assertEquals(99.00, (float) $item1->line_total);

        $item2 = $invoice->items->get(1);
        $this->assertEquals('Service Item B', $item2->description);
        $this->assertEquals(1.5, (float) $item2->quantity);
        $this->assertEquals(40.00, (float) $item2->unit_price);
        $this->assertEquals(0.00, (float) $item2->discount_amount);
        $this->assertEquals(0.00, (float) $item2->tax_rate);
        $this->assertEquals(0.00, (float) $item2->tax_amount);
        $this->assertEquals(60.00, (float) $item2->line_total);

        $this->assertEquals(160.00, (float) $invoice->subtotal);
        $this->assertEquals(10.00, (float) $invoice->discount_amount);
        $this->assertEquals(9.00, (float) $invoice->tax_amount);
        $this->assertEquals(159.00, (float) $invoice->total_amount);
    }

    /** 11. Standalone quotation conversion */
    public function test_standalone_quotation_converts_to_standalone_invoice(): void
    {
        $quotation = $this->quotationService->createQuotation([
            'client_id'      => $this->client->id,
            'staff_id'       => $this->staffA->id,
            'appointment_id' => null,
            'status'         => 'draft',
            'items'          => [
                [
                    'service_id'  => $this->serviceA->id,
                    'description' => 'Standalone Consult',
                    'quantity'    => 1,
                    'unit_price'  => 120.00,
                ],
            ],
        ]);
        $quotation->update(['status' => 'accepted']);

        $invoice = $this->conversionService->convertToInvoice($quotation);

        $this->assertNull($invoice->appointment_id);
        $this->assertDatabaseHas('invoices', [
            'id'             => $invoice->id,
            'appointment_id' => null,
            'quotation_id'   => $quotation->id,
            'total_amount'   => 120.00,
        ]);
    }

    /** 12. Appointment-linked quotation conversion */
    public function test_appointment_linked_quotation_preserves_appointment_on_invoice(): void
    {
        $quotation = $this->quotationService->createQuotation([
            'client_id'      => $this->client->id,
            'staff_id'       => $this->staffA->id,
            'appointment_id' => $this->appointment->id,
            'status'         => 'draft',
            'items'          => [
                [
                    'service_id'  => $this->serviceA->id,
                    'description' => 'Linked Appointment Consult',
                    'quantity'    => 1,
                    'unit_price'  => 100.00,
                ],
            ],
        ]);
        $quotation->update(['status' => 'accepted']);

        $invoice = $this->conversionService->convertToInvoice($quotation);

        $this->assertEquals($this->appointment->id, $invoice->appointment_id);
        $this->assertDatabaseHas('invoices', [
            'id'             => $invoice->id,
            'appointment_id' => $this->appointment->id,
            'quotation_id'   => $quotation->id,
        ]);
    }

    /** 13. Step 2 — Historical price protection (100 vs 150) */
    public function test_step2_historical_price_protection_retains_quotation_rate_when_service_price_changes(): void
    {
        $this->serviceA->update(['price' => 100.00]);

        $quotation = $this->quotationService->createQuotation([
            'client_id' => $this->client->id,
            'staff_id'  => $this->staffA->id,
            'status'    => 'draft',
            'items'     => [
                [
                    'service_id'  => $this->serviceA->id,
                    'description' => 'Fixed Price Service',
                    'quantity'    => 1,
                    'unit_price'  => 100.00,
                ],
            ],
        ]);
        $quotation->update(['status' => 'accepted']);

        // Catalog price jumps to 150.00
        $this->serviceA->update(['price' => 150.00]);

        $invoice = $this->conversionService->convertToInvoice($quotation);

        $this->assertEquals(100.00, (float) $invoice->items->first()->unit_price);
        $this->assertNotEquals(150.00, (float) $invoice->items->first()->unit_price);
        $this->assertEquals(100.00, (float) $invoice->total_amount);
    }

    /** 14. Step 2 — Financial snapshot headers and line totals match quotation exactly */
    public function test_step2_financial_snapshot_headers_and_line_totals_match_quotation_exactly(): void
    {
        $quotation = $this->quotationService->createQuotation([
            'client_id' => $this->client->id,
            'staff_id'  => $this->staffA->id,
            'status'    => 'draft',
            'items'     => [
                [
                    'service_id'      => $this->serviceA->id,
                    'description'     => 'Complex Item A',
                    'quantity'        => 3,
                    'unit_price'      => 45.00,
                    'discount_amount' => 15.00,
                    'tax_rate'        => 13.00,
                ],
                [
                    'service_id'      => $this->serviceB->id,
                    'description'     => 'Complex Item B',
                    'quantity'        => 2.5,
                    'unit_price'      => 60.00,
                    'discount_amount' => 10.00,
                    'tax_rate'        => 5.00,
                ],
            ],
        ]);
        $quotation->update(['status' => 'accepted']);

        $invoice = $this->conversionService->convertToInvoice($quotation);

        // Header level parity
        $this->assertEquals((float) $quotation->subtotal, (float) $invoice->subtotal);
        $this->assertEquals((float) $quotation->discount_amount, (float) $invoice->discount_amount);
        $this->assertEquals((float) $quotation->tax_amount, (float) $invoice->tax_amount);
        $this->assertEquals((float) $quotation->total_amount, (float) $invoice->total_amount);

        // Line item level parity
        $qItems = $quotation->items->values();
        $invItems = $invoice->items->values();

        for ($i = 0; $i < $qItems->count(); $i++) {
            $qItem = $qItems[$i];
            $invItem = $invItems[$i];

            $this->assertEquals((float) $qItem->discount_amount, (float) $invItem->discount_amount);
            $this->assertEquals((float) $qItem->tax_rate, (float) $invItem->tax_rate);
            $this->assertEquals((float) $qItem->tax_amount, (float) $invItem->tax_amount);
            $this->assertEquals((float) $qItem->line_total, (float) $invItem->line_total);
        }
    }

    /** 15. Step 2 — Decimal quantity preserved accurately */
    public function test_step2_decimal_quantity_is_preserved_accurately(): void
    {
        $quotation = $this->quotationService->createQuotation([
            'client_id' => $this->client->id,
            'staff_id'  => $this->staffA->id,
            'status'    => 'draft',
            'items'     => [
                [
                    'service_id'  => $this->serviceA->id,
                    'description' => 'Fractional Hours Service',
                    'quantity'    => 1.75,
                    'unit_price'  => 80.00,
                ],
            ],
        ]);
        $quotation->update(['status' => 'accepted']);

        $invoice = $this->conversionService->convertToInvoice($quotation);

        $this->assertEquals(1.75, (float) $invoice->items->first()->quantity);
        $this->assertEquals(140.00, (float) $invoice->items->first()->line_total);
        $this->assertEquals(140.00, (float) $invoice->total_amount);
    }

    /** 16. Step 2 — Item sort order is strictly preserved */
    public function test_step2_item_sort_order_is_strictly_preserved(): void
    {
        $quotation = $this->quotationService->createQuotation([
            'client_id' => $this->client->id,
            'staff_id'  => $this->staffA->id,
            'status'    => 'draft',
            'items'     => [
                [
                    'service_id'  => $this->serviceA->id,
                    'description' => 'Ordered Item 1',
                    'quantity'    => 1,
                    'unit_price'  => 10.00,
                ],
                [
                    'service_id'  => $this->serviceB->id,
                    'description' => 'Ordered Item 2',
                    'quantity'    => 1,
                    'unit_price'  => 20.00,
                ],
                [
                    'service_id'  => $this->serviceA->id,
                    'description' => 'Ordered Item 3',
                    'quantity'    => 1,
                    'unit_price'  => 30.00,
                ],
            ],
        ]);
        $quotation->update(['status' => 'accepted']);

        $invoice = $this->conversionService->convertToInvoice($quotation);

        $this->assertEquals(['Ordered Item 1', 'Ordered Item 2', 'Ordered Item 3'], $invoice->items->pluck('description')->all());
        $this->assertEquals([1, 2, 3], $invoice->items->pluck('sort_order')->all());
    }

    /** 17. Step 3 — Duplicate conversion attempt is rejected */
    public function test_step3_duplicate_conversion_attempt_is_rejected(): void
    {
        $quotation = $this->quotationService->createQuotation([
            'client_id' => $this->client->id,
            'staff_id'  => $this->staffA->id,
            'status'    => 'draft',
            'items'     => [
                [
                    'service_id'  => $this->serviceA->id,
                    'description' => 'Single Execution Item',
                    'quantity'    => 1,
                    'unit_price'  => 50.00,
                ],
            ],
        ]);
        $quotation->update(['status' => 'accepted']);

        // First conversion succeeds
        $invoice1 = $this->conversionService->convertToInvoice($quotation);
        $this->assertNotNull($invoice1);
        $this->assertEquals('converted', $quotation->fresh()->status);

        // Second conversion attempt must be rejected
        $this->expectException(InvalidArgumentException::class);

        try {
            $this->conversionService->convertToInvoice($quotation->fresh());
        } finally {
            $this->assertEquals(1, Invoice::where('quotation_id', $quotation->id)->count());
            $this->assertEquals(1, $invoice1->items()->count());
            $this->assertEquals('converted', $quotation->fresh()->status);
        }
    }

    /** 18. Step 3 — Existing linked invoice blocks conversion */
    public function test_step3_existing_linked_invoice_blocks_conversion(): void
    {
        $quotation = $this->quotationService->createQuotation([
            'client_id' => $this->client->id,
            'staff_id'  => $this->staffA->id,
            'status'    => 'draft',
            'items'     => [
                [
                    'service_id'  => $this->serviceA->id,
                    'description' => 'Pre-linked Item',
                    'quantity'    => 1,
                    'unit_price'  => 50.00,
                ],
            ],
        ]);
        $quotation->update(['status' => 'accepted']);

        // Pre-create an invoice directly linked to this quotation
        Invoice::create([
            'quotation_id'    => $quotation->id,
            'client_id'       => $this->client->id,
            'staff_id'        => $this->staffA->id,
            'invoice_number'  => 'INV-PREEXISTING-001',
            'subtotal'        => 50.00,
            'discount_amount' => 0.00,
            'tax_amount'      => 0.00,
            'total_amount'    => 50.00,
            'paid_amount'     => 0.00,
            'status'          => 'outstanding',
            'issued_date'     => now()->toDateString(),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Quotation {$quotation->quotation_number} has already been converted to an invoice.");

        $this->conversionService->convertToInvoice($quotation);
    }

    /** 19. Step 3 — Existing appointment invoice collision blocks conversion */
    public function test_step3_appointment_existing_invoice_collision_blocks_conversion(): void
    {
        $quotation = $this->quotationService->createQuotation([
            'client_id'      => $this->client->id,
            'staff_id'       => $this->staffA->id,
            'appointment_id' => $this->appointment->id,
            'status'         => 'draft',
            'items'          => [
                [
                    'service_id'  => $this->serviceA->id,
                    'description' => 'Appointment Collision Item',
                    'quantity'    => 1,
                    'unit_price'  => 100.00,
                ],
            ],
        ]);
        $quotation->update(['status' => 'accepted']);

        // An invoice already exists for this appointment
        Invoice::create([
            'appointment_id'  => $this->appointment->id,
            'client_id'       => $this->client->id,
            'staff_id'        => $this->staffA->id,
            'invoice_number'  => 'INV-APPT-EXISTING-001',
            'subtotal'        => 100.00,
            'discount_amount' => 0.00,
            'tax_amount'      => 0.00,
            'total_amount'    => 100.00,
            'paid_amount'     => 0.00,
            'status'          => 'outstanding',
            'issued_date'     => now()->toDateString(),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("An invoice already exists for the appointment linked to quotation {$quotation->quotation_number}.");

        $this->conversionService->convertToInvoice($quotation);
    }

    /** 20. Step 3 — Concurrency simulation with fresh lock state */
    public function test_step3_concurrency_race_condition_protection(): void
    {
        $quotation = $this->quotationService->createQuotation([
            'client_id' => $this->client->id,
            'staff_id'  => $this->staffA->id,
            'status'    => 'draft',
            'items'     => [
                [
                    'service_id'  => $this->serviceA->id,
                    'description' => 'Concurrency Item',
                    'quantity'    => 1,
                    'unit_price'  => 60.00,
                ],
            ],
        ]);
        $quotation->update(['status' => 'accepted']);

        // Simulate Worker 1 converting and changing status right as Worker 2 executes
        $invoice1 = $this->conversionService->convertToInvoice($quotation);
        $this->assertNotNull($invoice1);

        // Worker 2 attempts conversion with the initial model instance
        $this->expectException(InvalidArgumentException::class);
        $this->conversionService->convertToInvoice($quotation);

        $this->assertEquals(1, Invoice::where('quotation_id', $quotation->id)->count());
    }

    /** 21. Step 4 — Scenario A: Invoice creation failure rolls back atomically */
    public function test_step4_scenario_a_invoice_creation_failure_rolls_back_atomically(): void
    {
        $quotation = $this->quotationService->createQuotation([
            'client_id' => $this->client->id,
            'staff_id'  => $this->staffA->id,
            'status'    => 'draft',
            'items'     => [
                [
                    'service_id'  => $this->serviceA->id,
                    'description' => 'Rollback Test Item A',
                    'quantity'    => 1,
                    'unit_price'  => 50.00,
                ],
            ],
        ]);
        $quotation->update(['status' => 'accepted']);

        // Mock InvoiceCreationService to throw an exception during createInvoice
        $mockInvoiceService = $this->createMock(\App\Services\InvoiceCreationService::class);
        $mockInvoiceService->method('createInvoice')
            ->willThrowException(new \RuntimeException('Simulated invoice persistence error.'));

        $conversionService = new QuotationConversionService($mockInvoiceService);

        try {
            $conversionService->convertToInvoice($quotation);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Simulated invoice persistence error.', $e->getMessage());
        }

        // Verify zero invoices created and quotation remains accepted
        $this->assertEquals(0, Invoice::where('quotation_id', $quotation->id)->count());
        $this->assertEquals(0, \App\Models\InvoiceItem::count());
        $this->assertEquals('accepted', $quotation->fresh()->status);
    }

    /** 22. Step 4 — Scenario B: Failure after invoice creation but before status update rolls back everything */
    public function test_step4_scenario_b_status_update_failure_rolls_back_all_created_records(): void
    {
        $quotation = $this->quotationService->createQuotation([
            'client_id' => $this->client->id,
            'staff_id'  => $this->staffA->id,
            'status'    => 'draft',
            'items'     => [
                [
                    'service_id'  => $this->serviceA->id,
                    'description' => 'Rollback Test Item B',
                    'quantity'    => 1,
                    'unit_price'  => 75.00,
                ],
            ],
        ]);
        $quotation->update(['status' => 'accepted']);

        // Simulate crash right as quotation status is about to be saved as 'converted'
        Quotation::saving(function ($model) {
            if ($model->status === 'converted') {
                throw new \RuntimeException('Simulated failure during quotation status update to converted.');
            }
        });

        try {
            $this->conversionService->convertToInvoice($quotation);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Simulated failure during quotation status update to converted.', $e->getMessage());
        }

        // Entire transaction must have rolled back: no invoice, no invoice items, quotation remains accepted
        $this->assertEquals(0, Invoice::where('quotation_id', $quotation->id)->count());
        $this->assertEquals(0, \App\Models\InvoiceItem::count());
        $this->assertEquals('accepted', $quotation->fresh()->status);
    }

    /** 23. Step 4 — Scenario C: Successful conversion commits atomically */
    public function test_step4_scenario_c_successful_conversion_commits_atomically(): void
    {
        $quotation = $this->quotationService->createQuotation([
            'client_id' => $this->client->id,
            'staff_id'  => $this->staffA->id,
            'status'    => 'draft',
            'items'     => [
                [
                    'service_id'  => $this->serviceA->id,
                    'description' => 'Atomic Success Item',
                    'quantity'    => 2,
                    'unit_price'  => 50.00,
                ],
            ],
        ]);
        $quotation->update(['status' => 'accepted']);

        $invoice = $this->conversionService->convertToInvoice($quotation);

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertDatabaseHas('invoices', [
            'id'           => $invoice->id,
            'quotation_id' => $quotation->id,
            'total_amount' => 100.00,
        ]);
        $this->assertDatabaseHas('invoice_items', [
            'invoice_id'        => $invoice->id,
            'quotation_item_id' => $quotation->items->first()->id,
            'line_total'        => 100.00,
        ]);
        $this->assertEquals('converted', $quotation->fresh()->status);
    }

    /** 24. Step 5 — Route exists and accepted quotation converts via HTTP */
    public function test_step5_route_exists_and_accepted_quotation_converts_via_http(): void
    {
        $quotation = $this->quotationService->createQuotation([
            'client_id' => $this->client->id,
            'staff_id'  => $this->staffA->id,
            'status'    => 'draft',
            'items'     => [
                [
                    'service_id'  => $this->serviceA->id,
                    'description' => 'HTTP Convert Item',
                    'quantity'    => 1,
                    'unit_price'  => 50.00,
                ],
            ],
        ]);
        $quotation->update(['status' => 'accepted']);

        $response = $this->actingAs($this->admin, 'staff')
            ->post(route('quotations.convert', $quotation));

        $response->assertStatus(302);
        $this->assertEquals('converted', $quotation->fresh()->status);

        $invoice = Invoice::where('quotation_id', $quotation->id)->first();
        $this->assertNotNull($invoice);
        $response->assertRedirect(route('invoices.show', $invoice->id));
    }

    /** 25. Step 5 — Unauthorized staff gets 403 on convert */
    public function test_step5_unauthorized_staff_gets_403_on_convert(): void
    {
        // Quotation created for staffB at locationB
        $quotationB = $this->quotationService->createQuotation([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staffB->id,
            'created_by'  => $this->staffB->id,
            'issued_date' => '2026-10-06',
            'valid_until' => '2026-11-05',
            'status'      => 'draft',
            'items'       => [
                [
                    'service_id'  => $this->serviceB->id,
                    'quantity'    => 1,
                    'unit_price'  => 80.00,
                ],
            ],
        ]);
        $quotationB->update(['status' => 'accepted']);

        // StaffA belongs strictly to locationA -> 403 Forbidden
        $response = $this->actingAs($this->staffA, 'staff')
            ->post(route('quotations.convert', $quotationB));

        $response->assertStatus(403);
        $this->assertEquals('accepted', $quotationB->fresh()->status);
        $this->assertEquals(0, Invoice::where('quotation_id', $quotationB->id)->count());
    }

    /** 26. Step 5 — Rejected quotation cannot convert via HTTP */
    public function test_step5_rejected_quotation_cannot_convert_via_http(): void
    {
        $quotation = $this->quotationService->createQuotation([
            'client_id' => $this->client->id,
            'staff_id'  => $this->staffA->id,
            'status'    => 'draft',
            'items'     => [
                [
                    'service_id'  => $this->serviceA->id,
                    'quantity'    => 1,
                    'unit_price'  => 50.00,
                ],
            ],
        ]);
        $quotation->update(['status' => 'rejected']);

        $response = $this->actingAs($this->admin, 'staff')
            ->post(route('quotations.convert', $quotation));

        $response->assertStatus(302);
        $response->assertRedirect(route('quotations.show', $quotation->id));
        $response->assertSessionHas('error');
        $this->assertEquals(0, Invoice::where('quotation_id', $quotation->id)->count());
    }

    /** 27. Step 5 — Already converted quotation cannot convert via HTTP */
    public function test_step5_already_converted_quotation_cannot_convert_via_http(): void
    {
        $quotation = $this->quotationService->createQuotation([
            'client_id' => $this->client->id,
            'staff_id'  => $this->staffA->id,
            'status'    => 'draft',
            'items'     => [
                [
                    'service_id'  => $this->serviceA->id,
                    'quantity'    => 1,
                    'unit_price'  => 50.00,
                ],
            ],
        ]);
        $quotation->update(['status' => 'converted']);

        $response = $this->actingAs($this->admin, 'staff')
            ->post(route('quotations.convert', $quotation));

        $response->assertStatus(302);
        $response->assertRedirect(route('quotations.show', $quotation->id));
        $response->assertSessionHas('error');
        $this->assertEquals(0, Invoice::where('quotation_id', $quotation->id)->count());
    }

    /** 28. Step 5 — JSON request returns 200 with created invoice */
    public function test_step5_json_request_returns_200_with_invoice(): void
    {
        $quotation = $this->quotationService->createQuotation([
            'client_id' => $this->client->id,
            'staff_id'  => $this->staffA->id,
            'status'    => 'draft',
            'items'     => [
                [
                    'service_id'  => $this->serviceA->id,
                    'description' => 'Ajax Item',
                    'quantity'    => 1,
                    'unit_price'  => 50.00,
                ],
            ],
        ]);
        $quotation->update(['status' => 'accepted']);

        $response = $this->actingAs($this->admin, 'staff')
            ->postJson(route('quotations.convert', $quotation));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Quotation converted to invoice successfully.',
        ]);
    }

    /** 29. Step 6 — Convert button visible only when status is accepted on quotation show page */
    public function test_step6_convert_button_visible_only_when_status_is_accepted_on_show_page(): void
    {
        $quotation = $this->quotationService->createQuotation([
            'client_id' => $this->client->id,
            'staff_id'  => $this->staffA->id,
            'status'    => 'draft',
            'items'     => [
                [
                    'service_id'  => $this->serviceA->id,
                    'quantity'    => 1,
                    'unit_price'  => 50.00,
                ],
            ],
        ]);

        // draft
        $res = $this->actingAs($this->admin, 'staff')->get(route('quotations.show', $quotation));
        $res->assertStatus(200);
        $res->assertDontSee('Convert to Invoice');

        // sent
        $quotation->update(['status' => 'sent']);
        $res = $this->actingAs($this->admin, 'staff')->get(route('quotations.show', $quotation));
        $res->assertDontSee('Convert to Invoice');

        // accepted
        $quotation->update(['status' => 'accepted']);
        $res = $this->actingAs($this->admin, 'staff')->get(route('quotations.show', $quotation));
        $res->assertSee('Convert to Invoice');
        $res->assertSee(route('quotations.convert', $quotation));

        // rejected
        $quotation->update(['status' => 'rejected']);
        $res = $this->actingAs($this->admin, 'staff')->get(route('quotations.show', $quotation));
        $res->assertDontSee('Convert to Invoice');

        // expired
        $quotation->update(['status' => 'expired']);
        $res = $this->actingAs($this->admin, 'staff')->get(route('quotations.show', $quotation));
        $res->assertDontSee('Convert to Invoice');

        // converted
        $quotation->update(['status' => 'converted']);
        $res = $this->actingAs($this->admin, 'staff')->get(route('quotations.show', $quotation));
        $res->assertDontSee('Convert to Invoice');
    }
}
