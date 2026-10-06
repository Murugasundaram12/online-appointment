<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Location;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Staff;
use App\Models\StaffSchedule;
use App\Services\InvoiceCalculationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InvoiceCreationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Location $location;
    private Staff $admin;
    private Staff $staff;
    private Client $client;
    private ServiceCategory $category;
    private Service $service;
    private Carbon $monday;

    protected function setUp(): void
    {
        parent::setUp();

        $this->location = Location::create([
            'name'      => 'Toronto Clinic',
            'timezone'  => 'America/Toronto',
            'is_active' => true,
        ]);

        $this->category = ServiceCategory::create(['name' => 'Physiotherapy']);

        $this->admin = Staff::create([
            'location_id'  => $this->location->id,
            'name'         => 'Clinic Director',
            'email'        => 'director@example.com',
            'password'     => Hash::make('password123'),
            'access_level' => 'admin',
            'category'     => 'Physiotherapy',
            'is_active'    => true,
        ]);
        $this->admin->categories()->attach($this->category->id);

        $this->staff = Staff::create([
            'location_id'  => $this->location->id,
            'name'         => 'Dr. Jane Practitioner',
            'email'        => 'drjane@example.com',
            'password'     => Hash::make('password123'),
            'access_level' => 'staff',
            'category'     => 'Physiotherapy',
            'is_active'    => true,
        ]);
        $this->staff->categories()->attach($this->category->id);

        $this->client = Client::create([
            'first_name' => 'Alice',
            'last_name'  => 'Wonderland',
            'name'       => 'Alice Wonderland',
            'email'      => 'alice@example.com',
            'phone'      => '4165551234',
        ]);

        $this->service = Service::create([
            'service_category_id' => $this->category->id,
            'name'                => 'Physiotherapy Assessment',
            'type'                => 'in_person',
            'price'               => 125.50,
            'duration_minutes'    => 60,
            'buffer_minutes'      => 0,
            'category'            => 'Physiotherapy',
            'is_active'           => true,
        ]);

        // Provide working schedule for staff across all days
        for ($i = 0; $i < 7; $i++) {
            StaffSchedule::create([
                'staff_id'    => $this->staff->id,
                'day_of_week' => (string) $i,
                'start_time'  => '08:00',
                'end_time'    => '20:00',
                'is_working'  => true,
                'breaks'      => [],
            ]);
            StaffSchedule::create([
                'staff_id'    => $this->admin->id,
                'day_of_week' => (string) $i,
                'start_time'  => '08:00',
                'end_time'    => '20:00',
                'is_working'  => true,
                'breaks'      => [],
            ]);
        }

        $this->monday = Carbon::parse('next monday')->startOfDay();
    }

    /**
     * Test 1 — Auto invoice creates item:
     * Complete an eligible appointment.
     * Assert: invoice exists, invoice_items count = 1, invoice_item.invoice_id = invoice.id
     */
    public function test_auto_invoice_creates_invoice_and_item(): void
    {
        $startTime = $this->monday->copy()->setHour(10)->setMinute(0);
        $endTime = $this->monday->copy()->setHour(11)->setMinute(0);

        $appointment = Appointment::create([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staff->id,
            'service_id'  => $this->service->id,
            'location_id' => $this->location->id,
            'start_time'  => $startTime,
            'end_time'    => $endTime,
            'status'      => 'booked',
        ]);

        $this->assertDatabaseMissing('invoices', ['appointment_id' => $appointment->id]);
        $this->assertEquals(0, InvoiceItem::count());

        // Update appointment status to 'completed' via calendar controller endpoint
        $response = $this->actingAs($this->admin, 'staff')->putJson("/calendar/appointments/{$appointment->id}", [
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staff->id,
            'service_id'  => $this->service->id,
            'location_id' => $this->location->id,
            'start_time'  => $startTime->format('Y-m-d H:i:s'),
            'end_time'    => $endTime->format('Y-m-d H:i:s'),
            'status'      => 'completed',
        ]);

        $response->assertStatus(200);

        $invoice = Invoice::where('appointment_id', $appointment->id)->first();
        $this->assertNotNull($invoice, 'Invoice must be auto-created upon completion');
        $this->assertEquals(1, $invoice->items()->count(), 'Invoice must have exactly 1 invoice_item');

        $item = $invoice->items->first();
        $this->assertEquals($invoice->id, $item->invoice_id);
    }

    /**
     * Test 2 — Item values:
     * Assert: service_id correct, staff_id correct, quantity = 1, unit_price correct, line_total correct
     */
    public function test_auto_invoice_item_values_are_correct(): void
    {
        $startTime = $this->monday->copy()->setHour(14)->setMinute(0);
        $endTime = $this->monday->copy()->setHour(15)->setMinute(0);

        $appointment = Appointment::create([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staff->id,
            'service_id'  => $this->service->id,
            'location_id' => $this->location->id,
            'start_time'  => $startTime,
            'end_time'    => $endTime,
            'status'      => 'booked',
        ]);

        $response = $this->actingAs($this->admin, 'staff')->putJson("/calendar/appointments/{$appointment->id}", [
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staff->id,
            'service_id'  => $this->service->id,
            'location_id' => $this->location->id,
            'start_time'  => $startTime->format('Y-m-d H:i:s'),
            'end_time'    => $endTime->format('Y-m-d H:i:s'),
            'status'      => 'completed',
        ]);
        $response->assertStatus(200);

        $invoice = Invoice::where('appointment_id', $appointment->id)->first();
        $this->assertNotNull($invoice);
        $item = $invoice->items->first();
        $this->assertNotNull($item);

        $this->assertEquals($this->service->id, $item->service_id);
        $this->assertEquals($this->staff->id, $item->staff_id);
        $this->assertEquals('Physiotherapy Assessment', $item->description);
        $this->assertEquals(1.00, (float) $item->quantity);
        $this->assertEquals(125.50, (float) $item->unit_price);
        $this->assertEquals(0.00, (float) $item->discount_amount);
        $this->assertEquals(0.00, (float) $item->tax_rate);
        $this->assertEquals(0.00, (float) $item->tax_amount);
        $this->assertEquals(125.50, (float) $item->line_total);
    }

    /**
     * Test 3 — Invoice totals match InvoiceCalculationService:
     * Assert: invoice.subtotal, invoice.discount_amount, invoice.tax_amount, invoice.total_amount
     */
    public function test_invoice_totals_match_calculation_service(): void
    {
        $startTime = $this->monday->copy()->setHour(11)->setMinute(0);
        $endTime = $this->monday->copy()->setHour(12)->setMinute(0);

        $response = $this->actingAs($this->admin, 'staff')->postJson('/calendar/appointments', [
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staff->id,
            'service_id'  => $this->service->id,
            'location_id' => $this->location->id,
            'start_time'  => $startTime->format('Y-m-d H:i:s'),
            'end_time'    => $endTime->format('Y-m-d H:i:s'),
            'status'      => 'completed',
        ]);

        $response->assertStatus(201);
        $createdApptId = $response->json('appointment.id');

        $invoice = Invoice::where('appointment_id', $createdApptId)->first();
        $this->assertNotNull($invoice);

        $calcService = new InvoiceCalculationService();
        $expectedCalc = $calcService->calculateInvoice([
            [
                'quantity'        => 1.00,
                'unit_price'      => $this->service->price,
                'discount_amount' => 0.00,
                'tax_rate'        => 0.00,
            ]
        ]);

        $this->assertEquals($expectedCalc['subtotal'], (float) $invoice->subtotal);
        $this->assertEquals($expectedCalc['discount_amount'], (float) $invoice->discount_amount);
        $this->assertEquals($expectedCalc['tax_amount'], (float) $invoice->tax_amount);
        $this->assertEquals($expectedCalc['total_amount'], (float) $invoice->total_amount);
        $this->assertEquals('outstanding', $invoice->status);
        $this->assertEquals(0.00, (float) $invoice->paid_amount);
    }

    /**
     * Test 4 — No duplicate auto invoice:
     * Trigger completion twice.
     * Assert: invoice count = 1, invoice item count = 1
     */
    public function test_no_duplicate_auto_invoice_on_repeated_completion(): void
    {
        $startTime = $this->monday->copy()->setHour(9)->setMinute(0);
        $endTime = $this->monday->copy()->setHour(10)->setMinute(0);

        $appointment = Appointment::create([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staff->id,
            'service_id'  => $this->service->id,
            'location_id' => $this->location->id,
            'start_time'  => $startTime,
            'end_time'    => $endTime,
            'status'      => 'completed',
        ]);

        $controller = app(\App\Http\Controllers\CalendarController::class);
        $refMethod = new \ReflectionMethod(\App\Http\Controllers\CalendarController::class, 'autoCreateInvoiceIfCompleted');
        $refMethod->setAccessible(true);

        // First completion trigger
        $refMethod->invoke($controller, $appointment);

        $this->assertEquals(1, Invoice::where('appointment_id', $appointment->id)->count());
        $invoice = Invoice::where('appointment_id', $appointment->id)->first();
        $this->assertEquals(1, $invoice->items()->count());

        // Second completion trigger (must NOT create duplicate invoice or items)
        $refMethod->invoke($controller, $appointment);

        // Verify still exactly 1 invoice and 1 item
        $this->assertEquals(1, Invoice::where('appointment_id', $appointment->id)->count());
        $this->assertEquals(1, $invoice->fresh()->items()->count());
    }

    /**
     * Test 5 — Manual invoice:
     * Use actual current manual invoice request structure.
     * Assert: invoice created, invoice item created, total calculated server-side
     */
    public function test_manual_invoice_creation_builds_item_and_calculates_totals(): void
    {
        $startTime = $this->monday->copy()->setHour(15)->setMinute(0);
        $endTime = $this->monday->copy()->setHour(16)->setMinute(0);

        $appointment = Appointment::create([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staff->id,
            'service_id'  => $this->service->id,
            'location_id' => $this->location->id,
            'start_time'  => $startTime,
            'end_time'    => $endTime,
            'status'      => 'confirmed',
        ]);

        $response = $this->actingAs($this->admin, 'staff')->post(route('invoices.store'), [
            'appointment_id' => $appointment->id,
            'client_id'      => $this->client->id,
            'staff_id'       => $this->staff->id,
            'total_amount'   => 125.50,
            'status'         => 'outstanding',
            'issued_date'    => now()->toDateString(),
        ]);

        $response->assertRedirect();

        $invoice = Invoice::where('appointment_id', $appointment->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(125.50, (float) $invoice->total_amount);
        $this->assertEquals(125.50, (float) $invoice->subtotal);
        $this->assertEquals(0.00, (float) $invoice->discount_amount);
        $this->assertEquals(0.00, (float) $invoice->tax_amount);

        $this->assertEquals(1, $invoice->items()->count());
        $item = $invoice->items->first();
        $this->assertEquals(125.50, (float) $item->unit_price);
        $this->assertEquals(125.50, (float) $item->line_total);
        $this->assertEquals($this->service->id, $item->service_id);
    }

    /**
     * Test 6 — Client cannot override total:
     * If the current manual flow accepts total_amount, send a deliberately incorrect value.
     * Actual server-calculated total = 125.50
     * Client sends total_amount = 9999.00
     * Expected: invoice.total_amount != 9999, invoice.total_amount = 125.50
     */
    public function test_client_cannot_override_invoice_total(): void
    {
        $startTime = $this->monday->copy()->setHour(16)->setMinute(0);
        $endTime = $this->monday->copy()->setHour(17)->setMinute(0);

        $appointment = Appointment::create([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staff->id,
            'service_id'  => $this->service->id,
            'location_id' => $this->location->id,
            'start_time'  => $startTime,
            'end_time'    => $endTime,
            'status'      => 'confirmed',
        ]);

        // Client deliberately sends total_amount = 9999.00
        $response = $this->actingAs($this->admin, 'staff')->post(route('invoices.store'), [
            'appointment_id' => $appointment->id,
            'client_id'      => $this->client->id,
            'staff_id'       => $this->staff->id,
            'total_amount'   => 9999.00,
            'status'         => 'outstanding',
            'issued_date'    => now()->toDateString(),
        ]);

        $response->assertRedirect();

        $invoice = Invoice::where('appointment_id', $appointment->id)->first();
        $this->assertNotNull($invoice);

        // Crucial assertions: client total MUST NOT be used!
        $this->assertNotEquals(9999.00, (float) $invoice->total_amount);
        $this->assertEquals(125.50, (float) $invoice->total_amount);
        $this->assertEquals(125.50, (float) $invoice->items->first()->line_total);
    }

    /**
     * Test 7 — Atomic rollback:
     * Force invoice item creation to fail.
     * Assert: invoice does not remain orphaned, invoice_items = 0
     */
    public function test_atomic_rollback_on_item_creation_failure(): void
    {
        $startTime = $this->monday->copy()->setHour(17)->setMinute(0);
        $endTime = $this->monday->copy()->setHour(18)->setMinute(0);

        $appointment = Appointment::create([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staff->id,
            'service_id'  => $this->service->id,
            'location_id' => $this->location->id,
            'start_time'  => $startTime,
            'end_time'    => $endTime,
            'status'      => 'confirmed',
        ]);

        $initialInvoiceCount = Invoice::count();
        $initialItemCount = InvoiceItem::count();

        // Listen for InvoiceItem creating event and fail it
        InvoiceItem::saving(function () {
            throw new \Exception('Simulated database failure during InvoiceItem creation');
        });

        try {
            $this->actingAs($this->admin, 'staff')->post(route('invoices.store'), [
                'appointment_id' => $appointment->id,
                'client_id'      => $this->client->id,
                'staff_id'       => $this->staff->id,
                'total_amount'   => 125.50,
                'status'         => 'outstanding',
                'issued_date'    => now()->toDateString(),
            ]);
        } catch (\Throwable $e) {
            // Expected simulation exception
        }

        // Verify transaction completely rolled back
        $this->assertEquals($initialInvoiceCount, Invoice::count(), 'No orphaned invoice must remain');
        $this->assertEquals($initialItemCount, InvoiceItem::count(), 'No invoice items should be persisted');
        $this->assertDatabaseMissing('invoices', ['appointment_id' => $appointment->id]);
    }

    /**
     * Test 8 — Payment regression:
     * Manual invoice with initial payment creates payment record and sets correct paid_amount and status.
     */
    public function test_manual_invoice_with_initial_payment_creates_payment_record(): void
    {
        $startTime = $this->monday->copy()->setHour(13)->setMinute(0);
        $endTime = $this->monday->copy()->setHour(14)->setMinute(0);

        $appointment = Appointment::create([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staff->id,
            'service_id'  => $this->service->id,
            'location_id' => $this->location->id,
            'start_time'  => $startTime,
            'end_time'    => $endTime,
            'status'      => 'confirmed',
        ]);

        $response = $this->actingAs($this->admin, 'staff')->post(route('invoices.store'), [
            'appointment_id' => $appointment->id,
            'client_id'      => $this->client->id,
            'staff_id'       => $this->staff->id,
            'total_amount'   => 125.50,
            'paid_amount'    => 50.00,
            'status'         => 'outstanding',
            'issued_date'    => now()->toDateString(),
        ]);

        $response->assertRedirect();

        $invoice = Invoice::where('appointment_id', $appointment->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(125.50, (float) $invoice->total_amount);
        $this->assertEquals(50.00, (float) $invoice->paid_amount);
        $this->assertEquals('partially_paid', $invoice->status);

        $this->assertEquals(1, $invoice->payments()->count());
        $payment = $invoice->payments->first();
        $this->assertEquals(50.00, (float) $payment->amount);
    }

    /**
     * Phase 2B Step 4: Payment Compatibility Cases A, B, C, D
     */
    public function test_payment_compatibility_cases_a_b_c_d(): void
    {
        $testService = Service::create([
            'service_category_id' => $this->category->id,
            'name'                => 'Standard Session',
            'type'                => 'in_person',
            'price'               => 100.00,
            'duration_minutes'    => 60,
            'buffer_minutes'      => 0,
            'category'            => 'Physiotherapy',
            'is_active'           => true,
        ]);

        // Case A: Invoice = 100, Initial payment = 0 -> paid = 0, status = outstanding
        $apptA = Appointment::create([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staff->id,
            'service_id'  => $testService->id,
            'location_id' => $this->location->id,
            'start_time'  => $this->monday->copy()->setHour(9)->setMinute(0),
            'end_time'    => $this->monday->copy()->setHour(10)->setMinute(0),
            'status'      => 'confirmed',
        ]);
        $this->actingAs($this->admin, 'staff')->post(route('invoices.store'), [
            'appointment_id' => $apptA->id,
            'client_id'      => $this->client->id,
            'staff_id'       => $this->staff->id,
            'total_amount'   => 100.00,
            'paid_amount'    => 0.00,
            'issued_date'    => now()->toDateString(),
        ]);
        $invA = Invoice::where('appointment_id', $apptA->id)->first();
        $this->assertEquals(100.00, (float) $invA->total_amount);
        $this->assertEquals(0.00, (float) $invA->paid_amount);
        $this->assertEquals('outstanding', $invA->status);
        $this->assertEquals(0, $invA->payments()->count());

        // Case B: Invoice = 100, Initial payment = 40 -> paid = 40, status = partially_paid
        $apptB = Appointment::create([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staff->id,
            'service_id'  => $testService->id,
            'location_id' => $this->location->id,
            'start_time'  => $this->monday->copy()->setHour(10)->setMinute(0),
            'end_time'    => $this->monday->copy()->setHour(11)->setMinute(0),
            'status'      => 'confirmed',
        ]);
        $this->actingAs($this->admin, 'staff')->post(route('invoices.store'), [
            'appointment_id' => $apptB->id,
            'client_id'      => $this->client->id,
            'staff_id'       => $this->staff->id,
            'total_amount'   => 100.00,
            'paid_amount'    => 40.00,
            'issued_date'    => now()->toDateString(),
        ]);
        $invB = Invoice::where('appointment_id', $apptB->id)->first();
        $this->assertEquals(100.00, (float) $invB->total_amount);
        $this->assertEquals(40.00, (float) $invB->paid_amount);
        $this->assertEquals('partially_paid', $invB->status);
        $this->assertEquals(1, $invB->payments()->count());
        $this->assertEquals(40.00, (float) $invB->payments->first()->amount);

        // Case C: Invoice = 100, Initial payment = 100 -> paid = 100, status = paid
        $apptC = Appointment::create([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staff->id,
            'service_id'  => $testService->id,
            'location_id' => $this->location->id,
            'start_time'  => $this->monday->copy()->setHour(11)->setMinute(0),
            'end_time'    => $this->monday->copy()->setHour(12)->setMinute(0),
            'status'      => 'confirmed',
        ]);
        $this->actingAs($this->admin, 'staff')->post(route('invoices.store'), [
            'appointment_id' => $apptC->id,
            'client_id'      => $this->client->id,
            'staff_id'       => $this->staff->id,
            'total_amount'   => 100.00,
            'paid_amount'    => 100.00,
            'issued_date'    => now()->toDateString(),
        ]);
        $invC = Invoice::where('appointment_id', $apptC->id)->first();
        $this->assertEquals(100.00, (float) $invC->total_amount);
        $this->assertEquals(100.00, (float) $invC->paid_amount);
        $this->assertEquals('paid', $invC->status);
        $this->assertEquals(1, $invC->payments()->count());
        $this->assertEquals(100.00, (float) $invC->payments->first()->amount);

        // Case D: Invoice = 100, Client attempts payment = 999 -> capped at 100, status = paid
        $apptD = Appointment::create([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staff->id,
            'service_id'  => $testService->id,
            'location_id' => $this->location->id,
            'start_time'  => $this->monday->copy()->setHour(12)->setMinute(0),
            'end_time'    => $this->monday->copy()->setHour(13)->setMinute(0),
            'status'      => 'confirmed',
        ]);
        $this->actingAs($this->admin, 'staff')->post(route('invoices.store'), [
            'appointment_id' => $apptD->id,
            'client_id'      => $this->client->id,
            'staff_id'       => $this->staff->id,
            'total_amount'   => 100.00,
            'paid_amount'    => 999.00,
            'issued_date'    => now()->toDateString(),
        ]);
        $invD = Invoice::where('appointment_id', $apptD->id)->first();
        $this->assertEquals(100.00, (float) $invD->total_amount);
        $this->assertEquals(100.00, (float) $invD->paid_amount, 'Paid amount must be capped at total_amount');
        $this->assertEquals('paid', $invD->status);
        $this->assertEquals(1, $invD->payments()->count());
        $this->assertEquals(100.00, (float) $invD->payments->first()->amount);
    }

    /**
     * Phase 2B Step 5: Atomicity check when payment record creation fails.
     */
    public function test_atomicity_initial_payment_failure_rolls_back_everything(): void
    {
        $appointment = Appointment::create([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staff->id,
            'service_id'  => $this->service->id,
            'location_id' => $this->location->id,
            'start_time'  => $this->monday->copy()->setHour(14)->setMinute(0),
            'end_time'    => $this->monday->copy()->setHour(15)->setMinute(0),
            'status'      => 'confirmed',
        ]);

        $initialInvoices = Invoice::count();
        $initialItems = InvoiceItem::count();
        $initialPayments = \App\Models\PaymentRecord::count();

        \App\Models\PaymentRecord::saving(function () {
            throw new \Exception('Simulated database failure during PaymentRecord creation');
        });

        try {
            $this->actingAs($this->admin, 'staff')->post(route('invoices.store'), [
                'appointment_id' => $appointment->id,
                'client_id'      => $this->client->id,
                'staff_id'       => $this->staff->id,
                'total_amount'   => 125.50,
                'paid_amount'    => 50.00,
                'issued_date'    => now()->toDateString(),
            ]);
        } catch (\Throwable $e) {
            // Expected simulation exception
        }

        $this->assertEquals($initialInvoices, Invoice::count(), 'Invoice must not remain if payment fails');
        $this->assertEquals($initialItems, InvoiceItem::count(), 'InvoiceItem must not remain if payment fails');
        $this->assertEquals($initialPayments, \App\Models\PaymentRecord::count(), 'PaymentRecord must not remain');
        $this->assertDatabaseMissing('invoices', ['appointment_id' => $appointment->id]);
    }

    /**
     * Phase 2B Step 7: Comprehensive 4-point financial consistency check
     */
    public function test_invoice_item_header_consistency_all_four_components(): void
    {
        $appointment = Appointment::create([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staff->id,
            'service_id'  => $this->service->id,
            'location_id' => $this->location->id,
            'start_time'  => $this->monday->copy()->setHour(15)->setMinute(0),
            'end_time'    => $this->monday->copy()->setHour(16)->setMinute(0),
            'status'      => 'confirmed',
        ]);

        $this->actingAs($this->admin, 'staff')->post(route('invoices.store'), [
            'appointment_id' => $appointment->id,
            'client_id'      => $this->client->id,
            'staff_id'       => $this->staff->id,
            'total_amount'   => 125.50,
            'issued_date'    => now()->toDateString(),
        ]);

        $invoice = Invoice::with('items')->where('appointment_id', $appointment->id)->first();
        $this->assertNotNull($invoice);

        $lineTotalSum = (float) $invoice->items->sum('line_total');
        $subtotalSum = (float) $invoice->items->sum(fn ($i) => round($i->quantity * $i->unit_price, 2));
        $discountSum = (float) $invoice->items->sum('discount_amount');
        $taxSum = (float) $invoice->items->sum('tax_amount');

        $this->assertEquals((float) $invoice->total_amount, $lineTotalSum);
        $this->assertEquals((float) $invoice->subtotal, $subtotalSum);
        $this->assertEquals((float) $invoice->discount_amount, $discountSum);
        $this->assertEquals((float) $invoice->tax_amount, $taxSum);
    }

    /**
     * Phase 2B Step 8: Service price snapshot preservation
     */
    public function test_service_price_snapshot_preserved_after_service_catalog_change(): void
    {
        $appointment = Appointment::create([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staff->id,
            'service_id'  => $this->service->id,
            'location_id' => $this->location->id,
            'start_time'  => $this->monday->copy()->setHour(16)->setMinute(0),
            'end_time'    => $this->monday->copy()->setHour(17)->setMinute(0),
            'status'      => 'confirmed',
        ]);

        // Invoice created with initial price $125.50
        $this->actingAs($this->admin, 'staff')->post(route('invoices.store'), [
            'appointment_id' => $appointment->id,
            'client_id'      => $this->client->id,
            'staff_id'       => $this->staff->id,
            'total_amount'   => 125.50,
            'issued_date'    => now()->toDateString(),
        ]);

        $invoice = Invoice::where('appointment_id', $appointment->id)->first();
        $item = $invoice->items->first();
        $this->assertEquals(125.50, (float) $item->unit_price);
        $this->assertEquals(125.50, (float) $invoice->total_amount);

        // Service catalog price later changed to $200.00
        $this->service->update(['price' => 200.00]);
        $this->assertEquals(200.00, (float) $this->service->fresh()->price);

        // Historical invoice and invoice item MUST remain unchanged at $125.50
        $freshInvoice = $invoice->fresh();
        $freshItem = $item->fresh();
        $this->assertEquals(125.50, (float) $freshItem->unit_price, 'Historical item unit_price must not change');
        $this->assertEquals(125.50, (float) $freshItem->line_total, 'Historical item line_total must not change');
        $this->assertEquals(125.50, (float) $freshInvoice->total_amount, 'Historical invoice total must not change');
    }

    /**
     * Phase 2B Step 9: Edge Case — Appointment missing service does not create invoice
     */
    public function test_edge_case_appointment_missing_service_does_not_create_invoice(): void
    {
        $appointment = Appointment::create([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staff->id,
            'service_id'  => $this->service->id,
            'location_id' => $this->location->id,
            'start_time'  => $this->monday->copy()->setHour(8)->setMinute(0),
            'end_time'    => $this->monday->copy()->setHour(9)->setMinute(0),
            'status'      => 'completed',
        ]);

        // Point appointment to non-existent service ID and unset relationship to simulate missing service
        $appointment->service_id = 999999;
        $appointment->unsetRelation('service');

        $controller = app(\App\Http\Controllers\CalendarController::class);
        $refMethod = new \ReflectionMethod(\App\Http\Controllers\CalendarController::class, 'autoCreateInvoiceIfCompleted');
        $refMethod->setAccessible(true);
        $refMethod->invoke($controller, $appointment);

        $this->assertDatabaseMissing('invoices', ['appointment_id' => $appointment->id]);
    }

    /**
     * Phase 2B Step 9: Edge Case — Zero-price service creates zero dollar invoice and item
     */
    public function test_edge_case_zero_price_service_creates_zero_dollar_invoice_and_item(): void
    {
        $freeService = Service::create([
            'service_category_id' => $this->category->id,
            'name'                => 'Free Consultation',
            'type'                => 'in_person',
            'price'               => 0.00,
            'duration_minutes'    => 30,
            'buffer_minutes'      => 0,
            'category'            => 'Physiotherapy',
            'is_active'           => true,
        ]);

        $appointment = Appointment::create([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staff->id,
            'service_id'  => $freeService->id,
            'location_id' => $this->location->id,
            'start_time'  => $this->monday->copy()->setHour(17)->setMinute(0),
            'end_time'    => $this->monday->copy()->setHour(17)->setMinute(30),
            'status'      => 'completed',
        ]);

        $controller = app(\App\Http\Controllers\CalendarController::class);
        $refMethod = new \ReflectionMethod(\App\Http\Controllers\CalendarController::class, 'autoCreateInvoiceIfCompleted');
        $refMethod->setAccessible(true);
        $refMethod->invoke($controller, $appointment);

        $invoice = Invoice::where('appointment_id', $appointment->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(0.00, (float) $invoice->total_amount);
        $this->assertEquals(1, $invoice->items()->count());

        $item = $invoice->items->first();
        $this->assertEquals(0.00, (float) $item->unit_price);
        $this->assertEquals(0.00, (float) $item->line_total);
    }

    /**
     * Phase 2B Step 9: Edge Case — Negative price service does not create invoice
     */
    public function test_edge_case_negative_price_service_does_not_create_invoice(): void
    {
        $invalidService = Service::create([
            'service_category_id' => $this->category->id,
            'name'                => 'Negative Price Service',
            'type'                => 'in_person',
            'price'               => -50.00,
            'duration_minutes'    => 30,
            'buffer_minutes'      => 0,
            'category'            => 'Physiotherapy',
            'is_active'           => true,
        ]);

        $appointment = Appointment::create([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staff->id,
            'service_id'  => $invalidService->id,
            'location_id' => $this->location->id,
            'start_time'  => $this->monday->copy()->setHour(18)->setMinute(0),
            'end_time'    => $this->monday->copy()->setHour(18)->setMinute(30),
            'status'      => 'completed',
        ]);

        $controller = app(\App\Http\Controllers\CalendarController::class);
        $refMethod = new \ReflectionMethod(\App\Http\Controllers\CalendarController::class, 'autoCreateInvoiceIfCompleted');
        $refMethod->setAccessible(true);
        $refMethod->invoke($controller, $appointment);

        $this->assertDatabaseMissing('invoices', ['appointment_id' => $appointment->id]);
    }

    /**
     * Phase 2B Step 9: Edge Case — Missing staff does not create invoice
     */
    public function test_edge_case_appointment_missing_staff_does_not_create_invoice(): void
    {
        $appointment = new Appointment([
            'client_id'   => $this->client->id,
            'staff_id'    => null,
            'service_id'  => $this->service->id,
            'location_id' => $this->location->id,
            'start_time'  => $this->monday->copy()->setHour(19)->setMinute(0),
            'end_time'    => $this->monday->copy()->setHour(20)->setMinute(0),
            'status'      => 'completed',
        ]);

        $controller = app(\App\Http\Controllers\CalendarController::class);
        $refMethod = new \ReflectionMethod(\App\Http\Controllers\CalendarController::class, 'autoCreateInvoiceIfCompleted');
        $refMethod->setAccessible(true);
        $refMethod->invoke($controller, $appointment);

        $this->assertEquals(0, Invoice::where('client_id', $this->client->id)->count());
    }

    /**
     * Phase 2C: Multi-item calculation breakdown
     */
    public function test_phase2c_multi_item_calculation_breakdown(): void
    {
        $appointment = Appointment::create([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staff->id,
            'service_id'  => $this->service->id,
            'location_id' => $this->location->id,
            'start_time'  => $this->monday->copy()->setHour(9)->setMinute(0),
            'end_time'    => $this->monday->copy()->setHour(10)->setMinute(0),
            'status'      => 'confirmed',
        ]);

        $response = $this->actingAs($this->admin, 'staff')->post(route('invoices.store'), [
            'appointment_id' => $appointment->id,
            'client_id'      => $this->client->id,
            'staff_id'       => $this->staff->id,
            'issued_date'    => now()->toDateString(),
            'items'          => [
                [
                    'description'     => 'Assessment Session',
                    'quantity'        => 1.00,
                    'unit_price'      => 100.00,
                    'discount_amount' => 0.00,
                    'tax_rate'        => 10.00,
                ],
                [
                    'description'     => 'Specialized Treatment',
                    'quantity'        => 2.00,
                    'unit_price'      => 50.00,
                    'discount_amount' => 10.00,
                    'tax_rate'        => 5.00,
                ],
            ],
        ]);

        $response->assertRedirect();

        $invoice = Invoice::with('items')->where('appointment_id', $appointment->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(2, $invoice->items->count());

        $item1 = $invoice->items->first();
        $this->assertEquals(100.00, round($item1->quantity * $item1->unit_price, 2));
        $this->assertEquals(110.00, (float) $item1->line_total);
        $this->assertEquals(10.00, (float) $item1->tax_amount);

        $item2 = $invoice->items->last();
        // Item 2: qty 2 * 50 = 100 subtotal, discount 10 = 90 taxable, tax 5% of 90 = 4.50, total = 94.50
        $this->assertEquals(94.50, (float) $item2->line_total);
        $this->assertEquals(4.50, (float) $item2->tax_amount);
        $this->assertEquals(10.00, (float) $item2->discount_amount);

        // Header totals: subtotal = 200, discount = 10, tax = 14.50, total = 204.50
        $this->assertEquals(200.00, (float) $invoice->subtotal);
        $this->assertEquals(10.00, (float) $invoice->discount_amount);
        $this->assertEquals(14.50, (float) $invoice->tax_amount);
        $this->assertEquals(204.50, (float) $invoice->total_amount);
    }

    /**
     * Phase 2C: Quantity doubles line subtotal
     */
    public function test_phase2c_quantity_doubles_line_subtotal(): void
    {
        $appointment = Appointment::create([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staff->id,
            'service_id'  => $this->service->id,
            'location_id' => $this->location->id,
            'start_time'  => $this->monday->copy()->setHour(10)->setMinute(0),
            'end_time'    => $this->monday->copy()->setHour(11)->setMinute(0),
            'status'      => 'confirmed',
        ]);

        $this->actingAs($this->admin, 'staff')->post(route('invoices.store'), [
            'appointment_id' => $appointment->id,
            'client_id'      => $this->client->id,
            'staff_id'       => $this->staff->id,
            'issued_date'    => now()->toDateString(),
            'items'          => [
                [
                    'description' => 'Two hour block',
                    'quantity'    => 2.00,
                    'unit_price'  => 75.00,
                ],
            ],
        ]);

        $invoice = Invoice::with('items')->where('appointment_id', $appointment->id)->first();
        $this->assertEquals(150.00, (float) $invoice->subtotal);
        $this->assertEquals(150.00, (float) $invoice->total_amount);
        $this->assertEquals(150.00, (float) $invoice->items->first()->line_total);
    }

    /**
     * Phase 2C: Discount reduces taxable amount and line total
     */
    public function test_phase2c_discount_reduces_taxable_and_line_total(): void
    {
        $appointment = Appointment::create([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staff->id,
            'service_id'  => $this->service->id,
            'location_id' => $this->location->id,
            'start_time'  => $this->monday->copy()->setHour(11)->setMinute(0),
            'end_time'    => $this->monday->copy()->setHour(12)->setMinute(0),
            'status'      => 'confirmed',
        ]);

        $this->actingAs($this->admin, 'staff')->post(route('invoices.store'), [
            'appointment_id' => $appointment->id,
            'client_id'      => $this->client->id,
            'staff_id'       => $this->staff->id,
            'issued_date'    => now()->toDateString(),
            'items'          => [
                [
                    'description'     => 'Discounted session',
                    'quantity'        => 1.00,
                    'unit_price'      => 100.00,
                    'discount_amount' => 10.00,
                    'tax_rate'        => 10.00,
                ],
            ],
        ]);

        $invoice = Invoice::with('items')->where('appointment_id', $appointment->id)->first();
        $this->assertEquals(100.00, (float) $invoice->subtotal);
        $this->assertEquals(10.00, (float) $invoice->discount_amount);
        $this->assertEquals(9.00, (float) $invoice->tax_amount); // 10% of (100 - 10) = 9
        $this->assertEquals(99.00, (float) $invoice->total_amount);
    }

    /**
     * Phase 2C: Tax rate calculates tax amount correctly
     */
    public function test_phase2c_tax_rate_calculates_tax_amount_correctly(): void
    {
        $appointment = Appointment::create([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staff->id,
            'service_id'  => $this->service->id,
            'location_id' => $this->location->id,
            'start_time'  => $this->monday->copy()->setHour(12)->setMinute(0),
            'end_time'    => $this->monday->copy()->setHour(13)->setMinute(0),
            'status'      => 'confirmed',
        ]);

        $this->actingAs($this->admin, 'staff')->post(route('invoices.store'), [
            'appointment_id' => $appointment->id,
            'client_id'      => $this->client->id,
            'staff_id'       => $this->staff->id,
            'issued_date'    => now()->toDateString(),
            'items'          => [
                [
                    'description'     => 'Taxed product',
                    'quantity'        => 1.00,
                    'unit_price'      => 80.00,
                    'discount_amount' => 0.00,
                    'tax_rate'        => 13.00,
                ],
            ],
        ]);

        $invoice = Invoice::with('items')->where('appointment_id', $appointment->id)->first();
        $this->assertEquals(10.40, (float) $invoice->tax_amount);
        $this->assertEquals(90.40, (float) $invoice->total_amount);
    }

    /**
     * Phase 2C: Multiple items (3 or more) with header consistency
     */
    public function test_phase2c_three_or_more_items_persisted_with_header_consistency(): void
    {
        $appointment = Appointment::create([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staff->id,
            'service_id'  => $this->service->id,
            'location_id' => $this->location->id,
            'start_time'  => $this->monday->copy()->setHour(13)->setMinute(0),
            'end_time'    => $this->monday->copy()->setHour(14)->setMinute(0),
            'status'      => 'confirmed',
        ]);

        $this->actingAs($this->admin, 'staff')->post(route('invoices.store'), [
            'appointment_id' => $appointment->id,
            'client_id'      => $this->client->id,
            'staff_id'       => $this->staff->id,
            'issued_date'    => now()->toDateString(),
            'items'          => [
                ['description' => 'Item 1', 'quantity' => 1.00, 'unit_price' => 50.00, 'discount_amount' => 0.00, 'tax_rate' => 0.00],
                ['description' => 'Item 2', 'quantity' => 2.00, 'unit_price' => 30.00, 'discount_amount' => 5.00, 'tax_rate' => 10.00],
                ['description' => 'Item 3', 'quantity' => 1.00, 'unit_price' => 40.00, 'discount_amount' => 10.00, 'tax_rate' => 5.00],
            ],
        ]);

        $invoice = Invoice::with('items')->where('appointment_id', $appointment->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(3, $invoice->items->count());

        $lineTotalsSum = (float) $invoice->items->sum('line_total');
        $subtotalSum = (float) $invoice->items->sum(fn ($i) => round($i->quantity * $i->unit_price, 2));
        $discountSum = (float) $invoice->items->sum('discount_amount');
        $taxSum = (float) $invoice->items->sum('tax_amount');

        $this->assertEquals($lineTotalsSum, (float) $invoice->total_amount);
        $this->assertEquals($subtotalSum, (float) $invoice->subtotal);
        $this->assertEquals($discountSum, (float) $invoice->discount_amount);
        $this->assertEquals($taxSum, (float) $invoice->tax_amount);
    }

    /**
     * Phase 2C: Deterministic item ordering
     */
    public function test_phase2c_item_ordering_deterministic_sort_order(): void
    {
        $appointment = Appointment::create([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staff->id,
            'service_id'  => $this->service->id,
            'location_id' => $this->location->id,
            'start_time'  => $this->monday->copy()->setHour(14)->setMinute(0),
            'end_time'    => $this->monday->copy()->setHour(15)->setMinute(0),
            'status'      => 'confirmed',
        ]);

        $this->actingAs($this->admin, 'staff')->post(route('invoices.store'), [
            'appointment_id' => $appointment->id,
            'client_id'      => $this->client->id,
            'staff_id'       => $this->staff->id,
            'issued_date'    => now()->toDateString(),
            'items'          => [
                ['description' => 'Alpha', 'quantity' => 1.00, 'unit_price' => 10.00],
                ['description' => 'Beta',  'quantity' => 1.00, 'unit_price' => 20.00],
                ['description' => 'Gamma', 'quantity' => 1.00, 'unit_price' => 30.00],
            ],
        ]);

        $invoice = Invoice::with('items')->where('appointment_id', $appointment->id)->first();
        $items = $invoice->items->values();

        $this->assertEquals('Alpha', $items[0]->description);
        $this->assertEquals(1, $items[0]->sort_order);

        $this->assertEquals('Beta', $items[1]->description);
        $this->assertEquals(2, $items[1]->sort_order);

        $this->assertEquals('Gamma', $items[2]->description);
        $this->assertEquals(3, $items[2]->sort_order);
    }

    /**
     * Phase 2C: Custom item with null service_id
     */
    public function test_phase2c_custom_item_with_null_service_id_persists_and_calculates(): void
    {
        $appointment = Appointment::create([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staff->id,
            'service_id'  => $this->service->id,
            'location_id' => $this->location->id,
            'start_time'  => $this->monday->copy()->setHour(15)->setMinute(0),
            'end_time'    => $this->monday->copy()->setHour(16)->setMinute(0),
            'status'      => 'confirmed',
        ]);

        $this->actingAs($this->admin, 'staff')->post(route('invoices.store'), [
            'appointment_id' => $appointment->id,
            'client_id'      => $this->client->id,
            'staff_id'       => $this->staff->id,
            'issued_date'    => now()->toDateString(),
            'items'          => [
                [
                    'service_id'  => null,
                    'description' => 'Custom Orthotics Fitting',
                    'quantity'    => 1.00,
                    'unit_price'  => 175.00,
                ],
            ],
        ]);

        $invoice = Invoice::with('items')->where('appointment_id', $appointment->id)->first();
        $this->assertNotNull($invoice);
        $item = $invoice->items->first();
        $this->assertNull($item->service_id);
        $this->assertEquals('Custom Orthotics Fitting', $item->description);
        $this->assertEquals(175.00, (float) $item->unit_price);
        $this->assertEquals(175.00, (float) $invoice->total_amount);
    }

    /**
     * Phase 2C: Multi-item client tampering overridden by server calculations
     */
    public function test_phase2c_multi_item_client_tampering_server_calculation_wins(): void
    {
        $appointment = Appointment::create([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staff->id,
            'service_id'  => $this->service->id,
            'location_id' => $this->location->id,
            'start_time'  => $this->monday->copy()->setHour(16)->setMinute(0),
            'end_time'    => $this->monday->copy()->setHour(17)->setMinute(0),
            'status'      => 'confirmed',
        ]);

        $this->actingAs($this->admin, 'staff')->post(route('invoices.store'), [
            'appointment_id'  => $appointment->id,
            'client_id'       => $this->client->id,
            'staff_id'        => $this->staff->id,
            'total_amount'    => 9999.99,
            'subtotal'        => 8888.88,
            'discount_amount' => 777.77,
            'tax_amount'      => 666.66,
            'issued_date'     => now()->toDateString(),
            'items'           => [
                ['description' => 'Item A', 'quantity' => 1.00, 'unit_price' => 50.00],
                ['description' => 'Item B', 'quantity' => 1.00, 'unit_price' => 50.00],
            ],
        ]);

        $invoice = Invoice::where('appointment_id', $appointment->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(100.00, (float) $invoice->total_amount);
        $this->assertEquals(100.00, (float) $invoice->subtotal);
        $this->assertEquals(0.00, (float) $invoice->discount_amount);
        $this->assertEquals(0.00, (float) $invoice->tax_amount);
        $this->assertNotEquals(9999.99, (float) $invoice->total_amount);
    }

    /**
     * Phase 2C: Invalid quantity rejected
     */
    public function test_phase2c_invalid_quantity_rejected(): void
    {
        $appointment = Appointment::create([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staff->id,
            'service_id'  => $this->service->id,
            'location_id' => $this->location->id,
            'start_time'  => $this->monday->copy()->setHour(17)->setMinute(0),
            'end_time'    => $this->monday->copy()->setHour(18)->setMinute(0),
            'status'      => 'confirmed',
        ]);

        $response = $this->actingAs($this->admin, 'staff')->post(route('invoices.store'), [
            'appointment_id' => $appointment->id,
            'client_id'      => $this->client->id,
            'staff_id'       => $this->staff->id,
            'issued_date'    => now()->toDateString(),
            'items'          => [
                ['description' => 'Invalid Qty', 'quantity' => 0, 'unit_price' => 50.00],
            ],
        ]);

        $response->assertSessionHasErrors(['items.0.quantity']);
    }

    /**
     * Phase 2C: Negative price rejected
     */
    public function test_phase2c_negative_price_rejected(): void
    {
        $appointment = Appointment::create([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staff->id,
            'service_id'  => $this->service->id,
            'location_id' => $this->location->id,
            'start_time'  => $this->monday->copy()->setHour(8)->setMinute(0),
            'end_time'    => $this->monday->copy()->setHour(9)->setMinute(0),
            'status'      => 'confirmed',
        ]);

        $response = $this->actingAs($this->admin, 'staff')->post(route('invoices.store'), [
            'appointment_id' => $appointment->id,
            'client_id'      => $this->client->id,
            'staff_id'       => $this->staff->id,
            'issued_date'    => now()->toDateString(),
            'items'          => [
                ['description' => 'Negative Price', 'quantity' => 1.00, 'unit_price' => -25.00],
            ],
        ]);

        $response->assertSessionHasErrors(['items.0.unit_price']);
    }

    /**
     * Phase 2C: Discount exceeding line subtotal rejected
     */
    public function test_phase2c_discount_exceeding_subtotal_rejected(): void
    {
        $appointment = Appointment::create([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staff->id,
            'service_id'  => $this->service->id,
            'location_id' => $this->location->id,
            'start_time'  => $this->monday->copy()->setHour(9)->setMinute(0),
            'end_time'    => $this->monday->copy()->setHour(10)->setMinute(0),
            'status'      => 'confirmed',
        ]);

        $response = $this->actingAs($this->admin, 'staff')->post(route('invoices.store'), [
            'appointment_id' => $appointment->id,
            'client_id'      => $this->client->id,
            'staff_id'       => $this->staff->id,
            'issued_date'    => now()->toDateString(),
            'items'          => [
                ['description' => 'Huge Discount', 'quantity' => 1.00, 'unit_price' => 50.00, 'discount_amount' => 70.00],
            ],
        ]);

        $response->assertSessionHasErrors(['items']);
    }

    /**
     * Phase 2C: Invalid tax rate rejected
     */
    public function test_phase2c_invalid_tax_rate_rejected(): void
    {
        $appointment = Appointment::create([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staff->id,
            'service_id'  => $this->service->id,
            'location_id' => $this->location->id,
            'start_time'  => $this->monday->copy()->setHour(10)->setMinute(0),
            'end_time'    => $this->monday->copy()->setHour(11)->setMinute(0),
            'status'      => 'confirmed',
        ]);

        $response = $this->actingAs($this->admin, 'staff')->post(route('invoices.store'), [
            'appointment_id' => $appointment->id,
            'client_id'      => $this->client->id,
            'staff_id'       => $this->staff->id,
            'issued_date'    => now()->toDateString(),
            'items'          => [
                ['description' => 'Extreme Tax', 'quantity' => 1.00, 'unit_price' => 50.00, 'tax_rate' => 150.00],
            ],
        ]);

        $response->assertSessionHasErrors(['items.0.tax_rate']);
    }

    /**
     * Phase 2C: Multi-item atomic rollback when one item creation fails
     */
    public function test_phase2c_multi_item_atomic_rollback_on_item_failure(): void
    {
        $appointment = Appointment::create([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staff->id,
            'service_id'  => $this->service->id,
            'location_id' => $this->location->id,
            'start_time'  => $this->monday->copy()->setHour(11)->setMinute(0),
            'end_time'    => $this->monday->copy()->setHour(12)->setMinute(0),
            'status'      => 'confirmed',
        ]);

        $initialInvoices = Invoice::count();
        $initialItems = InvoiceItem::count();

        $savedItemCount = 0;
        InvoiceItem::saving(function () use (&$savedItemCount) {
            $savedItemCount++;
            if ($savedItemCount >= 2) {
                throw new \Exception('Simulated crash on second item insertion');
            }
        });

        try {
            $this->actingAs($this->admin, 'staff')->post(route('invoices.store'), [
                'appointment_id' => $appointment->id,
                'client_id'      => $this->client->id,
                'staff_id'       => $this->staff->id,
                'issued_date'    => now()->toDateString(),
                'items'          => [
                    ['description' => 'Item 1', 'quantity' => 1.00, 'unit_price' => 30.00],
                    ['description' => 'Item 2', 'quantity' => 1.00, 'unit_price' => 40.00],
                ],
            ]);
        } catch (\Throwable $e) {
            // Expected simulation
        }

        $this->assertEquals($initialInvoices, Invoice::count(), 'No partial invoice persisted');
        $this->assertEquals($initialItems, InvoiceItem::count(), 'No partial invoice items persisted');
    }

    /**
     * Phase 2C: Standalone invoice readiness at service-layer level
     */
    public function test_phase2c_standalone_invoice_readiness_at_service_layer(): void
    {
        $creationService = app(\App\Services\InvoiceCreationService::class);

        $invoice = $creationService->createInvoice([
            'appointment_id' => null,
            'client_id'      => $this->client->id,
            'staff_id'       => $this->staff->id,
            'issued_date'    => now()->toDateString(),
            'due_date'       => now()->addDays(30)->toDateString(),
            'items'          => [
                [
                    'description' => 'Clinic Consultation Fee',
                    'quantity'    => 1.00,
                    'unit_price'  => 85.00,
                ],
                [
                    'description' => 'Custom Brace',
                    'quantity'    => 1.00,
                    'unit_price'  => 120.00,
                ],
            ],
        ]);

        $this->assertNotNull($invoice);
        $this->assertNull($invoice->appointment_id);
        $this->assertEquals(205.00, (float) $invoice->total_amount);
        $this->assertEquals(2, $invoice->items->count());
        $this->assertDatabaseHas('invoices', [
            'id'             => $invoice->id,
            'appointment_id' => null,
            'total_amount'   => 205.00,
        ]);
    }

    /**
     * Phase 2C: Multi-item payment compatibility
     */
    public function test_phase2c_multi_item_payment_compatibility(): void
    {
        $appointment = Appointment::create([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staff->id,
            'service_id'  => $this->service->id,
            'location_id' => $this->location->id,
            'start_time'  => $this->monday->copy()->setHour(12)->setMinute(0),
            'end_time'    => $this->monday->copy()->setHour(13)->setMinute(0),
            'status'      => 'confirmed',
        ]);

        // Create 2-item invoice: 50 + 50 = $100 with initial payment of $40
        $this->actingAs($this->admin, 'staff')->post(route('invoices.store'), [
            'appointment_id' => $appointment->id,
            'client_id'      => $this->client->id,
            'staff_id'       => $this->staff->id,
            'paid_amount'    => 40.00,
            'issued_date'    => now()->toDateString(),
            'items'          => [
                ['description' => 'Item X', 'quantity' => 1.00, 'unit_price' => 50.00],
                ['description' => 'Item Y', 'quantity' => 1.00, 'unit_price' => 50.00],
            ],
        ]);

        $invoice = Invoice::where('appointment_id', $appointment->id)->first();
        $this->assertEquals(100.00, (float) $invoice->total_amount);
        $this->assertEquals(40.00, (float) $invoice->paid_amount);
        $this->assertEquals('partially_paid', $invoice->status);
        $this->assertEquals(1, $invoice->payments()->count());

        // Attempt payment exceeding remaining balance ($70 > $60)
        $excessResponse = $this->actingAs($this->admin, 'staff')->post(route('payment-records.store'), [
            'invoice_id'     => $invoice->id,
            'amount'         => 70.00,
            'payment_method' => 'cash',
            'payment_date'   => now()->toDateString(),
        ]);
        $excessResponse->assertSessionHasErrors(['amount']);

        // Add remaining $60 payment
        $payResponse = $this->actingAs($this->admin, 'staff')->post(route('payment-records.store'), [
            'invoice_id'     => $invoice->id,
            'amount'         => 60.00,
            'payment_method' => 'card',
            'card_brand'     => 'Visa',
            'card_last_four' => '4242',
            'payment_date'   => now()->toDateString(),
        ]);
        $payResponse->assertRedirect();

        $freshInvoice = $invoice->fresh();
        $this->assertEquals(100.00, (float) $freshInvoice->paid_amount);
        $this->assertEquals('paid', $freshInvoice->status);
        $this->assertEquals(2, $freshInvoice->payments()->count());

        // Attempt payment on already fully paid invoice
        $overpayResponse = $this->actingAs($this->admin, 'staff')->post(route('payment-records.store'), [
            'invoice_id'     => $invoice->id,
            'amount'         => 10.00,
            'payment_method' => 'cash',
            'payment_date'   => now()->toDateString(),
        ]);
        $overpayResponse->assertSessionHasErrors(['invoice_id']);
    }
}

