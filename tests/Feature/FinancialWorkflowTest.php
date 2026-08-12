<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\PaymentRecord;
use App\Models\Service;
use App\Models\Staff;
use App\Models\StaffSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FinancialWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Location $locationA;
    private Location $locationB;
    private Staff $adminStaff;
    private Staff $staffLocationA;
    private Staff $staffLocationB;
    private Client $client;
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->locationA = Location::create([
            'name' => 'Location Alpha',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        $this->locationB = Location::create([
            'name' => 'Location Beta',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        $this->adminStaff = Staff::create([
            'location_id' => $this->locationA->id,
            'name' => 'Admin Boss',
            'email' => 'adminboss@example.com',
            'password' => Hash::make('password123'),
            'access_level' => 'admin',
            'is_active' => true,
        ]);

        $this->staffLocationA = Staff::create([
            'location_id' => $this->locationA->id,
            'name' => 'Staff Alpha',
            'email' => 'staffalpha@example.com',
            'password' => Hash::make('password123'),
            'access_level' => 'staff',
            'is_active' => true,
        ]);

        $this->staffLocationB = Staff::create([
            'location_id' => $this->locationB->id,
            'name' => 'Staff Beta',
            'email' => 'staffbeta@example.com',
            'password' => Hash::make('password123'),
            'access_level' => 'staff',
            'is_active' => true,
        ]);

        $this->client = Client::create([
            'first_name' => 'Charlie',
            'last_name' => 'Finance',
            'name' => 'Charlie Finance',
            'email' => 'charlie@example.com',
            'phone' => '1234567890',
        ]);

        $this->service = Service::create([
            'name' => 'Financial Consultation',
            'type' => 'in_person',
            'price' => 200,
            'duration_minutes' => 60,
            'buffer_minutes' => 0,
            'is_active' => true,
        ]);
    }

    /** 1. PHPUnit uses isolated SQLite memory database */
    public function test_phpunit_uses_isolated_sqlite_memory_database(): void
    {
        $this->assertEquals('sqlite', DB::getDefaultConnection());
        $this->assertEquals(':memory:', DB::connection()->getDatabaseName());
    }

    /** 2. Authorized staff accesses own/allowed invoice */
    public function test_authorized_staff_can_access_allowed_invoice(): void
    {
        $invoice = Invoice::create([
            'staff_id' => $this->staffLocationA->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-TEST-001',
            'issued_date' => now()->toDateString(),
            'total_amount' => 200.00,
            'paid_amount' => 0,
            'status' => 'outstanding',
        ]);

        $res = $this->actingAs($this->staffLocationA, 'staff')->get("/invoices/{$invoice->id}");
        $res->assertStatus(200);
        $res->assertSee('INV-TEST-001');
    }

    /** 3. Unauthorized staff accessing another location's invoice returns 403 */
    public function test_unauthorized_staff_accessing_other_location_invoice_returns_403(): void
    {
        $invoice = Invoice::create([
            'staff_id' => $this->staffLocationB->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-TEST-002',
            'issued_date' => now()->toDateString(),
            'total_amount' => 200.00,
            'paid_amount' => 0,
            'status' => 'outstanding',
        ]);

        // Staff Location A trying to view Staff Location B's invoice
        $res = $this->actingAs($this->staffLocationA, 'staff')->get("/invoices/{$invoice->id}");
        $res->assertStatus(403);
    }

    /** 4. Unauthorized staff attempts invoice update/delete/download returns 403 */
    public function test_unauthorized_staff_invoice_actions_return_403(): void
    {
        $invoice = Invoice::create([
            'staff_id' => $this->staffLocationB->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-TEST-003',
            'issued_date' => now()->toDateString(),
            'total_amount' => 200.00,
            'paid_amount' => 0,
            'status' => 'outstanding',
        ]);

        $updateRes = $this->actingAs($this->staffLocationA, 'staff')->put("/invoices/{$invoice->id}", [
            'client_id' => $this->client->id,
            'staff_id' => $this->staffLocationB->id,
            'invoice_number' => 'INV-TEST-003',
            'total_amount' => 250.00,
            'status' => 'outstanding',
            'issued_date' => now()->toDateString(),
        ]);
        $updateRes->assertStatus(403);

        $deleteRes = $this->actingAs($this->staffLocationA, 'staff')->delete("/invoices/{$invoice->id}");
        $deleteRes->assertStatus(403);

        $downloadRes = $this->actingAs($this->staffLocationA, 'staff')->get("/invoices/{$invoice->id}/download");
        $downloadRes->assertStatus(403);
    }

    /** 5. Unauthorized staff attempts payment create/delete returns 403 */
    public function test_unauthorized_staff_payment_actions_return_403(): void
    {
        $invoice = Invoice::create([
            'staff_id' => $this->staffLocationB->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-TEST-004',
            'issued_date' => now()->toDateString(),
            'total_amount' => 200.00,
            'paid_amount' => 0,
            'status' => 'outstanding',
        ]);

        $payment = PaymentRecord::create([
            'invoice_id' => $invoice->id,
            'amount' => 50.00,
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
        ]);

        $storeRes = $this->actingAs($this->staffLocationA, 'staff')->post('/payment-records', [
            'invoice_id' => $invoice->id,
            'amount' => 50.00,
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
        ]);
        $storeRes->assertStatus(403);

        $deleteRes = $this->actingAs($this->staffLocationA, 'staff')->delete("/payment-records/{$payment->id}");
        $deleteRes->assertStatus(403);
    }

    /** 6. Admin access is preserved across all locations */
    public function test_admin_access_preserved(): void
    {
        $invoice = Invoice::create([
            'staff_id' => $this->staffLocationB->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-TEST-005',
            'issued_date' => now()->toDateString(),
            'total_amount' => 300.00,
            'paid_amount' => 0,
            'status' => 'outstanding',
        ]);

        $res = $this->actingAs($this->adminStaff, 'staff')->get("/invoices/{$invoice->id}");
        $res->assertStatus(200);
    }

    /** 7. Partial and Full payments accumulate correctly */
    public function test_partial_and_full_payments_accumulate_and_update_status(): void
    {
        $invoice = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-TEST-006',
            'issued_date' => now()->toDateString(),
            'total_amount' => 100.00,
            'paid_amount' => 0,
            'status' => 'outstanding',
        ]);

        // Partial payment 1: $40
        $res1 = $this->actingAs($this->adminStaff, 'staff')->post('/payment-records', [
            'invoice_id' => $invoice->id,
            'amount' => 40.00,
            'payment_method' => 'card',
            'payment_date' => now()->toDateString(),
        ]);
        $res1->assertRedirect(route('payment-records.index'));

        $invoice->refresh();
        $this->assertEquals(40.00, (float) $invoice->paid_amount);
        $this->assertEquals('partially_paid', $invoice->status);

        // Full payment completion: remaining $60
        $res2 = $this->actingAs($this->adminStaff, 'staff')->post('/payment-records', [
            'invoice_id' => $invoice->id,
            'amount' => 60.00,
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
        ]);
        $res2->assertRedirect(route('payment-records.index'));

        $invoice->refresh();
        $this->assertEquals(100.00, (float) $invoice->paid_amount);
        $this->assertEquals('paid', $invoice->status);
    }

    /** 8. Overpayment is rejected with validation error */
    public function test_overpayment_is_rejected(): void
    {
        $invoice = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-TEST-007',
            'issued_date' => now()->toDateString(),
            'total_amount' => 100.00,
            'paid_amount' => 0,
            'status' => 'outstanding',
        ]);

        $res = $this->actingAs($this->adminStaff, 'staff')->post('/payment-records', [
            'invoice_id' => $invoice->id,
            'amount' => 150.00,
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
        ]);
        $res->assertSessionHasErrors('amount');
    }

    /** 9. Payment on void invoice is rejected */
    public function test_payment_on_void_invoice_is_rejected(): void
    {
        $invoice = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-TEST-008',
            'issued_date' => now()->toDateString(),
            'total_amount' => 100.00,
            'paid_amount' => 0,
            'status' => 'void',
        ]);

        $res = $this->actingAs($this->adminStaff, 'staff')->post('/payment-records', [
            'invoice_id' => $invoice->id,
            'amount' => 50.00,
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
        ]);
        $res->assertSessionHasErrors('invoice_id');
    }

    /** 10. Deleting payment recalculates invoice paid_amount and status */
    public function test_deleting_payment_recalculates_invoice(): void
    {
        $invoice = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-TEST-009',
            'issued_date' => now()->toDateString(),
            'total_amount' => 100.00,
            'paid_amount' => 0,
            'status' => 'outstanding',
        ]);

        $pmt1 = PaymentRecord::create(['invoice_id' => $invoice->id, 'amount' => 40.00, 'payment_method' => 'card', 'payment_date' => now()->toDateString()]);
        $pmt2 = PaymentRecord::create(['invoice_id' => $invoice->id, 'amount' => 60.00, 'payment_method' => 'cash', 'payment_date' => now()->toDateString()]);
        $invoice->update(['paid_amount' => 100.00, 'status' => 'paid']);

        // Delete $60 payment
        $res = $this->actingAs($this->adminStaff, 'staff')->delete("/payment-records/{$pmt2->id}");
        $res->assertRedirect(route('payment-records.index'));

        $invoice->refresh();
        $this->assertEquals(40.00, (float) $invoice->paid_amount);
        $this->assertEquals('partially_paid', $invoice->status);
    }

    /** 11. Invoice update uses payment_records as source of truth for paid_amount */
    public function test_invoice_update_derives_paid_amount_from_payment_records(): void
    {
        $invoice = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-TEST-010',
            'issued_date' => now()->toDateString(),
            'total_amount' => 200.00,
            'paid_amount' => 0,
            'status' => 'outstanding',
        ]);

        PaymentRecord::create(['invoice_id' => $invoice->id, 'amount' => 50.00, 'payment_method' => 'cash', 'payment_date' => now()->toDateString()]);
        $invoice->update(['paid_amount' => 50.00, 'status' => 'partially_paid']);

        // Attempt manual update attempting to set paid_amount to 190.00 without payment records
        $res = $this->actingAs($this->adminStaff, 'staff')->put("/invoices/{$invoice->id}", [
            'client_id' => $this->client->id,
            'staff_id' => $this->adminStaff->id,
            'invoice_number' => 'INV-TEST-010',
            'total_amount' => 200.00,
            'paid_amount' => 190.00, // Should be ignored in favor of real payments sum (50.00)
            'status' => 'outstanding',
            'issued_date' => now()->toDateString(),
        ]);
        $res->assertRedirect(route('invoices.index'));

        $invoice->refresh();
        $this->assertEquals(50.00, (float) $invoice->paid_amount);
        $this->assertEquals('partially_paid', $invoice->status);
    }

    /** 12. Invoice total reduction below recorded payments is rejected */
    public function test_invoice_total_reduction_below_recorded_payments_rejected(): void
    {
        $invoice = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-TEST-011',
            'issued_date' => now()->toDateString(),
            'total_amount' => 200.00,
            'paid_amount' => 150.00,
            'status' => 'partially_paid',
        ]);

        PaymentRecord::create(['invoice_id' => $invoice->id, 'amount' => 150.00, 'payment_method' => 'cash', 'payment_date' => now()->toDateString()]);

        // Attempting to lower total_amount to 100.00 (less than 150.00 paid)
        $res = $this->actingAs($this->adminStaff, 'staff')->put("/invoices/{$invoice->id}", [
            'client_id' => $this->client->id,
            'staff_id' => $this->adminStaff->id,
            'invoice_number' => 'INV-TEST-011',
            'total_amount' => 100.00,
            'status' => 'partially_paid',
            'issued_date' => now()->toDateString(),
        ]);
        $res->assertSessionHasErrors('total_amount');
    }

    /** 13. Client profile financial statistics reflect DB relationships */
    public function test_client_financial_summary_reflects_actual_balances(): void
    {
        $inv = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-TEST-012',
            'issued_date' => now()->toDateString(),
            'total_amount' => 500.00,
            'paid_amount' => 200.00,
            'status' => 'partially_paid',
        ]);

        PaymentRecord::create(['invoice_id' => $inv->id, 'amount' => 200.00, 'payment_method' => 'transfer', 'payment_date' => now()->toDateString()]);

        $res = $this->actingAs($this->adminStaff, 'staff')->get("/clients/{$this->client->id}");
        $res->assertStatus(200);
        $res->assertSee('$500.00'); // Total Invoiced
        $res->assertSee('$200.00'); // Total Paid
        $res->assertSee('$300.00'); // Outstanding
    }
}
