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
use App\Services\InvoiceCalculationService;
use App\Services\QuotationCreationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class QuotationCreationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Location $locationA;
    private Location $locationB;
    private Staff $admin;
    private Staff $staffA;
    private Staff $staffB;
    private Client $clientA;
    private Client $clientB;
    private ServiceCategory $category;
    private Service $serviceA;
    private Service $serviceB;
    private Appointment $appointmentA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->locationA = Location::create([
            'name'      => 'Toronto Downtown Clinic',
            'timezone'  => 'America/Toronto',
            'is_active' => true,
        ]);

        $this->locationB = Location::create([
            'name'      => 'York Clinic',
            'timezone'  => 'America/Toronto',
            'is_active' => true,
        ]);

        $this->category = ServiceCategory::create(['name' => 'Physiotherapy']);

        $this->admin = Staff::create([
            'location_id'  => $this->locationA->id,
            'name'         => 'Clinic Director Admin',
            'email'        => 'director@example.com',
            'password'     => Hash::make('password123'),
            'access_level' => 'admin',
            'is_active'    => true,
        ]);

        $this->staffA = Staff::create([
            'location_id'  => $this->locationA->id,
            'name'         => 'Dr. Jane Staff A',
            'email'        => 'drjane@example.com',
            'password'     => Hash::make('password123'),
            'access_level' => 'staff',
            'is_active'    => true,
        ]);

        $this->staffB = Staff::create([
            'location_id'  => $this->locationB->id,
            'name'         => 'Dr. Bob Staff B',
            'email'        => 'drbob@example.com',
            'password'     => Hash::make('password123'),
            'access_level' => 'staff',
            'is_active'    => true,
        ]);

        $this->clientA = Client::create([
            'name'   => 'Alice Client A',
            'email'  => 'alice@example.com',
            'phone'  => '416-555-0101',
            'status' => 'active',
        ]);

        $this->clientB = Client::create([
            'name'   => 'Bob Client B',
            'email'  => 'bob@example.com',
            'phone'  => '416-555-0202',
            'status' => 'active',
        ]);

        $this->serviceA = Service::create([
            'service_category_id' => $this->category->id,
            'name'                => 'Initial Assessment',
            'price'               => 90.00,
            'duration_minutes'    => 60,
            'is_active'           => true,
        ]);

        $this->serviceB = Service::create([
            'service_category_id' => $this->category->id,
            'name'                => 'Follow-up Treatment',
            'price'               => 70.00,
            'duration_minutes'    => 45,
            'is_active'           => true,
        ]);

        $this->appointmentA = Appointment::create([
            'client_id'   => $this->clientA->id,
            'staff_id'    => $this->staffA->id,
            'service_id'  => $this->serviceA->id,
            'location_id' => $this->locationA->id,
            'start_time'  => Carbon::tomorrow()->setTime(10, 0),
            'end_time'    => Carbon::tomorrow()->setTime(11, 0),
            'status'      => 'booked',
        ]);
    }

    /** Test 1 — Single item draft */
    public function test_single_item_draft_creation(): void
    {
        $payload = [
            'client_id'   => $this->clientA->id,
            'staff_id'    => $this->staffA->id,
            'issued_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'notes'       => 'Preliminary assessment quote',
            'items'       => [
                [
                    'service_id'      => $this->serviceA->id,
                    'description'     => 'Initial Assessment',
                    'quantity'        => 1,
                    'unit_price'      => 90.00,
                    'discount_amount' => 0.00,
                    'tax_rate'        => 0.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin, 'staff')
            ->postJson(route('quotations.store'), $payload);

        $response->assertStatus(201);
        $this->assertDatabaseCount('quotations', 1);
        $this->assertDatabaseCount('quotation_items', 1);

        $quotation = Quotation::first();
        $this->assertNotNull($quotation);
        $this->assertEquals('draft', $quotation->status);
        $this->assertNotNull($quotation->quotation_number);
        $this->assertStringStartsWith('QUO-', $quotation->quotation_number);
        $this->assertEquals(90.00, (float) $quotation->total_amount);
        $this->assertEquals(90.00, (float) $quotation->subtotal);

        $item = QuotationItem::first();
        $this->assertEquals($quotation->id, $item->quotation_id);
        $this->assertEquals($this->serviceA->id, $item->service_id);
        $this->assertEquals(90.00, (float) $item->line_total);
    }

    /** Test 2 — Multiple items */
    public function test_multi_item_creation_persists_all_attributes_and_order(): void
    {
        $payload = [
            'client_id'   => $this->clientA->id,
            'staff_id'    => $this->staffA->id,
            'issued_date' => now()->toDateString(),
            'valid_until' => now()->addDays(14)->toDateString(),
            'items'       => [
                [
                    'service_id'      => $this->serviceA->id,
                    'staff_id'        => $this->staffA->id,
                    'description'     => 'Item One Assessment',
                    'quantity'        => 1.0,
                    'unit_price'      => 90.00,
                    'discount_amount' => 0.00,
                    'tax_rate'        => 0.00,
                ],
                [
                    'service_id'      => $this->serviceB->id,
                    'staff_id'        => $this->staffA->id,
                    'description'     => 'Item Two Therapy',
                    'quantity'        => 2.0,
                    'unit_price'      => 70.00,
                    'discount_amount' => 10.00,
                    'tax_rate'        => 13.00,
                ],
                [
                    'service_id'      => null,
                    'staff_id'        => $this->staffA->id,
                    'description'     => 'Item Three Custom Exercise Band',
                    'quantity'        => 3.0,
                    'unit_price'      => 15.00,
                    'discount_amount' => 5.00,
                    'tax_rate'        => 0.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin, 'staff')
            ->postJson(route('quotations.store'), $payload);

        $response->assertStatus(201);
        $quotation = Quotation::with('items')->first();
        $this->assertCount(3, $quotation->items);

        $items = $quotation->items->sortBy('sort_order')->values();
        $this->assertEquals(1, $items[0]->sort_order);
        $this->assertEquals('Item One Assessment', $items[0]->description);
        $this->assertEquals(90.00, (float) $items[0]->unit_price);
        $this->assertEquals(1.0, (float) $items[0]->quantity);

        $this->assertEquals(2, $items[1]->sort_order);
        $this->assertEquals('Item Two Therapy', $items[1]->description);
        $this->assertEquals(70.00, (float) $items[1]->unit_price);
        $this->assertEquals(2.0, (float) $items[1]->quantity);
        $this->assertEquals(10.00, (float) $items[1]->discount_amount);
        $this->assertEquals(13.00, (float) $items[1]->tax_rate);

        $this->assertEquals(3, $items[2]->sort_order);
        $this->assertEquals('Item Three Custom Exercise Band', $items[2]->description);
        $this->assertEquals(15.00, (float) $items[2]->unit_price);
        $this->assertEquals(3.0, (float) $items[2]->quantity);
    }

    /** Test 3 — Financial calculation */
    public function test_financial_calculation_parity_with_calculation_service(): void
    {
        // Item A: qty 2, price 50, discount 10, tax 10%
        // subtotal = 100, taxable = 90, tax = 9, line total = 99
        // Item B: qty 1.5, price 40, discount 0, tax 0%
        // subtotal = 60, taxable = 60, tax = 0, line total = 60
        // Expected: Subtotal 160.00, Discount 10.00, Tax 9.00, Total 159.00
        $payload = [
            'client_id'   => $this->clientA->id,
            'staff_id'    => $this->staffA->id,
            'issued_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'items'       => [
                [
                    'service_id'      => $this->serviceA->id,
                    'description'     => 'Service A',
                    'quantity'        => 2,
                    'unit_price'      => 50.00,
                    'discount_amount' => 10.00,
                    'tax_rate'        => 10.00,
                ],
                [
                    'service_id'      => $this->serviceB->id,
                    'description'     => 'Service B',
                    'quantity'        => 1.5,
                    'unit_price'      => 40.00,
                    'discount_amount' => 0.00,
                    'tax_rate'        => 0.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin, 'staff')
            ->postJson(route('quotations.store'), $payload);

        $response->assertStatus(201);
        $quotation = Quotation::with('items')->first();

        $this->assertEquals(160.00, (float) $quotation->subtotal);
        $this->assertEquals(10.00, (float) $quotation->discount_amount);
        $this->assertEquals(9.00, (float) $quotation->tax_amount);
        $this->assertEquals(159.00, (float) $quotation->total_amount);

        $items = $quotation->items;
        $this->assertEquals(99.00, (float) $items[0]->line_total);
        $this->assertEquals(9.00, (float) $items[0]->tax_amount);
        $this->assertEquals(60.00, (float) $items[1]->line_total);
        $this->assertEquals(0.00, (float) $items[1]->tax_amount);
    }

    /** Test 4 — Client total tampering */
    public function test_client_total_tampering_ignored_server_calculation_wins(): void
    {
        $payload = [
            'client_id'       => $this->clientA->id,
            'staff_id'        => $this->staffA->id,
            'issued_date'     => now()->toDateString(),
            'valid_until'     => now()->addDays(30)->toDateString(),
            'subtotal'        => 9999.00,
            'discount_amount' => 999.00,
            'tax_amount'      => 999.00,
            'total_amount'    => 1.00,
            'items'           => [
                [
                    'service_id'      => $this->serviceA->id,
                    'description'     => 'Service A',
                    'quantity'        => 2,
                    'unit_price'      => 50.00,
                    'discount_amount' => 10.00,
                    'tax_rate'        => 10.00,
                    'line_total'      => 0.50,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin, 'staff')
            ->postJson(route('quotations.store'), $payload);

        $response->assertStatus(201);
        $quotation = Quotation::first();

        // Server calculated values must win
        $this->assertEquals(100.00, (float) $quotation->subtotal);
        $this->assertEquals(10.00, (float) $quotation->discount_amount);
        $this->assertEquals(9.00, (float) $quotation->tax_amount);
        $this->assertEquals(99.00, (float) $quotation->total_amount);
    }

    /** Test 5 — Service price snapshot */
    public function test_service_price_snapshot_preserved_after_service_catalog_change(): void
    {
        $service = Service::create([
            'service_category_id' => $this->category->id,
            'name'                => 'Massage Therapy Snapshot',
            'price'               => 80.00,
            'duration_minutes'    => 60,
            'is_active'           => true,
        ]);

        $payload = [
            'client_id'   => $this->clientA->id,
            'staff_id'    => $this->staffA->id,
            'issued_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'items'       => [
                [
                    'service_id'  => $service->id,
                    'quantity'    => 1,
                    'unit_price'  => 80.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin, 'staff')
            ->postJson(route('quotations.store'), $payload);

        $response->assertStatus(201);

        // Change catalog price
        $service->update(['price' => 125.00]);

        $quotationItem = QuotationItem::first();
        $this->assertEquals(80.00, (float) $quotationItem->unit_price);
        $this->assertEquals(80.00, (float) $quotationItem->line_total);
    }

    /** Test 6 — Custom item */
    public function test_custom_item_with_null_service_id_persists_and_calculates(): void
    {
        $payload = [
            'client_id'   => $this->clientA->id,
            'staff_id'    => $this->staffA->id,
            'issued_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'items'       => [
                [
                    'service_id'      => null,
                    'description'     => 'Custom Wellness Consultation Plan',
                    'quantity'        => 1,
                    'unit_price'      => 150.00,
                    'discount_amount' => 20.00,
                    'tax_rate'        => 10.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin, 'staff')
            ->postJson(route('quotations.store'), $payload);

        $response->assertStatus(201);
        $item = QuotationItem::first();
        $this->assertNull($item->service_id);
        $this->assertEquals('Custom Wellness Consultation Plan', $item->description);
        $this->assertEquals(150.00, (float) $item->unit_price);
        $this->assertEquals(20.00, (float) $item->discount_amount);
        $this->assertEquals(13.00, (float) $item->tax_amount); // (150-20)*10% = 13.00
        $this->assertEquals(143.00, (float) $item->line_total);
    }

    /** Test 7 — Decimal quantity */
    public function test_decimal_quantity_persists_and_calculates_correctly(): void
    {
        $payload = [
            'client_id'   => $this->clientA->id,
            'staff_id'    => $this->staffA->id,
            'issued_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'items'       => [
                [
                    'service_id'      => $this->serviceA->id,
                    'quantity'        => 2.5,
                    'unit_price'      => 100.00,
                    'discount_amount' => 0.00,
                    'tax_rate'        => 0.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin, 'staff')
            ->postJson(route('quotations.store'), $payload);

        $response->assertStatus(201);
        $item = QuotationItem::first();
        $this->assertEquals(2.5, (float) $item->quantity);
        $this->assertEquals(250.00, (float) $item->line_total);
    }

    /** Test 8 — Invalid quantity */
    public function test_invalid_quantity_rejected(): void
    {
        // Zero quantity
        $response1 = $this->actingAs($this->admin, 'staff')
            ->postJson(route('quotations.store'), [
                'client_id'   => $this->clientA->id,
                'staff_id'    => $this->staffA->id,
                'issued_date' => now()->toDateString(),
                'valid_until' => now()->addDays(30)->toDateString(),
                'items'       => [
                    [
                        'service_id' => $this->serviceA->id,
                        'quantity'   => 0,
                        'unit_price' => 50.00,
                    ],
                ],
            ]);
        $response1->assertStatus(422)->assertJsonValidationErrors(['items.0.quantity']);

        // Negative quantity
        $response2 = $this->actingAs($this->admin, 'staff')
            ->postJson(route('quotations.store'), [
                'client_id'   => $this->clientA->id,
                'staff_id'    => $this->staffA->id,
                'issued_date' => now()->toDateString(),
                'valid_until' => now()->addDays(30)->toDateString(),
                'items'       => [
                    [
                        'service_id' => $this->serviceA->id,
                        'quantity'   => -1,
                        'unit_price' => 50.00,
                    ],
                ],
            ]);
        $response2->assertStatus(422)->assertJsonValidationErrors(['items.0.quantity']);
    }

    /** Test 9 — Invalid discount */
    public function test_discount_exceeding_line_subtotal_rejected(): void
    {
        $response = $this->actingAs($this->admin, 'staff')
            ->postJson(route('quotations.store'), [
                'client_id'   => $this->clientA->id,
                'staff_id'    => $this->staffA->id,
                'issued_date' => now()->toDateString(),
                'valid_until' => now()->addDays(30)->toDateString(),
                'items'       => [
                    [
                        'service_id'      => $this->serviceA->id,
                        'quantity'        => 1,
                        'unit_price'      => 50.00,
                        'discount_amount' => 75.00, // exceeds 50.00
                    ],
                ],
            ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['items.0.discount_amount']);
    }

    /** Test 10 — Invalid tax */
    public function test_invalid_tax_rate_rejected(): void
    {
        $responseNegative = $this->actingAs($this->admin, 'staff')
            ->postJson(route('quotations.store'), [
                'client_id'   => $this->clientA->id,
                'staff_id'    => $this->staffA->id,
                'issued_date' => now()->toDateString(),
                'valid_until' => now()->addDays(30)->toDateString(),
                'items'       => [
                    [
                        'service_id' => $this->serviceA->id,
                        'quantity'   => 1,
                        'unit_price' => 50.00,
                        'tax_rate'   => -5.00,
                    ],
                ],
            ]);
        $responseNegative->assertStatus(422)->assertJsonValidationErrors(['items.0.tax_rate']);

        $responseOver100 = $this->actingAs($this->admin, 'staff')
            ->postJson(route('quotations.store'), [
                'client_id'   => $this->clientA->id,
                'staff_id'    => $this->staffA->id,
                'issued_date' => now()->toDateString(),
                'valid_until' => now()->addDays(30)->toDateString(),
                'items'       => [
                    [
                        'service_id' => $this->serviceA->id,
                        'quantity'   => 1,
                        'unit_price' => 50.00,
                        'tax_rate'   => 105.00,
                    ],
                ],
            ]);
        $responseOver100->assertStatus(422)->assertJsonValidationErrors(['items.0.tax_rate']);
    }

    /** Test 11 — Missing item list */
    public function test_quotation_without_items_rejected(): void
    {
        $response = $this->actingAs($this->admin, 'staff')
            ->postJson(route('quotations.store'), [
                'client_id'   => $this->clientA->id,
                'staff_id'    => $this->staffA->id,
                'issued_date' => now()->toDateString(),
                'valid_until' => now()->addDays(30)->toDateString(),
                'items'       => [],
            ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['items']);
    }

    /** Test 12 — Rollback */
    public function test_atomic_rollback_on_item_creation_failure(): void
    {
        $service = app(QuotationCreationService::class);

        $initialQuotations = Quotation::count();
        $initialItems = QuotationItem::count();

        // Listen for quotation created event and force an exception during items creation
        QuotationItem::creating(function ($item) {
            if ($item->description === 'FORCE_FAILURE') {
                throw new \Exception('Simulated database write failure');
            }
        });

        try {
            $service->createQuotation([
                'client_id'   => $this->clientA->id,
                'staff_id'    => $this->staffA->id,
                'issued_date' => now()->toDateString(),
                'valid_until' => now()->addDays(30)->toDateString(),
                'items'       => [
                    [
                        'description' => 'FORCE_FAILURE',
                        'quantity'    => 1,
                        'unit_price'  => 50.00,
                    ],
                ],
            ]);
            $this->fail('Exception was expected but not thrown');
        } catch (\Throwable $e) {
            $this->assertStringContainsString('Simulated database write failure', $e->getMessage());
        }

        $this->assertEquals($initialQuotations, Quotation::count());
        $this->assertEquals($initialItems, QuotationItem::count());
    }

    /** Test 13 — Authorization */
    public function test_unauthorized_location_scoped_staff_rejected_with_403(): void
    {
        // staffA belongs to locationA, trying to create a quotation for staffB at locationB
        $response = $this->actingAs($this->staffA, 'staff')
            ->postJson(route('quotations.store'), [
                'client_id'   => $this->clientB->id,
                'staff_id'    => $this->staffB->id, // Location B
                'issued_date' => now()->toDateString(),
                'valid_until' => now()->addDays(30)->toDateString(),
                'items'       => [
                    [
                        'service_id' => $this->serviceA->id,
                        'quantity'   => 1,
                        'unit_price' => 50.00,
                    ],
                ],
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseCount('quotations', 0);
    }

    /** Test 14 — Appointment optionality */
    public function test_standalone_quotation_without_appointment_succeeds(): void
    {
        $payload = [
            'client_id'      => $this->clientA->id,
            'staff_id'       => $this->staffA->id,
            'appointment_id' => null,
            'issued_date'    => now()->toDateString(),
            'valid_until'    => now()->addDays(30)->toDateString(),
            'items'          => [
                [
                    'service_id' => $this->serviceA->id,
                    'quantity'   => 1,
                    'unit_price' => 90.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin, 'staff')
            ->postJson(route('quotations.store'), $payload);

        $response->assertStatus(201);
        $quotation = Quotation::first();
        $this->assertNull($quotation->appointment_id);
    }

    /** Test 15 — Appointment/client relationship */
    public function test_appointment_belonging_to_different_client_rejected(): void
    {
        // appointmentA belongs to clientA, but payload specifies clientB
        $payload = [
            'client_id'      => $this->clientB->id, // Mismatch!
            'staff_id'       => $this->staffA->id,
            'appointment_id' => $this->appointmentA->id,
            'issued_date'    => now()->toDateString(),
            'valid_until'    => now()->addDays(30)->toDateString(),
            'items'          => [
                [
                    'service_id' => $this->serviceA->id,
                    'quantity'   => 1,
                    'unit_price' => 90.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin, 'staff')
            ->postJson(route('quotations.store'), $payload);

        $response->assertStatus(422)->assertJsonValidationErrors(['appointment_id']);
        $this->assertDatabaseCount('quotations', 0);
    }
}
