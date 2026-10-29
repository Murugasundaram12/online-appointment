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
use App\Services\QuotationCreationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class QuotationEditTest extends TestCase
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
    private Appointment $appointmentB;
    private QuotationCreationService $quotationCreationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->locationA = Location::create([
            'name'      => 'Central Clinic',
            'timezone'  => 'America/Toronto',
            'is_active' => true,
        ]);

        $this->locationB = Location::create([
            'name'      => 'East Clinic',
            'timezone'  => 'America/Toronto',
            'is_active' => true,
        ]);

        $this->admin = Staff::create([
            'location_id'  => $this->locationA->id,
            'name'         => 'Director Admin',
            'email'        => 'admin@clinic.test',
            'password'     => Hash::make('secret123'),
            'access_level' => 'admin',
            'is_active'    => true,
        ]);

        $this->staffA = Staff::create([
            'location_id'  => $this->locationA->id,
            'name'         => 'Dr. Alice Staff A',
            'email'        => 'alice@clinic.test',
            'password'     => Hash::make('secret123'),
            'access_level' => 'staff',
            'is_active'    => true,
        ]);

        $this->staffB = Staff::create([
            'location_id'  => $this->locationB->id,
            'name'         => 'Dr. Bob Staff B',
            'email'        => 'bob@clinic.test',
            'password'     => Hash::make('secret123'),
            'access_level' => 'staff',
            'is_active'    => true,
        ]);

        $this->clientA = Client::create([
            'name'   => 'Jane Doe Patient',
            'email'  => 'jane@patient.test',
            'phone'  => '555-0100',
            'status' => 'active',
        ]);

        $this->clientB = Client::create([
            'name'   => 'John Smith Patient',
            'email'  => 'john@patient.test',
            'phone'  => '555-0200',
            'status' => 'active',
        ]);

        $this->category = ServiceCategory::create(['name' => 'General Medicine']);

        $this->serviceA = Service::create([
            'service_category_id' => $this->category->id,
            'name'                => 'General Consultation',
            'price'               => 100.00,
            'duration_minutes'    => 30,
            'is_active'           => true,
        ]);

        $this->serviceB = Service::create([
            'service_category_id' => $this->category->id,
            'name'                => 'Advanced Therapy',
            'price'               => 150.00,
            'duration_minutes'    => 60,
            'is_active'           => true,
        ]);

        $this->appointmentA = Appointment::create([
            'client_id'   => $this->clientA->id,
            'staff_id'    => $this->staffA->id,
            'service_id'  => $this->serviceA->id,
            'location_id' => $this->locationA->id,
            'start_time'  => Carbon::tomorrow()->setTime(10, 0),
            'end_time'    => Carbon::tomorrow()->setTime(10, 30),
            'status'      => 'booked',
        ]);

        $this->appointmentB = Appointment::create([
            'client_id'   => $this->clientB->id,
            'staff_id'    => $this->staffB->id,
            'service_id'  => $this->serviceB->id,
            'location_id' => $this->locationB->id,
            'start_time'  => Carbon::tomorrow()->setTime(11, 0),
            'end_time'    => Carbon::tomorrow()->setTime(12, 0),
            'status'      => 'booked',
        ]);

        $this->quotationCreationService = app(QuotationCreationService::class);
    }

    private function createSampleDraftQuotation(): Quotation
    {
        return $this->quotationCreationService->createQuotation([
            'client_id'      => $this->clientA->id,
            'staff_id'       => $this->staffA->id,
            'created_by'     => $this->staffA->id,
            'appointment_id' => $this->appointmentA->id,
            'issued_date'    => now()->toDateString(),
            'valid_until'    => now()->addDays(14)->toDateString(),
            'notes'          => 'Initial treatment plan notes',
            'terms'          => 'Standard terms apply',
            'status'         => 'draft',
            'items'          => [
                [
                    'service_id'      => $this->serviceA->id,
                    'staff_id'        => $this->staffA->id,
                    'description'     => 'General Consultation Row',
                    'quantity'        => 1.0,
                    'unit_price'      => 100.00,
                    'discount_amount' => 10.00,
                    'tax_rate'        => 13.00,
                ],
            ],
        ]);
    }

    /** 1. Draft edit page renders with existing values */
    public function test_draft_quotation_edit_page_renders_with_existing_values(): void
    {
        $quotation = $this->createSampleDraftQuotation();

        $response = $this->actingAs($this->admin, 'staff')
            ->get(route('quotations.edit', $quotation->id));

        $response->assertStatus(200);
        $response->assertViewIs('quotations.edit');
        $response->assertSee('Edit Quotation');
        $response->assertSee($quotation->quotation_number);
        $response->assertSee('General Consultation Row');
        $response->assertSee('Initial treatment plan notes');
        $response->assertSee('Standard terms apply');
        $response->assertSee('action="' . route('quotations.update', $quotation->id) . '"', false);
    }

    /** 2. Non-draft quotations cannot access edit page */
    public function test_non_draft_quotations_cannot_access_edit_page(): void
    {
        $quotation = $this->createSampleDraftQuotation();

        foreach (['sent', 'accepted', 'rejected', 'expired', 'converted'] as $status) {
            $quotation->status = $status;
            $quotation->save();

            $response = $this->actingAs($this->admin, 'staff')
                ->get(route('quotations.edit', $quotation->id));

            $response->assertStatus(403);
        }
    }

    /** 3. Unauthenticated staff redirected to login */
    public function test_unauthenticated_staff_redirected_to_login(): void
    {
        $quotation = $this->createSampleDraftQuotation();

        $response = $this->get(route('quotations.edit', $quotation->id));
        $response->assertRedirect(route('login'));

        $putResponse = $this->put(route('quotations.update', $quotation->id), []);
        $putResponse->assertRedirect(route('login'));
    }

    /** 4. Location-scoped staff cannot edit quotation of another location */
    public function test_location_scoped_staff_cannot_edit_quotation_of_another_location(): void
    {
        $quotation = $this->createSampleDraftQuotation();

        // Staff B belongs to location B and is neither admin, assigned practitioner, creator, nor appointment staff
        $response = $this->actingAs($this->staffB, 'staff')
            ->get(route('quotations.edit', $quotation->id));

        $response->assertStatus(403);

        $putResponse = $this->actingAs($this->staffB, 'staff')
            ->put(route('quotations.update', $quotation->id), [
                'client_id'   => $this->clientA->id,
                'staff_id'    => $this->staffA->id,
                'issued_date' => now()->toDateString(),
                'valid_until' => now()->addDays(14)->toDateString(),
                'items'       => [
                    [
                        'description' => 'Updated item',
                        'quantity'    => 1,
                        'unit_price'  => 50,
                    ],
                ],
            ]);

        $putResponse->assertStatus(403);
    }

    /** 5. Draft quotation updates successfully and recalculates totals */
    public function test_draft_quotation_updates_successfully_and_recalculates_totals(): void
    {
        $quotation = $this->createSampleDraftQuotation();
        $originalNumber = $quotation->quotation_number;
        $originalCreatedBy = $quotation->created_by;

        $updatePayload = [
            'client_id'      => $this->clientA->id,
            'staff_id'       => $this->staffA->id,
            'appointment_id' => $this->appointmentA->id,
            'issued_date'    => now()->toDateString(),
            'valid_until'    => now()->addDays(30)->toDateString(),
            'notes'          => 'Updated therapy notes',
            'terms'          => 'Updated 30-day terms',
            'items'          => [
                [
                    'service_id'      => $this->serviceA->id,
                    'staff_id'        => $this->staffA->id,
                    'description'     => 'General Consultation (Revised)',
                    'quantity'        => 2.0,
                    'unit_price'      => 80.00,
                    'discount_amount' => 10.00,
                    'tax_rate'        => 10.00,
                    'sort_order'      => 1,
                ],
                [
                    'service_id'      => $this->serviceB->id,
                    'staff_id'        => $this->staffA->id,
                    'description'     => 'Added Therapy Session',
                    'quantity'        => 1.0,
                    'unit_price'      => 150.00,
                    'discount_amount' => 0.00,
                    'tax_rate'        => 0.00,
                    'sort_order'      => 2,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin, 'staff')
            ->put(route('quotations.update', $quotation->id), $updatePayload);

        $response->assertRedirect(route('quotations.show', $quotation->id));
        $response->assertSessionHas('success');

        $quotation->refresh();

        // 6. Quotation ID, number, created_by, and draft status remain unchanged
        $this->assertEquals($originalNumber, $quotation->quotation_number);
        $this->assertEquals($originalCreatedBy, $quotation->created_by);
        $this->assertEquals('draft', $quotation->status);
        $this->assertEquals('Updated therapy notes', $quotation->notes);
        $this->assertEquals('Updated 30-day terms', $quotation->terms);

        // Server-side calculation verification:
        // Item 1: 2 * 80 = 160, taxable = 150, tax 10% = 15.00, line_total = 165.00
        // Item 2: 1 * 150 = 150, taxable = 150, tax 0% = 0.00, line_total = 150.00
        // Subtotal = 310.00, Discount = 10.00, Tax = 15.00, Grand Total = 315.00
        $this->assertEquals(310.00, (float) $quotation->subtotal);
        $this->assertEquals(10.00, (float) $quotation->discount_amount);
        $this->assertEquals(15.00, (float) $quotation->tax_amount);
        $this->assertEquals(315.00, (float) $quotation->total_amount);

        // Line items replaced cleanly
        $this->assertCount(2, $quotation->items);
        $items = $quotation->items()->orderBy('sort_order')->get();
        $this->assertEquals('General Consultation (Revised)', $items[0]->description);
        $this->assertEquals(165.00, (float) $items[0]->line_total);
        $this->assertEquals('Added Therapy Session', $items[1]->description);
        $this->assertEquals(150.00, (float) $items[1]->line_total);
    }

    /** 7. Client total tampering ignored during update */
    public function test_client_total_tampering_ignored_during_update(): void
    {
        $quotation = $this->createSampleDraftQuotation();

        $tamperedPayload = [
            'client_id'       => $this->clientA->id,
            'staff_id'        => $this->staffA->id,
            'issued_date'     => now()->toDateString(),
            'valid_until'     => now()->addDays(14)->toDateString(),
            'subtotal'        => 1.00,    // Tampered
            'discount_amount' => 999.00, // Tampered
            'tax_amount'      => 0.00,   // Tampered
            'total_amount'    => 1.00,   // Tampered
            'items'           => [
                [
                    'description'     => 'Real Item',
                    'quantity'        => 2.0,
                    'unit_price'      => 100.00,
                    'discount_amount' => 0.00,
                    'tax_rate'        => 10.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin, 'staff')
            ->put(route('quotations.update', $quotation->id), $tamperedPayload);

        $response->assertRedirect(route('quotations.show', $quotation->id));

        $quotation->refresh();
        // Server recalculates: 2 * 100 = 200, 10% tax = 20, total = 220
        $this->assertEquals(200.00, (float) $quotation->subtotal);
        $this->assertEquals(20.00, (float) $quotation->tax_amount);
        $this->assertEquals(220.00, (float) $quotation->total_amount);
    }

    /** 8. Non-draft quotation update submission is rejected */
    public function test_non_draft_quotation_update_submission_is_rejected(): void
    {
        $quotation = $this->createSampleDraftQuotation();

        foreach (['sent', 'accepted', 'rejected', 'expired', 'converted'] as $status) {
            $quotation->status = $status;
            $quotation->save();

            $response = $this->actingAs($this->admin, 'staff')
                ->put(route('quotations.update', $quotation->id), [
                    'client_id'   => $this->clientA->id,
                    'staff_id'    => $this->staffA->id,
                    'issued_date' => now()->toDateString(),
                    'valid_until' => now()->addDays(14)->toDateString(),
                    'items'       => [
                        [
                            'description' => 'Will not update',
                            'quantity'    => 1,
                            'unit_price'  => 50,
                        ],
                    ],
                ]);

            $response->assertStatus(403);
        }
    }

    /** 9. Invalid items rejected during update */
    public function test_invalid_items_rejected_during_update(): void
    {
        $quotation = $this->createSampleDraftQuotation();

        // Submitting with discount exceeding subtotal
        $response = $this->actingAs($this->admin, 'staff')
            ->put(route('quotations.update', $quotation->id), [
                'client_id'   => $this->clientA->id,
                'staff_id'    => $this->staffA->id,
                'issued_date' => now()->toDateString(),
                'valid_until' => now()->addDays(14)->toDateString(),
                'items'       => [
                    [
                        'description'     => 'Bad item',
                        'quantity'        => 1,
                        'unit_price'      => 50,
                        'discount_amount' => 60, // 60 > 50
                    ],
                ],
            ]);

        $response->assertSessionHasErrors(['items.0.discount_amount']);
    }

    /** 10. Mismatched appointment and client rejected */
    public function test_mismatched_appointment_and_client_rejected(): void
    {
        $quotation = $this->createSampleDraftQuotation();

        // Appointment B belongs to Client B, but submitting for Client A
        $response = $this->actingAs($this->admin, 'staff')
            ->put(route('quotations.update', $quotation->id), [
                'client_id'      => $this->clientA->id,
                'staff_id'       => $this->staffA->id,
                'appointment_id' => $this->appointmentB->id,
                'issued_date'    => now()->toDateString(),
                'valid_until'    => now()->addDays(14)->toDateString(),
                'items'          => [
                    [
                        'description' => 'Valid item',
                        'quantity'    => 1,
                        'unit_price'  => 50,
                    ],
                ],
            ]);

        $response->assertSessionHasErrors(['appointment_id']);
    }

    /** 11. Failed update rolls back atomically */
    public function test_failed_update_rolls_back_atomically(): void
    {
        $quotation = $this->createSampleDraftQuotation();
        $originalItemCount = $quotation->items()->count();
        $originalTotal = (float) $quotation->total_amount;

        // Force a service-level failure inside transaction by mocking or triggering validation
        try {
            $this->quotationCreationService->updateDraftQuotation($quotation, [
                'client_id'   => $this->clientA->id,
                'staff_id'    => $this->staffA->id,
                'issued_date' => now()->toDateString(),
                'valid_until' => now()->addDays(14)->toDateString(),
                'items'       => [], // Invalid: empty items
            ]);
        } catch (\InvalidArgumentException $e) {
            // Expected
        }

        $quotation->refresh();
        $this->assertEquals($originalItemCount, $quotation->items()->count());
        $this->assertEquals($originalTotal, (float) $quotation->total_amount);
    }

    /** 12. Edit buttons appear only for draft quotations */
    public function test_edit_buttons_appear_only_for_draft_quotations(): void
    {
        $quotation = $this->createSampleDraftQuotation();

        // On Index page: draft shows edit link
        $indexResponse = $this->actingAs($this->admin, 'staff')
            ->get(route('quotations.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee(route('quotations.edit', $quotation->id));

        // On Show page: draft shows edit link
        $showResponse = $this->actingAs($this->admin, 'staff')
            ->get(route('quotations.show', $quotation->id));
        $showResponse->assertStatus(200);
        $showResponse->assertSee(route('quotations.edit', $quotation->id));

        // Change status to accepted
        $quotation->status = 'accepted';
        $quotation->save();

        $indexResponse2 = $this->actingAs($this->admin, 'staff')
            ->get(route('quotations.index'));
        $indexResponse2->assertDontSee(route('quotations.edit', $quotation->id));

        $showResponse2 = $this->actingAs($this->admin, 'staff')
            ->get(route('quotations.show', $quotation->id));
        $showResponse2->assertDontSee(route('quotations.edit', $quotation->id));
    }

    /** 13. Edit does not modify appointments, payments, or create invoices */
    public function test_edit_does_not_modify_appointments_or_create_invoices(): void
    {
        $quotation = $this->createSampleDraftQuotation();
        $originalApptStatus = $this->appointmentA->status;

        $response = $this->actingAs($this->admin, 'staff')
            ->put(route('quotations.update', $quotation->id), [
                'client_id'      => $this->clientA->id,
                'staff_id'       => $this->staffA->id,
                'appointment_id' => $this->appointmentA->id,
                'issued_date'    => now()->toDateString(),
                'valid_until'    => now()->addDays(20)->toDateString(),
                'items'          => [
                    [
                        'description' => 'Updated item description',
                        'quantity'    => 3,
                        'unit_price'  => 50,
                    ],
                ],
            ]);

        $response->assertRedirect(route('quotations.show', $quotation->id));

        $this->appointmentA->refresh();
        $this->assertEquals($originalApptStatus, $this->appointmentA->status);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('payment_records', 0);
    }

    /** 14. Updating with linked invoice is rejected */
    public function test_updating_with_linked_invoice_is_rejected(): void
    {
        $quotation = $this->createSampleDraftQuotation();

        // Create a dummy invoice linked to this quotation to simulate an abnormal collision
        Invoice::create([
            'invoice_number' => 'INV-TEST-001',
            'quotation_id'   => $quotation->id,
            'client_id'      => $this->clientA->id,
            'staff_id'       => $this->staffA->id,
            'issued_date'    => now()->toDateString(),
            'due_date'       => now()->addDays(7)->toDateString(),
            'total_amount'   => 100.00,
            'status'         => 'outstanding',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be edited because it is linked to an existing invoice');

        $this->quotationCreationService->updateDraftQuotation($quotation, [
            'client_id'   => $this->clientA->id,
            'staff_id'    => $this->staffA->id,
            'issued_date' => now()->toDateString(),
            'valid_until' => now()->addDays(14)->toDateString(),
            'items'       => [
                [
                    'description' => 'Will fail',
                    'quantity'    => 1,
                    'unit_price'  => 50,
                ],
            ],
        ]);
    }

    /** 15. Draft edit persists same practitioner on quotation and all items */
    public function test_draft_edit_persists_same_practitioner_on_quotation_and_all_items(): void
    {
        $quotation = $this->createSampleDraftQuotation();

        // Update top-level practitioner to admin (also in Location A)
        $response = $this->actingAs($this->admin, 'staff')
            ->put(route('quotations.update', $quotation->id), [
                'client_id'      => $this->clientA->id,
                'staff_id'       => $this->admin->id,
                'appointment_id' => $this->appointmentA->id,
                'issued_date'    => now()->toDateString(),
                'valid_until'    => now()->addDays(14)->toDateString(),
                'items'          => [
                    [
                        'service_id'  => $this->serviceA->id,
                        'staff_id'    => $this->admin->id,
                        'description' => 'Item 1',
                        'quantity'    => 1,
                        'unit_price'  => 50,
                    ],
                    [
                        'service_id'  => $this->serviceB->id,
                        'staff_id'    => null, // Null / empty inherits quotation-level staff
                        'description' => 'Item 2',
                        'quantity'    => 2,
                        'unit_price'  => 60,
                    ],
                ],
            ]);

        $response->assertRedirect(route('quotations.show', $quotation->id));
        $quotation->refresh();

        $this->assertEquals($this->admin->id, $quotation->staff_id);
        $items = $quotation->items()->orderBy('sort_order')->get();
        $this->assertCount(2, $items);
        $this->assertEquals($this->admin->id, $items[0]->staff_id);
        $this->assertEquals($this->admin->id, $items[1]->staff_id);
    }

    /** 16. Draft edit rejects mismatched item practitioner via controller validation */
    public function test_draft_edit_rejects_mismatched_item_practitioner_via_controller(): void
    {
        $quotation = $this->createSampleDraftQuotation();

        // Top-level is staffA, but second item specifies admin
        $response = $this->actingAs($this->admin, 'staff')
            ->put(route('quotations.update', $quotation->id), [
                'client_id'      => $this->clientA->id,
                'staff_id'       => $this->staffA->id,
                'appointment_id' => $this->appointmentA->id,
                'issued_date'    => now()->toDateString(),
                'valid_until'    => now()->addDays(14)->toDateString(),
                'items'          => [
                    [
                        'service_id'  => $this->serviceA->id,
                        'staff_id'    => $this->staffA->id,
                        'description' => 'Item 1',
                        'quantity'    => 1,
                        'unit_price'  => 50,
                    ],
                    [
                        'service_id'  => $this->serviceB->id,
                        'staff_id'    => $this->admin->id, // Mismatch!
                        'description' => 'Item 2',
                        'quantity'    => 1,
                        'unit_price'  => 60,
                    ],
                ],
            ]);

        $response->assertSessionHasErrors(['items.1.staff_id']);
    }

    /** 17. Draft edit rejects mismatched item practitioner via service layer */
    public function test_draft_edit_rejects_mismatched_item_practitioner_via_service(): void
    {
        $quotation = $this->createSampleDraftQuotation();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('All quotation items must belong to the selected practitioner');

        $this->quotationCreationService->updateDraftQuotation($quotation, [
            'client_id'      => $this->clientA->id,
            'staff_id'       => $this->staffA->id,
            'appointment_id' => $this->appointmentA->id,
            'issued_date'    => now()->toDateString(),
            'valid_until'    => now()->addDays(14)->toDateString(),
            'items'          => [
                [
                    'service_id'  => $this->serviceA->id,
                    'staff_id'    => $this->admin->id, // Mismatch!
                    'description' => 'Item 1',
                    'quantity'    => 1,
                    'unit_price'  => 50,
                ],
            ],
        ]);
    }

    /** 18. Location-scoped staff cannot bypass cross-location practitioner restriction via appointment on edit */
    public function test_draft_edit_cross_location_practitioner_with_same_location_appointment_rejected_with_403(): void
    {
        $quotation = $this->createSampleDraftQuotation();

        // staffA attempts to edit quotation to assign staffB (locationB) with appointmentA (locationA)
        $response = $this->actingAs($this->staffA, 'staff')
            ->put(route('quotations.update', $quotation->id), [
                'client_id'      => $this->clientA->id,
                'staff_id'       => $this->staffB->id, // Location B!
                'appointment_id' => $this->appointmentA->id, // Location A
                'issued_date'    => now()->toDateString(),
                'valid_until'    => now()->addDays(14)->toDateString(),
                'items'          => [
                    [
                        'service_id'  => $this->serviceA->id,
                        'staff_id'    => $this->staffB->id,
                        'description' => 'Item 1',
                        'quantity'    => 1,
                        'unit_price'  => 50,
                    ],
                ],
            ]);

        $response->assertStatus(403);
        $quotation->refresh();
        $this->assertEquals($this->staffA->id, $quotation->staff_id);
    }

    /** 19. Location-scoped staff can assign quotation to peer practitioner at same location on edit */
    public function test_draft_edit_same_location_practitioner_assignment_succeeds_for_location_scoped_staff(): void
    {
        $quotation = $this->createSampleDraftQuotation();

        $peerStaffA = Staff::create([
            'location_id'  => $this->locationA->id,
            'name'         => 'Peer Staff Location A',
            'email'        => 'peer.edit@clinic.test',
            'password'     => Hash::make('secret123'),
            'access_level' => 'staff',
            'is_active'    => true,
        ]);

        $response = $this->actingAs($this->staffA, 'staff')
            ->put(route('quotations.update', $quotation->id), [
                'client_id'      => $this->clientA->id,
                'staff_id'       => $peerStaffA->id,
                'appointment_id' => $this->appointmentA->id,
                'issued_date'    => now()->toDateString(),
                'valid_until'    => now()->addDays(14)->toDateString(),
                'items'          => [
                    [
                        'service_id'  => $this->serviceA->id,
                        'staff_id'    => $peerStaffA->id,
                        'description' => 'Item 1',
                        'quantity'    => 1,
                        'unit_price'  => 50,
                    ],
                ],
            ]);

        $response->assertRedirect(route('quotations.show', $quotation->id));
        $quotation->refresh();
        $this->assertEquals($peerStaffA->id, $quotation->staff_id);
        $this->assertEquals($peerStaffA->id, $quotation->items->first()->staff_id);
    }
}

