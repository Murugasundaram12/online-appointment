<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\InsuranceCompany;
use App\Models\InsuranceInformation;
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

    /** 12. Test 1: Split Cash ₹400 + Insurance ₹600 on ₹1000 invoice => Paid */
    public function test_split_payment_cash_and_insurance_fully_pays_invoice(): void
    {
        $company = InsuranceCompany::create(['name' => 'Blue Cross']);

        $invoice = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-SPLIT-INS-001',
            'issued_date' => now()->toDateString(),
            'total_amount' => 1000.00,
            'paid_amount' => 0,
            'status' => 'outstanding',
        ]);

        $response = $this->actingAs($this->adminStaff, 'staff')->post('/payment-records', [
            'invoice_id' => $invoice->id,
            'amount' => 1000.00,
            'payment_method' => 'both',
            'cash_amount' => 400.00,
            'insurance_amount' => 600.00,
            'insurance_company_id' => $company->id,
            'policy_id' => 'POL-998877',
            'member_id_or_contract_number' => 'MEM-112233',
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('invoices.show', $invoice->id));

        $invoice->refresh();
        $this->assertEquals(1000.00, (float) $invoice->paid_amount);
        $this->assertEquals('paid', $invoice->status);

        $payment = PaymentRecord::where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals('cash_insurance', $payment->payment_method);
        $this->assertEquals(1000.00, (float) $payment->amount);
        $this->assertEquals('cash', $payment->primary_method);
        $this->assertEquals('insurance', $payment->secondary_method);
        $this->assertEquals(400.00, (float) $payment->primary_amount);
        $this->assertEquals(600.00, (float) $payment->secondary_amount);
        $this->assertEquals(400.00, $payment->cash_amount);
        $this->assertEquals(600.00, $payment->insurance_amount);
        $this->assertEquals('POL-998877', $payment->policy_id);
    }

    /** 13. Test 2: Split Cash ₹300 + Insurance ₹400 on ₹1000 invoice => Partially Paid, remaining ₹300 */
    public function test_split_payment_cash_and_insurance_partial_payment(): void
    {
        $company = InsuranceCompany::create(['name' => 'Manulife']);

        $invoice = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-SPLIT-INS-002',
            'issued_date' => now()->toDateString(),
            'total_amount' => 1000.00,
            'paid_amount' => 0,
            'status' => 'outstanding',
        ]);

        $response = $this->actingAs($this->adminStaff, 'staff')->post('/payment-records', [
            'invoice_id' => $invoice->id,
            'amount' => 700.00,
            'payment_method' => 'both',
            'cash_amount' => 300.00,
            'insurance_amount' => 400.00,
            'insurance_company_id' => $company->id,
            'policy_id' => 'POL-332211',
            'member_id_or_contract_number' => 'MEM-778899',
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('invoices.show', $invoice->id));

        $invoice->refresh();
        $this->assertEquals(700.00, (float) $invoice->paid_amount);
        $this->assertEquals('partially_paid', $invoice->status);
        $this->assertEquals(300.00, (float) ($invoice->total_amount - $invoice->paid_amount));
    }

    /** 14. Test 3: Card ₹400 + Insurance ₹600 => Paid */
    public function test_split_payment_card_and_insurance(): void
    {
        $company = InsuranceCompany::create(['name' => 'Sun Life']);

        $invoice = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-SPLIT-INS-003',
            'issued_date' => now()->toDateString(),
            'total_amount' => 1000.00,
            'paid_amount' => 0,
            'status' => 'outstanding',
        ]);

        $response = $this->actingAs($this->adminStaff, 'staff')->post('/payment-records', [
            'invoice_id' => $invoice->id,
            'amount' => 1000.00,
            'payment_method' => 'both',
            'card_amount' => 400.00,
            'insurance_amount' => 600.00,
            'card_brand' => 'Mastercard',
            'card_last_four' => '4455',
            'insurance_company_id' => $company->id,
            'policy_id' => 'POL-CARD-INS',
            'member_id_or_contract_number' => 'MEM-CARD-INS',
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('invoices.show', $invoice->id));

        $invoice->refresh();
        $this->assertEquals(1000.00, (float) $invoice->paid_amount);
        $this->assertEquals('paid', $invoice->status);

        $payment = PaymentRecord::where('invoice_id', $invoice->id)->first();
        $this->assertEquals('card_insurance', $payment->payment_method);
        $this->assertEquals(400.00, $payment->card_amount);
        $this->assertEquals(600.00, $payment->insurance_amount);
    }

    /** 15. Test 4: E-Transfer ₹400 + Insurance ₹600 => Paid */
    public function test_split_payment_etransfer_and_insurance(): void
    {
        $company = InsuranceCompany::create(['name' => 'Canada Life']);

        $invoice = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-SPLIT-INS-004',
            'issued_date' => now()->toDateString(),
            'total_amount' => 1000.00,
            'paid_amount' => 0,
            'status' => 'outstanding',
        ]);

        $response = $this->actingAs($this->adminStaff, 'staff')->post('/payment-records', [
            'invoice_id' => $invoice->id,
            'amount' => 1000.00,
            'payment_method' => 'both',
            'e_transfer_amount' => 400.00,
            'insurance_amount' => 600.00,
            'e_transfer_reference' => 'ETR-SPLIT-99',
            'insurance_company_id' => $company->id,
            'policy_id' => 'POL-ET-INS',
            'member_id_or_contract_number' => 'MEM-ET-INS',
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('invoices.show', $invoice->id));

        $invoice->refresh();
        $this->assertEquals(1000.00, (float) $invoice->paid_amount);
        $this->assertEquals('paid', $invoice->status);

        $payment = PaymentRecord::where('invoice_id', $invoice->id)->first();
        $this->assertEquals('e_transfer_insurance', $payment->payment_method);
        $this->assertEquals(400.00, $payment->e_transfer_amount);
        $this->assertEquals(600.00, $payment->insurance_amount);
    }

    /** 16. Test: Dynamic split rejects selecting fewer than two methods */
    public function test_split_payment_rejects_invalid_method_count(): void
    {
        $invoice = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-SPLIT-INVALID',
            'issued_date' => now()->toDateString(),
            'total_amount' => 500.00,
            'paid_amount' => 0,
            'status' => 'outstanding',
        ]);

        // Only 1 method provided
        $response1 = $this->actingAs($this->adminStaff, 'staff')->post('/payment-records', [
            'invoice_id' => $invoice->id,
            'amount' => 500.00,
            'payment_method' => 'both',
            'split_methods' => ['cash'],
            'cash_amount' => 500.00,
            'payment_date' => now()->toDateString(),
        ]);
        $response1->assertSessionHasErrors(['payment_method']);

        // 0 methods provided
        $response0 = $this->actingAs($this->adminStaff, 'staff')->post('/payment-records', [
            'invoice_id' => $invoice->id,
            'amount' => 500.00,
            'payment_method' => 'both',
            'split_methods' => [],
            'payment_date' => now()->toDateString(),
        ]);
        $response0->assertSessionHasErrors(['payment_method']);
    }

    /** 17. Test 6: Split Cash + Insurance requires Insurance Details */
    public function test_split_payment_cash_and_insurance_requires_insurance_fields(): void
    {
        $invoice = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-SPLIT-INS-REQ',
            'issued_date' => now()->toDateString(),
            'total_amount' => 500.00,
            'paid_amount' => 0,
            'status' => 'outstanding',
        ]);

        $response = $this->actingAs($this->adminStaff, 'staff')->post('/payment-records', [
            'invoice_id' => $invoice->id,
            'amount' => 500.00,
            'payment_method' => 'both',
            'cash_amount' => 250.00,
            'insurance_amount' => 250.00,
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors(['insurance_company_id', 'policy_id', 'member_id_or_contract_number']);
    }

    /** 18. Test 7: Split Cash + Card nullifies insurance fields */
    public function test_split_payment_cash_and_card_nullifies_insurance_fields(): void
    {
        $company = InsuranceCompany::create(['name' => 'Stale Insurance']);

        $invoice = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-SPLIT-CLEANUP',
            'issued_date' => now()->toDateString(),
            'total_amount' => 500.00,
            'paid_amount' => 0,
            'status' => 'outstanding',
        ]);

        $response = $this->actingAs($this->adminStaff, 'staff')->post('/payment-records', [
            'invoice_id' => $invoice->id,
            'amount' => 500.00,
            'payment_method' => 'both',
            'cash_amount' => 300.00,
            'card_amount' => 200.00,
            'card_brand' => 'Visa',
            'card_last_four' => '9900',
            // Stale insurance metadata submitted:
            'insurance_company_id' => $company->id,
            'policy_id' => 'STALE-POL',
            'member_id_or_contract_number' => 'STALE-MEM',
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('invoices.show', $invoice->id));

        $payment = PaymentRecord::where('invoice_id', $invoice->id)->first();
        $this->assertEquals('cash_card', $payment->payment_method);
        $this->assertNull($payment->insurance_company_id);
        $this->assertNull($payment->policy_id);
        $this->assertNull($payment->member_id_or_contract_number);
    }

    /** 19. Test 8: Saved insurance binding persists insurance_information_id */
    public function test_split_payment_persists_saved_insurance_information_id(): void
    {
        $company = InsuranceCompany::create(['name' => 'Equitable Life']);
        $savedInfo = InsuranceInformation::create([
            'client_id' => $this->client->id,
            'insurance_company_id' => $company->id,
            'policy_id' => 'SAVED-POL-001',
            'member_id_or_contract_number' => 'SAVED-MEM-001',
        ]);

        $invoice = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-SAVED-INS-001',
            'issued_date' => now()->toDateString(),
            'total_amount' => 800.00,
            'paid_amount' => 0,
            'status' => 'outstanding',
        ]);

        $response = $this->actingAs($this->adminStaff, 'staff')->post('/payment-records', [
            'invoice_id' => $invoice->id,
            'amount' => 800.00,
            'payment_method' => 'both',
            'cash_amount' => 300.00,
            'insurance_amount' => 500.00,
            'insurance_information_id' => $savedInfo->id,
            'insurance_company_id' => $company->id,
            'policy_id' => 'SAVED-POL-001',
            'member_id_or_contract_number' => 'SAVED-MEM-001',
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('invoices.show', $invoice->id));

        $payment = PaymentRecord::where('invoice_id', $invoice->id)->first();
        $this->assertEquals($savedInfo->id, $payment->insurance_information_id);
        $this->assertEquals('cash_insurance', $payment->payment_method);
    }

    /** 20. Test 9 & 10: Fully paid invoice excluded from Add Payment dropdown, partially paid appears */
    public function test_invoice_dropdown_filtering_by_remaining_balance(): void
    {
        // 1. Fully paid invoice (remaining balance = 0)
        $fullyPaid = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-FULLY-PAID-001',
            'issued_date' => now()->toDateString(),
            'total_amount' => 500.00,
            'paid_amount' => 500.00,
            'status' => 'paid',
        ]);
        PaymentRecord::create([
            'invoice_id' => $fullyPaid->id,
            'amount' => 500.00,
            'payment_method' => 'cash',
            'cash_amount' => 500.00,
            'payment_date' => now()->toDateString(),
        ]);

        // 2. Partially paid invoice (total 1000, paid 600 => balance 400)
        $partiallyPaid = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-PARTIAL-PAID-001',
            'issued_date' => now()->toDateString(),
            'total_amount' => 1000.00,
            'paid_amount' => 600.00,
            'status' => 'partially_paid',
        ]);
        PaymentRecord::create([
            'invoice_id' => $partiallyPaid->id,
            'amount' => 600.00,
            'payment_method' => 'cash',
            'cash_amount' => 600.00,
            'payment_date' => now()->toDateString(),
        ]);

        // 3. Outstanding invoice
        $outstanding = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-OUTSTANDING-001',
            'issued_date' => now()->toDateString(),
            'total_amount' => 300.00,
            'paid_amount' => 0.00,
            'status' => 'outstanding',
        ]);

        $res = $this->actingAs($this->adminStaff, 'staff')->get('/payment-records');
        $res->assertStatus(200);

        // Fully paid invoice MUST NOT appear in the dropdown
        $res->assertDontSee('INV-FULLY-PAID-001');

        // Partially paid invoice MUST appear
        $res->assertSee('INV-PARTIAL-PAID-001');

        // Outstanding invoice MUST appear
        $res->assertSee('INV-OUTSTANDING-001');
    }

    /** 21. Test 11: Backend protection rejects manual payment on fully paid invoice */
    public function test_backend_protection_rejects_payment_on_fully_paid_invoice(): void
    {
        $paidInvoice = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-PROTECTED-001',
            'issued_date' => now()->toDateString(),
            'total_amount' => 400.00,
            'paid_amount' => 400.00,
            'status' => 'paid',
        ]);
        PaymentRecord::create([
            'invoice_id' => $paidInvoice->id,
            'amount' => 400.00,
            'payment_method' => 'cash',
            'cash_amount' => 400.00,
            'payment_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->adminStaff, 'staff')->post('/payment-records', [
            'invoice_id' => $paidInvoice->id,
            'amount' => 50.00,
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors(['invoice_id']);
        $this->assertEquals(
            'This invoice is already fully paid.',
            session('errors')->get('invoice_id')[0]
        );

        // Confirm no payment record created
        $this->assertEquals(1, PaymentRecord::where('invoice_id', $paidInvoice->id)->count());
    }

    /** 22. Test 12: Overpayment on split payment or single payment rejected */
    public function test_overpayment_rejected_on_split_and_single_payment(): void
    {
        $invoice = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-OVERPAY-001',
            'issued_date' => now()->toDateString(),
            'total_amount' => 1000.00,
            'paid_amount' => 600.00,
            'status' => 'partially_paid',
        ]);
        PaymentRecord::create([
            'invoice_id' => $invoice->id,
            'amount' => 600.00,
            'payment_method' => 'cash',
            'cash_amount' => 600.00,
            'payment_date' => now()->toDateString(),
        ]);

        // Remaining = 400. Attempt payment of 401:
        $response = $this->actingAs($this->adminStaff, 'staff')->post('/payment-records', [
            'invoice_id' => $invoice->id,
            'amount' => 401.00,
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors(['amount']);
        $this->assertEquals(
            'Paid amount cannot exceed the remaining balance.',
            session('errors')->get('amount')[0]
        );
    }

    /** Scenario 7: Split Payment with Cash + Card + E-Transfer (3 methods) */
    public function test_scenario_7_cash_card_etransfer_split(): void
    {
        $invoice = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-SPLIT-3M-001',
            'issued_date' => now()->toDateString(),
            'total_amount' => 600.00,
            'paid_amount' => 0,
            'status' => 'outstanding',
        ]);

        $response = $this->actingAs($this->adminStaff, 'staff')->post('/payment-records', [
            'invoice_id' => $invoice->id,
            'amount' => 600.00,
            'payment_method' => 'both',
            'split_methods' => ['cash', 'card', 'e_transfer'],
            'cash_amount' => 200.00,
            'card_amount' => 250.00,
            'e_transfer_amount' => 150.00,
            'card_brand' => 'Visa',
            'card_last_four' => '1234',
            'cardholder_name' => 'Alice Johnson',
            'transaction_reference' => 'TXN-3M-01',
            'e_transfer_reference' => 'ETR-3M-01',
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('invoices.show', $invoice->id));

        $invoice->refresh();
        $this->assertEquals(600.00, (float) $invoice->paid_amount);
        $this->assertEquals('paid', $invoice->status);

        $payment = PaymentRecord::where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(200.00, $payment->cash_amount);
        $this->assertEquals(250.00, $payment->card_amount);
        $this->assertEquals(150.00, $payment->e_transfer_amount);
        $this->assertEquals(0.00, $payment->insurance_amount);
        $this->assertStringContainsString('Cash + Card + E-Transfer', $payment->formatted_method_label);
    }

    /** Scenario 8: Split Payment with Cash + Card + Insurance (3 methods) */
    public function test_scenario_8_cash_card_insurance_split(): void
    {
        $company = InsuranceCompany::create(['name' => 'Great-West']);
        $invoice = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-SPLIT-3M-002',
            'issued_date' => now()->toDateString(),
            'total_amount' => 700.00,
            'paid_amount' => 0,
            'status' => 'outstanding',
        ]);

        $response = $this->actingAs($this->adminStaff, 'staff')->post('/payment-records', [
            'invoice_id' => $invoice->id,
            'amount' => 700.00,
            'payment_method' => 'both',
            'split_methods' => ['cash', 'card', 'insurance'],
            'cash_amount' => 100.00,
            'card_amount' => 200.00,
            'insurance_amount' => 400.00,
            'card_brand' => 'Mastercard',
            'card_last_four' => '5678',
            'insurance_company_id' => $company->id,
            'policy_id' => 'POL-3M-02',
            'member_id_or_contract_number' => 'MEM-3M-02',
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('invoices.show', $invoice->id));

        $payment = PaymentRecord::where('invoice_id', $invoice->id)->first();
        $this->assertEquals(100.00, $payment->cash_amount);
        $this->assertEquals(200.00, $payment->card_amount);
        $this->assertEquals(400.00, $payment->insurance_amount);
        $this->assertEquals(0.00, $payment->e_transfer_amount);
        $this->assertStringContainsString('Cash + Card + Insurance', $payment->formatted_method_label);
    }

    /** Scenario 9: Split Payment with Cash + E-Transfer + Insurance (3 methods) */
    public function test_scenario_9_cash_etransfer_insurance_split(): void
    {
        $company = InsuranceCompany::create(['name' => 'Wawanesa']);
        $invoice = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-SPLIT-3M-003',
            'issued_date' => now()->toDateString(),
            'total_amount' => 800.00,
            'paid_amount' => 0,
            'status' => 'outstanding',
        ]);

        $response = $this->actingAs($this->adminStaff, 'staff')->post('/payment-records', [
            'invoice_id' => $invoice->id,
            'amount' => 800.00,
            'payment_method' => 'both',
            'split_methods' => ['cash', 'e_transfer', 'insurance'],
            'cash_amount' => 150.00,
            'e_transfer_amount' => 250.00,
            'insurance_amount' => 400.00,
            'e_transfer_reference' => 'ETR-3M-03',
            'insurance_company_id' => $company->id,
            'policy_id' => 'POL-3M-03',
            'member_id_or_contract_number' => 'MEM-3M-03',
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('invoices.show', $invoice->id));

        $payment = PaymentRecord::where('invoice_id', $invoice->id)->first();
        $this->assertEquals(150.00, $payment->cash_amount);
        $this->assertEquals(250.00, $payment->e_transfer_amount);
        $this->assertEquals(400.00, $payment->insurance_amount);
        $this->assertEquals(0.00, $payment->card_amount);
        $this->assertStringContainsString('Cash + E-Transfer + Insurance', $payment->formatted_method_label);
    }

    /** Scenario 10: Split Payment with Card + E-Transfer + Insurance (3 methods) */
    public function test_scenario_10_card_etransfer_insurance_split(): void
    {
        $company = InsuranceCompany::create(['name' => 'Desjardins']);
        $invoice = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-SPLIT-3M-004',
            'issued_date' => now()->toDateString(),
            'total_amount' => 900.00,
            'paid_amount' => 0,
            'status' => 'outstanding',
        ]);

        $response = $this->actingAs($this->adminStaff, 'staff')->post('/payment-records', [
            'invoice_id' => $invoice->id,
            'amount' => 900.00,
            'payment_method' => 'both',
            'split_methods' => ['card', 'e_transfer', 'insurance'],
            'card_amount' => 300.00,
            'e_transfer_amount' => 200.00,
            'insurance_amount' => 400.00,
            'card_brand' => 'American Express',
            'card_last_four' => '3344',
            'e_transfer_reference' => 'ETR-3M-04',
            'insurance_company_id' => $company->id,
            'policy_id' => 'POL-3M-04',
            'member_id_or_contract_number' => 'MEM-3M-04',
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('invoices.show', $invoice->id));

        $payment = PaymentRecord::where('invoice_id', $invoice->id)->first();
        $this->assertEquals(0.00, $payment->cash_amount);
        $this->assertEquals(300.00, $payment->card_amount);
        $this->assertEquals(200.00, $payment->e_transfer_amount);
        $this->assertEquals(400.00, $payment->insurance_amount);
        $this->assertStringContainsString('Card + E-Transfer + Insurance', $payment->formatted_method_label);
    }

    /** Scenario 11: All 4 methods split fully pays invoice (Invoice = ₹1000, Cash = 200, Card = 300, E-Transfer = 150, Insurance = 350 => Total = 1000, Status = Paid) */
    public function test_scenario_11_cash_card_etransfer_insurance_four_method_fully_paid(): void
    {
        $company = InsuranceCompany::create(['name' => 'Allianz']);
        $invoice = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-FOUR-WAY-001',
            'issued_date' => now()->toDateString(),
            'total_amount' => 1000.00,
            'paid_amount' => 0,
            'status' => 'outstanding',
        ]);

        $response = $this->actingAs($this->adminStaff, 'staff')->post('/payment-records', [
            'invoice_id' => $invoice->id,
            'amount' => 1000.00,
            'payment_method' => 'both',
            'split_methods' => ['cash', 'card', 'e_transfer', 'insurance'],
            'cash_amount' => 200.00,
            'card_amount' => 300.00,
            'e_transfer_amount' => 150.00,
            'insurance_amount' => 350.00,
            'card_brand' => 'Visa',
            'cardholder_name' => 'Alice Johnson',
            'card_last_four' => '9988',
            'transaction_reference' => 'TXN-ALL-01',
            'e_transfer_reference' => 'ETR-ALL-01',
            'insurance_company_id' => $company->id,
            'policy_id' => 'POL-ALL-01',
            'member_id_or_contract_number' => 'MEM-ALL-01',
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('invoices.show', $invoice->id));

        $invoice->refresh();
        $this->assertEquals(1000.00, (float) $invoice->paid_amount);
        $this->assertEquals('paid', $invoice->status);

        $payment = PaymentRecord::where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(200.00, $payment->cash_amount);
        $this->assertEquals(300.00, $payment->card_amount);
        $this->assertEquals(150.00, $payment->e_transfer_amount);
        $this->assertEquals(350.00, $payment->insurance_amount);
        $this->assertEquals(1000.00, (float) $payment->amount);
        $this->assertStringContainsString('Cash + Card + E-Transfer + Insurance', $payment->formatted_method_label);
    }

    /** Scenario 12: 3-method partial payment */
    public function test_scenario_12_three_method_partial_payment(): void
    {
        $company = InsuranceCompany::create(['name' => 'Sun Life']);
        $invoice = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-PARTIAL-3M',
            'issued_date' => now()->toDateString(),
            'total_amount' => 1000.00,
            'paid_amount' => 0,
            'status' => 'outstanding',
        ]);

        $response = $this->actingAs($this->adminStaff, 'staff')->post('/payment-records', [
            'invoice_id' => $invoice->id,
            'amount' => 600.00,
            'payment_method' => 'both',
            'split_methods' => ['cash', 'card', 'insurance'],
            'cash_amount' => 100.00,
            'card_amount' => 200.00,
            'insurance_amount' => 300.00,
            'card_brand' => 'Visa',
            'card_last_four' => '1111',
            'insurance_company_id' => $company->id,
            'policy_id' => 'POL-P3',
            'member_id_or_contract_number' => 'MEM-P3',
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('invoices.show', $invoice->id));

        $invoice->refresh();
        $this->assertEquals(600.00, (float) $invoice->paid_amount);
        $this->assertEquals('partially_paid', $invoice->status);
        $this->assertEquals(400.00, (float) ($invoice->total_amount - $invoice->paid_amount));
    }

    /** Scenario 13: 4-method partial payment */
    public function test_scenario_13_four_method_partial_payment(): void
    {
        $company = InsuranceCompany::create(['name' => 'Sun Life']);
        $invoice = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-PARTIAL-4M',
            'issued_date' => now()->toDateString(),
            'total_amount' => 1000.00,
            'paid_amount' => 0,
            'status' => 'outstanding',
        ]);

        $response = $this->actingAs($this->adminStaff, 'staff')->post('/payment-records', [
            'invoice_id' => $invoice->id,
            'amount' => 700.00,
            'payment_method' => 'both',
            'split_methods' => ['cash', 'card', 'e_transfer', 'insurance'],
            'cash_amount' => 100.00,
            'card_amount' => 200.00,
            'e_transfer_amount' => 150.00,
            'insurance_amount' => 250.00,
            'card_brand' => 'Visa',
            'card_last_four' => '2222',
            'insurance_company_id' => $company->id,
            'policy_id' => 'POL-P4',
            'member_id_or_contract_number' => 'MEM-P4',
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('invoices.show', $invoice->id));

        $invoice->refresh();
        $this->assertEquals(700.00, (float) $invoice->paid_amount);
        $this->assertEquals('partially_paid', $invoice->status);
        $this->assertEquals(300.00, (float) ($invoice->total_amount - $invoice->paid_amount));
    }

    /** Scenario 14: Overpayment with 3 methods rejected */
    public function test_scenario_14_overpayment_with_three_methods(): void
    {
        $company = InsuranceCompany::create(['name' => 'Sun Life']);
        $invoice = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-OVER-3M',
            'issued_date' => now()->toDateString(),
            'total_amount' => 600.00,
            'paid_amount' => 0,
            'status' => 'outstanding',
        ]);

        // Attempt total 650 on balance 600
        $response = $this->actingAs($this->adminStaff, 'staff')->post('/payment-records', [
            'invoice_id' => $invoice->id,
            'amount' => 650.00,
            'payment_method' => 'both',
            'split_methods' => ['cash', 'card', 'insurance'],
            'cash_amount' => 200.00,
            'card_amount' => 200.00,
            'insurance_amount' => 250.00,
            'card_brand' => 'Visa',
            'card_last_four' => '3333',
            'insurance_company_id' => $company->id,
            'policy_id' => 'POL-O3',
            'member_id_or_contract_number' => 'MEM-O3',
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors(['amount']);
        $this->assertEquals('Paid amount cannot exceed the remaining balance.', session('errors')->get('amount')[0]);
    }

    /** Scenario 15: Overpayment with 4 methods rejected */
    public function test_scenario_15_overpayment_with_four_methods(): void
    {
        $company = InsuranceCompany::create(['name' => 'Sun Life']);
        $invoice = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-OVER-4M',
            'issued_date' => now()->toDateString(),
            'total_amount' => 1000.00,
            'paid_amount' => 400.00,
            'status' => 'partially_paid',
        ]);
        PaymentRecord::create([
            'invoice_id' => $invoice->id,
            'amount' => 400.00,
            'payment_method' => 'cash',
            'cash_amount' => 400.00,
            'payment_date' => now()->toDateString(),
        ]);

        // Balance is 600. Attempt 650 with 4 methods:
        $response = $this->actingAs($this->adminStaff, 'staff')->post('/payment-records', [
            'invoice_id' => $invoice->id,
            'amount' => 650.00,
            'payment_method' => 'both',
            'split_methods' => ['cash', 'card', 'e_transfer', 'insurance'],
            'cash_amount' => 200.00,
            'card_amount' => 200.00,
            'e_transfer_amount' => 100.00,
            'insurance_amount' => 150.00,
            'card_brand' => 'Visa',
            'card_last_four' => '4444',
            'insurance_company_id' => $company->id,
            'policy_id' => 'POL-O4',
            'member_id_or_contract_number' => 'MEM-O4',
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors(['amount']);
        $this->assertEquals('Paid amount cannot exceed the remaining balance.', session('errors')->get('amount')[0]);
    }

    /** Scenario 16: Unselected method fields are cleared on backend */
    public function test_scenario_16_unselected_method_fields_are_cleared(): void
    {
        $company = InsuranceCompany::create(['name' => 'Stale Company']);
        $invoice = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-CLEAR-UNSEL',
            'issued_date' => now()->toDateString(),
            'total_amount' => 500.00,
            'paid_amount' => 0,
            'status' => 'outstanding',
        ]);

        // User selected ONLY cash and e_transfer, but stale card & insurance fields were submitted:
        $response = $this->actingAs($this->adminStaff, 'staff')->post('/payment-records', [
            'invoice_id' => $invoice->id,
            'amount' => 500.00,
            'payment_method' => 'both',
            'split_methods' => ['cash', 'e_transfer'],
            'cash_amount' => 250.00,
            'e_transfer_amount' => 250.00,
            'card_amount' => 99.00, // Stale!
            'insurance_amount' => 88.00, // Stale!
            'card_brand' => 'Visa',
            'card_last_four' => '9999',
            'insurance_company_id' => $company->id,
            'policy_id' => 'STALE-POL',
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('invoices.show', $invoice->id));

        $payment = PaymentRecord::where('invoice_id', $invoice->id)->first();
        $this->assertEquals(250.00, $payment->cash_amount);
        $this->assertEquals(250.00, $payment->e_transfer_amount);
        $this->assertEquals(0.00, $payment->card_amount);
        $this->assertEquals(0.00, $payment->insurance_amount);
        $this->assertNull($payment->card_brand);
        $this->assertNull($payment->card_last_four);
        $this->assertNull($payment->insurance_company_id);
        $this->assertNull($payment->policy_id);
    }

    /** Scenario 17: Insurance auto-fill in 3-method split persists insurance_information_id */
    public function test_scenario_17_insurance_autofill_in_three_method_split(): void
    {
        $company = InsuranceCompany::create(['name' => 'Blue Cross']);
        $savedInfo = InsuranceInformation::create([
            'client_id' => $this->client->id,
            'insurance_company_id' => $company->id,
            'policy_id' => 'POL-AUTO-3M',
            'member_id_or_contract_number' => 'MEM-AUTO-3M',
        ]);

        $invoice = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-AUTO-3M',
            'issued_date' => now()->toDateString(),
            'total_amount' => 800.00,
            'paid_amount' => 0,
            'status' => 'outstanding',
        ]);

        $response = $this->actingAs($this->adminStaff, 'staff')->post('/payment-records', [
            'invoice_id' => $invoice->id,
            'amount' => 800.00,
            'payment_method' => 'both',
            'split_methods' => ['cash', 'card', 'insurance'],
            'cash_amount' => 200.00,
            'card_amount' => 300.00,
            'insurance_amount' => 300.00,
            'card_brand' => 'Visa',
            'card_last_four' => '5555',
            'insurance_information_id' => $savedInfo->id,
            'insurance_company_id' => $company->id,
            'policy_id' => 'POL-AUTO-3M',
            'member_id_or_contract_number' => 'MEM-AUTO-3M',
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('invoices.show', $invoice->id));

        $payment = PaymentRecord::where('invoice_id', $invoice->id)->first();
        $this->assertEquals($savedInfo->id, $payment->insurance_information_id);
        $this->assertEquals($company->id, $payment->insurance_company_id);
        $this->assertEquals('POL-AUTO-3M', $payment->policy_id);
    }

    /** Scenario 18: Insurance auto-fill in 4-method split persists insurance_information_id */
    public function test_scenario_18_insurance_autofill_in_four_method_split(): void
    {
        $company = InsuranceCompany::create(['name' => 'Sun Life']);
        $savedInfo = InsuranceInformation::create([
            'client_id' => $this->client->id,
            'insurance_company_id' => $company->id,
            'policy_id' => 'POL-AUTO-4M',
            'member_id_or_contract_number' => 'MEM-AUTO-4M',
        ]);

        $invoice = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-AUTO-4M',
            'issued_date' => now()->toDateString(),
            'total_amount' => 1000.00,
            'paid_amount' => 0,
            'status' => 'outstanding',
        ]);

        $response = $this->actingAs($this->adminStaff, 'staff')->post('/payment-records', [
            'invoice_id' => $invoice->id,
            'amount' => 1000.00,
            'payment_method' => 'both',
            'split_methods' => ['cash', 'card', 'e_transfer', 'insurance'],
            'cash_amount' => 200.00,
            'card_amount' => 300.00,
            'e_transfer_amount' => 150.00,
            'insurance_amount' => 350.00,
            'card_brand' => 'Mastercard',
            'card_last_four' => '6677',
            'insurance_information_id' => $savedInfo->id,
            'insurance_company_id' => $company->id,
            'policy_id' => 'POL-AUTO-4M',
            'member_id_or_contract_number' => 'MEM-AUTO-4M',
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('invoices.show', $invoice->id));

        $payment = PaymentRecord::where('invoice_id', $invoice->id)->first();
        $this->assertEquals($savedInfo->id, $payment->insurance_information_id);
        $this->assertEquals('POL-AUTO-4M', $payment->policy_id);
    }

    /** Scenario 19: Card metadata only saved when Card is selected */
    public function test_scenario_19_card_metadata_only_when_card_is_selected(): void
    {
        $company = InsuranceCompany::create(['name' => 'Equitable Life']);
        $invoice = Invoice::create([
            'staff_id' => $this->adminStaff->id,
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-NOCARD-SPLIT',
            'issued_date' => now()->toDateString(),
            'total_amount' => 600.00,
            'paid_amount' => 0,
            'status' => 'outstanding',
        ]);

        // Cash + E-Transfer + Insurance selected (NO CARD)
        $response = $this->actingAs($this->adminStaff, 'staff')->post('/payment-records', [
            'invoice_id' => $invoice->id,
            'amount' => 600.00,
            'payment_method' => 'both',
            'split_methods' => ['cash', 'e_transfer', 'insurance'],
            'cash_amount' => 200.00,
            'e_transfer_amount' => 200.00,
            'insurance_amount' => 200.00,
            // Card info sent by accident:
            'card_brand' => 'Visa',
            'cardholder_name' => 'Unwanted Name',
            'card_last_four' => '9999',
            'insurance_company_id' => $company->id,
            'policy_id' => 'POL-NC',
            'member_id_or_contract_number' => 'MEM-NC',
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('invoices.show', $invoice->id));

        $payment = PaymentRecord::where('invoice_id', $invoice->id)->first();
        $this->assertNull($payment->card_brand);
        $this->assertNull($payment->cardholder_name);
        $this->assertNull($payment->card_last_four);
    }
}
