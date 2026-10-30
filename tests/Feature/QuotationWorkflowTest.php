<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\PaymentRecord;
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
use Tests\TestCase;

class QuotationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Location $locationA;
    private Location $locationB;
    private Staff $admin;
    private Staff $staffA;
    private Staff $staffB;
    private Staff $globalStaff;
    private Client $client;
    private ServiceCategory $category;
    private Service $service;
    private Appointment $appointment;
    private QuotationCreationService $creationService;
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
            'password'     => Hash::make('secret123'),
            'access_level' => 'admin',
            'is_active'    => true,
        ]);

        $this->staffA = Staff::create([
            'location_id'  => $this->locationA->id,
            'name'         => 'Dr. Alice Location A',
            'email'        => 'alice@clinic.test',
            'password'     => Hash::make('secret123'),
            'access_level' => 'staff',
            'is_active'    => true,
        ]);

        $this->staffB = Staff::create([
            'location_id'  => $this->locationB->id,
            'name'         => 'Dr. Bob Location B',
            'email'        => 'bob@clinic.test',
            'password'     => Hash::make('secret123'),
            'access_level' => 'staff',
            'is_active'    => true,
        ]);

        $this->globalStaff = Staff::create([
            'location_id'  => null,
            'name'         => 'Emily Global Receptionist',
            'email'        => 'emily@clinic.test',
            'password'     => Hash::make('secret123'),
            'access_level' => 'receptionist',
            'is_active'    => true,
        ]);

        $this->client = Client::create([
            'name'   => 'Jane Client',
            'email'  => 'jane@example.com',
            'phone'  => '555-0199',
            'status' => 'active',
        ]);

        $this->category = ServiceCategory::create([
            'name'        => 'General Wellness',
            'description' => 'Wellness therapies',
        ]);

        $this->service = Service::create([
            'service_category_id' => $this->category->id,
            'name'                => 'Physical Therapy Session',
            'price'               => 120.00,
            'duration_minutes'    => 60,
            'is_active'           => true,
        ]);

        $this->appointment = Appointment::create([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staffA->id,
            'service_id'  => $this->service->id,
            'location_id' => $this->locationA->id,
            'start_time'  => Carbon::tomorrow()->setTime(10, 0),
            'end_time'    => Carbon::tomorrow()->setTime(11, 0),
            'status'      => 'booked',
        ]);

        $this->creationService = app(QuotationCreationService::class);
        $this->conversionService = app(QuotationConversionService::class);
    }

    private function createSampleQuotation(string $status = 'draft', ?Staff $staff = null): Quotation
    {
        $practitioner = $staff ?? $this->staffA;
        $quotation = Quotation::create([
            'quotation_number' => 'QUO-' . strtoupper(uniqid()),
            'client_id'        => $this->client->id,
            'staff_id'         => $practitioner->id,
            'created_by'       => $practitioner->id,
            'appointment_id'   => $this->appointment->id,
            'status'           => $status,
            'issued_date'      => now()->toDateString(),
            'valid_until'      => now()->addDays(30)->toDateString(),
            'subtotal'         => 120.00,
            'discount_amount'  => 0.00,
            'tax_amount'       => 0.00,
            'total_amount'     => 120.00,
        ]);

        QuotationItem::create([
            'quotation_id'    => $quotation->id,
            'service_id'      => $this->service->id,
            'staff_id'        => $practitioner->id,
            'description'     => 'Physical Therapy Session',
            'quantity'        => 1.0,
            'unit_price'      => 120.00,
            'discount_amount' => 0.00,
            'tax_rate'        => 0.00,
            'tax_amount'      => 0.00,
            'line_total'      => 120.00,
            'sort_order'      => 1,
        ]);

        return $quotation;
    }

    /** 1. Draft can be approved -> accepted */
    public function test_draft_can_be_approved_to_accepted(): void
    {
        $quotation = $this->createSampleQuotation('draft');

        $response = $this->actingAs($this->staffA, 'staff')
            ->post(route('quotations.accept', $quotation->id));

        $response->assertRedirect(route('quotations.show', $quotation->id));
        $response->assertSessionHas('success', "Quotation {$quotation->quotation_number} has been approved.");

        $quotation->refresh();
        $this->assertEquals('accepted', $quotation->status);
    }

    /** 2. Sent can be approved -> accepted */
    public function test_sent_can_be_approved_to_accepted(): void
    {
        $quotation = $this->createSampleQuotation('sent');

        $response = $this->actingAs($this->staffA, 'staff')
            ->postJson(route('quotations.accept', $quotation->id));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => "Quotation {$quotation->quotation_number} has been approved.",
        ]);

        $quotation->refresh();
        $this->assertEquals('accepted', $quotation->status);
    }

    /** 3. Repeated approval is rejected */
    public function test_repeated_approval_is_rejected(): void
    {
        $quotation = $this->createSampleQuotation('accepted');

        $response = $this->actingAs($this->staffA, 'staff')
            ->postJson(route('quotations.accept', $quotation->id));

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $this->assertStringContainsString("cannot be approved", $response->json('message'));
    }

    /** 4. Draft, sent, and accepted can be cancelled to rejected */
    public function test_draft_sent_and_accepted_can_be_cancelled_to_rejected(): void
    {
        foreach (['draft', 'sent', 'accepted'] as $initialStatus) {
            $quotation = $this->createSampleQuotation($initialStatus);

            $response = $this->actingAs($this->staffA, 'staff')
                ->post(route('quotations.cancel', $quotation->id));

            $response->assertRedirect(route('quotations.show', $quotation->id));
            $response->assertSessionHas('success', "Quotation {$quotation->quotation_number} has been cancelled.");

            $quotation->refresh();
            $this->assertEquals('rejected', $quotation->status);
        }
    }

    /** 5. Rejected, expired, and converted quotations cannot be approved */
    public function test_rejected_expired_and_converted_quotations_cannot_be_approved(): void
    {
        foreach (['rejected', 'expired', 'converted'] as $invalidStatus) {
            $quotation = $this->createSampleQuotation($invalidStatus);

            $response = $this->actingAs($this->staffA, 'staff')
                ->postJson(route('quotations.accept', $quotation->id));

            $response->assertStatus(422);
            $quotation->refresh();
            $this->assertEquals($invalidStatus, $quotation->status);
        }
    }

    /** 6. Rejected and converted quotations cannot be cancelled again */
    public function test_rejected_and_converted_quotations_cannot_be_cancelled(): void
    {
        foreach (['rejected', 'converted'] as $terminalStatus) {
            $quotation = $this->createSampleQuotation($terminalStatus);

            $response = $this->actingAs($this->staffA, 'staff')
                ->postJson(route('quotations.cancel', $quotation->id));

            $response->assertStatus(422);
            $quotation->refresh();
            $this->assertEquals($terminalStatus, $quotation->status);
        }
    }

    /** 7. Accepted quotation with linked invoice cannot be cancelled */
    public function test_accepted_quotation_with_linked_invoice_cannot_be_cancelled(): void
    {
        $quotation = $this->createSampleQuotation('accepted');

        // Create linked invoice
        Invoice::create([
            'invoice_number'  => 'INV-LINKED-01',
            'client_id'       => $this->client->id,
            'staff_id'        => $this->staffA->id,
            'quotation_id'    => $quotation->id,
            'status'          => 'outstanding',
            'issued_date'     => now()->toDateString(),
            'due_date'        => now()->addDays(14)->toDateString(),
            'subtotal'        => 120.00,
            'total_amount'    => 120.00,
            'balance_due'     => 120.00,
        ]);

        $response = $this->actingAs($this->staffA, 'staff')
            ->postJson(route('quotations.cancel', $quotation->id));

        $response->assertStatus(422);
        $this->assertStringContainsString("already been created", $response->json('message'));

        $quotation->refresh();
        $this->assertEquals('accepted', $quotation->status);
    }

    /** 8. Cancelled/rejected quotations cannot be converted to invoices */
    public function test_rejected_quotations_cannot_be_converted_to_invoices(): void
    {
        $quotation = $this->createSampleQuotation('rejected');

        $response = $this->actingAs($this->staffA, 'staff')
            ->postJson(route('quotations.convert', $quotation->id));

        $response->assertStatus(422);
        $this->assertDatabaseCount('invoices', 0);
    }

    /** 9. Approval does not create invoices, modify appointments, or create payment records */
    public function test_approval_does_not_create_invoice_modify_appointment_or_create_payment(): void
    {
        $quotation = $this->createSampleQuotation('draft');

        $initialApptStatus = $this->appointment->status;
        $initialApptStaff = $this->appointment->staff_id;

        $response = $this->actingAs($this->staffA, 'staff')
            ->post(route('quotations.accept', $quotation->id));

        $response->assertStatus(302);

        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('payment_records', 0);

        $this->appointment->refresh();
        $this->assertEquals($initialApptStatus, $this->appointment->status);
        $this->assertEquals($initialApptStaff, $this->appointment->staff_id);
    }

    /** 10. Cancellation does not modify appointment, invoice, or payment records */
    public function test_cancellation_does_not_modify_appointment_invoice_or_payment(): void
    {
        $quotation = $this->createSampleQuotation('draft');

        $initialApptStatus = $this->appointment->status;
        $initialApptStaff = $this->appointment->staff_id;

        $response = $this->actingAs($this->staffA, 'staff')
            ->post(route('quotations.cancel', $quotation->id));

        $response->assertStatus(302);

        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('payment_records', 0);

        $this->appointment->refresh();
        $this->assertEquals($initialApptStatus, $this->appointment->status);
        $this->assertEquals($initialApptStaff, $this->appointment->staff_id);
    }

    /** 11. Unauthorized cross-location actions are rejected with 403 */
    public function test_unauthorized_cross_location_actions_rejected_with_403(): void
    {
        // staffB belongs strictly to locationB, quotation belongs to staffA at locationA
        $quotation = $this->createSampleQuotation('draft', $this->staffA);

        $responseAccept = $this->actingAs($this->staffB, 'staff')
            ->post(route('quotations.accept', $quotation->id));
        $responseAccept->assertStatus(403);

        $responseCancel = $this->actingAs($this->staffB, 'staff')
            ->post(route('quotations.cancel', $quotation->id));
        $responseCancel->assertStatus(403);
    }

    /** 12. Global access for null-location staff/receptionists and admin is preserved */
    public function test_global_access_preserved_for_null_location_staff_and_admin(): void
    {
        $quotation = $this->createSampleQuotation('draft', $this->staffA);

        // Global receptionist Emily can approve quotation at locationA
        $responseGlobal = $this->actingAs($this->globalStaff, 'staff')
            ->post(route('quotations.accept', $quotation->id));
        $responseGlobal->assertStatus(302);

        $quotation->refresh();
        $this->assertEquals('accepted', $quotation->status);

        // Admin can cancel quotation
        $responseAdmin = $this->actingAs($this->admin, 'staff')
            ->post(route('quotations.cancel', $quotation->id));
        $responseAdmin->assertStatus(302);

        $quotation->refresh();
        $this->assertEquals('rejected', $quotation->status);
    }

    /** 13. Cancelled quotation cannot be edited */
    public function test_cancelled_quotation_cannot_be_edited(): void
    {
        $quotation = $this->createSampleQuotation('rejected');

        $response = $this->actingAs($this->staffA, 'staff')
            ->get(route('quotations.edit', $quotation->id));
        $response->assertStatus(403);

        $responseUpdate = $this->actingAs($this->staffA, 'staff')
            ->put(route('quotations.update', $quotation->id), [
                'client_id'      => $this->client->id,
                'staff_id'       => $this->staffA->id,
                'issued_date'    => now()->toDateString(),
                'valid_until'    => now()->addDays(14)->toDateString(),
                'items'          => [
                    [
                        'service_id'  => $this->service->id,
                        'staff_id'    => $this->staffA->id,
                        'description' => 'Item 1',
                        'quantity'    => 1,
                        'unit_price'  => 50,
                    ],
                ],
            ]);
        $responseUpdate->assertStatus(403);
    }

    /** 14. UI tests verify the bx-pencil Edit icon on index */
    public function test_ui_edit_button_matches_bx_pencil_icon_on_index(): void
    {
        $quotationDraft = $this->createSampleQuotation('draft');
        $quotationAccepted = $this->createSampleQuotation('accepted');

        $response = $this->actingAs($this->admin, 'staff')
            ->get(route('quotations.index'));

        $response->assertStatus(200);

        // Edit link is visible for draft with bx-pencil
        $response->assertSee(route('quotations.edit', $quotationDraft->id));
        $response->assertSee('bx-pencil');
        $response->assertDontSee('bx-edit fs-5');

        // Edit link is NOT visible for accepted quotation
        $response->assertDontSee(route('quotations.edit', $quotationAccepted->id));
    }

    /** 15. UI tests verify Approve and Cancel action buttons on show */
    public function test_ui_show_page_displays_actions_based_on_status(): void
    {
        // 1. Draft quotation shows Edit, Approve, Cancel; hides Convert
        $draft = $this->createSampleQuotation('draft');
        $resDraft = $this->actingAs($this->admin, 'staff')->get(route('quotations.show', $draft->id));
        $resDraft->assertStatus(200);
        $resDraft->assertSee('Edit Quotation');
        $resDraft->assertSee('Approve Quotation');
        $resDraft->assertSee('Cancel Quotation');
        $resDraft->assertDontSee('Convert to Invoice');

        // 2. Accepted quotation hides Edit, hides Approve; shows Cancel, shows Convert
        $accepted = $this->createSampleQuotation('accepted');
        $resAccepted = $this->actingAs($this->admin, 'staff')->get(route('quotations.show', $accepted->id));
        $resAccepted->assertStatus(200);
        $resAccepted->assertDontSee('Edit Quotation');
        $resAccepted->assertDontSee('Approve Quotation');
        $resAccepted->assertSee('Cancel Quotation');
        $resAccepted->assertSee('Convert to Invoice');

        // 3. Rejected quotation hides Edit, Approve, Cancel, Convert
        $rejected = $this->createSampleQuotation('rejected');
        $resRejected = $this->actingAs($this->admin, 'staff')->get(route('quotations.show', $rejected->id));
        $resRejected->assertStatus(200);
        $resRejected->assertDontSee('Edit Quotation');
        $resRejected->assertDontSee('Approve Quotation');
        $resRejected->assertDontSee('Cancel Quotation');
        $resRejected->assertDontSee('Convert to Invoice');
    }

    /** 16. UI test verifies cancel quotation modal replaces browser confirm dialog */
    public function test_ui_show_page_renders_cancel_quotation_modal_without_browser_confirm(): void
    {
        $draft = $this->createSampleQuotation('draft');

        $response = $this->actingAs($this->admin, 'staff')
            ->get(route('quotations.show', $draft->id));

        $response->assertStatus(200);

        // 1. Verify no native browser confirm() call is present on the page
        $response->assertDontSee("confirm('Are you sure you want to cancel", false);
        $response->assertDontSee('onsubmit="return confirm(', false);

        // 2. Verify trigger button targets the custom modal
        $response->assertSee('data-bs-toggle="modal"', false);
        $response->assertSee('data-bs-target="#cancelQuotationModal"', false);
        $response->assertSee('Cancel Quotation', false);

        // 3. Verify modal structure, title, warning icon, and dynamic quotation number
        $response->assertSee('id="cancelQuotationModal"', false);
        $response->assertSee('Cancel Quotation');
        $response->assertSee('bx-error-circle', false);
        $response->assertSee('Are you sure you want to cancel quotation', false);
        $response->assertSee($draft->quotation_number, false);

        // 4. Verify dismissal button (Keep Quotation) closes modal without submitting
        $response->assertSee('Keep Quotation', false);
        $response->assertSee('data-bs-dismiss="modal"', false);

        // 5. Verify confirmation button (Yes, Cancel Quotation) submits cancellation form
        $response->assertSee('Yes, Cancel Quotation', false);
        $response->assertSee('id="confirmCancelQuotationBtn"', false);
        $response->assertSee(route('quotations.cancel', $draft->id), false);
        $response->assertSee('id="cancel-quotation-form"', false);

        // 6. Verify duplicate submission prevention logic is rendered
        $response->assertSee('Cancelling...', false);
    }
}

