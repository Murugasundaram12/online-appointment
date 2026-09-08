<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\PaymentRecord;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PaymentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Location $location;
    private Staff $adminStaff;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->location = Location::create([
            'name' => 'Downtown Clinic',
            'timezone' => 'America/New_York',
            'is_active' => true,
        ]);

        $this->adminStaff = Staff::create([
            'location_id' => $this->location->id,
            'name' => 'Manager Dave',
            'email' => 'dave@example.com',
            'password' => Hash::make('secret123'),
            'access_level' => 'admin',
            'is_active' => true,
        ]);

        $this->client = Client::create([
            'location_id' => $this->location->id,
            'name' => 'Alice Johnson',
            'email' => 'alice@example.com',
            'phone' => '555-1234',
        ]);
    }

    /** 1. Test Cash Payment */
    public function test_cash_payment(): void
    {
        $invoice = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-CASH-001',
            'issued_date' => now()->toDateString(),
            'total_amount' => 100.00,
            'paid_amount' => 0,
            'status' => 'outstanding',
        ]);

        $response = $this->actingAs($this->adminStaff, 'staff')->post('/payment-records', [
            'invoice_id' => $invoice->id,
            'amount' => 100.00,
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('invoices.show', $invoice->id));

        $invoice->refresh();
        $this->assertEquals(100.00, (float) $invoice->paid_amount);
        $this->assertEquals('paid', $invoice->status);

        $payment = PaymentRecord::where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals('cash', $payment->payment_method);
        $this->assertEquals(100.00, (float) $payment->amount);
        $this->assertEquals(100.00, $payment->cash_amount);
        $this->assertEquals(0.00, $payment->card_amount);
        $this->assertEquals(0.00, $payment->e_transfer_amount);
        $this->assertNull($payment->card_brand);
    }

    /** 2. Test Card Payment */
    public function test_card_payment_with_card_details(): void
    {
        $invoice = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-CARD-001',
            'issued_date' => now()->toDateString(),
            'total_amount' => 150.00,
            'paid_amount' => 0,
            'status' => 'outstanding',
        ]);

        $response = $this->actingAs($this->adminStaff, 'staff')->post('/payment-records', [
            'invoice_id' => $invoice->id,
            'amount' => 150.00,
            'payment_method' => 'card',
            'card_brand' => 'Visa',
            'cardholder_name' => 'Alice Johnson',
            'card_last_four' => '4242',
            'transaction_reference' => 'TXN-CARD-1234',
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('invoices.show', $invoice->id));

        $payment = PaymentRecord::where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals('card', $payment->payment_method);
        $this->assertEquals('Visa', $payment->card_brand);
        $this->assertEquals('Alice Johnson', $payment->cardholder_name);
        $this->assertEquals('4242', $payment->card_last_four);
        $this->assertEquals('TXN-CARD-1234', $payment->transaction_reference);
        $this->assertEquals(150.00, $payment->card_amount);
    }

    /** 3. Test E-Transfer Payment */
    public function test_etransfer_payment(): void
    {
        $invoice = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-ETR-001',
            'issued_date' => now()->toDateString(),
            'total_amount' => 80.00,
            'paid_amount' => 0,
            'status' => 'outstanding',
        ]);

        $response = $this->actingAs($this->adminStaff, 'staff')->post('/payment-records', [
            'invoice_id' => $invoice->id,
            'amount' => 80.00,
            'payment_method' => 'e_transfer',
            'e_transfer_reference' => 'ETR-REF-9988',
            'sender_name' => 'Alice J',
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('invoices.show', $invoice->id));

        $payment = PaymentRecord::where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals('e_transfer', $payment->payment_method);
        $this->assertEquals('ETR-REF-9988', $payment->e_transfer_reference);
        $this->assertEquals(80.00, $payment->e_transfer_amount);
        $this->assertNull($payment->card_brand);
    }

    /** 4. Test Both with Cash + Card */
    public function test_both_split_payment_with_cash_and_card(): void
    {
        $invoice = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-SPLIT-001',
            'issued_date' => now()->toDateString(),
            'total_amount' => 200.00,
            'paid_amount' => 0,
            'status' => 'outstanding',
        ]);

        $response = $this->actingAs($this->adminStaff, 'staff')->post('/payment-records', [
            'invoice_id' => $invoice->id,
            'amount' => 200.00,
            'payment_method' => 'both',
            'cash_amount' => 80.00,
            'card_amount' => 120.00,
            'e_transfer_amount' => 0.00,
            'card_brand' => 'Mastercard',
            'cardholder_name' => 'Alice Johnson',
            'card_last_four' => '5555',
            'transaction_reference' => 'TXN-BOTH-CC-001',
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('invoices.show', $invoice->id));

        $invoice->refresh();
        $this->assertEquals(200.00, (float) $invoice->paid_amount);
        $this->assertEquals('paid', $invoice->status);

        $payment = PaymentRecord::where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals('cash_card', $payment->payment_method);
        $this->assertEquals(80.00, $payment->cash_amount);
        $this->assertEquals(120.00, $payment->card_amount);
        $this->assertEquals(0.00, $payment->e_transfer_amount);
        $this->assertEquals('Mastercard', $payment->card_brand);
        $this->assertEquals('5555', $payment->card_last_four);
        $this->assertEquals('Alice Johnson', $payment->cardholder_name);
        $this->assertEquals('TXN-BOTH-CC-001', $payment->transaction_reference);
        $this->assertTrue($payment->is_split_payment);
    }

    /** 5. Test Both with Card + E-Transfer */
    public function test_both_split_payment_with_card_and_etransfer(): void
    {
        $invoice = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-SPLIT-002',
            'issued_date' => now()->toDateString(),
            'total_amount' => 150.00,
            'paid_amount' => 0,
            'status' => 'outstanding',
        ]);

        $response = $this->actingAs($this->adminStaff, 'staff')->post('/payment-records', [
            'invoice_id' => $invoice->id,
            'amount' => 150.00,
            'payment_method' => 'both',
            'cash_amount' => 0.00,
            'card_amount' => 70.00,
            'e_transfer_amount' => 80.00,
            'card_brand' => 'Visa',
            'cardholder_name' => 'Alice Johnson',
            'card_last_four' => '1234',
            'transaction_reference' => 'TXN-CARD-PART',
            'e_transfer_reference' => 'ETR-PART-77',
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('invoices.show', $invoice->id));

        $payment = PaymentRecord::where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals('card_e_transfer', $payment->payment_method);
        $this->assertEquals(0.00, $payment->cash_amount);
        $this->assertEquals(70.00, $payment->card_amount);
        $this->assertEquals(80.00, $payment->e_transfer_amount);
        $this->assertEquals('Visa', $payment->card_brand);
        $this->assertEquals('1234', $payment->card_last_four);
        $this->assertEquals('ETR-PART-77', $payment->e_transfer_reference);
        $this->assertEquals('TXN-CARD-PART', $payment->transaction_reference);
    }

    /** 6. Test Both with Cash + E-Transfer (Card Amount = 0) */
    public function test_both_split_payment_with_cash_and_etransfer_card_amount_zero(): void
    {
        $invoice = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-SPLIT-003',
            'issued_date' => now()->toDateString(),
            'total_amount' => 120.00,
            'paid_amount' => 0,
            'status' => 'outstanding',
        ]);

        $response = $this->actingAs($this->adminStaff, 'staff')->post('/payment-records', [
            'invoice_id' => $invoice->id,
            'amount' => 120.00,
            'payment_method' => 'both',
            'cash_amount' => 60.00,
            'card_amount' => 0.00,
            'e_transfer_amount' => 60.00,
            'e_transfer_reference' => 'ETR-CASH-ETR-01',
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('invoices.show', $invoice->id));

        $payment = PaymentRecord::where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals('cash_e_transfer', $payment->payment_method);
        $this->assertEquals(60.00, $payment->cash_amount);
        $this->assertEquals(0.00, $payment->card_amount);
        $this->assertEquals(60.00, $payment->e_transfer_amount);
        $this->assertNull($payment->card_brand);
        $this->assertNull($payment->card_last_four);
        $this->assertNull($payment->cardholder_name);
    }

    /** 7. Test Card Amount > 0 in Both requires card brand */
    public function test_both_split_payment_requires_card_brand_when_card_amount_greater_than_zero(): void
    {
        $invoice = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-SPLIT-004',
            'issued_date' => now()->toDateString(),
            'total_amount' => 100.00,
            'paid_amount' => 0,
            'status' => 'outstanding',
        ]);

        $response = $this->actingAs($this->adminStaff, 'staff')->post('/payment-records', [
            'invoice_id' => $invoice->id,
            'amount' => 100.00,
            'payment_method' => 'both',
            'cash_amount' => 50.00,
            'card_amount' => 50.00,
            'e_transfer_amount' => 0.00,
            'card_brand' => '', // Missing card brand
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors('card_brand');
    }

    /** 8. Test Card Last 4 must be exactly 4 numeric digits */
    public function test_card_last_four_validation(): void
    {
        $invoice = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-SPLIT-005',
            'issued_date' => now()->toDateString(),
            'total_amount' => 100.00,
            'paid_amount' => 0,
            'status' => 'outstanding',
        ]);

        $response = $this->actingAs($this->adminStaff, 'staff')->post('/payment-records', [
            'invoice_id' => $invoice->id,
            'amount' => 100.00,
            'payment_method' => 'both',
            'cash_amount' => 50.00,
            'card_amount' => 50.00,
            'card_brand' => 'Visa',
            'card_last_four' => '12A4', // Invalid non-digit
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors('card_last_four');
    }

    /** 9. Test Overpayment is rejected */
    public function test_overpayment_rejected(): void
    {
        $invoice = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-OVER-001',
            'issued_date' => now()->toDateString(),
            'total_amount' => 100.00,
            'paid_amount' => 0,
            'status' => 'outstanding',
        ]);

        $response = $this->actingAs($this->adminStaff, 'staff')->post('/payment-records', [
            'invoice_id' => $invoice->id,
            'amount' => 120.00,
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors('amount');
    }

    /** 10. Test Invoice document and Payment Records list render breakdown */
    public function test_invoice_document_and_payment_records_render_split_breakdown(): void
    {
        $invoice = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-VIEW-001',
            'issued_date' => now()->toDateString(),
            'total_amount' => 200.00,
            'paid_amount' => 0,
            'status' => 'outstanding',
        ]);

        $this->actingAs($this->adminStaff, 'staff')->post('/payment-records', [
            'invoice_id' => $invoice->id,
            'amount' => 200.00,
            'payment_method' => 'both',
            'cash_amount' => 80.00,
            'card_amount' => 120.00,
            'card_brand' => 'Visa',
            'card_last_four' => '9988',
            'transaction_reference' => 'TXN-VIEW-REF',
            'payment_date' => now()->toDateString(),
        ]);

        // Check invoice preview page
        $invoiceRes = $this->actingAs($this->adminStaff, 'staff')->get("/invoices/{$invoice->id}");
        $invoiceRes->assertStatus(200);
        $invoiceRes->assertSee('Cash + Card');
        $invoiceRes->assertSee('Visa ****9988');
        $invoiceRes->assertSee('TXN-VIEW-REF');

        // Check payment-records page
        $recordsRes = $this->actingAs($this->adminStaff, 'staff')->get('/payment-records');
        $recordsRes->assertStatus(200);
        $recordsRes->assertSee('Cash + Card');
        $recordsRes->assertSee('Visa ****9988');
        $recordsRes->assertSee('TXN-VIEW-REF');
    }

    /** 11. Test Payment Records Index Summary includes split portions */
    public function test_payment_records_index_summary_includes_split_payments(): void
    {
        $invoice = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-SUMM-001',
            'issued_date' => now()->toDateString(),
            'total_amount' => 300.00,
            'paid_amount' => 0,
            'status' => 'outstanding',
        ]);

        $this->actingAs($this->adminStaff, 'staff')->post('/payment-records', [
            'invoice_id' => $invoice->id,
            'amount' => 300.00,
            'payment_method' => 'both',
            'cash_amount' => 100.00,
            'card_amount' => 200.00,
            'card_brand' => 'Visa',
            'card_last_four' => '1122',
            'payment_date' => now()->toDateString(),
        ]);

        $res = $this->actingAs($this->adminStaff, 'staff')->get('/payment-records');
        $res->assertStatus(200);
        // Summary cards should reflect the split cash ($100) and card ($200)
        $res->assertSee('$300.00'); // Total
        $res->assertSee('$100.00'); // Cash total
        $res->assertSee('$200.00'); // Card total
    }
}
