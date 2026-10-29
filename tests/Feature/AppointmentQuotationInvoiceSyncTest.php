<?php

namespace Tests\Feature;

use App\Http\Controllers\CalendarController;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Staff;
use App\Services\InvoiceCreationService;
use App\Services\QuotationConversionService;
use App\Services\QuotationCreationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AppointmentQuotationInvoiceSyncTest extends TestCase
{
    use RefreshDatabase;

    private Location $location;
    private Staff $admin;
    private Staff $staff;
    private Client $client;
    private ServiceCategory $category;
    private Service $service;
    private InvoiceCreationService $invoiceCreationService;
    private QuotationCreationService $quotationCreationService;
    private QuotationConversionService $quotationConversionService;

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
            'name'         => 'Clinic Admin',
            'email'        => 'admin@clinic.test',
            'password'     => Hash::make('password123'),
            'access_level' => 'admin',
            'is_active'    => true,
        ]);

        $this->staff = Staff::create([
            'location_id'  => $this->location->id,
            'name'         => 'Dr. Specialist',
            'email'        => 'specialist@clinic.test',
            'password'     => Hash::make('password123'),
            'access_level' => 'staff',
            'is_active'    => true,
        ]);

        $this->client = Client::create([
            'name'   => 'John Patient',
            'email'  => 'john.patient@example.com',
            'phone'  => '555-0100',
            'status' => 'active',
        ]);

        $this->category = ServiceCategory::create([
            'name'        => 'Physiotherapy',
            'description' => 'Rehab services',
        ]);

        $this->service = Service::create([
            'service_category_id' => $this->category->id,
            'name'                => 'Comprehensive Rehab',
            'price'               => 150.00,
            'duration_minutes'    => 60,
            'is_active'           => true,
        ]);

        $this->invoiceCreationService = app(InvoiceCreationService::class);
        $this->quotationCreationService = app(QuotationCreationService::class);
        $this->quotationConversionService = app(QuotationConversionService::class);
    }

    private function createSampleAppointment(string $status = 'booked'): Appointment
    {
        return Appointment::create([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staff->id,
            'service_id'  => $this->service->id,
            'location_id' => $this->location->id,
            'start_time'  => Carbon::tomorrow()->setTime(10, 0),
            'end_time'    => Carbon::tomorrow()->setTime(11, 0),
            'status'      => $status,
        ]);
    }

    private function createSampleQuotation(Appointment $appointment, string $status = 'draft'): Quotation
    {
        $quotation = Quotation::create([
            'quotation_number' => 'QUO-' . strtoupper(uniqid()),
            'client_id'        => $appointment->client_id,
            'staff_id'         => $appointment->staff_id,
            'created_by'       => $appointment->staff_id,
            'appointment_id'   => $appointment->id,
            'status'           => $status,
            'issued_date'      => now()->toDateString(),
            'valid_until'      => now()->addDays(30)->toDateString(),
            'subtotal'         => 200.00,
            'discount_amount'  => 20.00,
            'tax_amount'       => 0.00,
            'total_amount'     => 180.00,
        ]);

        QuotationItem::create([
            'quotation_id'    => $quotation->id,
            'service_id'      => $this->service->id,
            'staff_id'        => $appointment->staff_id,
            'description'     => 'Negotiated Rehab Package',
            'quantity'        => 2.0,
            'unit_price'      => 100.00,
            'discount_amount' => 20.00,
            'tax_rate'        => 0.00,
            'tax_amount'      => 0.00,
            'line_total'      => 180.00,
            'sort_order'      => 1,
        ]);

        return $quotation;
    }

    /** A.1 Completing an eligible appointment with no quotation creates exactly one invoice */
    public function test_standard_appointment_completion_without_quotation_creates_invoice(): void
    {
        $appointment = $this->createSampleAppointment('booked');
        $appointment->update(['status' => 'completed']);

        $invoice = $this->invoiceCreationService->createFromAppointment($appointment);

        $this->assertNotNull($invoice);
        $this->assertEquals(1, Invoice::where('appointment_id', $appointment->id)->count());
        $this->assertEquals(150.00, (float) $invoice->total_amount);
        $this->assertNull($invoice->quotation_id);
    }

    /** A.2 Repeated completion/update calls do not create duplicate invoices */
    public function test_repeated_appointment_completion_does_not_create_duplicate_invoices(): void
    {
        $appointment = $this->createSampleAppointment('completed');

        $invoice1 = $this->invoiceCreationService->createFromAppointment($appointment);
        $this->assertNotNull($invoice1);

        $invoice2 = $this->invoiceCreationService->createFromAppointment($appointment);
        $this->assertNull($invoice2);

        $this->assertEquals(1, Invoice::where('appointment_id', $appointment->id)->count());
    }

    /** A.3 Existing invoice eligibility conditions remain enforced */
    public function test_non_completed_appointment_does_not_create_invoice(): void
    {
        $appointment = $this->createSampleAppointment('booked');

        $invoice = $this->invoiceCreationService->createFromAppointment($appointment);

        $this->assertNull($invoice);
        $this->assertEquals(0, Invoice::where('appointment_id', $appointment->id)->count());
    }

    /** B.4 Linked draft quotation defers standard invoicing */
    public function test_linked_draft_quotation_defers_automatic_invoicing(): void
    {
        $appointment = $this->createSampleAppointment('completed');
        $quotation = $this->createSampleQuotation($appointment, 'draft');

        $invoice = $this->invoiceCreationService->createFromAppointment($appointment);

        $this->assertNull($invoice);
        $this->assertEquals(0, Invoice::where('appointment_id', $appointment->id)->count());
    }

    /** B.5 Linked sent quotation defers standard invoicing */
    public function test_linked_sent_quotation_defers_automatic_invoicing(): void
    {
        $appointment = $this->createSampleAppointment('completed');
        $quotation = $this->createSampleQuotation($appointment, 'sent');

        $invoice = $this->invoiceCreationService->createFromAppointment($appointment);

        $this->assertNull($invoice);
        $this->assertEquals(0, Invoice::where('appointment_id', $appointment->id)->count());
    }

    /** B.6 Linked accepted quotation defers standard invoicing */
    public function test_linked_accepted_quotation_defers_automatic_invoicing(): void
    {
        $appointment = $this->createSampleAppointment('completed');
        $quotation = $this->createSampleQuotation($appointment, 'accepted');

        $invoice = $this->invoiceCreationService->createFromAppointment($appointment);

        $this->assertNull($invoice);
        $this->assertEquals(0, Invoice::where('appointment_id', $appointment->id)->count());
    }

    /** C.7-12 Conversion after appointment completion */
    public function test_conversion_succeeds_after_appointment_completion(): void
    {
        $appointment = $this->createSampleAppointment('completed');
        $quotation = $this->createSampleQuotation($appointment, 'draft');

        // 7. Completing appointment deferred invoicing
        $autoInvoice = $this->invoiceCreationService->createFromAppointment($appointment);
        $this->assertNull($autoInvoice);
        $this->assertEquals(0, Invoice::count());

        // 8. Explicit approval changes quotation to accepted without creating an invoice
        $approvedQuotation = $this->quotationCreationService->acceptQuotation($quotation);
        $this->assertEquals('accepted', $approvedQuotation->status);
        $this->assertEquals(0, Invoice::count());

        // 9. Explicit conversion creates exactly one invoice
        $convertedInvoice = $this->quotationConversionService->convertToInvoice($approvedQuotation);
        $this->assertNotNull($convertedInvoice);
        $this->assertEquals(1, Invoice::count());

        // 10. The resulting invoice has the correct appointment_id and quotation_id
        $this->assertEquals($appointment->id, $convertedInvoice->appointment_id);
        $this->assertEquals($quotation->id, $convertedInvoice->quotation_id);

        // 11. Resulting invoice has quotation-derived items and amounts
        $this->assertEquals(180.00, (float) $convertedInvoice->total_amount);
        $this->assertEquals(20.00, (float) $convertedInvoice->discount_amount);
        $this->assertEquals(1, $convertedInvoice->items()->count());
        $this->assertEquals('Negotiated Rehab Package', $convertedInvoice->items->first()->description);

        // 12. Quotation becomes converted only after successful invoice creation
        $quotation->refresh();
        $this->assertEquals('converted', $quotation->status);
    }

    /** D.13-15 Conversion before appointment completion */
    public function test_conversion_before_appointment_completion_maintains_single_authoritative_invoice(): void
    {
        $appointment = $this->createSampleAppointment('booked');
        $quotation = $this->createSampleQuotation($appointment, 'accepted');

        // 13. Convert accepted quotation first
        $invoice = $this->quotationConversionService->convertToInvoice($quotation);
        $this->assertNotNull($invoice);
        $this->assertEquals(1, Invoice::count());

        // 14. Complete appointment afterward
        $appointment->update(['status' => 'completed']);
        $autoInvoice = $this->invoiceCreationService->createFromAppointment($appointment);

        // 15. Exactly one invoice exists and quotation-derived invoice remains authoritative
        $this->assertNull($autoInvoice);
        $this->assertEquals(1, Invoice::where('appointment_id', $appointment->id)->count());
        $this->assertEquals($quotation->id, Invoice::where('appointment_id', $appointment->id)->first()->quotation_id);
    }

    /** E.16-18 Rejection after appointment completion generates deferred invoice */
    public function test_rejection_after_appointment_completion_generates_standard_invoice(): void
    {
        $appointment = $this->createSampleAppointment('completed');
        $quotation = $this->createSampleQuotation($appointment, 'draft');

        // Invoice was deferred
        $this->assertEquals(0, Invoice::where('appointment_id', $appointment->id)->count());

        // Reject quotation
        $cancelledQuotation = $this->quotationCreationService->cancelQuotation($quotation);

        $this->assertEquals('rejected', $cancelledQuotation->status);

        // Exactly one standard invoice is created for the completed appointment
        $this->assertEquals(1, Invoice::where('appointment_id', $appointment->id)->count());
        $invoice = Invoice::where('appointment_id', $appointment->id)->first();
        $this->assertNull($invoice->quotation_id);
        $this->assertEquals(150.00, (float) $invoice->total_amount); // Standard catalog rate
    }

    /** E.19 Rejection before appointment completion follows standard invoicing on completion */
    public function test_rejection_before_appointment_completion_enables_standard_invoicing_on_subsequent_completion(): void
    {
        $appointment = $this->createSampleAppointment('booked');
        $quotation = $this->createSampleQuotation($appointment, 'draft');

        // Reject quotation while appointment is still booked
        $this->quotationCreationService->cancelQuotation($quotation);
        $this->assertEquals(0, Invoice::count());

        // Appointment completed later
        $appointment->update(['status' => 'completed']);
        $invoice = $this->invoiceCreationService->createFromAppointment($appointment);

        $this->assertNotNull($invoice);
        $this->assertEquals(1, Invoice::where('appointment_id', $appointment->id)->count());
        $this->assertEquals(150.00, (float) $invoice->total_amount);
    }

    /** E.20 Repeated rejection attempts do not create duplicate invoices */
    public function test_repeated_rejection_attempts_do_not_create_duplicate_invoices(): void
    {
        $appointment = $this->createSampleAppointment('completed');
        $quotation = $this->createSampleQuotation($appointment, 'draft');

        $this->quotationCreationService->cancelQuotation($quotation);
        $this->assertEquals(1, Invoice::where('appointment_id', $appointment->id)->count());

        // Second cancellation attempt is rejected because quotation is already in rejected status
        $this->expectException(\InvalidArgumentException::class);
        $this->quotationCreationService->cancelQuotation($quotation);

        $this->assertEquals(1, Invoice::where('appointment_id', $appointment->id)->count());
    }

    /** F.21 Expired quotation does not block standard invoicing on completion */
    public function test_appointment_with_expired_quotation_creates_standard_invoice_on_completion(): void
    {
        $appointment = $this->createSampleAppointment('completed');
        $quotation = $this->createSampleQuotation($appointment, 'expired');

        $invoice = $this->invoiceCreationService->createFromAppointment($appointment);

        $this->assertNotNull($invoice);
        $this->assertEquals(1, Invoice::where('appointment_id', $appointment->id)->count());
        $this->assertNull($invoice->quotation_id);
    }

    /** F.23 Multiple quotations linked to same appointment: deferred while any is active */
    public function test_multiple_quotations_defers_while_any_is_active_and_creates_invoice_only_when_all_rejected(): void
    {
        $appointment = $this->createSampleAppointment('completed');
        $quote1 = $this->createSampleQuotation($appointment, 'draft');
        $quote2 = $this->createSampleQuotation($appointment, 'draft');

        // Both are active -> auto-invoicing deferred
        $this->assertNull($this->invoiceCreationService->createFromAppointment($appointment));
        $this->assertEquals(0, Invoice::count());

        // Cancel quote 1 -> quote 2 is STILL active -> auto-invoicing STILL deferred
        $this->quotationCreationService->cancelQuotation($quote1);
        $this->assertEquals(0, Invoice::count());

        // Cancel quote 2 -> no active quotations remain -> standard appointment invoice created!
        $this->quotationCreationService->cancelQuotation($quote2);
        $this->assertEquals(1, Invoice::where('appointment_id', $appointment->id)->count());
    }

    /** G.24 Two sequential conversions of different quotations linked to same appointment */
    public function test_two_sequential_conversions_of_different_quotations_linked_to_same_appointment_blocks_second_invoice(): void
    {
        $appointment = $this->createSampleAppointment('booked');
        $quote1 = $this->createSampleQuotation($appointment, 'accepted');
        $quote2 = $this->createSampleQuotation($appointment, 'accepted');

        // Worker 1 converts quote 1 successfully
        $invoice1 = $this->quotationConversionService->convertToInvoice($quote1);
        $this->assertNotNull($invoice1);
        $this->assertEquals(1, Invoice::where('appointment_id', $appointment->id)->count());
        $this->assertEquals('converted', $quote1->fresh()->status);

        // Worker 2 attempts to convert quote 2 for the same appointment -> blocked!
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("An invoice already exists for the appointment linked to quotation {$quote2->quotation_number}.");

        try {
            $this->quotationConversionService->convertToInvoice($quote2);
        } finally {
            // Verify exactly one invoice exists and quote 2 was NOT converted
            $this->assertEquals(1, Invoice::where('appointment_id', $appointment->id)->count());
            $this->assertEquals('accepted', $quote2->fresh()->status);
        }
    }

    /** G.25 Standalone quotation without appointment converts cleanly without appointment lock */
    public function test_standalone_quotation_without_appointment_converts_cleanly(): void
    {
        $quotation = $this->quotationCreationService->createQuotation([
            'client_id'      => $this->client->id,
            'staff_id'       => $this->staff->id,
            'appointment_id' => null,
            'items'          => [
                [
                    'service_id'      => $this->service->id,
                    'staff_id'        => $this->staff->id,
                    'description'     => 'Standalone Consultation',
                    'quantity'        => 1.0,
                    'unit_price'      => 120.00,
                    'discount_amount' => 10.00,
                    'tax_rate'        => 0.00,
                    'sort_order'      => 1,
                ]
            ]
        ]);

        $acceptedQuotation = $this->quotationCreationService->acceptQuotation($quotation);
        $this->assertEquals('accepted', $acceptedQuotation->status);

        $invoice = $this->quotationConversionService->convertToInvoice($acceptedQuotation);

        $this->assertNotNull($invoice);
        $this->assertNull($invoice->appointment_id);
        $this->assertEquals($quotation->id, $invoice->quotation_id);
        $this->assertEquals($this->staff->id, $invoice->staff_id);
        $this->assertEquals(110.00, (float) $invoice->total_amount);
        $this->assertEquals(10.00, (float) $invoice->discount_amount);
        $this->assertEquals('converted', $acceptedQuotation->fresh()->status);
    }

    /** H.26 Creating quotation for non-completed appointment locks and succeeds */
    public function test_creating_quotation_for_non_completed_appointment_locks_and_succeeds(): void
    {
        $appointment = $this->createSampleAppointment('booked');

        $quotation = $this->quotationCreationService->createQuotation([
            'client_id'      => $this->client->id,
            'staff_id'       => $this->staff->id,
            'appointment_id' => $appointment->id,
            'items'          => [
                [
                    'service_id'      => $this->service->id,
                    'staff_id'        => $this->staff->id,
                    'description'     => 'Booked Consultation Quote',
                    'quantity'        => 1.0,
                    'unit_price'      => 150.00,
                    'discount_amount' => 0.00,
                    'tax_rate'        => 0.00,
                    'sort_order'      => 1,
                ]
            ]
        ]);

        $this->assertNotNull($quotation);
        $this->assertEquals($appointment->id, $quotation->appointment_id);
        $this->assertEquals('draft', $quotation->status);
        $this->assertEquals('booked', $appointment->fresh()->status);
        $this->assertEquals(0, Invoice::count());
    }

    /** H.27 Creating quotation for completed appointment defers future auto-invoicing */
    public function test_creating_quotation_for_completed_appointment_defers_subsequent_auto_invoicing(): void
    {
        $appointment = $this->createSampleAppointment('completed');

        // Create quotation for this completed appointment
        $quotation = $this->quotationCreationService->createQuotation([
            'client_id'      => $this->client->id,
            'staff_id'       => $this->staff->id,
            'appointment_id' => $appointment->id,
            'items'          => [
                [
                    'service_id'      => $this->service->id,
                    'staff_id'        => $this->staff->id,
                    'description'     => 'Retroactive Package Deal',
                    'quantity'        => 1.0,
                    'unit_price'      => 200.00,
                    'discount_amount' => 25.00,
                    'tax_rate'        => 0.00,
                    'sort_order'      => 1,
                ]
            ]
        ]);

        $this->assertNotNull($quotation);
        $this->assertEquals('draft', $quotation->status);

        // An auto-invoicing attempt now encounters the active quotation and defers
        $invoice = $this->invoiceCreationService->createFromAppointment($appointment);
        $this->assertNull($invoice);
        $this->assertEquals(0, Invoice::where('appointment_id', $appointment->id)->count());
    }

    /** H.28 Updating draft quotation synchronizes with linked appointment row */
    public function test_updating_draft_quotation_synchronizes_with_linked_appointment(): void
    {
        $appointment = $this->createSampleAppointment('booked');
        $quotation = $this->createSampleQuotation($appointment, 'draft');

        $updated = $this->quotationCreationService->updateDraftQuotation($quotation, [
            'client_id'      => $this->client->id,
            'staff_id'       => $this->staff->id,
            'appointment_id' => $appointment->id,
            'items'          => [
                [
                    'service_id'      => $this->service->id,
                    'staff_id'        => $this->staff->id,
                    'description'     => 'Updated Negotiated Package',
                    'quantity'        => 2.0,
                    'unit_price'      => 90.00,
                    'discount_amount' => 10.00,
                    'tax_rate'        => 0.00,
                    'sort_order'      => 1,
                ]
            ]
        ]);

        $this->assertEquals(170.00, (float) $updated->total_amount);
        $this->assertEquals($appointment->id, $updated->appointment_id);
        $this->assertEquals('draft', $updated->status);
    }

    /** I.29 Re-linking draft from completed Appointment A to Appointment B */
    public function test_relinking_draft_from_completed_appointment_a_to_b(): void
    {
        $appointmentA = $this->createSampleAppointment('completed');
        $appointmentB = $this->createSampleAppointment('booked');
        $quotation = $this->createSampleQuotation($appointmentA, 'draft');

        // Re-link quotation from Appointment A to Appointment B
        $updated = $this->quotationCreationService->updateDraftQuotation($quotation, [
            'client_id'      => $this->client->id,
            'staff_id'       => $this->staff->id,
            'appointment_id' => $appointmentB->id,
            'items'          => [
                [
                    'service_id'      => $this->service->id,
                    'staff_id'        => $this->staff->id,
                    'description'     => 'Re-linked Consultation',
                    'quantity'        => 1.0,
                    'unit_price'      => 150.00,
                    'discount_amount' => 0.00,
                    'tax_rate'        => 0.00,
                    'sort_order'      => 1,
                ]
            ]
        ]);

        $this->assertEquals($appointmentB->id, $updated->appointment_id);

        // Appointment A was completed and now has no active quotations -> deferred auto-invoice generated!
        $this->assertEquals(1, Invoice::where('appointment_id', $appointmentA->id)->count());

        // Appointment B is now governed by the active quotation -> 0 invoices for B
        $this->assertEquals(0, Invoice::where('appointment_id', $appointmentB->id)->count());
    }

    /** I.30 Unlinking draft from completed Appointment A triggers deferred invoice */
    public function test_unlinking_draft_from_completed_appointment_triggers_deferred_invoice(): void
    {
        $appointmentA = $this->createSampleAppointment('completed');
        $quotation = $this->createSampleQuotation($appointmentA, 'draft');

        // Unlink quotation completely (standalone)
        $updated = $this->quotationCreationService->updateDraftQuotation($quotation, [
            'client_id'      => $this->client->id,
            'staff_id'       => $this->staff->id,
            'appointment_id' => null,
            'items'          => [
                [
                    'service_id'      => $this->service->id,
                    'staff_id'        => $this->staff->id,
                    'description'     => 'Unlinked Standalone Quote',
                    'quantity'        => 1.0,
                    'unit_price'      => 150.00,
                    'discount_amount' => 0.00,
                    'tax_rate'        => 0.00,
                    'sort_order'      => 1,
                ]
            ]
        ]);

        $this->assertNull($updated->appointment_id);

        // Appointment A was completed -> unlinking quotation released deferred auto-invoicing
        $this->assertEquals(1, Invoice::where('appointment_id', $appointmentA->id)->count());
    }

    /** I.31 Linking previously unlinked draft to appointment defers future auto-invoicing */
    public function test_linking_previously_unlinked_draft_to_appointment_defers_future_auto_invoicing(): void
    {
        $appointmentA = $this->createSampleAppointment('completed');

        // Standalone quotation
        $quotation = $this->quotationCreationService->createQuotation([
            'client_id'      => $this->client->id,
            'staff_id'       => $this->staff->id,
            'appointment_id' => null,
            'items'          => [
                [
                    'service_id'      => $this->service->id,
                    'staff_id'        => $this->staff->id,
                    'description'     => 'Initial Standalone Quote',
                    'quantity'        => 1.0,
                    'unit_price'      => 150.00,
                    'discount_amount' => 0.00,
                    'tax_rate'        => 0.00,
                    'sort_order'      => 1,
                ]
            ]
        ]);

        // Link quotation to Appointment A
        $updated = $this->quotationCreationService->updateDraftQuotation($quotation, [
            'client_id'      => $this->client->id,
            'staff_id'       => $this->staff->id,
            'appointment_id' => $appointmentA->id,
            'items'          => [
                [
                    'service_id'      => $this->service->id,
                    'staff_id'        => $this->staff->id,
                    'description'     => 'Now Linked to Appointment A',
                    'quantity'        => 1.0,
                    'unit_price'      => 150.00,
                    'discount_amount' => 0.00,
                    'tax_rate'        => 0.00,
                    'sort_order'      => 1,
                ]
            ]
        ]);

        $this->assertEquals($appointmentA->id, $updated->appointment_id);

        // Subsequent attempt to auto-invoice Appointment A defers because active quotation is now attached
        $invoice = $this->invoiceCreationService->createFromAppointment($appointmentA);
        $this->assertNull($invoice);
        $this->assertEquals(0, Invoice::where('appointment_id', $appointmentA->id)->count());
    }

    /** I.32 Unlinking draft from completed appointment with existing invoice does not duplicate */
    public function test_unlinking_draft_from_completed_appointment_with_existing_invoice_does_not_duplicate(): void
    {
        $appointmentA = $this->createSampleAppointment('completed');

        // An invoice already exists for Appointment A
        $existingInvoice = Invoice::create([
            'appointment_id'  => $appointmentA->id,
            'client_id'       => $this->client->id,
            'staff_id'        => $this->staff->id,
            'invoice_number'  => 'INV-EXISTING-001',
            'subtotal'        => 150.00,
            'discount_amount' => 0.00,
            'tax_amount'      => 0.00,
            'total_amount'    => 150.00,
            'paid_amount'     => 0.00,
            'status'          => 'outstanding',
            'issued_date'     => now()->toDateString(),
        ]);

        $quotation = $this->createSampleQuotation($appointmentA, 'draft');

        // Unlink quotation
        $updated = $this->quotationCreationService->updateDraftQuotation($quotation, [
            'client_id'      => $this->client->id,
            'staff_id'       => $this->staff->id,
            'appointment_id' => null,
            'items'          => [
                [
                    'service_id'      => $this->service->id,
                    'staff_id'        => $this->staff->id,
                    'description'     => 'Unlinked Quote',
                    'quantity'        => 1.0,
                    'unit_price'      => 150.00,
                    'discount_amount' => 0.00,
                    'tax_rate'        => 0.00,
                    'sort_order'      => 1,
                ]
            ]
        ]);

        $this->assertNull($updated->appointment_id);
        // Still exactly 1 invoice exists for Appointment A
        $this->assertEquals(1, Invoice::where('appointment_id', $appointmentA->id)->count());
    }

    /** I.33 Unlinking draft from completed appointment with another active quotation remains deferred */
    public function test_unlinking_draft_from_completed_appointment_with_another_active_quotation_remains_deferred(): void
    {
        $appointmentA = $this->createSampleAppointment('completed');
        $quote1 = $this->createSampleQuotation($appointmentA, 'draft');
        $quote2 = $this->createSampleQuotation($appointmentA, 'draft');

        // Unlink quote 1
        $updated = $this->quotationCreationService->updateDraftQuotation($quote1, [
            'client_id'      => $this->client->id,
            'staff_id'       => $this->staff->id,
            'appointment_id' => null,
            'items'          => [
                [
                    'service_id'      => $this->service->id,
                    'staff_id'        => $this->staff->id,
                    'description'     => 'Unlinked Quote 1',
                    'quantity'        => 1.0,
                    'unit_price'      => 150.00,
                    'discount_amount' => 0.00,
                    'tax_rate'        => 0.00,
                    'sort_order'      => 1,
                ]
            ]
        ]);

        $this->assertNull($updated->appointment_id);

        // Quote 2 is still active on Appointment A -> auto-invoicing remains deferred
        $this->assertEquals(0, Invoice::where('appointment_id', $appointmentA->id)->count());
    }

    /** I.34 Rollback behavior if deferred invoice creation fails during unlinking */
    public function test_rollback_behavior_if_deferred_invoice_creation_fails_during_unlinking(): void
    {
        $appointmentA = $this->createSampleAppointment('completed');
        $quotation = $this->createSampleQuotation($appointmentA, 'draft');

        // Create a mock InvoiceCreationService that throws on createFromAppointment
        $failingInvoiceService = $this->createMock(\App\Services\InvoiceCreationService::class);
        $failingInvoiceService->expects($this->once())
            ->method('createFromAppointment')
            ->willThrowException(new \RuntimeException('Deferred invoice generation database failure'));

        $serviceWithFailingInvoice = new \App\Services\QuotationCreationService(
            app(\App\Services\InvoiceCalculationService::class),
            $failingInvoiceService
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Deferred invoice generation database failure');

        try {
            $serviceWithFailingInvoice->updateDraftQuotation($quotation, [
                'client_id'      => $this->client->id,
                'staff_id'       => $this->staff->id,
                'appointment_id' => null,
                'items'          => [
                    [
                        'service_id'      => $this->service->id,
                        'staff_id'        => $this->staff->id,
                        'description'     => 'Attempted Unlink',
                        'quantity'        => 1.0,
                        'unit_price'      => 150.00,
                        'discount_amount' => 0.00,
                        'tax_rate'        => 0.00,
                        'sort_order'      => 1,
                    ]
                ]
            ]);
        } finally {
            // Verify full rollback: quotation remains linked to Appointment A in draft status
            $freshQuote = $quotation->fresh();
            $this->assertEquals($appointmentA->id, $freshQuote->appointment_id);
            $this->assertEquals('draft', $freshQuote->status);
            $this->assertEquals(0, Invoice::where('appointment_id', $appointmentA->id)->count());
        }
    }
}

